<?php

namespace App\Providers;

use App\Core\KTBootstrap;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\ServiceProvider;
// use App\Livewire\MasterData\SupplierModal;
use Livewire\Livewire; // Import the facade
use App\Livewire\QaChecklistForm;
use App\Models\Role;
use App\Models\Permission;
use App\Observers\RoleObserver;
use App\Observers\PermissionObserver;
use App\Observers\LivestockDepletionObserver;
use App\Models\LivestockDepletion;
use Laravel\Sanctum\Sanctum;
use App\Models\PersonalAccessToken;
use App\Models\Company;
use App\Observers\CompanyObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Update defaultStringLength
        Builder::defaultStringLength(191);

        KTBootstrap::init();

        $this->app->singleton('app.menu', function ($app) {
            $menuService = new \App\Services\MenuService();
            return $menuService->processMenu(config('xolution.menu'));
        });

        // Livewire::component('master-data.supplier-modal', SupplierModal::class);
        // Livewire::component('master-data.supplier', App\Livewire\MasterData\Supplier::class);

        // Register Livewire components
        Livewire::component('qa-checklist-form', QaChecklistForm::class);

        // Register observers
        Role::observe(RoleObserver::class);
        Permission::observe(PermissionObserver::class);
        LivestockDepletion::observe(LivestockDepletionObserver::class);

        // Auto-sync master data when company created
        Company::observe(CompanyObserver::class);

        // Force Sanctum to use our custom PersonalAccessToken model
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        // =========================================
        // Share Version Information Globally
        // =========================================
        $detectedVersion = cache()->rememberForever('app_version', function () {
            $default = config('xolution.APPS.Version');
            $filePath = base_path('CHANGELOG.md');

            if (!file_exists($filePath)) {
                return $default;
            }

            $handle = fopen($filePath, 'r');
            if (! $handle) {
                return $default;
            }

            while (($line = fgets($handle)) !== false) {
                // look for lines like: ## [V1.2.3] – 2025-01-24  or ## [1.2.3]
                if (preg_match('/^##\s*\[?v?([0-9]+(?:\.[0-9]+)+)\]?/i', $line, $matches)) {
                    fclose($handle);
                    return strtoupper('V' . ltrim($matches[1], 'vV'));
                }
            }
            fclose($handle);

            return $default; // fallback
        });

        // Overwrite config value at runtime so existing calls continue to work
        config(['xolution.APPS.Version' => $detectedVersion]);

        // Share to all blade views
        view()->share('app_version', $detectedVersion);

        // Only load Pulse and Telescope migrations in local/dev
        if (app()->environment(['local', 'development', 'dev', 'testing'])) {
            $this->loadMigrationsFrom(database_path('migrations/pulse'));
            $this->loadMigrationsFrom(database_path('migrations/telescope'));
        }
    }
}
