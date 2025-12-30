<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/**
 * MySite Storage Service
 * 
 * Centralized service for managing MySite file storage operations.
 * Provides a single source of truth for storage disk configuration.
 */
class MySiteStorageService
{
    /**
     * Storage disk name constant
     */
    private const DISK_NAME = 'my_site_storage';

    /**
     * The filesystem instance
     */
    protected Filesystem $disk;

    /**
     * Initialize storage service
     */
    public function __construct()
    {
        $this->disk = Storage::disk(self::DISK_NAME);
    }

    /**
     * Get the filesystem disk instance
     *
     * @return Filesystem
     */
    public function disk(): Filesystem
    {
        return $this->disk;
    }

    /**
     * Check if file exists
     *
     * @param string $path
     * @return bool
     */
    public function exists(string $path): bool
    {
        return $this->disk->exists($path);
    }

    /**
     * Get file contents
     *
     * @param string $path
     * @return string
     */
    public function get(string $path): string
    {
        return $this->disk->get($path);
    }

    /**
     * Write file contents
     *
     * @param string $path
     * @param string $contents
     * @return bool
     */
    public function put(string $path, string $contents): bool
    {
        return $this->disk->put($path, $contents);
    }

    /**
     * Delete a file
     *
     * @param string $path
     * @return bool
     */
    public function delete(string $path): bool
    {
        return $this->disk->delete($path);
    }

    /**
     * Get all files in directory
     *
     * @param string $directory
     * @return array
     */
    public function files(string $directory): array
    {
        return $this->disk->files($directory);
    }

    /**
     * Get last modified time of file
     *
     * @param string $path
     * @return int
     */
    public function lastModified(string $path): int
    {
        return $this->disk->lastModified($path);
    }

    /**
     * Delete entire directory
     *
     * @param string $directory
     * @return bool
     */
    public function deleteDirectory(string $directory): bool
    {
        return $this->disk->deleteDirectory($directory);
    }

    /**
     * Get site-specific directory path
     *
     * @param string $siteName
     * @param string $subdir (e.g., 'sh', 'log')
     * @return string
     */
    public function getSitePath(string $siteName, string $subdir = ''): string
    {
        $path = $siteName;
        if ($subdir) {
            $path .= '/' . trim($subdir, '/');
        }
        return $path;
    }

    /**
     * Get shell script path for site
     *
     * @param string $siteName
     * @return string
     */
    public function getShellScriptPath(string $siteName): string
    {
        return $this->getSitePath($siteName, "sh/{$siteName}_build.sh");
    }

    /**
     * Get log directory path for site
     *
     * @param string $siteName
     * @return string
     */
    public function getLogDirectory(string $siteName): string
    {
        return $this->getSitePath($siteName, 'log');
    }

    /**
     * Get initial log file path for site
     *
     * @param string $siteName
     * @return string
     */
    public function getInitialLogPath(string $siteName): string
    {
        return $this->getSitePath($siteName, "log/{$siteName}_first.log");
    }
}
