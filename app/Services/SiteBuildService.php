<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\BuildScriptGeneratorInterface;
use App\Models\MySite;
use App\Models\Parameter;
use App\Notifications\BuildNotification; // Assuming this exists or using inline mail
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * Service for managing site CRUD operations, script generation, and environment configuration.
 */
class SiteBuildService
{
    protected Filesystem $storage;
    protected BuildScriptGeneratorInterface $scriptGenerator;
    protected EnvManagerService $envManager;

    public function __construct(
        BuildScriptGeneratorInterface $scriptGenerator,
        EnvManagerService $envManager
    ) {
        $this->storage = Storage::disk('my_site_storage');
        $this->scriptGenerator = $scriptGenerator;
        $this->envManager = $envManager;
    }

    /**
     * Create a new site with shell script and initial log
     */
    public function createSite(string $siteName, string $folderSourcePath, bool $includePM2, ?string $portPM2 = null): bool
    {
        $shContentDir = $siteName . "/sh/" . $siteName . "_build.sh";
        $logPathInit = $siteName . "/log/" . $siteName . "_first.log";

        // Generate and store shell script using injected generator
        $shellScript = $this->scriptGenerator->generate($siteName, $folderSourcePath, $includePM2);
        $this->storage->put($shContentDir, $shellScript);

        // Create initial log file
        $this->storage->put($logPathInit, str()->random());

        // Insert site record using Eloquent
        $site = MySite::create([
            'site_name'        => $siteName,
            'path_log'         => $logPathInit,
            'sh_content_dir'   => $shContentDir,
            'is_generate_env'  => 1,
            'last_user_build'  => Auth::id(),
            'path_source_code' => $folderSourcePath,
            'port_pm2'         => $portPM2,
        ]);

        return $site !== null;
    }

    /**
     * Update site configuration
     */
    public function updateSite(int $siteId, array $data): bool
    {
        $site = MySite::findOrFail($siteId);

        return $site->update([
            'port_pm2'         => $data['port_pm2'] ?? '',
            'api_endpoint_url' => trim($data['api_endpoint_url'] ?? ''),
            'last_user_build'  => Auth::id(),
        ]);
    }

    /**
     * Regenerate shell script for an existing site
     * Use this when the shell script template has been updated
     */
    public function regenerateShellScript(int $siteId): bool
    {
        $buildLogger = Log::channel('build');

        $site = MySite::findOrFail($siteId);

        // Determine if PM2 is enabled (has port_pm2 set)
        $includePM2 = !empty($site->port_pm2);

        // Generate new shell script using injected generator
        $shellScript = $this->scriptGenerator->generate(
            $site->site_name,
            $site->path_source_code,
            $includePM2
        );

        // Save to storage
        $this->storage->put($site->sh_content_dir, $shellScript);

        $buildLogger->info('Shell script regenerated', [
            'site_id' => $siteId,
            'site_name' => $site->site_name,
            'sh_content_dir' => $site->sh_content_dir,
        ]);

        return true;
    }

