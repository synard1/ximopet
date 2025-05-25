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
use App\Http\Controllers\MasterData\WorkerController;

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
use App\Http\Controllers\MenuController;
use App\Http\Controllers\UserManagement\RoleController;

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

// Public Routes
Route::get('/', function () {
    return view('layout._frontend');
});

Route::get('/frontend', function () {
    return view('layout._frontend');
});

Route::get('/test', function () {
    return view('test');
});

// Authentication Routes
Route::get('/auth/redirect/{provider}', [SocialiteController::class, 'redirect']);

// Protected Routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard Routes
    Route::get('/', [DashboardController::class, 'index']);
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Administrator Routes
    Route::name('administrator.')->middleware(['auth'])->prefix('administrator')->group(function () {
        Route::get('/qa', [AdminController::class, 'qaIndex'])
            ->middleware(['permission:access qa checklist'])
            ->name('qa');

        // QA Todo Routes
        Route::get('/qa-todo', [AdminController::class, 'qaTodoIndex'])
            ->middleware(['permission:access qa-todo'])
            ->name('qa.todo');

        Route::get('/qa-todo/download-attachment/{comment}/{index}', [AdminController::class, 'downloadAttachment'])
            ->middleware(['permission:access qa-todo'])
            ->name('qa.todo.download-attachment');

        Route::get('/routes', [AdminController::class, 'routeIndex'])
            ->middleware(['permission:access route manager'])
            ->name('routes.manager');
        Route::get('/', function () {
            return view('test3');
        });
        Route::get('/setting', function () {
            return view('pages.system.setting.index');
        });

        // Menu Management Routes
        Route::middleware(['auth', 'role:SuperAdmin'])->name('menu.')->prefix('menu')->group(function () {
            // Add export route BEFORE the resource route
            Route::get('export', [MenuController::class, 'export'])->name('export');
            // Add import route BEFORE the resource route
            Route::post('import', [MenuController::class, 'import'])->name('import');

            // Explicitly define the index route for DataTables
            Route::get('/', [MenuController::class, 'index'])->name('index');

            // Explicitly define the edit route
            Route::get('{menu}/edit', [MenuController::class, 'edit'])->name('edit');
            Route::put('{menu}', [MenuController::class, 'update'])->name('update');

            // Add duplicate route
            Route::post('{menu}/duplicate', [MenuController::class, 'duplicate'])->name('duplicate');

            // Explicitly define the delete route
            Route::delete('{menu}', [MenuController::class, 'destroy'])->name('destroy');

            // Resource route for standard CRUD operations (excluding index, show, edit, and destroy)
            Route::resource('/', MenuController::class)->except([
                'index',
                'show',
                'edit',
                'destroy' // Exclude destroy as we are defining it explicitly
            ]);

            // Custom route for updating menu order
            Route::post('update-order', [MenuController::class, 'updateOrder'])->name('update-order');
        });
    });

    // User Management Routes
    Route::name('user-management.')->group(function () {
        Route::resource('administrator/users', UserManagementController::class);
        Route::resource('/user/roles', RoleManagementController::class);
        Route::resource('/user/permissions', PermissionManagementController::class);
        Route::get('roles/data', [App\Http\Controllers\UserManagement\RoleController::class, 'data'])->name('roles.data');
    });

    // Master Data Routes
    Route::name('master.')->group(function () {
        // Company Management
        Route::resource('/master/companies', CompanyController::class);

        // Expedition Management
        Route::resource('/master/expeditions', ExpeditionController::class);

        // Partner Management
        Route::resource('/master/partners', RekananController::class);
        Route::get('/master/suppliers', [RekananController::class, 'supplierIndex'])->name('suppliers.index');
        Route::get('/master/customers', [RekananController::class, 'customerIndex'])->name('customers.index');

        // Farm Management
        Route::resource('/master/farms', FarmController::class);
        Route::resource('/master/kandangs', KandangController::class);

        // Inventory Management
        Route::resource('/master/stocks', StokController::class);
        Route::resource('/master/feeds', FeedController::class);
        Route::resource('/master/supplies', SupplyController::class);
        Route::resource('/master/units', UnitController::class);

        // Livestock Management
        Route::resource('/master/livestocks', LivestockController::class);
        Route::resource('/master/livestock-standard', StandarBobotController::class);
        Route::resource('/master/ternaks', TernakController::class);
        Route::resource('/master/workers', WorkerController::class);

        // Item Categories
        Route::get('/master/item-categories/list', [ItemCategoryController::class, 'getList'])->name('item-categories.list');
    });

    // Transaction Routes
    Route::name('transaction.')->prefix('transaction')->group(function () {
        // Sales Routes
        Route::get('/transaction/sales', [TransaksiJualController::class, 'index'])->name('sales.index');
        Route::get('/transaction/sales/export', [TransaksiJualController::class, 'export'])->name('sales.export');

        // Daily Transaction Routes
        Route::get('/transaction/daily', [TransaksiController::class, 'harianIndex'])->name('daily.index');
        Route::post('/transaction/daily/filter', [TransaksiHarianController::class, 'filter'])->name('daily.filter');

        // Feed Routes
        Route::get('/feed-mutation', [FeedController::class, 'mutasi'])->name('feed.mutation');
        Route::get('/feed', [TransactionController::class, 'feedIndex'])->name('feed');

        // Supply Routes
        Route::get('/supply-mutation', [SupplyController::class, 'mutasi'])->name('supply.mutation');
        Route::get('/supply', [TransactionController::class, 'supplyIndex'])->name('supply');

        // Stock Usage Routes
        Route::get('/transaction/stock-usage', [TransaksiController::class, 'stokPakaiIndex'])->name('stock-usage.index');

        // Document Routes
        // Route::get('/transaction/documents', [TransaksiController::class, 'docIndex'])->name('documents.index');

        // Livestock Death Routes
        Route::get('/transaction/livestock-death', [TernakController::class, 'kematianTernakIndex'])->name('livestock-death.index');

        // Document Routes
        Route::get('/livestock', [TransactionController::class, 'livestockIndex'])->name('livestock.index');
    });

    // Inventory Routes
    Route::name('inventory.')->group(function () {
        // Route::get('/inventory/documents', [TransaksiController::class, 'docIndex'])->name('documents.index');
        Route::get('/inventory/stocks', [TransaksiController::class, 'stokIndex'])->name('stocks.index');
        Route::get('/inventory/feed', [TransaksiBeliController::class, 'pakanIndex'])->name('feed.index');
        Route::get('/inventory/ovk', [StokController::class, 'stockOvk'])->name('ovk.index');
    });

    // Stock Management Routes
    Route::name('stock.')->group(function () {
        Route::post('/stock/reduce', [StockController::class, 'reduceStock']);
        Route::post('/stock/transfer', [StockController::class, 'transferStock'])->name('transfer');
        Route::get('/stock/check-available', [StockController::class, 'checkAvailableStock'])->name('check-available');
        Route::get('/stock/supply', [StockController::class, 'stockSupply'])->name('supply');
        Route::get('/stock/feed', [StockController::class, 'stockPakan'])->name('feed');
    });

    // Livestock Management Routes
    Route::name('livestock.')->group(function () {
        Route::get('/livestock/recording', [TernakController::class, 'recordingIndex'])->name('recording.index');
        Route::get('/livestock/supply-recording', [LivestockController::class, 'supplyRecordingIndex'])->name('supply-recording');
        Route::get('/livestock/mutation', [LivestockController::class, 'mutationIndex'])->name('mutation');
        Route::get('/livestock/rollback', [TernakController::class, 'rollbackIndex'])->name('rollback.index');
        Route::get('/livestock/disposal', [TernakController::class, 'ternakAfkirIndex'])->name('disposal.index');
        Route::get('/livestock/sales', [TernakController::class, 'ternakJualIndex'])->name('sales.index');
        Route::get('/livestock/death', [TernakController::class, 'ternakMatiIndex'])->name('death.index');
        Route::get('/livestock/{id}/detail', [LivestockController::class, 'showLivestockDetails'])->name('detail');
        Route::resource('/livestock', TernakController::class);
        Route::delete('/livestock/recording/{id}', [TernakController::class, 'destroyRecording']);
    });

    // Farm Management Routes
    Route::name('farm.')->group(function () {
        Route::get('/farm/{farm}/kandangs', [FarmController::class, 'getKandangs'])->name('kandangs');
    });

    // Report Routes
    Route::name('report.')->group(function () {
        Route::get('/report/daily', [ReportsController::class, 'indexHarian'])->name('daily');
        Route::get('/report/daily-cost', [ReportsController::class, 'indexDailyCost'])->name('daily-cost');
        Route::get('/report/batch-worker', [ReportsController::class, 'indexBatchWorker'])->name('batch-worker');
        Route::get('/report/sales', [ReportsController::class, 'indexPenjualan'])->name('sales');
        Route::get('/report/feed/purchase', [FeedController::class, 'indexReportFeedPurchase'])->name('feed-purchase');
        Route::get('/report/partner-performance', [ReportsController::class, 'indexPerformaMitra'])->name('partner-performance');
        Route::get('/report/performance', [ReportsController::class, 'indexPerforma'])->name('performance');
        Route::get('/report/inventory', [ReportsController::class, 'indexInventory'])->name('inventory');
    });

    // Role Management Routes
    Route::middleware(['auth'])->group(function () {
        Route::resource('roles', RoleController::class);
        Route::post('roles/backup', [RoleController::class, 'createBackup'])->name('roles.backup');
    });
});

// Error Routes
Route::get('/error', function () {
    abort(500);
});

require __DIR__ . '/auth.php';

/*
|--------------------------------------------------------------------------
| Legacy Routes (Kept for backward compatibility)
|--------------------------------------------------------------------------
*/

// Original routes are kept here as comments for reference
// Route::get('/supplier-create', SupplierModal::class)->name('supplier.create');
// Route::get('/test2', function () {
//     return view('test2');
// });
// Route::get('/tokens/create', function (Request $request) {
//     $token = $request->user()->createToken($request->token_name);
//     return ['token' => $token->plainTextToken];
// });
