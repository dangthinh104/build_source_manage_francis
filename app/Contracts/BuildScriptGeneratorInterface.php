<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Interface for build script generation strategies.
 * 
 * Allows swapping different script generators (Bash, Docker, etc.)
 * without modifying consumer code.
 */
interface BuildScriptGeneratorInterface
{
    /**
     * Generate a build script for the given site.
     *
     * @param string $siteName Unique identifier for the site
     * @param string $sourcePath Absolute path to the source code directory
     * @param bool $includePM2 Whether to include PM2 process management
     * @return string The complete build script content
     */
    public function generate(string $siteName, string $sourcePath, bool $includePM2): string;
}
