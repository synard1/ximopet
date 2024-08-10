<?php

namespace App\Providers;

use App\Core\KTBootstrap;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\ServiceProvider;
use App\Livewire\MasterData\SupplierModal;
use Livewire\Livewire; // Import the facade


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

        Livewire::component('master-data._create_supplier', Supplier::class);
        // Livewire::component('master-data.supplier', App\Livewire\MasterData\Supplier::class);


    }
}
