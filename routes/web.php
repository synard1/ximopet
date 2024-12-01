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
use App\Http\Controllers\StockController;
use App\Http\Controllers\TernakController;
use Illuminate\Http\Request;

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

Route::get('/tokens/create', function (Request $request) {
    $token = $request->user()->createToken($request->token_name);
 
    return ['token' => $token->plainTextToken];
});

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/', [DashboardController::class, 'index']);

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::name('administrator.')->group(function () {
        Route::get('/administrator', function () {
            return view('test3');
        });
        Route::get('/setting', function () {
            return view('pages.system.setting.index');
        });
        Route::resource('/master-data/users', UserManagementController::class);
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
        Route::resource('/master-data/ternaks', TernakController::class);
        Route::get('/master-data/customers', [RekananController::class, 'customerIndex'])->name('customers.index');
    });

    Route::name('transaksi.')->group(function () {
        Route::get('/transaksi/harian', [TransaksiController::class, 'harianIndex'])->name('harian.index');
        Route::get('/transaksi/stoks', [TransaksiController::class, 'stokIndex'])->name('stoks.index');
        Route::get('/transaksi/pakai', [TransaksiController::class, 'stokPakaiIndex'])->name('stoks.pakai.index');
        Route::get('/transaksi/docs', [TransaksiController::class, 'docIndex'])->name('docs.index');
        Route::post('/reduce-stock', [StockController::class, 'reduceStock']);
        Route::get('/transaksi/kematian-ternak', [TernakController::class, 'kematianTernakIndex'])->name('kematian-ternak.index');

    });

    Route::name('ternak.')->group(function () {
        Route::get('/ternak/afkir', [TernakController::class, 'ternakAfkirIndex'])->name('afkir.index');
        Route::get('/ternak/jual', [TernakController::class, 'ternakJualIndex'])->name('jual.index');
        Route::get('/ternak/mati', [TernakController::class, 'ternakMatiIndex'])->name('mati.index');
        Route::resource('/ternak', TernakController::class);


    });

    

});

Route::get('/error', function () {
    abort(500);
});

Route::get('/auth/redirect/{provider}', [SocialiteController::class, 'redirect']);

require __DIR__ . '/auth.php';
