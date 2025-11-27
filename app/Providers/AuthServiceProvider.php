<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider {
    public function boot()
    {
        Gate::define('isAdmin', function ($user) {
            return $user->role === 'Admin';
        });
    }
}

