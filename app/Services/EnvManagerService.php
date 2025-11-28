<?php

namespace App\Services;

use Exception;

class EnvManagerService
{
    /**
     * Update or create env keys in the target .env file.
     * Preserves comments and ordering where possible.
     *
     * @param string $path Absolute or relative path to project root (where .env resides)
     * @param array $data Key => value pairs to set
     * @throws Exception on permission errors or write failures
     */
    public function updateOrCreateEnv(string $path, array $data): void
    {
        // Determine .env file path
        $envPath = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '.env';

        if (!file_exists($envPath)) {
            // Try to create an empty .env file
            if (false === @file_put_contents($envPath, "")) {
                throw new Exception("Cannot create .env at {$envPath}. Check permissions.");
            }
        }

        // Create a timestamped backup
        $backupPath = $envPath . '.' . date('Ymd_His') . '.bak';
        if (false === @copy($envPath, $backupPath)) {
            // Not a fatal error; warn by throwing only if original exists but not copyable
            if (file_exists($envPath)) {
                throw new Exception("Failed to create backup of .env at {$backupPath}");
            }
        }

        if (!is_writable($envPath)) {
            // Attempt to set writable and if still not writable, throw
            @chmod($envPath, 0666);
            if (!is_writable($envPath)) {
                throw new Exception(".env file is not writable: {$envPath}");
            }
        }

        $contents = file_get_contents($envPath);
        if ($contents === false) {
            throw new Exception("Failed to read .env at {$envPath}");
        }

        $lines = preg_split('/\r\n|\n|\r/', $contents);
        if ($lines === false) {
            $lines = [];
        }

        // Map existing keys to line index
        $keyLineMap = [];
        foreach ($lines as $i => $line) {
            $trim = trim($line);
            if ($trim === '' || strpos($trim, '#') === 0) {
                continue;
            }
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $k = trim($parts[0]);
                $keyLineMap[$k] = $i;
            }
        }

        // Prepare updates
        foreach ($data as $key => $value) {
            if (!is_string($key) || $key === '') {
                continue;
            }

            // Ensure we quote values with spaces or special chars
            $safeValue = $this->quoteIfNeeded($value);

            if (array_key_exists($key, $keyLineMap)) {
                $lines[$keyLineMap[$key]] = $key . '=' . $safeValue;
            } else {
                // Append at the end
                $lines[] = $key . '=' . $safeValue;
            }
        }

        $newContents = implode(PHP_EOL, $lines);

        $tmp = $envPath . '.tmp';
        // Preserve original permissions
        $perms = null;
        if (file_exists($envPath)) {
            $perms = fileperms($envPath);
        }

        if (false === @file_put_contents($tmp, $newContents)) {
            throw new Exception("Failed to write temporary .env file: {$tmp}");
        }

        if (!@rename($tmp, $envPath)) {
            // Try fallback: overwrite
            if (false === @file_put_contents($envPath, $newContents)) {
                @unlink($tmp);
                throw new Exception("Failed to replace .env file at {$envPath}");
            }
        }

        // Restore permissions if captured
        if ($perms !== null) {
            @chmod($envPath, $perms & 0777);
        }
    }

    protected function quoteIfNeeded($value): string
    {
        if (is_null($value)) {
            return '';
        }

        $value = (string) $value;

        // Escape any existing double quotes
        $value = str_replace('"', '\\"', $value);

        // If contains spaces or # or quotes or dollar signs or newlines, wrap in double quotes
        if (preg_match('/[\s#"\\$\n\r]/', $value)) {
            return '"' . $value . '"';
        }

        return $value;
    }
}
