<?php

use App\Http\Controllers\Apps\PermissionManagementController;
use App\Http\Controllers\Apps\RoleManagementController;
use App\Http\Controllers\Apps\UserManagementController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
// use App\Http\Controllers\FeedController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ItemCategoryController;
use App\Http\Controllers\LivestockController;

use App\Http\Controllers\MasterData\ExpeditionController;
use App\Http\Controllers\MasterData\RekananController;
use App\Http\Controllers\MasterData\FarmController;
use App\Http\Controllers\MasterData\KandangController;
use App\Http\Controllers\MasterData\StokController;
use App\Http\Controllers\MasterData\SupplyController;
use App\Http\Controllers\MasterData\FeedController;
use App\Http\Controllers\MasterData\UnitController;

use App\Http\Controllers\ReportsController;
use App\Models\Stok;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\Transaksi\TransaksiController;
use App\Http\Controllers\TransaksiBeliController;
use App\Http\Controllers\TransaksiHarianController;
use App\Http\Controllers\TransaksiJualController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StandarBobotController;
use App\Http\Controllers\TernakController;
use App\Models\TransaksiJual;
use Illuminate\Http\Request;

use App\Http\Controllers\AdminController;
use App\Http\Controllers\OVKRecordController;

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

Route::get('/penjualan/export', [TransaksiJualController::class, 'export'])->name('penjualan.export');
Route::get('/reports/performance', [ReportsController::class, 'exportPerformance'])->name('reports.performance');


Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/', [DashboardController::class, 'index']);

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::name('administrator.')->group(function () {
        Route::get('/administrator/qa', [AdminController::class, 'qaIndex'])
            ->middleware(['auth', 'permission:access qa checklist'])
            ->name('qa');
        Route::get('/administrator/routes', [AdminController::class, 'routeIndex'])
            ->middleware(['auth', 'permission:access route manager'])
            ->name('routes.manager');

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
        Route::resource('/users', UserManagementController::class);
        Route::resource('/user/roles', RoleManagementController::class);
        Route::resource('/user/permissions', PermissionManagementController::class);
    });

    Route::name('master-data.')->group(function () {
        Route::resource('/master-data/perusahaans', CompanyController::class);
        Route::resource('/master-data/expeditions', ExpeditionController::class);
        Route::resource('/master-data/suppliers', RekananController::class);
        Route::resource('/master-data/farms', FarmController::class);
        Route::resource('/master-data/kandangs', KandangController::class);
        Route::resource('/master-data/stoks', StokController::class);
        Route::resource('/master-data/ternaks', TernakController::class);
        Route::resource('/master-data/feeds', App\Http\Controllers\MasterData\FeedController::class);
        Route::resource('/master-data/supplies', App\Http\Controllers\MasterData\SupplyController::class);
        Route::resource('/master-data/workers', App\Http\Controllers\MasterData\WorkerController::class);
        Route::resource('/master-data/livestocks', LivestockController::class);
        Route::resource('/master-data/units', UnitController::class);
        Route::get('/master-data/customers', [RekananController::class, 'customerIndex'])->name('customers.index');
        Route::get('/item-categories/list', [ItemCategoryController::class, 'getList'])->name('item-categories.list');
    });

    Route::name('data.')->group(function () {
        Route::resource('/data/farms', FarmController::class);
        Route::resource('/data/kandangs', KandangController::class);
        Route::resource('/data/stoks', StokController::class);
        Route::resource('/data/ternaks', TernakController::class);
        Route::resource('/data/livestocks', LivestockController::class);
        Route::resource('/data/livestock', LivestockController::class);
        Route::resource('/data/standar-bobot', StandarBobotController::class);

        Route::resource('/master-data/ternaks', TernakController::class);
        Route::get('/master-data/customers', [RekananController::class, 'customerIndex'])->name('customers.index');
        // Route::get('/item-categories/list', [ItemCategoryController::class, 'getList'])->name('item-categories.list');
    });

    Route::name('rekanan.')->group(function () {
        Route::resource('/rekanan/suppliers', RekananController::class);
        Route::get('/rekanan/customers', [RekananController::class, 'customerIndex'])->name('rekanan.customers');
        Route::get('/rekanan/ekspedisis', [RekananController::class, 'ekspedisiIndex'])->name('rekanan.ekspedisi');
    });

    Route::name('transaksi.')->group(function () {
        Route::get('/transaksi/penjualan', [TransaksiJualController::class, 'index'])->name('penjualan.index');
        Route::get('/transaksi/harian', [TransaksiController::class, 'harianIndex'])->name('harian.index');
        Route::get('/transaction/feed', [TransaksiController::class, 'stokIndex'])->name('feed');
        Route::get('/transaksi/supply', [TransaksiController::class, 'supplyIndex'])->name('supply');
        Route::get('/transaksi/pakai', [TransaksiController::class, 'stokPakaiIndex'])->name('stoks.pakai.index');
        Route::get('/transaksi/docs', [TransaksiController::class, 'docIndex'])->name('docs.index');
        Route::post('/reduce-stock', [StockController::class, 'reduceStock']);
        Route::get('/transaksi/kematian-ternak', [TernakController::class, 'kematianTernakIndex'])->name('kematian-ternak.index');
        Route::post('/transaksi-harian/filter', [TransaksiHarianController::class, 'filter'])->name('harian.filter');
    });

    Route::name('transaction.')->group(function () {
        Route::get('/transaction/feed', [TransactionController::class, 'feedIndex'])->name('feed');
        Route::get('/transaction/supply', [TransactionController::class, 'supplyIndex'])->name('supply');
        Route::get('/transaction/sales', [TransactionController::class, 'salesIndex'])->name('sales');
    });

    Route::name('inventory.')->group(function () {
        Route::get('/inventory/docs', [TransaksiController::class, 'docIndex'])->name('docs.index');
        Route::get('/inventory/stocks', [TransaksiController::class, 'stokIndex'])->name('stoks.index');
        Route::get('/inventory/pakan', [TransaksiBeliController::class, 'pakanIndex'])->name('pakan.index');
        Route::get('/inventory/ovk', [StokController::class, 'stockOvk'])->name('ovk.index');
    });

    Route::name('stocks.')->group(function () {
        Route::get('/inventory/docs', [TransaksiController::class, 'docIndex'])->name('docs.index');
        Route::get('/inventory/stocks', [TransaksiController::class, 'stokIndex'])->name('stoks.index');
        // Route::get('/stocks/mutasi', [StokController::class, 'stockMutasi'])->name('mutasi.index');
        // Route::get('/stocks/pakan', [StokController::class, 'stockPakan'])->name('pakan.index');
        Route::get('/stocks/ovk', [StokController::class, 'stockOvk'])->name('ovk.index');
        Route::post('/stocks/transfer', [StokController::class, 'transferStock'])->name('transfer');
        Route::get('/stocks/check-available', [StockController::class, 'checkAvailableStock'])->name('check-available');

        Route::get('/stocks/supply', [StockController::class, 'stockSupply'])->name('supply');
        Route::get('/stocks/feed', [StockController::class, 'stockPakan'])->name('pakan');
        Route::get('/feeds/mutation', [FeedController::class, 'mutasi'])->name('mutasiFeed');
        Route::get('/supplies/mutation', [SupplyController::class, 'mutasi'])->name('mutasiSupply');
    });

    Route::name('pembelian.')->group(function () {
        Route::get('/pembelian/stock', [TransaksiController::class, 'stokIndex'])->name('stoks.index');
        Route::get('/pembelian/doc', [TransaksiBeliController::class, 'indexDoc'])->name('docs.index');
        Route::get('/pembelian/pakan', [TransaksiBeliController::class, 'indexPakan'])->name('pakan.index');
        Route::get('/pembelian/ovk', [TransaksiBeliController::class, 'indexOvk'])->name('ovk.index');
    });

    Route::name('livestock.')->group(function () {
        Route::get('/livestock/recording', [TernakController::class, 'recordingIndex'])->name('recording.index');
        Route::get('/livestock/supply-recording', [LivestockController::class, 'supplyRecordingIndex'])->name('supplyRecording');
        Route::get('/livestock/mutasi', [LivestockController::class, 'mutationIndex'])->name('mutation');
        Route::get('/livestock/rollback', [TernakController::class, 'rollbackIndex'])->name('rollback.index');
        Route::get('/livestock/afkir', [TernakController::class, 'ternakAfkirIndex'])->name('afkir.index');
        Route::get('/livestock/jual', [TernakController::class, 'ternakJualIndex'])->name('jual.index');
        Route::get('/livestock/mati', [TernakController::class, 'ternakMatiIndex'])->name('mati.index');
        Route::get('/livestock/{id}/detail', [LivestockController::class, 'showLivestockDetails'])->name('detail');
        // Route::get('/livestock/{id}/detail', [TernakController::class, 'showTernakDetails'])->name('detail');
        // Route::get('/livestock/{id}/detail', [TernakController::class, 'showDetail'])->name('detail');
        Route::resource('/livestock', TernakController::class);
        Route::delete('/recording/delete/{id}', [TernakController::class, 'destroyRecording']);
    });




    Route::name('farm.')->group(function () {
        Route::get('/farm/{farm}/kandangs', [FarmController::class, 'getKandangs'])->name('kandangs');
    });

    Route::name('reports.')->group(function () {
        Route::get('/reports/harian', [ReportsController::class, 'indexHarian']);
        Route::get('/reports/daily-cost', [ReportsController::class, 'indexDailyCost']);
        Route::get('/reports/batch-worker', [ReportsController::class, 'indexBatchWorker']);
        Route::get('/reports/penjualan', [ReportsController::class, 'indexPenjualan']);
        Route::get('/reports/feed/purchase', [FeedController::class, 'indexReportFeedPurchase']);
        Route::get('/reports/performa-mitra', [ReportsController::class, 'indexPerformaMitra']);
        Route::get('/reports/performa', [ReportsController::class, 'indexPerforma']);
        Route::get('/reports/inventory', [ReportsController::class, 'indexInventory']);
    });

    // Route::get('/ovk-records', App\Livewire\OVK\Index::class)->name('ovk-records.index');
    // Route::get('/ovk-records/create', App\Livewire\OVK\Create::class)->name('ovk-records.create');
    // Route::get('/ovk-records/{ovkRecord}/edit', App\Livewire\OVK\Edit::class)->name('ovk-records.edit');
});

Route::get('/error', function () {
    abort(500);
});

Route::get('/auth/redirect/{provider}', [SocialiteController::class, 'redirect']);

// Route::middleware(['auth', 'permission:access route manager'])->group(function () {
//     Route::get('/administrator/routes', App\Livewire\RouteManager::class)->name('route.manager');
// });

require __DIR__ . '/auth.php';
