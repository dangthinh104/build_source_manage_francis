<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'test@admin.com'],
            [
                'name' => 'Test Admin',
                'password' => Hash::make('123456'),
                'role' => 'Admin',
                'email_verified_at' => now(),
            ],
        );

        // Seed default parameters
        $this->call(ParameterSeeder::class);
        
        // Seed role permissions (must be before SuperAdminSeeder)
        $this->call(RolePermissionSeeder::class);
        
        $this->call(\Database\Seeders\SuperAdminSeeder::class);
    }
}
