<?php

declare(strict_types=1);

namespace App\Services\ScriptGenerators;

use App\Contracts\BuildScriptGeneratorInterface;
use App\Models\Parameter;

/**
 * Bash script generator for site builds.
 *
 * Generates shell scripts for building sites with optional PM2 support.
 * Security: All paths are escaped, NO sudo commands are used.
 */
class BashScriptGenerator implements BuildScriptGeneratorInterface
{
    /**
     * Generate a bash build script for the given site.
     *
     * @param string $siteName Unique identifier for the site
     * @param string $sourcePath Absolute path to the source code directory
     * @param bool $includePM2 Whether to include PM2 process management
     * @return string The complete bash script content
     */
    public function generate(string $siteName, string $sourcePath, bool $includePM2): string
    {
        // Feature flags from parameters
        $gitAutoPull = filter_var(
            Parameter::getValue('git_auto_pull', 'true'),
            FILTER_VALIDATE_BOOLEAN
        );
        $npmInstallOnBuild = filter_var(
            Parameter::getValue('npm_install_on_build', 'true'),
            FILTER_VALIDATE_BOOLEAN
        );
        $npmRunBuild = filter_var(
            Parameter::getValue('npm_run_build', 'true'),
            FILTER_VALIDATE_BOOLEAN
        );
        $defaultPm2Port = Parameter::getValue('default_pm2_port_start', '3000');

        // PM2 section
        $pm2Process = $includePM2 ? $this->getPM2ScriptSection($defaultPm2Port) : '';

        // Conditional steps
        $gitPullStep = $gitAutoPull ? $this->getGitPullStep() : '';
        $npmInstallStep = $npmInstallOnBuild ? $this->getNpmInstallStep() : '';
        $npmBuildStep = $npmRunBuild ? $this->getNpmBuildStep() : '';

        // Escape folder path for safe use in shell script
        $escapedFolderPath = escapeshellarg($sourcePath);

        $script = <<<EOT
#!/bin/bash
# Build script for {$siteName}
# All output goes to stdout and is captured by PHP
# Security: This script runs WITHOUT sudo - ensure proper file permissions

echo "-----\$(date): Start script-----"

echo "-----\$(date): cd {$escapedFolderPath}-----"
cd {$escapedFolderPath} 2>&1
if [ \$? -ne 0 ]; then
    echo "Error: Failed to change directory to {$escapedFolderPath}"
    exit 1
fi

echo "-----\$(date): Git Status-----"
git status 2>&1
if [ \$? -ne 0 ]; then
    echo "Error: git status failed"
    exit 1
fi

{$gitPullStep}
{$npmInstallStep}
{$npmBuildStep}
{$pm2Process}

echo "-----\$(date): Build completed successfully-----"
exit 0
EOT;

        // CRITICAL: Convert Windows line endings (CRLF) to Unix (LF)
        // This prevents "$'\r': command not found" errors on Linux servers
        return str_replace("\r\n", "\n", $script);
    }

    /**
     * Get PM2 script section.
     */
    protected function getPM2ScriptSection(string $defaultPm2Port): string
    {
        return <<<EOT
# Run pm2 script
echo "-----\$(date): Starting PM2-----"
sh pm2_dev.sh 2>&1
if [ \$? -ne 0 ]; then
    echo "Error: pm2_dev.sh script failed"
    exit 1
fi
EOT;
    }

    /**
     * Get git pull step.
     */
    protected function getGitPullStep(): string
    {
        return <<<'GITPULL'
echo "-----$(date): Start Pull Source-----"
git pull 2>&1
if [ $? -ne 0 ]; then
    echo "Error: git pull failed"
    exit 1
fi

GITPULL;
    }

    /**
     * Get npm install step.
     */
    protected function getNpmInstallStep(): string
    {
        return <<<'NPMINSTALL'
echo "-----$(date): NPM Install-----"
npm install 2>&1

NPMINSTALL;
    }

    /**
     * Get npm build step.
     */
    protected function getNpmBuildStep(): string
    {
        return <<<'NPMBUILD'
echo "-----$(date): NPM BUILD-----"
npm run build 2>&1
if [ $? -ne 0 ]; then
    echo "Error: npm run build failed"
    exit 1
fi

NPMBUILD;
    }
}
