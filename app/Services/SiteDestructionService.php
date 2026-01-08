<?php

namespace App\Services;

use App\Models\Parameter;
use Illuminate\Support\Facades\Log;
use App\Jobs\SiteDestructionJob;

class SiteDestructionService
{
    protected MySiteStorageService $storage;

    public function __construct(MySiteStorageService $storage)
    {
        $this->storage = $storage;
    }

    /**
     * Validate a path to avoid catastrophic deletion.
     */
    protected function validatePath(string $path): bool
    {
        // Normalize path separators for cross-platform support
        $normalizedPath = str_replace('\\', '/', trim($path));

        // Do not allow empty paths
        if (empty($normalizedPath)) {
            Log::warning('SiteDestructionService: Empty path provided');
            return false;
        }



        // Get project root from database Parameter (consistent with SiteBuildService)
        $projectRoot = $this->getParameter('path_project', env('PATH_PROJECT', '/var/www/html'));
        $normalizedProjectRoot = str_replace('\\', '/', trim($projectRoot));

        // If path starts with the project root, it's valid
        if (stripos($normalizedPath, $normalizedProjectRoot) === 0) {
            return true;
        }

        // Additional check: if the path looks like a valid absolute path and exists, allow it
        // This handles cases where the site was created before path_project was configured
        if ($this->isValidProjectPath($path)) {
            return true;
        }

        Log::warning('SiteDestructionService: Path not under project root', [
            'path' => $path,
            'projectRoot' => $projectRoot,
            'normalizedPath' => $normalizedPath,
            'normalizedProjectRoot' => $normalizedProjectRoot,
        ]);

        return false;
    }

    /**
     * Check if a path looks like a valid project path (exists and is a directory).
     */
    protected function isValidProjectPath(string $path): bool
    {
        // Check if the path exists and is a directory
        if (is_dir($path)) {
            // Additional safety: path should have at least 3 segments
            // e.g., /var/www/mysite or C:\Projects\mysite
            $normalizedPath = str_replace('\\', '/', $path);
            $segments = array_filter(explode('/', $normalizedPath));
            return count($segments) >= 3;
        }

        return false;
    }

    /**
     * Get parameter value from database or fallback to default.
     * @deprecated Use Parameter::getValue() directly instead
     */
    protected function getParameter(string $key, $default = null)
    {
        return Parameter::getValue($key, $default);
    }

    public function destroy(string $siteFolderPath, string $appName = null): array
    {
        $result = ['success' => false, 'messages' => []];

        if (!$this->validatePath($siteFolderPath)) {
            $result['messages'][] = 'Invalid or unsafe path. Aborting.';
            return $result;
        }

        // Dispatch the async job to perform destruction. This avoids blocking the request and
        // allows the operation to run under the queue worker's permissions.
        try {
            // Use appName for storage path (site_name folder in my_site_storage)
            // NOT basename of siteFolderPath which is different (e.g., /var/www/test vs test.example.com)
            $storageFolderName = $appName ?: basename($siteFolderPath);
            SiteDestructionJob::dispatch(
                $siteFolderPath,
                $this->storage->disk()->path($storageFolderName),
                $appName,
                null // Apache config removal is handled manually
            );
            $result['messages'][] = 'Destruction job dispatched';
            $result['success'] = true;
        } catch (\Exception $e) {
            Log::error('Failed to dispatch SiteDestructionJob: ' . $e->getMessage());
            $result['messages'][] = 'Failed to dispatch destruction job';
        }

        return $result;
    }
}
