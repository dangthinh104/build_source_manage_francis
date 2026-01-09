<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\SiteBuildCompleted;
use App\Models\BuildHistory;
use App\Models\MySite;
use App\Services\SiteBuildService;
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
 * Handles the heavy lifting of executing build scripts in the background.
 */
class ProcessSiteBuild implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 600; // 10 minutes

    public function __construct(
        public readonly int $siteId,
        public readonly ?int $userId = null,
        public readonly ?int $historyId = null
    ) {}

    public function handle(SiteBuildService $startBuildService): void
    {
        $buildLogger = Log::channel('build');
        $storage = Storage::disk('my_site_storage');

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

        // Use existing history record if provided, otherwise create new one
        $history = $this->historyId 
            ? BuildHistory::find($this->historyId) 
            : null;
        
        if (!$history) {
            $history = BuildHistory::create([
                'site_id' => $this->siteId,
                'user_id' => $this->userId,
                'status' => 'processing',
                'output_log' => 'Build started...',
            ]);
        } else {
            $history->update([
                'status' => 'processing',
                'output_log' => 'Build started...',
            ]);
        }

        try {
            $pathSH = $storage->path($site->sh_content_dir);

            if (!file_exists($pathSH)) {
                throw new \Exception("Shell script not found: {$pathSH}");
            }

            // Execute shell script
            $output = [];
            $returnVar = null;
            exec("/bin/bash " . escapeshellarg($pathSH) . " 2>&1", $output, $returnVar);

            $buildStatus = $returnVar === 0 ? 'success' : 'failed';
            $timestamp = now()->format('Ymd_His');

            $logContent = "=== Build Log ===\n";
            $logContent .= "Site: {$site->site_name}\n";
            $logContent .= "Time: " . now()->format('Y-m-d H:i:s') . "\n";
            $logContent .= "Status: {$buildStatus}\n";
            $logContent .= "Return Code: {$returnVar}\n";
            $logContent .= "=================\n\n";
            $logContent .= implode("\n", $output);

            $logPath = $site->site_name . '/log/execution_' . $timestamp . '_' . $buildStatus . '.log';
            $storage->put($logPath, $logContent);

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

            // Generate env file via Service
            if ($site->is_generate_env) {
                try {
                    $startBuildService->compileEnvFile($site);
                } catch (\Exception $e) {
                     // Logged in service, but we might want to note it in history or just continue
                     // Build status itself is determined by shell script, env generation failure
                     // is usually non-fatal to the *build* artifact but maybe fatal to running.
                     // We'll keep buildStatus as is.
                }
            }

            $history->update([
                'status' => $buildStatus,
                'output_log' => implode("\n", $output),
            ]);

            SiteBuildCompleted::dispatch($history->fresh(), $site->fresh(), $buildStatus);

        } catch (\Exception $e) {
            $buildLogger->error('ProcessSiteBuild: Exception during build', [
                'site_id' => $this->siteId,
                'error' => $e->getMessage(),
            ]);

            $history->update([
                'status' => 'failed',
                'output_log' => "Build failed with exception: " . $e->getMessage(),
            ]);

            $site->update(['last_build_fail' => now()]);
            SiteBuildCompleted::dispatch($history->fresh(), $site->fresh(), 'failed');
        }
    }

    public function failed(?\Throwable $exception): void
    {
        Log::channel('build')->error('ProcessSiteBuild: Job failed completely', [
            'site_id' => $this->siteId,
            'error' => $exception?->getMessage(),
        ]);
    }
}
