<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Eloquent\BuildHistoryRepository;
use App\Repositories\Eloquent\MySiteRepository;
use App\Repositories\Eloquent\UserRepository;
use App\Repositories\Interfaces\BuildHistoryRepositoryInterface;
use App\Repositories\Interfaces\MySiteRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\ServiceProvider;

/**
 * Repository Service Provider
 * 
 * Binds Repository interfaces to their concrete implementations.
 * This enables Dependency Injection throughout the application.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register repository bindings
     *
     * @return void
     */
    public function register(): void
    {
        // MySite Repository
        $this->app->bind(
            MySiteRepositoryInterface::class,
            MySiteRepository::class
        );

        // BuildHistory Repository
        $this->app->bind(
            BuildHistoryRepositoryInterface::class,
            BuildHistoryRepository::class
        );

        // User Repository
        $this->app->bind(
            UserRepositoryInterface::class,
            UserRepository::class
        );

        // EnvVariable Repository
        $this->app->bind(
            \App\Repositories\Interfaces\EnvVariableRepositoryInterface::class,
            \App\Repositories\Eloquent\EnvVariableRepository::class
        );
    }

    /**
     * Bootstrap services
     *
     * @return void
     */
    public function boot(): void
    {
        //
    }
}
