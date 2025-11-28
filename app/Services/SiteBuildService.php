<?php

namespace App\Services;

use App\Models\Parameter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Mail\BuildNotification;

class SiteBuildService
{
    protected $storage;

    public function __construct()
    {
        $this->storage = Storage::disk('my_site_storage');
    }

    /**
     * Create a new site with shell script and initial log
     */
    public function createSite(string $siteName, string $folderSourcePath, bool $includePM2, ?string $portPM2 = null): bool
    {
        $shContentDir = $siteName . "/sh/" . $siteName . "_build.sh";
        $logPathInit = $siteName . "/log/" . $siteName . "_first.log";

        // Generate and store shell script
        $shellScript = $this->generateShellScript($siteName, $folderSourcePath, $includePM2);
        $this->storage->put($shContentDir, $shellScript);

        // Create initial log file
        $this->storage->put($logPathInit, str()->random());

        // Insert site record
        $dataInsert = [
            'site_name'        => $siteName,
            'path_log'         => $logPathInit,
            'sh_content_dir'   => $shContentDir,
            'is_generate_env'  => 1,
            'last_user_build'  => Auth::user()->id,
            'path_source_code' => $folderSourcePath,
            'port_pm2'         => $portPM2,
            'created_at'       => now(),
        ];

        return DB::table('my_site')->insert($dataInsert);
    }

    /**
     * Update site configuration
     */
    public function updateSite(int $siteId, array $data): bool
    {
        $dataUpdate = [
            'port_pm2'         => trim($data['port_pm2'] ?? ''),
            'api_endpoint_url' => trim($data['api_endpoint_url'] ?? ''),
            'last_user_build'  => Auth::user()->id,
            'updated_at'       => now(),
        ];

        return DB::table('my_site')->where('id', $siteId)->update($dataUpdate);
    }

    /**
     * Build a site by ID
     */
    public function buildSiteById(int $siteId): array
    {
        $siteObject = DB::table('my_site')->where('id', $siteId)->first();

        if (!$siteObject) {
            throw new \Exception('Site not found.');
        }

        return $this->executeBuild($siteObject, Auth::user()->id);
    }

    /**
     * Build a site by name (for outbound API calls)
     */
    public function buildSiteByName(string $siteName, ?string $userName = null): array
    {
        $siteObject = DB::table('my_site')->where('site_name', $siteName)->first();

        if (!$siteObject) {
            throw new \Exception('Site not found.');
        }

        $userId = Auth::user()->id ?? null;

        // If username provided, lookup user ID
        if ($userName) {
            $user = DB::table('users')->where('name', $userName)->first();
            if (!$user) {
                throw new \Exception("Developer name not found: {$userName}");
            }
            $userId = $user->id;
        }

        return $this->executeBuild($siteObject, $userId);
    }

    /**
     * Execute the actual build process
     */
    protected function executeBuild(object $siteObject, ?int $userId): array
    {
        // Get shell script path
        $pathSH = $this->storage->path($siteObject->sh_content_dir);

        // Execute shell script
        $output = [];
        $returnVar = null;
        exec("sudo /bin/bash {$pathSH} 2>&1", $output, $returnVar);

        // Prepare update data
        $update = [
            'last_build'      => now(),
            'last_user_build' => $userId,
            'path_log'        => $siteObject->site_name . '/log/execution_' . ($output[0] ?? 'error') . '.log',
        ];

        // Update success/fail timestamp
        if ($returnVar === 0) {
            $update['last_build_success'] = now();
        } else {
            $update['last_build_fail'] = now();
        }

        // Generate env file if enabled
        if ($siteObject->is_generate_env) {
            $this->generateEnvFile($siteObject->path_source_code, [
                'PORT_PM2'      => $siteObject->port_pm2,
                'VITE_API_URL'  => $siteObject->api_endpoint_url,
                'VITE_WEB_NAME' => $siteObject->site_name,
            ]);
        }

        // Update database
        $status = DB::table('my_site')
            ->where('id', $siteObject->id)
            ->update($update);

        return [
            'status'     => $status ? 1 : 0,
            'return_var' => $returnVar,
            'output'     => $output,
            'log_path'   => $update['path_log'],
        ];
    }

    /**
     * Send build notification email
     */
    public function sendBuildNotification(object $siteObject, string $logPath, string $userEmail): void
    {
        if (!$this->storage->exists($logPath)) {
            return;
        }

        $mailContent = $this->storage->get($logPath);
        $devEmail = $this->getParameter('dev_email', env('DEV_EMAIL', 'dev@example.com'));

        Mail::to($devEmail)->send(new BuildNotification(
            $siteObject->site_name,
            'success',
            now()->format('Y-m-d H:i:s'),
            $mailContent,
            $userEmail
        ));
    }

    /**
     * Get site log content
     */
    public function getLogContent(int $siteId): array
    {
        $siteObject = DB::table('my_site')->where('id', $siteId)->first();

        if (!$siteObject) {
            throw new \Exception('Site not found.');
        }

        $content = '';
        if ($this->storage->exists($siteObject->path_log)) {
            $content = $this->storage->get($siteObject->path_log);
        }

        return [
            'log_content' => $content,
            'site_name'   => $siteObject->site_name,
            'path_log'    => $siteObject->path_log,
        ];
    }

