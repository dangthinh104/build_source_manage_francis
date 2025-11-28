<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider {
    public function boot()
    {
        Gate::define('isAdmin', function ($user) {
            $role = strtolower($user->role ?? '');
            return in_array($role, ['admin', 'super_admin']);
        });
    }
}

