<?php

namespace App\Providers;

use App\Contracts\BuildScriptGeneratorInterface;
use App\Events\SiteBuildCompleted;
use App\Listeners\SendBuildNotification;
use App\Repositories\Eloquent\BuildGroupRepository;
use App\Repositories\Eloquent\ParameterRepository;
use App\Repositories\Interfaces\BuildGroupRepositoryInterface;
use App\Repositories\Interfaces\ParameterRepositoryInterface;
use App\Services\ScriptGenerators\BashScriptGenerator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind the BuildScriptGenerator interface to the Bash implementation
        $this->app->bind(BuildScriptGeneratorInterface::class, BashScriptGenerator::class);

        // Register repositories
        $this->app->bind(
            BuildGroupRepositoryInterface::class,
            BuildGroupRepository::class
        );

        $this->app->bind(
            ParameterRepositoryInterface::class,
            ParameterRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register event listeners
        Event::listen(SiteBuildCompleted::class, SendBuildNotification::class);
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
