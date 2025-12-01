<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportRoleRightsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-rights';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import role permissions from database/data/role_rights.json';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $jsonPath = database_path('data/role_rights.json');

        if (!File::exists($jsonPath)) {
            $this->error("JSON file not found at: {$jsonPath}");
            return 1;
        }

        $this->info('Reading role rights from JSON...');
        $json = File::get($jsonPath);
        $data = json_decode($json, true);

        if (!$data) {
            $this->error('Failed to parse JSON file.');
            return 1;
        }

        $this->info('Truncating role_permissions table...');
        DB::table('role_permissions')->truncate();

        $permissions = [];
        $now = now();

        foreach ($data as $role => $rolePermissions) {
            $this->info("Processing role: {$role}");
            
            foreach ($rolePermissions as $permission) {
                $permissions[] = [
                    'role' => $role,
                    'permission' => $permission,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        $this->info('Inserting ' . count($permissions) . ' permission records...');
        DB::table('role_permissions')->insert($permissions);

        $this->info('✓ Role permissions imported successfully!');
        return 0;
    }
}
