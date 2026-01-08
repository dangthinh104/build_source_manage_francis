<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Filesystem\Filesystem;

class SiteDestructionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $sitePath;
    public $storagePath;
    public $pm2Name;
    public $apacheConfPath;

    /**
     * Create a new job instance.
     */
    public function __construct(string $sitePath, string $storagePath, string $pm2Name = null, string $apacheConfPath = null)
    {
        $this->sitePath = $sitePath;
        $this->storagePath = $storagePath;
        $this->pm2Name = $pm2Name;
        $this->apacheConfPath = $apacheConfPath;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $fs = new Filesystem();

        // Stop and delete PM2 process if given
        if ($this->pm2Name) {
            @exec("pm2 delete " . escapeshellarg($this->pm2Name) . " 2>&1", $output, $rc);
        }

        // NOTE: Apache config removal is handled manually by the user

        // Remove site files (best-effort)
        if ($this->sitePath && $fs->isDirectory($this->sitePath)) {
            try {
                $fs->deleteDirectory($this->sitePath);
            } catch (\Exception $e) {
                // swallow
            }
        }

        // Remove storage artifacts
        if ($this->storagePath && $fs->isDirectory($this->storagePath)) {
            try {
                $fs->deleteDirectory($this->storagePath);
            } catch (\Exception $e) {
                // swallow
            }
        }
    }
}
