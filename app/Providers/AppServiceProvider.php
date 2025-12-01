<?php

namespace App\Providers;

use App\Models\RolePermission;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Define permission gates dynamically
        $permissions = [
            'view_dashboard',
            'view_profile',
            'edit_profile',
            'view_parameters',
            'manage_parameters',
            'view_mysites',
            'manage_mysites',
            'build_mysites',
            'view_env_variables',
            'manage_env_variables',
            'view_logs',
            'view_users',
            'manage_users',
        ];

        foreach ($permissions as $permission) {
            Gate::define($permission, function ($user) use ($permission) {
                return $user->hasPermission($permission);
            });
        }
    }
}
