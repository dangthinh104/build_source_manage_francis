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
     * Regenerate shell script for an existing site
     * Use this when the shell script template has been updated
     */
    public function regenerateShellScript(int $siteId): bool
    {
        $buildLogger = \Log::channel('build');

        $siteObject = DB::table('my_site')->where('id', $siteId)->first();

        if (!$siteObject) {
            throw new \Exception('Site not found.');
        }

        // Determine if PM2 is enabled (has port_pm2 set)
        $includePM2 = !empty($siteObject->port_pm2);

        // Generate new shell script
        $shellScript = $this->generateShellScript(
            $siteObject->site_name,
            $siteObject->path_source_code,
            $includePM2
        );

        // Save to storage
        $this->storage->put($siteObject->sh_content_dir, $shellScript);

        $buildLogger->info('Shell script regenerated', [
            'site_id' => $siteId,
            'site_name' => $siteObject->site_name,
            'sh_content_dir' => $siteObject->sh_content_dir,
        ]);

        return true;
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
        $buildLogger = \Log::channel('build');

        $buildLogger->info('Build started', [
            'site_id' => $siteObject->id,
            'site_name' => $siteObject->site_name,
            'user_id' => $userId,
        ]);

        // Get shell script path
        $pathSH = $this->storage->path($siteObject->sh_content_dir);

        // Execute shell script with escaped path to prevent injection
        $output = [];
        $returnVar = null;
        exec("sudo /bin/bash " . escapeshellarg($pathSH) . " 2>&1", $output, $returnVar);

        // Determine build status for log filename
        $buildStatus = $returnVar === 0 ? 'success' : 'failed';
        $timestamp = now()->format('Ymd_His');

        // Prepare update data
        $update = [
            'last_build'      => now(),
            'last_user_build' => $userId,
            'path_log'        => $siteObject->site_name . '/log/execution_' . $timestamp . '_' . $buildStatus . '.log',
        ];

        // Update success/fail timestamp
        if ($returnVar === 0) {
            $update['last_build_success'] = now();
            $buildLogger->info('Build completed successfully', [
                'site_name' => $siteObject->site_name,
                'return_var' => $returnVar,
            ]);
        } else {
            $update['last_build_fail'] = now();
            $buildLogger->error('Build failed', [
                'site_name' => $siteObject->site_name,
                'return_var' => $returnVar,
                'output' => implode("\n", array_slice($output, -10)), // Last 10 lines
            ]);
        }

        // Generate env file if enabled (wrapped in try-catch to prevent build failure)
        if ($siteObject->is_generate_env) {
            try {
                $this->generateEnvFile($siteObject->path_source_code, [
                    'PORT_PM2'      => $siteObject->port_pm2,
                    'VITE_API_URL'  => $siteObject->api_endpoint_url,
                    'VITE_WEB_NAME' => $siteObject->site_name,
                ]);
                $buildLogger->info('Env file generated', ['site_name' => $siteObject->site_name]);
            } catch (\Exception $e) {
                $buildLogger->warning('Failed to generate env file', [
                    'site_name' => $siteObject->site_name,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Write build output to log file in storage
        $logContent = "=== Build Log ===\n";
        $logContent .= "Site: {$siteObject->site_name}\n";
        $logContent .= "Time: " . now()->format('Y-m-d H:i:s') . "\n";
        $logContent .= "Status: {$buildStatus}\n";
        $logContent .= "Return Code: {$returnVar}\n";
        $logContent .= "=================\n\n";
        $logContent .= implode("\n", $output);

        $this->storage->put($update['path_log'], $logContent);

        // Update database
        $status = DB::table('my_site')
            ->where('id', $siteObject->id)
            ->update($update);

        // Record build history
        try {
            \App\Models\BuildHistory::create([
                'site_id' => $siteObject->id,
                'user_id' => $userId,
                'status'  => $buildStatus,
                'output_log' => implode("\n", $output),
            ]);
        } catch (\Exception $e) {
            $buildLogger->error('Failed to record build history', [
                'site_id' => $siteObject->id,
                'error' => $e->getMessage(),
            ]);
        }

        return [
            'status'     => $status ? 1 : 0,
            'return_var' => $returnVar,
            'output'     => $output,
            'log_path'   => $update['path_log'],
        ];
    }

    /**
     * Send build notification email
     *
     * @param object $siteObject Site database object
     * @param string $logPath Path to the build log file
     * @param string $userEmail Email of the user who triggered the build
     * @param string $status Build status ('success' or 'failed')
     */
    public function sendBuildNotification(object $siteObject, string $logPath, string $userEmail, string $status = 'success'): void
    {
        $buildLogger = \Log::channel('build');

        if (!$this->storage->exists($logPath)) {
            $buildLogger->warning('Cannot send notification: log file not found', [
                'site_name' => $siteObject->site_name,
                'log_path' => $logPath,
            ]);
            return;
        }

        $mailContent = $this->storage->get($logPath);
        $devEmail = Parameter::getValue('dev_email', env('DEV_EMAIL', 'dev@example.com'));

        try {
            Mail::to($devEmail)->send(new BuildNotification(
                $siteObject->site_name,
                $status,
                now()->format('Y-m-d H:i:s'),
                $mailContent,
                $userEmail
            ));

            $buildLogger->info('Build notification email sent', [
                'site_name' => $siteObject->site_name,
                'status' => $status,
                'to' => $devEmail,
            ]);
        } catch (\Exception $e) {
            $buildLogger->error('Failed to send build notification email', [
                'site_name' => $siteObject->site_name,
                'error' => $e->getMessage(),
            ]);
        }
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

        if ($this->storage->exists($siteObject->sh_content_dir)) {
            $shContent = $this->storage->get($siteObject->sh_content_dir);
        }

        return [
            'sh_content'         => $shContent,
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
     * 
     * Note: The script outputs to stdout/stderr which is captured by PHP exec().
     * PHP then saves the complete output to a single log file with proper naming.
     */
    public function generateShellScript(string $siteName, string $folderSourcePath, bool $includePM2): string
    {
        // Feature flags from parameters
        $gitAutoPull = filter_var(Parameter::getValue('git_auto_pull', 'true'), FILTER_VALIDATE_BOOLEAN);
        $npmInstallOnBuild = filter_var(Parameter::getValue('npm_install_on_build', 'true'), FILTER_VALIDATE_BOOLEAN);
        $defaultPm2Port = Parameter::getValue('default_pm2_port_start', '3000');

        // PM2 section can export a default port so pm2 scripts can reference it
        $pm2Process = '';
        if ($includePM2) {
            $pm2Process = $this->getPM2ScriptSection($defaultPm2Port);
        }

        // Conditional steps - output to stdout
        $gitPullStep = $gitAutoPull ? <<<'GITPULL'
echo "-----$(date): Start Pull Source-----"
sudo git pull 2>&1
if [ $? -ne 0 ]; then
    echo "Error: git pull failed"
    exit 1
fi

GITPULL : '';

        $npmInstallStep = $npmInstallOnBuild ? <<<'NPMINSTALL'
echo "-----$(date): NPM Install-----"
sudo npm install 2>&1

NPMINSTALL : '';

        // Escape folder path for safe use in shell script
        $escapedFolderPath = escapeshellarg($folderSourcePath);

        return <<<EOT
#!/bin/bash
# Build script for {$siteName}
# All output goes to stdout and is captured by PHP

echo "-----\$(date): Start script-----"

echo "-----\$(date): cd {$escapedFolderPath}-----"
cd {$escapedFolderPath} 2>&1
if [ \$? -ne 0 ]; then
    echo "Error: Failed to change directory to {$escapedFolderPath}"
    exit 1
fi

echo "-----\$(date): Git Status-----"
sudo git status 2>&1
if [ \$? -ne 0 ]; then
    echo "Error: git status failed"
    exit 1
fi

{$gitPullStep}
{$npmInstallStep}
echo "-----\$(date): NPM BUILD-----"
sudo npm run build 2>&1
if [ \$? -ne 0 ]; then
    echo "Error: npm run build failed"
    exit 1
fi

{$pm2Process}

echo "-----\$(date): Build completed successfully-----"
exit 0
EOT;
    }

    /**
     * Get PM2 script section
     *
     * @param string $defaultPm2Port Default port for PM2
     */
    protected function getPM2ScriptSection(string $defaultPm2Port): string
    {
        return <<<EOT
# Run pm2 script
echo "-----\$(date): Starting PM2-----"
export PORT_PM2={$defaultPm2Port}
sudo sh pm2_dev.sh 2>&1
if [ \$? -ne 0 ]; then
    echo "Error: pm2_dev.sh script failed"
    exit 1
fi
EOT;
    }

    /**
     * Generate or update .env file with dynamic source selection
     *
     * @param string $path Project root path
     * @param array $custom Custom environment variables to set
     * @throws \Exception When no valid source .env file exists
     */
    protected function generateEnvFile(string $path, array $custom): void
    {
        $buildLogger = \Log::channel('build');
        $sourcePath = $this->determineEnvSourcePath($path);
        $projectRoot = rtrim($path, DIRECTORY_SEPARATOR);
        $targetPath = $projectRoot . DIRECTORY_SEPARATOR . '.env';

        // Ensure directory exists with proper permissions
        $this->ensureDirectoryExists($projectRoot);

        // Always copy source to target (replace existing .env with source template)
        $buildLogger->info('Copying env source file', [
            'source' => basename($sourcePath),
            'target' => $targetPath,
        ]);

        if (!copy($sourcePath, $targetPath)) {
            throw new \Exception("Failed to copy {$sourcePath} to {$targetPath}. Check directory permissions.");
        }
        // Set file permissions to be writable
        @chmod($targetPath, 0664);

        // Replace ###VARIABLE_NAME placeholders with values from env_variables table
        $this->replacePlaceholders($targetPath, $buildLogger);

        // Use EnvManagerService to safely update/create .env values
        try {
            $envService = app(EnvManagerService::class);
            $envService->updateOrCreateEnv($path, $custom);

            $buildLogger->info('Env variables updated', [
                'path' => $targetPath,
                'variables' => array_keys($custom),
            ]);
        } catch (\Exception $e) {
            $buildLogger->warning('Failed to update .env file', [
                'error' => $e->getMessage(),
                'path' => $path,
            ]);
        }
    }

    /**
     * Replace ###VARIABLE_NAME placeholders in .env file with values from env_variables table
     *
     * @param string $filePath Path to the .env file
     * @param \Psr\Log\LoggerInterface $logger Logger instance
     */
    protected function replacePlaceholders(string $filePath, $logger): void
    {
        $content = file_get_contents($filePath);

        // Find all ###VARIABLE_NAME patterns (including numbers: DB_DATABASE_23)
        preg_match_all('/###([A-Z0-9_]+)/', $content, $matches);

        if (empty($matches[1])) {
            $logger->info('No placeholders found in env file');
            return;
        }

        $variableNames = array_unique($matches[1]);
        $logger->info('Found placeholders to replace', ['variables' => $variableNames]);

        // Get all values from env_variables table and build lookup map
        $envVariables = \App\Models\EnvVariable::whereIn('variable_name', $variableNames)->get();
        
        $valueMap = [];
        foreach ($envVariables as $envVar) {
            $decryptedValue = decryptValue($envVar->variable_value);
            if ($decryptedValue !== false) {
                $valueMap[$envVar->variable_name] = $decryptedValue;
                $logger->info('Loaded variable', ['variable' => $envVar->variable_name]);
            } else {
                $logger->warning('Failed to decrypt env variable', [
                    'variable' => $envVar->variable_name,
                ]);
            }
        }

        // Log placeholders not found in database
        foreach ($variableNames as $varName) {
            if (!isset($valueMap[$varName])) {
                $logger->warning('Placeholder not found in env_variables table', [
                    'placeholder' => '###' . $varName,
                ]);
            }
        }

        // Use preg_replace_callback to replace all placeholders in one pass
        // This avoids ordering issues because each placeholder is matched exactly
        $newContent = preg_replace_callback(
            '/###([A-Z0-9_]+)/',
            function ($match) use ($valueMap, $logger) {
                $varName = $match[1];
                if (isset($valueMap[$varName])) {
                    $logger->info('Replacing placeholder', ['placeholder' => '###' . $varName]);
                    return $valueMap[$varName];
                }
                // Keep original placeholder if no value found
                return $match[0];
            },
            $content
        );

        file_put_contents($filePath, $newContent);
        $logger->info('Placeholders replaced', ['count' => count($valueMap)]);
    }

    /**
     * Determine the appropriate .env source file based on APP_ENV_BUILD parameter
     *
     * @param string $path Project root path
     * @return string Absolute path to the source .env file
     * @throws \Exception When no valid source file exists
     */
    protected function determineEnvSourcePath(string $path): string
    {
        $projectRoot = rtrim($path, DIRECTORY_SEPARATOR);
        $appEnvBuild = Parameter::getValue('APP_ENV_BUILD', '');

        // Determine source file based on parameter
        $sourceFile = match (strtolower(trim($appEnvBuild))) {
            'prod' => '.env.prod',
            'dev' => '.env.develop',
            default => '.env.example',
        };

        $sourcePath = $projectRoot . DIRECTORY_SEPARATOR . $sourceFile;

        // Validate source file exists
        if (file_exists($sourcePath)) {
            return $sourcePath;
        }

        // Fallback to .env.example if preferred source doesn't exist
        if ($sourceFile !== '.env.example') {
            \Log::warning("Preferred source file {$sourceFile} not found, falling back to .env.example", [
                'path' => $sourcePath,
                'appEnvBuild' => $appEnvBuild
            ]);

            $fallbackPath = $projectRoot . DIRECTORY_SEPARATOR . '.env.example';
            if (file_exists($fallbackPath)) {
                return $fallbackPath;
            }
        }

        // No valid source file found - throw exception
        throw new \Exception(
            "No valid .env source file found. Checked: {$sourcePath}" .
            ($sourceFile !== '.env.example' ? " and {$projectRoot}/.env.example" : '')
        );
    }



    /**
     * Ensure directory exists with proper permissions
     * Creates the directory recursively if it doesn't exist
     *
     * @param string $path Directory path to ensure exists
     * @throws \Exception When directory cannot be created or is not writable
     */
    protected function ensureDirectoryExists(string $path): void
    {
        // If directory already exists, check if it's writable
        if (is_dir($path)) {
            if (!is_writable($path)) {
                // Try to chmod without sudo (will work if current user owns the dir)
                @chmod($path, 0775);

                if (!is_writable($path)) {
                    throw new \Exception(
                        "Directory exists but is not writable: {$path}. " .
                        "Please run: docker exec francis_app chown -R www-data:www-data " . escapeshellarg($path)
                    );
                }
            }
            return;
        }

        // Set umask to allow group write permissions
        $oldUmask = umask(0002);

        try {
            // Create directory with 775 permissions (rwxrwxr-x)
            if (!@mkdir($path, 0775, true)) {
                $error = error_get_last();
                throw new \Exception(
                    "Failed to create directory: {$path}. " .
                    ($error['message'] ?? 'Unknown error') . ". " .
                    "Please ensure parent directory is writable or run: " .
                    "docker exec francis_app mkdir -p " . escapeshellarg($path) . " && " .
                    "docker exec francis_app chown -R www-data:www-data " . escapeshellarg($path)
                );
            }

            // Ensure permissions are correct
            @chmod($path, 0775);

        } finally {
            // Restore original umask
            umask($oldUmask);
        }

        // Final check
        if (!is_writable($path)) {
            throw new \Exception(
                "Created directory but it's not writable: {$path}. " .
                "Please run: docker exec francis_app chown -R www-data:www-data " . escapeshellarg($path)
            );
        }
    }
}