    /**
     * Get detailed site information
     */
    public function getSiteDetails(int $siteId): array
    {
        $siteObject = DB::table('my_site')
            ->selectRaw('
                my_site.id, 
                port_pm2, 
                path_source_code, 
                site_name, 
                api_endpoint_url, 
                is_generate_env, 
                path_log, 
                sh_content_dir, 
                last_build, 
                my_site.created_at, 
                my_site.updated_at, 
                users.name, 
                last_build_success, 
                last_build_fail
            ')
            ->join('users', 'my_site.last_user_build', '=', 'users.id')
            ->where('my_site.id', $siteId)
            ->first();

        if (!$siteObject) {
            throw new \Exception('Site not found.');
        }

        $shContent = '';
        $envVariables = '';

        if ($this->storage->exists($siteObject->sh_content_dir)) {
            $shContent = $this->storage->get($siteObject->sh_content_dir);
        }

        if (function_exists('readNodeEnv')) {
            $envVariables = readNodeEnv($siteObject->path_source_code);
        }

        return [
            'sh_content'         => $shContent . "\n\n\n\n====================== ENV File ===================\n" . $envVariables,
            'site_name'          => $siteObject->site_name,
            'last_path_log'      => $siteObject->path_log,
            'sh_content_dir'     => $siteObject->sh_content_dir,
            'created_at'         => $siteObject->created_at,
            'last_user_build'    => $siteObject->name,
            'last_build_success' => $siteObject->last_build_success,
            'last_build_fail'    => $siteObject->last_build_fail,
            'last_build'         => $siteObject->last_build,
            'port_pm2'           => $siteObject->port_pm2,
            'path_source_code'   => $siteObject->path_source_code,
            'api_endpoint_url'   => $siteObject->api_endpoint_url,
            'is_generate_env'    => $siteObject->is_generate_env,
            'id'                 => $siteObject->id,
        ];
    }

    /**
     * Generate shell script for site build
     */
    public function generateShellScript(string $siteName, string $folderSourcePath, bool $includePM2): string
    {
        $pathProject = $this->getParameter('path_project', env('PATH_PROJECT', '/var/www/html'));
        $buildManagerPath = $this->getParameter('build_manager_path', $pathProject . '/build_source_manage');

        // Feature flags from parameters
        $gitAutoPull = filter_var($this->getParameter('git_auto_pull', 'true'), FILTER_VALIDATE_BOOLEAN);
        $npmInstallOnBuild = filter_var($this->getParameter('npm_install_on_build', 'true'), FILTER_VALIDATE_BOOLEAN);
        $defaultPm2Port = $this->getParameter('default_pm2_port_start', '3000');

        // PM2 section can export a default port so pm2 scripts can reference it
        $pm2Process = '';
        if ($includePM2) {
            $pm2Process = "export PORT_PM2={$defaultPm2Port}\n" . $this->getPM2ScriptSection();
        }

        // Conditional steps
        $gitPullStep = $gitAutoPull ? "echo \"-----\$(date): Start Pull Source-----\" >> \$LOG_FILE\nsudo git pull >> \$LOG_FILE 2>&1\nif [ \$? -ne 0 ]; then\n    echo \"Error: git pull failed\" >> \$LOG_FILE\n    echo \$time\n    exit 1\nfi\n\n" : '';

        $npmInstallStep = $npmInstallOnBuild ? "echo \"-----\$(date): NPM Install-----\" >> \$LOG_FILE\nsudo npm install >> \$LOG_FILE 2>&1\n\n" : '';

        return <<<EOT
#!/bin/bash
time="\$(date +%s)"
LOG_FILE="{$buildManagerPath}/storage/my_site_storage/{$siteName}/log/execution_\$time.log"
touch \$LOG_FILE

echo "-----\$(date): Start script-----" >> \$LOG_FILE

echo "-----\$(date): cd {$folderSourcePath} -----" >> \$LOG_FILE
cd {$folderSourcePath} >> \$LOG_FILE 2>&1
if [ \$? -ne 0 ]; then
    echo "Error: Failed to change directory" >> \$LOG_FILE
    echo \$time
    exit 1
fi

echo "-----\$(date): Git Status Source -----" >> \$LOG_FILE
sudo git status >> \$LOG_FILE 2>&1
if [ \$? -ne 0 ]; then
    echo "Error: git status failed" >> \$LOG_FILE
    echo \$time
    exit 1
fi

{$gitPullStep}
{$npmInstallStep}
echo "-----\$(date): NPM BUILD-----" >> \$LOG_FILE
sudo npm run build >> \$LOG_FILE 2>&1
if [ \$? -ne 0 ]; then
    echo "Error: npm run build fail" >> \$LOG_FILE
    echo \$time
    exit 1
fi

{$pm2Process}

echo "-----\$(date): Finish-----" >> \$LOG_FILE
echo \$time
exit 0
EOT;
    }

    /**
     * Get PM2 script section
     */
    protected function getPM2ScriptSection(): string
    {
        return <<<EOT
# Run pm2 script
sudo sh pm2_dev.sh >> \$LOG_FILE 2>&1
if [ \$? -ne 0 ]; then
    echo "Error: pm2_dev.sh script failed" >> \$LOG_FILE
    echo \$time
    exit 1
fi
EOT;
    }

    /**
     * Generate or update .env file
     */
    protected function generateEnvFile(string $path, array $custom): void
    {
        // Use EnvManagerService to safely update/create .env values
        try {
            $envService = app(EnvManagerService::class);
            $envService->updateOrCreateEnv($path, $custom);
        } catch (\Exception $e) {
            // Log exception silently; building should continue but admin should be notified in more advanced flows
        }
    }

    /**
     * Get parameter value from database or fallback to default
     */
    protected function getParameter(string $key, $default = null)
    {
        try {
            $parameter = Parameter::where('key', $key)->first();
            return $parameter ? $parameter->value : $default;
        } catch (\Exception $e) {
            return $default;
        }
    }
}
