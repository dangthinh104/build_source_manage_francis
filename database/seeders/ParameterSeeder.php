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
                'key' => 'dev_email',
                'value' => env('DEV_EMAIL', 'dev@example.com'),
                'type' => 'email',
                'description' => 'Default developer email for notifications',
            ],

            // Build Process Configuration
            [
                'key' => 'git_auto_pull',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Enable automatic git pull during build process',
            ],
            [
                'key' => 'npm_install_on_build',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Enable npm install during build process',
            ],
            [
                'key' => 'npm_run_build',
                'value' => 'true',
                'type' => 'boolean',
                'description' => 'Enable npm run build during build process',
            ],
            [
                'key' => 'default_pm2_port_start',
                'value' => '3000',
                'type' => 'integer',
                'description' => 'Default starting port for PM2 applications',
            ],
            [
                'key' => 'APP_ENV_BUILD',
                'value' => 'dev',
                'type' => 'string',
                'description' => 'Defines the source .env file to copy from. Values: "dev" (.env.develop), "prod" (.env.prod). Default is .env.example.',
            ],

            // Environment Variable Configuration
            [
                'key' => 'ENV_SITE_NAME_KEYWORD',
                'value' => 'SITE_NAME',
                'type' => 'string',
                'description' => 'Reserved keyword for site-specific env variable placeholders (e.g., ###SITE_NAME###API_KEY)',
            ],

            [
                'key' => 'LOG_PM2_PATH',
                'value' => env('LOG_PM2_PATH', '/var/www/html/log_pm2'),
                'type' => 'path',
                'description' => 'Path to PM2 log files directory for viewing site logs',
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
