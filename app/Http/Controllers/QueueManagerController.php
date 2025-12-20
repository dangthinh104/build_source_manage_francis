<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller for managing queue jobs.
 * 
 * Provides a dashboard for Super Admins to monitor and manage
 * active jobs and failed jobs in the database queue.
 */
class QueueManagerController extends Controller
{
    /**
     * Display the queue management dashboard.
     */
    public function index(): Response
    {
        // Fetch active/pending jobs from the jobs table
        $activeJobs = DB::table('jobs')
            ->orderBy('available_at', 'desc')
            ->limit(100)
            ->get()
            ->map(function ($job) {
                return [
                    'id' => $job->id,
                    'queue' => $job->queue,
                    'attempts' => $job->attempts,
                    'reserved_at' => $job->reserved_at 
                        ? now()->parse($job->reserved_at)->diffForHumans() 
                        : null,
                    'available_at' => now()->parse($job->available_at)->diffForHumans(),
                    'created_at' => now()->parse($job->created_at)->diffForHumans(),
                    'payload' => $this->parsePayload($job->payload),
                ];
            });

        // Fetch failed jobs
        $failedJobs = DB::table('failed_jobs')
            ->orderBy('failed_at', 'desc')
            ->limit(100)
            ->get()
            ->map(function ($job) {
                return [
                    'id' => $job->id,
                    'uuid' => $job->uuid,
                    'connection' => $job->connection,
                    'queue' => $job->queue,
                    'failed_at' => $job->failed_at,
                    'failed_at_human' => now()->parse($job->failed_at)->diffForHumans(),
                    'exception' => $this->truncateException($job->exception),
                    'exception_full' => $job->exception,
                    'payload' => $this->parsePayload($job->payload),
                ];
            });

        return Inertia::render('Queues/Index', [
            'activeJobs' => $activeJobs,
            'failedJobs' => $failedJobs,
            'stats' => [
                'active_count' => DB::table('jobs')->count(),
                'failed_count' => DB::table('failed_jobs')->count(),
            ],
        ]);
    }

    /**
     * Retry a specific failed job.
     */
    public function retry(Request $request, string $uuid)
    {
        try {
            Artisan::call('queue:retry', ['id' => [$uuid]]);
            
            return back()->with('success', "Job {$uuid} has been pushed back onto the queue.");
        } catch (\Exception $e) {
            return back()->with('error', "Failed to retry job: {$e->getMessage()}");
        }
    }

    /**
     * Delete a specific failed job.
     */
    public function destroy(Request $request, string $uuid)
    {
        try {
            Artisan::call('queue:forget', ['id' => $uuid]);
            
            return back()->with('success', "Job {$uuid} has been deleted.");
        } catch (\Exception $e) {
            return back()->with('error', "Failed to delete job: {$e->getMessage()}");
        }
    }

    /**
     * Retry all failed jobs.
     */
    public function retryAll()
    {
        try {
            Artisan::call('queue:retry', ['id' => ['all']]);
            
            return back()->with('success', 'All failed jobs have been pushed back onto the queue.');
        } catch (\Exception $e) {
            return back()->with('error', "Failed to retry jobs: {$e->getMessage()}");
        }
    }

    /**
     * Flush (delete) all failed jobs.
     */
    public function flush()
    {
        try {
            Artisan::call('queue:flush');
            
            return back()->with('success', 'All failed jobs have been deleted.');
        } catch (\Exception $e) {
            return back()->with('error', "Failed to flush jobs: {$e->getMessage()}");
        }
    }

    /**
     * Parse job payload to extract job name.
     */
    protected function parsePayload(string $payload): array
    {
        try {
            $data = json_decode($payload, true);
            $displayName = $data['displayName'] ?? 'Unknown';
            
            // Extract just the class name for cleaner display
            $parts = explode('\\', $displayName);
            $shortName = end($parts);
            
            return [
                'displayName' => $displayName,
                'shortName' => $shortName,
                'maxTries' => $data['maxTries'] ?? null,
                'timeout' => $data['timeout'] ?? null,
            ];
        } catch (\Exception $e) {
            return [
                'displayName' => 'Unknown',
                'shortName' => 'Unknown',
            ];
        }
    }

    /**
     * Truncate exception message for display.
     */
    protected function truncateException(?string $exception): string
    {
        if (!$exception) {
            return 'No exception message';
        }

        // Get first line (usually the main error message)
        $lines = explode("\n", $exception);
        $firstLine = $lines[0] ?? '';
        
        // Truncate if too long
        if (strlen($firstLine) > 150) {
            return substr($firstLine, 0, 150) . '...';
        }
        
        return $firstLine;
    }
}
