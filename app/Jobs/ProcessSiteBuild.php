<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Contracts\BuildScriptGeneratorInterface;
use App\Events\SiteBuildCompleted;
use App\Models\BuildHistory;
use App\Models\MySite;
use App\Services\EnvManagerService;
use App\Services\ScriptGenerators\BashScriptGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Async job for processing site builds.
 * 
 * Handles the heavy lifting of executing build scripts in the background,
 * updating build history status, and firing completion events.
 */
class ProcessSiteBuild implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted.
     * Builds should not auto-retry as they may leave state inconsistent.
     */
    public int $tries = 1;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 600; // 10 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly int $siteId,
        public readonly ?int $userId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $buildLogger = Log::channel('build');
        $storage = Storage::disk('my_site_storage');

        // Load site
        $site = MySite::find($this->siteId);
        if (!$site) {
            $buildLogger->error('ProcessSiteBuild: Site not found', ['site_id' => $this->siteId]);
            return;
        }

        $buildLogger->info('ProcessSiteBuild: Starting build', [
            'site_id' => $this->siteId,
            'site_name' => $site->site_name,
            'user_id' => $this->userId,
        ]);

        // Create initial build history record with "processing" status
        $history = BuildHistory::create([
            'site_id' => $this->siteId,
            'user_id' => $this->userId,
            'status' => 'processing',
            'output_log' => 'Build started...',
        ]);

        try {
            // Get shell script path
            $pathSH = $storage->path($site->sh_content_dir);

            if (!file_exists($pathSH)) {
                throw new \Exception("Shell script not found: {$pathSH}");
            }

            // Execute shell script WITHOUT sudo (security hardening)
            $output = [];
            $returnVar = null;
            exec("/bin/bash " . escapeshellarg($pathSH) . " 2>&1", $output, $returnVar);

            // Determine build status
            $buildStatus = $returnVar === 0 ? 'success' : 'failed';
            $timestamp = now()->format('Ymd_His');

            // Prepare log content
            $logContent = "=== Build Log ===\n";
            $logContent .= "Site: {$site->site_name}\n";
            $logContent .= "Time: " . now()->format('Y-m-d H:i:s') . "\n";
            $logContent .= "Status: {$buildStatus}\n";
            $logContent .= "Return Code: {$returnVar}\n";
            $logContent .= "=================\n\n";
            $logContent .= implode("\n", $output);

            // Save log file
            $logPath = $site->site_name . '/log/execution_' . $timestamp . '_' . $buildStatus . '.log';
            $storage->put($logPath, $logContent);

            // Update site record
            $siteUpdate = [
                'last_build' => now(),
                'last_user_build' => $this->userId,
                'path_log' => $logPath,
            ];

            if ($returnVar === 0) {
                $siteUpdate['last_build_success'] = now();
                $buildLogger->info('ProcessSiteBuild: Build completed successfully', [
                    'site_name' => $site->site_name,
                    'return_var' => $returnVar,
                ]);
            } else {
                $siteUpdate['last_build_fail'] = now();
                $buildLogger->error('ProcessSiteBuild: Build failed', [
                    'site_name' => $site->site_name,
                    'return_var' => $returnVar,
                    'output' => implode("\n", array_slice($output, -10)),
                ]);
            }

            $site->update($siteUpdate);

            // Generate env file if enabled
            if ($site->is_generate_env) {
                $this->generateEnvFile($site, $buildLogger);
            }

            // Update build history with final status
            $history->update([
                'status' => $buildStatus,
                'output_log' => implode("\n", $output),
            ]);

            // Fire completion event (Observer pattern)
            SiteBuildCompleted::dispatch($history->fresh(), $site->fresh(), $buildStatus);

        } catch (\Exception $e) {
            $buildLogger->error('ProcessSiteBuild: Exception during build', [
                'site_id' => $this->siteId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Update history with failure
            $history->update([
                'status' => 'failed',
                'output_log' => "Build failed with exception: " . $e->getMessage(),
            ]);

            // Reload site and update failure timestamp
            $site->update(['last_build_fail' => now()]);

            // Still fire event so notifications go out
            SiteBuildCompleted::dispatch($history->fresh(), $site->fresh(), 'failed');
        }
    }

    /**
     * Generate or update .env file for the site.
     */
    protected function generateEnvFile(MySite $site, $logger): void
    {
        try {
            $envService = app(EnvManagerService::class);
            
            // Determine source path and copy to .env
            $projectRoot = rtrim($site->path_source_code, DIRECTORY_SEPARATOR);
            $sourcePath = $this->determineEnvSourcePath($projectRoot);
            $targetPath = $projectRoot . DIRECTORY_SEPARATOR . '.env';

            if (!copy($sourcePath, $targetPath)) {
                throw new \Exception("Failed to copy env file from {$sourcePath}");
            }
            @chmod($targetPath, 0664);

            // Replace placeholders
            $this->replacePlaceholders($targetPath, $logger);

            // Read source content to check which keys exist
            $sourceContent = file_get_contents($sourcePath);
            
            // Build update array - PORT is always set if port_pm2 exists
            $envUpdates = [];
            if (!empty($site->port_pm2)) {
                $envUpdates['PORT'] = $site->port_pm2;
            }
            
            // Only set VITE_API_URL if it exists in source file
            if (preg_match('/^VITE_API_URL=/m', $sourceContent) && !empty($site->api_endpoint_url)) {
                $envUpdates['VITE_API_URL'] = $site->api_endpoint_url;
            }
            
            // Only set VITE_WEB_NAME if it exists in source file
            if (preg_match('/^VITE_WEB_NAME=/m', $sourceContent)) {
                $envUpdates['VITE_WEB_NAME'] = $site->site_name;
            }
            
            // Update custom values
            if (!empty($envUpdates)) {
                $envService->updateOrCreateEnv($site->path_source_code, $envUpdates);
            }

            $logger->info('Env file generated', [
                'site_name' => $site->site_name,
                'keys_updated' => array_keys($envUpdates),
            ]);
        } catch (\Exception $e) {
            $logger->warning('Failed to generate env file', [
                'site_name' => $site->site_name,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Determine the appropriate .env source file.
     */
    protected function determineEnvSourcePath(string $projectRoot): string
    {
        $appEnvBuild = \App\Models\Parameter::getValue('APP_ENV_BUILD', '');

        $sourceFile = match (strtolower(trim($appEnvBuild))) {
            'prod' => '.env.prod',
            'dev' => '.env.develop',
            default => '.env.example',
        };

        $sourcePath = $projectRoot . DIRECTORY_SEPARATOR . $sourceFile;

        if (file_exists($sourcePath)) {
            return $sourcePath;
        }

        // Fallback to .env.example
        if ($sourceFile !== '.env.example') {
            $fallbackPath = $projectRoot . DIRECTORY_SEPARATOR . '.env.example';
            if (file_exists($fallbackPath)) {
                return $fallbackPath;
            }
        }

        throw new \Exception("No valid .env source file found at {$sourcePath}");
    }

    /**
     * Replace ###VARIABLE_NAME placeholders in .env file.
     */
    protected function replacePlaceholders(string $filePath, $logger): void
    {
        $content = file_get_contents($filePath);

        preg_match_all('/###([A-Z0-9_]+)/', $content, $matches);

        if (empty($matches[1])) {
            return;
        }

        $variableNames = array_unique($matches[1]);
        $envVariables = \App\Models\EnvVariable::whereIn('variable_name', $variableNames)->get();

        $valueMap = [];
        foreach ($envVariables as $envVar) {
            $decryptedValue = decryptValue($envVar->variable_value);
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
        $logger->info('Placeholders replaced', ['count' => count($valueMap)]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::channel('build')->error('ProcessSiteBuild: Job failed completely', [
            'site_id' => $this->siteId,
            'error' => $exception?->getMessage(),
        ]);
    }
}
