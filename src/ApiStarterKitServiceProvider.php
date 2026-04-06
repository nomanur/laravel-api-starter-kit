<?php

namespace LaravelApi\StarterKit;

use Illuminate\Support\ServiceProvider;
use LaravelApi\StarterKit\Console\Commands\InstallApiStarterKit;
use LaravelApi\StarterKit\Console\Commands\MakeApiResource;

class ApiStarterKitServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'api-starter-kit');

        // Register the main class to use with the facade
        $this->app->singleton('api-boilerplate', function () {
            return new ApiBoilerplate;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publishing configuration
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('api-starter-kit.php'),
            ], 'api-starter-kit-config');

            // Publishing routes
            $this->publishes([
                __DIR__.'/../routes/api.php' => base_path('routes/api.php'),
            ], 'api-starter-kit-routes');

            // Registering package commands
            $this->commands([
                InstallApiStarterKit::class,
                MakeApiResource::class,
            ]);
        }

        // Load routes if not already loaded
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
