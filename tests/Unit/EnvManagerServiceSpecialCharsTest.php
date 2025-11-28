<?php

namespace Tests\Unit;

use App\Services\EnvManagerService;
use PHPUnit\Framework\TestCase;

class EnvManagerServiceSpecialCharsTest extends TestCase
{
    public function test_quote_and_write_special_chars()
    {
        $tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'francis_test_' . uniqid();
        mkdir($tmpDir);
        $envPath = $tmpDir . DIRECTORY_SEPARATOR . '.env';
        file_put_contents($envPath, "# existing comment\nEXIST=old\n");

        $service = new EnvManagerService();
        $service->updateOrCreateEnv($tmpDir, [
            'FOO' => 'with spaces',
            'BAR' => 'contains"quote',
            'DOLLAR' => 'price $5',
            'MULTI' => "line1\nline2",
        ]);

        $contents = file_get_contents($envPath);
        $this->assertStringContainsString('FOO="with spaces"', $contents);
        $this->assertStringContainsString('BAR="contains\\"quote"', $contents);
        $this->assertStringContainsString('DOLLAR="price $5"', $contents);
        $this->assertMatchesRegularExpression('/MULTI="line1(?:\\\\n|\\r\\n|\\n)line2"/', $contents);

        // cleanup - remove all files including dotfiles (backups), then directory
        $files = array_merge(glob($tmpDir . DIRECTORY_SEPARATOR . '*') ?: [], glob($tmpDir . DIRECTORY_SEPARATOR . '.*') ?: []);
        foreach ($files as $f) {
            $base = basename($f);
            if ($base === '.' || $base === '..') {
                continue;
            }
            if (is_file($f)) {
                @unlink($f);
            }
        }
        @rmdir($tmpDir);
    }
}
