<?php

use App\Http\Controllers\Apps\PermissionManagementController;
use App\Http\Controllers\Apps\RoleManagementController;
use App\Http\Controllers\Apps\UserManagementController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MasterData\RekananController;
use App\Http\Controllers\MasterData\FarmController;
use App\Http\Controllers\MasterData\KandangController;
use App\Http\Controllers\MasterData\StokController;
use App\Models\Stok;
use App\Http\Controllers\Transaksi\TransaksiController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Route::get('/supplier-create', SupplierModal::class)->name('supplier.create');


Route::get('/', function () {
    return view('layout._frontend');
});

Route::get('/frontend', function () {
    return view('layout._frontend');
});

// Route::get('/test2', function () {
//     return view('test2');
// });

Route::get('/test', function () {
    return view('test');
});


Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/', [DashboardController::class, 'index']);

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::name('admin.')->group(function () {
        Route::get('/administrator', function () {
            return view('test3');
        });
        Route::resource('/administrator/users', UserManagementController::class);
        Route::resource('/user-management/roles', RoleManagementController::class);
        Route::resource('/user-management/permissions', PermissionManagementController::class);
    });

    Route::name('user-management.')->group(function () {
        Route::resource('/user-management/users', UserManagementController::class);
        Route::resource('/user-management/roles', RoleManagementController::class);
        Route::resource('/user-management/permissions', PermissionManagementController::class);
    });

    Route::name('master-data.')->group(function () {
        Route::resource('/master-data/suppliers', RekananController::class);
        Route::resource('/master-data/farms', FarmController::class);
        Route::resource('/master-data/kandangs', KandangController::class);
        Route::resource('/master-data/stoks', StokController::class);
        Route::get('/master-data/customers', [RekananController::class, 'customerIndex'])->name('customers.index');
    });

    Route::name('transaksi.')->group(function () {
        Route::get('/transaksi/stoks', [TransaksiController::class, 'stokIndex'])->name('stoks.index');
        Route::get('/transaksi/docs', [TransaksiController::class, 'docIndex'])->name('docs.index');
    });

});

Route::get('/error', function () {
    abort(500);
});

Route::get('/auth/redirect/{provider}', [SocialiteController::class, 'redirect']);

require __DIR__ . '/auth.php';
