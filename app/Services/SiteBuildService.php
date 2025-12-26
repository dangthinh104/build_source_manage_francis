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

            $this->replacePlaceholders($targetPath, $logger, $site);

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
     * Replace placeholders in .env file with values from env_variables table
     * 
     * Supports three formats:
     * 1. ###VAR_NAME           → Global variable (no group, no site)
     * 2. ###GROUP_NAME###VAR_NAME → Group-scoped variable
     * 3. ###SITE_NAME###VAR_NAME  → Site-specific variable (SITE_NAME is reserved keyword)
     */
    protected function replacePlaceholders(string $filePath, $logger, MySite $site): void
    {
        $content = file_get_contents($filePath);

        // Pattern 1: ###PREFIX###VAR_NAME (group or site-specific)
        // Pattern 2: ###VAR_NAME (global)
        $pattern = '/###([A-Z0-9_]+)(###([A-Z0-9_]+))?/';
        
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

        if (empty($matches)) {
            return;
        }

        $logger->info('Found placeholders to replace', [
            'count' => count($matches),
            'site_id' => $site->id,
            'site_name' => $site->site_name,
        ]);

        // Load site-specific keyword from parameters (configurable)
        $siteNameKeyword = strtoupper(
            Parameter::getValue('ENV_SITE_NAME_KEYWORD', 'SITE_NAME')
        );

        // Build a value map for all placeholders
        $valueMap = [];
        
        foreach ($matches as $match) {
            $fullPlaceholder = $match[0]; // e.g., "###APP_KEY" or "###DEV_SERVER###DB_HOST"
            
            // Check if it's a prefixed pattern (###PREFIX###VAR)
            if (isset($match[3]) && !empty($match[3])) {
                $prefix = $match[1];      // e.g., "DEV_SERVER" or "SITE_NAME"
                $varName = $match[3];     // e.g., "DB_HOST" or "API_KEY"
                
                if (strtoupper($prefix) === $siteNameKeyword) {
                    // Site-specific variable
                    $envVar = \App\Models\EnvVariable::forSite($site->id)
                        ->where('variable_name', $varName)
                        ->first();
                    
                    if ($envVar) {
                        $valueMap[$fullPlaceholder] = $this->decryptEnvValue($envVar->variable_value);
                        $logger->debug('Site-specific variable matched', [
                            'variable' => $varName,
                            'site_id' => $site->id,
                            'keyword' => $siteNameKeyword,
                        ]);
                    } else {
                        $logger->warning('Site-specific variable not found', [
                            'variable' => $varName,
                            'site_id' => $site->id,
                            'keyword' => $siteNameKeyword,
                        ]);
                    }
                } else {
                    // Group-scoped variable
                    $envVar = \App\Models\EnvVariable::forGroup($prefix)
                        ->where('variable_name', $varName)
                        ->first();
                    
                    if ($envVar) {
                        $valueMap[$fullPlaceholder] = $this->decryptEnvValue($envVar->variable_value);
                        $logger->debug('Group variable matched', [
                            'variable' => $varName,
                            'group' => $prefix,
                        ]);
                    } else {
                        $logger->warning('Group variable not found', [
                            'variable' => $varName,
                            'group' => $prefix,
                        ]);
                    }
                }
            } else {
                // Global variable (###VAR_NAME)
                $varName = $match[1];
                
                $envVar = \App\Models\EnvVariable::global()
                    ->where('variable_name', $varName)
                    ->first();
                
                if ($envVar) {
                    $valueMap[$fullPlaceholder] = $this->decryptEnvValue($envVar->variable_value);
                    $logger->debug('Global variable matched', ['variable' => $varName]);
                } else {
                    $logger->warning('Global variable not found', ['variable' => $varName]);
                }
            }
        }

        // Replace all placeholders with their values
        $newContent = str_replace(array_keys($valueMap), array_values($valueMap), $content);

        file_put_contents($filePath, $newContent);
        
        $logger->info('Placeholder replacement complete', [
            'replaced_count' => count($valueMap),
            'path' => $filePath,
        ]);
    }

    /**
     * Helper method to decrypt environment variable value
     */
    protected function decryptEnvValue(string $encryptedValue)
    {
        if (function_exists('decryptValue')) {
            return decryptValue($encryptedValue);
        }
        
        try {
            return \Illuminate\Support\Facades\Crypt::decryptString($encryptedValue);
        } catch (\Exception $e) {
            return $encryptedValue; // Return as-is if decryption fails
        }
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
