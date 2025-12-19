<?php

namespace Database\Seeders;

use App\Models\Parameter;
use Illuminate\Database\Seeder;

class ParameterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $parameters = [
            [
                'key' => 'path_project',
                'value' => env('PATH_PROJECT', '/var/www/html'),
                'type' => 'path',
                'description' => 'Base path for all projects and sites',
            ],
            [
                'key' => 'build_manager_path',
                'value' => env('PATH_PROJECT', '/var/www/html') . '/build_source_manage',
                'type' => 'path',
                'description' => 'Path to the build manager application',
            ],
            [
                'key' => 'dev_email',
                'value' => env('DEV_EMAIL', 'dev@example.com'),
                'type' => 'email',
                'description' => 'Default developer email for notifications',
            ],
            [
                'key' => 'default_pm2_port_start',
                'value' => '3000',
                'type' => 'integer',
                'description' => 'Starting port number for PM2 processes',
            ],
            [
                'key' => 'git_auto_pull',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Enable automatic git pull before build',
            ],
            [
                'key' => 'npm_install_on_build',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Run npm install during build process',
            ],
            [
                'key' => 'APP_ENV_BUILD',
                'value' => 'dev',
                'type' => 'string',
                'description' => 'Defines the source .env file to copy from. Values: "dev" (.env.develop), "prod" (.env.prod). Default is .env.example.',
            ],
        ];

        foreach ($parameters as $param) {
            Parameter::updateOrCreate(
                ['key' => $param['key']],
                [
                    'value' => $param['value'],
                    'type' => $param['type'],
                    'description' => $param['description'],
                ]
            );
        }
    }
}
