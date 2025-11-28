<?php

namespace Tests\Unit;

use App\Services\EnvManagerService;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\TestCase;

class EnvManagerServiceTest extends TestCase
{
    public function test_update_or_create_env_writes_values()
    {
        $tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'francis_test_' . uniqid();
        mkdir($tmpDir);
        $envPath = $tmpDir . DIRECTORY_SEPARATOR . '.env';
        file_put_contents($envPath, "# existing comment\nFOO=bar\n");

        $service = new EnvManagerService();
        $service->updateOrCreateEnv($tmpDir, [
            'FOO' => 'baz baz',
            'NEW_KEY' => 'value',
        ]);

        $contents = file_get_contents($envPath);
        $this->assertStringContainsString('FOO="baz baz"', $contents);
        $this->assertStringContainsString('NEW_KEY=value', $contents);

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
