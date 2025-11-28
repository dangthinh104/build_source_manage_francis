<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SiteBuildService;
use Illuminate\Support\Facades\DB;

class SiteTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'site:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $svc = app(SiteBuildService::class);
            $this->info('Service: ' . get_class($svc));

            $site = DB::table('my_site')->first();
            if (!$site) {
                $this->info('No sites found');
                return 0;
            }

            $this->info('First site id: ' . $site->id);
            $details = $svc->getSiteDetails($site->id);
            $this->info('Site details keys: ' . implode(', ', array_keys($details)));
            return 0;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }
}