    /**
     * Send build notification email
     */
    public function sendBuildNotification(object $siteObject, string $logPath, string $userEmail, string $status = 'success'): void
    {
        $buildLogger = Log::channel('build');

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
            // Check if BuildNotification exists, otherwise mock or skip
            if (class_exists(\App\Notifications\BuildNotification::class) || class_exists(\App\Mail\BuildNotification::class)) {
                 // Assuming Mail::to()->send() pattern. 
                 // If using Mailable:
                 // Mail::to($devEmail)->send(new BuildNotification(...));
                 // For now preserving "Mail::to...send(new BuildNotification" syntax from original
                 Mail::to($devEmail)->send(new \App\Mail\BuildNotification(
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
            }
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
        $site = MySite::findOrFail($siteId);

        $content = '';
        if ($this->storage->exists($site->path_log)) {
            $content = $this->storage->get($site->path_log);
        }

        return [
            'log_content' => $content,
            'site_name'   => $site->site_name,
            'path_log'    => $site->path_log,
        ];
    }

    /**
     * Get detailed site information
     */
    public function getSiteDetails(int $siteId): array
    {
        $site = MySite::with('lastBuilder')->findOrFail($siteId);

        $shContent = '';
        if ($this->storage->exists($site->sh_content_dir)) {
            $shContent = $this->storage->get($site->sh_content_dir);
        }

        // Read .env file from site's source path
        $envContent = '';
        if ($site->path_source_code) {
            $envPath = rtrim($site->path_source_code, '/') . '/.env';
            if (file_exists($envPath) && is_readable($envPath)) {
                $envContent = file_get_contents($envPath);
            } else {
                $envContent = 'File .env not found or not readable at: ' . $envPath;
            }
        }

        return [
            'sh_content'         => $shContent,
            'env_content'        => $envContent,
            'site_name'          => $site->site_name,
            'last_path_log'      => $site->path_log,
            'sh_content_dir'     => $site->sh_content_dir,
            'created_at'         => $site->created_at,
            'last_user_build'    => $site->lastBuilder?->name ?? 'Unknown',
            'last_build_success' => $site->last_build_success,
            'last_build_fail'    => $site->last_build_fail,
            'last_build'         => $site->last_build,
            'port_pm2'           => $site->port_pm2,
            'path_source_code'   => $site->path_source_code,
            'api_endpoint_url'   => $site->api_endpoint_url,
            'is_generate_env'    => $site->is_generate_env,
            'id'                 => $site->id,
        ];
    }

    /**
     * Generate or update .env file with dynamic source selection
     * Public method to be used by Jobs.
     */
    public function compileEnvFile(MySite $site): void
    {
        $logger = Log::channel('build');
        $path = $site->path_source_code;
        
        try {
            $sourcePath = $this->determineEnvSourcePath($path);
            $projectRoot = rtrim($path, DIRECTORY_SEPARATOR);
            $targetPath = $projectRoot . DIRECTORY_SEPARATOR . '.env';

            $this->ensureDirectoryExists($projectRoot);

            $logger->info('Copying env source file', [
                'source' => basename($sourcePath),
                'target' => $targetPath,
            ]);

            if (!copy($sourcePath, $targetPath)) {
                throw new \Exception("Failed to copy {$sourcePath} to {$targetPath}. Check directory permissions.");
            }
            @chmod($targetPath, 0664);

            $this->replacePlaceholders($targetPath, $logger);

            // Read source content for selective updates
            $sourceContent = file_get_contents($sourcePath);

            // Prepare custom values for EnvManager
            $envUpdates = [];
            
            // PORT logic
            if (!empty($site->port_pm2)) {
                $envUpdates['PORT'] = $site->port_pm2;
            }

            // VITE_API_URL logic
            if (preg_match('/^VITE_API_URL=/m', $sourceContent) && !empty($site->api_endpoint_url)) {
                 $envUpdates['VITE_API_URL'] = $site->api_endpoint_url;
            }

            // VITE_WEB_NAME logic
            if (preg_match('/^VITE_WEB_NAME=/m', $sourceContent)) {
                 $envUpdates['VITE_WEB_NAME'] = $site->site_name;
            }

            // Use EnvManagerService to safely update .env values
            if (!empty($envUpdates)) {
                $this->envManager->updateOrCreateEnv($path, $envUpdates);

                $logger->info('Env variables updated', [
                    'path' => $targetPath,
                    'variables' => array_keys($envUpdates),
                ]);
            }

        } catch (\Exception $e) {
            $logger->warning('Failed to generate env file', [
                'site_name' => $site->site_name,
                'error' => $e->getMessage(),
            ]);
            throw $e; // Re-throw so caller knows it failed
        }
    }

    /**
     * Replace ###VARIABLE_NAME placeholders in .env file with values from env_variables table
     */
    protected function replacePlaceholders(string $filePath, $logger): void
    {
        $content = file_get_contents($filePath);

        preg_match_all('/###([A-Z0-9_]+)/', $content, $matches);

        if (empty($matches[1])) {
            return;
        }

        $variableNames = array_unique($matches[1]);
        $logger->info('Found placeholders to replace', ['variables' => $variableNames]);

        $envVariables = \App\Models\EnvVariable::whereIn('variable_name', $variableNames)->get();

        $valueMap = [];
        foreach ($envVariables as $envVar) {
            // Assuming globally available decryptValue helper or use Crypt
            if (function_exists('decryptValue')) {
                $decryptedValue = decryptValue($envVar->variable_value);
            } else {
                try {
                    $decryptedValue = \Illuminate\Support\Facades\Crypt::decryptString($envVar->variable_value);
                } catch (\Exception $e) {
                    $decryptedValue = false;
                }
            }
            
            if ($decryptedValue !== false) {
                $valueMap[$envVar->variable_name] = $decryptedValue;
            }
        }

        $newContent = preg_replace_callback(
            '/###([A-Z0-9_]+)/',
            function ($match) use ($valueMap) {
                return $valueMap[$match[1]] ?? $match[0];
            },
            $content
        );

        file_put_contents($filePath, $newContent);
    }

    /**
     * Determine the appropriate .env source file based on APP_ENV_BUILD parameter
     */
    protected function determineEnvSourcePath(string $path): string
    {
        $projectRoot = rtrim($path, DIRECTORY_SEPARATOR);
        $appEnvBuild = Parameter::getValue('APP_ENV_BUILD', '');

        $sourceFile = match (strtolower(trim($appEnvBuild))) {
            'prod' => '.env.prod',
            'dev' => '.env.develop',
            default => '.env.example',
        };

        $sourcePath = $projectRoot . DIRECTORY_SEPARATOR . $sourceFile;

        if (file_exists($sourcePath)) {
            return $sourcePath;
        }

        if ($sourceFile !== '.env.example') {
            $fallbackPath = $projectRoot . DIRECTORY_SEPARATOR . '.env.example';
            if (file_exists($fallbackPath)) {
                return $fallbackPath;
            }
        }

        throw new \Exception("No valid .env source file found. Checked: {$sourcePath}");
    }

    protected function ensureDirectoryExists(string $path): void
    {
        if (is_dir($path)) {
            if (!is_writable($path)) {
                @chmod($path, 0775);
                if (!is_writable($path)) {
                    throw new \Exception("Directory exists but is not writable: {$path}.");
                }
            }
            return;
        }

        $oldUmask = umask(0002);
        try {
            if (!@mkdir($path, 0775, true)) {
                throw new \Exception("Failed to create directory: {$path}.");
            }
            @chmod($path, 0775);
        } finally {
            umask($oldUmask);
        }
    }
}
