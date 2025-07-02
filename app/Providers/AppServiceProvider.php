<?php

namespace App\Providers;

use App\Core\KTBootstrap;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\ServiceProvider;
// use App\Livewire\MasterData\SupplierModal;
use Livewire\Livewire; // Import the facade
use App\Livewire\QaChecklistForm;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
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

        // Only load Pulse and Telescope migrations in local/dev
        if (app()->environment(['local', 'development', 'dev', 'testing'])) {
            $this->loadMigrationsFrom(database_path('migrations/pulse'));
            $this->loadMigrationsFrom(database_path('migrations/telescope'));
        }
    }
}
