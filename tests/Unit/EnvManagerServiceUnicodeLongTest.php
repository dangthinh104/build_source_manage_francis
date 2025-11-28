<?php

namespace Tests\Unit;

use App\Services\EnvManagerService;
use PHPUnit\Framework\TestCase;

class EnvManagerServiceUnicodeLongTest extends TestCase
{
    public function test_unicode_and_long_values_and_backup()
    {
        $tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'francis_test_' . uniqid();
        mkdir($tmpDir);
        $envPath = $tmpDir . DIRECTORY_SEPARATOR . '.env';
        file_put_contents($envPath, "KEY=initial\n");

        $service = new EnvManagerService();
        $long = str_repeat('αβγδεζηθ' , 100);
        $service->updateOrCreateEnv($tmpDir, [
            'UNICODE' => "こんにちは世界",
            'LONG' => $long,
        ]);

        $contents = file_get_contents($envPath);
        $this->assertStringContainsString('UNICODE=こんにちは世界', $contents);
        $prefix = substr($long, 0, 40);
        $escaped = preg_quote($prefix, '/');
        $this->assertMatchesRegularExpression('/LONG=(?:"?)' . $escaped . '/u', $contents);

        // backup exists
        $bak = glob($tmpDir . DIRECTORY_SEPARATOR . '.env.*.bak');
        $this->assertNotEmpty($bak, 'Backup .env file should exist');

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
