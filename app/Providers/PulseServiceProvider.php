<?php

namespace App\Providers;

use App\Helpers\EnvironmentHelper;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class PulseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Only register Pulse in local environment
        if (EnvironmentHelper::shouldLoadPackage('laravel/pulse')) {
            $this->app->register(\Laravel\Pulse\PulseServiceProvider::class);
        }
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Only enable Pulse in local environment
        if (EnvironmentHelper::shouldLoadPackage('laravel/pulse')) {
            Gate::define('viewPulse', function ($user = null) {
                return true; // Allow all access in local environment
            });
        }
    }
}
