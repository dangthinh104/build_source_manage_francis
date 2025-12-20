<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RolePermission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Permissions structure:
     * - super_admin: * (wildcard - full access)
     * - admin: manage sites, build, env variables
     * - user: view only
     */
    public function run(): void
    {
        // Clear existing permissions
        RolePermission::truncate();
        RolePermission::clearCache();

        // Super Admin - has wildcard permission (full access)
        RolePermission::create([
            'role' => 'super_admin',
            'permission' => '*',
        ]);

        // Admin permissions
        $adminPermissions = [
            'view_mysites',
            'manage_mysites',
            'create_mysites',
            'delete_mysites',
            'build_mysites',
            'view_env_variables',
            'manage_env_variables',
            'view_parameters',
            'manage_parameters',
            'view_build_history',
            'view_logs',
        ];

        foreach ($adminPermissions as $permission) {
            RolePermission::create([
                'role' => 'admin',
                'permission' => $permission,
            ]);
        }

        // User permissions (view only)
        $userPermissions = [
            'view_mysites',
            'view_env_variables',
            'view_parameters',
            'view_build_history',
        ];

        foreach ($userPermissions as $permission) {
            RolePermission::create([
                'role' => 'user',
                'permission' => $permission,
            ]);
        }

        echo "Role permissions seeded successfully!\n";
        echo "- super_admin: * (full access)\n";
        echo "- admin: " . count($adminPermissions) . " permissions\n";
        echo "- user: " . count($userPermissions) . " permissions\n";
    }
}
