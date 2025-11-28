<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Jobs\SiteDestructionJob;
use Illuminate\Bus\Dispatcher;

class SiteDestructionService
{
    protected $storage;

    public function __construct()
    {
        $this->storage = Storage::disk('my_site_storage');
    }

    /**
     * Validate a path to avoid catastrophic deletion.
     */
    protected function validatePath(string $path): bool
    {
        // Do not allow empty, root paths, or common system folders
        $blacklist = ['/', '/*', '/etc', '/bin', '/usr', '/var', '/home'];
        foreach ($blacklist as $b) {
            if (trim($path) === $b || stripos($path, $b) === 0) {
                return false;
            }
        }

        // Basic safe check: path must be under configured project path
        $projectRoot = config('app.path_project', env('PATH_PROJECT', '/var/www/html'));
        if (stripos(realpath($path) ?: $path, realpath($projectRoot) ?: $projectRoot) !== 0) {
            return false;
        }

        return true;
    }

    public function destroy(string $siteFolderPath, string $appName = null): array
    {
        $result = ['success' => false, 'messages' => []];

        if (!$this->validatePath($siteFolderPath)) {
            $result['messages'][] = 'Invalid or unsafe path. Aborting.';
            return $result;
        }

        // Prepare best-effort paths for async job
        $apacheConf = null;
        if ($appName) {
            $apacheConf = '/etc/apache2/sites-available/' . $appName . '.conf';
        }

        // Dispatch the async job to perform destruction. This avoids blocking the request and
        // allows the operation to run under the queue worker's permissions.
        try {
            SiteDestructionJob::dispatch($siteFolderPath, $this->storage->path(basename($siteFolderPath)), $appName, $apacheConf);
            $result['messages'][] = 'Destruction job dispatched';
            $result['success'] = true;
        } catch (\Exception $e) {
            Log::error('Failed to dispatch SiteDestructionJob: ' . $e->getMessage());
            $result['messages'][] = 'Failed to dispatch destruction job';
        }

        return $result;
    }
}
