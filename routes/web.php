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
use App\Http\Controllers\MasterData\CoopController;
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
use App\Http\Controllers\MasterData\PartnerController;
use App\Http\Controllers\QaController;
use App\Http\Livewire\AuditTrail;
use App\Http\Controllers\PurchaseReportsController;
use App\Http\Controllers\PageController;
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

        // Resource route for standard CRUD operations (excluding index, show, edit, and destroy)
        Route::resource('/qa', QaController::class)->except([
            'index',
            'show',
            'edit',
            'destroy' // Exclude destroy as we are defining it explicitly
        ]);

        // QA Management Routes
        Route::middleware(['auth', 'permission:access qa checklist'])->name('qa.')->prefix('qa')->group(function () {
            // Add export route BEFORE the resource route
            Route::get('export', [QaController::class, 'export'])->name('export');
            // Add import route BEFORE the resource route
            Route::post('import', [QaController::class, 'import'])->name('import');

            // Explicitly define the index route for DataTables
            Route::get('/', [QaController::class, 'index'])->name('index');

            // Explicitly define the edit route
            Route::get('{qa}/edit', [QaController::class, 'edit'])->name('edit');
            Route::put('{qa}', [QaController::class, 'update'])->name('update');

            // Add duplicate route
            Route::post('{qa}/duplicate', [QaController::class, 'duplicate'])->name('duplicate');

            // Explicitly define the delete route
            Route::delete('{qa}', [QaController::class, 'destroy'])->name('destroy');

            // Resource route for standard CRUD operations (excluding index, show, edit, and destroy)
            Route::resource('/', QaController::class)->except([
                'index',
                'show',
                'edit',
                'destroy' // Exclude destroy as we are defining it explicitly
            ]);

            // Custom route for updating QA order
            Route::post('update-order', [QaController::class, 'updateOrder'])->name('update-order');
        });

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
            // Add import preview route BEFORE the import route
            Route::post('import-preview', [MenuController::class, 'importPreview'])->name('import-preview');
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

        // Supply Data Integrity Check
        Route::get('/supply/integrity-check', function () {
            return view('pages.admin.data-integrity.supply-integrity-check');
        })->name('supply.integrity-check');

        // Supply Data Integrity Check
        Route::get('/feed/integrity-check', function () {
            return view('pages.admin.data-integrity.feed-integrity-check');
        })->name('feed.integrity-check');

        // Livestock Data Integrity Check
        Route::get('/livestock/integrity-check', function () {
            return view('pages.admin.data-integrity.livestock-integrity-check');
        })->name('livestock.integrity-check');
    });

    // User Management Routes
    Route::name('user-management.')->group(function () {
        Route::resource('/users', UserManagementController::class);
        Route::resource('/user/roles', RoleManagementController::class);
        Route::resource('/user/permissions', PermissionManagementController::class);
        Route::get('roles/data', [App\Http\Controllers\UserManagement\RoleController::class, 'data'])->name('roles.data');
    });

    // Master Data Routes
    Route::name('master.')->group(function () {
        // Company Management
        Route::resource('/master/companies', CompanyController::class);

        // Expedition Management
        Route::get('/master/expeditions', [PartnerController::class, 'expeditionIndex'])->name('expeditions.index');

        // Partner Management
        Route::resource('/master/partners', RekananController::class);
        Route::get('/master/suppliers', [RekananController::class, 'supplierIndex'])->name('suppliers.index');
        Route::get('/master/customers', [RekananController::class, 'customerIndex'])->name('customers.index');

        // Farm Management
        Route::resource('/master/farms', FarmController::class);
        Route::resource('/master/coops', CoopController::class);
        // Route::resource('/master/kandangs', KandangController::class);

        // Inventory Management
        Route::resource('/master/stocks', StokController::class);
        Route::resource('/master/feeds', FeedController::class);
        Route::resource('/master/supplies', SupplyController::class);
        Route::resource('/master/units', UnitController::class);

        // Livestock Management
        // Route::resource('/master/livestocks', LivestockController::class);
        Route::get('/master/livestock-strains', [LivestockController::class, 'livestockStrainIndex'])->name('livestock-strains.index');
        Route::get('/master/livestock-standard', [LivestockController::class, 'livestockStandardIndex'])->name('livestock-standard.index');
        // Route::resource('/master/livestock-standard', StandarBobotController::class);
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
        Route::get('/supply-usage', [SupplyController::class, 'usageIndex'])->name('supply.usage');
        Route::get('/supply', [TransactionController::class, 'supplyIndex'])->name('supply');

        // Stock Usage Routes
        Route::get('/stock-usage', [TransaksiController::class, 'stokPakaiIndex'])->name('stock-usage.index');

        // Document Routes
        // Route::get('/transaction/documents', [TransaksiController::class, 'docIndex'])->name('documents.index');

        // Livestock Routes
        Route::get('/livestock-mutation', [LivestockController::class, 'mutasi'])->name('livestock.mutation');
        Route::get('/transaction/livestock', [LivestockController::class, 'purchaseIndex'])->name('livestock.index');
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
        // Route::get('/stock/supply', [StockController::class, 'stockSupply'])->name('supply');
        // Route::get('/stock/feed', [StockController::class, 'stockPakan'])->name('feed');
    });

    // Livestock Management Routes
    Route::name('livestock.')->prefix('livestock')->group(function () {
        // Specific batch routes to avoid conflicts
        Route::get('/batch', [LivestockController::class, 'index'])->name('batch.index');
        Route::get('/batch/create', [LivestockController::class, 'create'])->name('batch.create');
        Route::post('/batch', [LivestockController::class, 'store'])->name('batch.store');
        Route::get('/batch/{id}', [LivestockController::class, 'show'])->name('batch.show');
        Route::get('/batch/{id}/edit', [LivestockController::class, 'edit'])->name('batch.edit');
        Route::put('/batch/{id}', [LivestockController::class, 'update'])->name('batch.update');
        Route::delete('/batch/{id}', [LivestockController::class, 'destroy'])->name('batch.destroy');

        Route::get('/supply', [StockController::class, 'stockSupply'])->name('supply');
        Route::get('/feed', [StockController::class, 'stockPakan'])->name('feed');
        Route::get('/livestock/recording', [TernakController::class, 'recordingIndex'])->name('recording.index');
        Route::get('/livestock/supply-recording', [LivestockController::class, 'supplyRecordingIndex'])->name('supply-recording');
        Route::get('/livestock/mutation', [LivestockController::class, 'mutationIndex'])->name('mutation');
        Route::get('/livestock/rollback', [TernakController::class, 'rollbackIndex'])->name('rollback.index');
        Route::get('/livestock/disposal', [TernakController::class, 'ternakAfkirIndex'])->name('disposal.index');
        Route::get('/livestock/sales', [TernakController::class, 'ternakJualIndex'])->name('sales.index');
        Route::get('/livestock/death', [TernakController::class, 'ternakMatiIndex'])->name('death.index');
        Route::get('/{id}/detail', [LivestockController::class, 'showLivestockDetails'])->name('detail');
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
        Route::post('/report/daily/export', [ReportsController::class, 'exportHarian'])->name('daily.export');
        Route::get('/report/daily/export', [ReportsController::class, 'exportHarian'])->name('daily.export');
        Route::get('/report/daily-cost', [ReportsController::class, 'indexDailyCost'])->name('daily-cost');
        Route::get('/report/batch-worker', [ReportsController::class, 'indexBatchWorker'])->name('batch-worker');
        Route::get('/report/sales', [ReportsController::class, 'indexPenjualan'])->name('sales');
        Route::get('/report/feed/purchase', [FeedController::class, 'indexReportFeedPurchase'])->name('feed-purchase');
        Route::get('/report/partner-performance', [ReportsController::class, 'indexPerformaMitra'])->name('partner-performance');
        Route::get('/report/performance', [ReportsController::class, 'indexPerforma'])->name('performance');
        Route::get('/report/inventory', [ReportsController::class, 'indexInventory'])->name('inventory');
        Route::get('/report/smart-analytics', [ReportsController::class, 'smartAnalytics'])->name('smart-analytics');
        // Route::get('/report/pembelian-livestock', [PurchaseReportsController::class, 'indexPembelianLivestock'])->name('pembelian-livestock');
        // Route::get('/report/pembelian-pakan', [PurchaseReportsController::class, 'indexPembelianPakan'])->name('pembelian-pakan');
        // Route::get('/report/pembelian-supply', [PurchaseReportsController::class, 'indexPembelianSupply'])->name('pembelian-supply');

        // Supply Purchase Reports
        Route::prefix('supply-purchase')->group(function () {
            Route::get('/', [PurchaseReportsController::class, 'indexPembelianSupply'])->name('supply-purchase.index');
            Route::get('/export', [PurchaseReportsController::class, 'exportPembelianSupply'])->name('supply-purchase.export');
            Route::get('/html', [PurchaseReportsController::class, 'exportPembelianSupplyHtml'])->name('supply-purchase.html');
        });
    });

    Route::name('purchase-reports.')->group(function () {
        // Livestock Purchase Reports
        Route::get('/report/livestock-purchase', [PurchaseReportsController::class, 'indexPembelianLivestock'])->name('pembelian-livestock');
        Route::get('/report/livestock-purchase/export', [PurchaseReportsController::class, 'exportPembelianLivestock'])->name('export-livestock');
        Route::post('/report/livestock-purchase/export', [PurchaseReportsController::class, 'exportPembelianLivestock'])->name('export-livestock');

        // Feed Purchase Reports  
        Route::get('/report/feed-purchase', [PurchaseReportsController::class, 'indexPembelianPakan'])->name('pembelian-pakan');
        Route::get('/report/feed-purchase/export', [PurchaseReportsController::class, 'exportPembelianPakan'])->name('export-pakan');
        Route::post('/report/feed-purchase/export', [PurchaseReportsController::class, 'exportPembelianPakan'])->name('export-pakan');

        // Supply Purchase Reports
        Route::get('/report/supply-purchase', [PurchaseReportsController::class, 'indexPembelianSupply'])->name('pembelian-supply');
        Route::get('/report/supply-purchase/export', [PurchaseReportsController::class, 'exportPembelianSupply'])->name('export-supply');
        Route::post('/report/supply-purchase/export', [PurchaseReportsController::class, 'exportPembelianSupply'])->name('export-supply');
    });

    Route::get('/report/supply-purchase/html', function (\Illuminate\Http\Request $request) {
        $query = \App\Models\SupplyPurchaseBatch::with([
            'supplier',
            'supplyPurchases.supply',
            'supplyPurchases.unit',
            'farm'
        ])
            ->when($request->start_date, fn($q) => $q->where('date', '>=', $request->start_date))
            ->when($request->end_date, fn($q) => $q->where('date', '<=', $request->end_date))
            ->when($request->supplier, fn($q) => $q->where('supplier_id', $request->supplier))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->farm, fn($q) => $q->where('farm_id', $request->farm))
            ->when($request->tahun, fn($q) => $q->whereYear('date', $request->tahun))
            ->when($request->supply, function ($q) use ($request) {
                $q->whereHas('supplyPurchases', function ($sq) use ($request) {
                    $sq->where('supply_id', $request->supply);
                });
            })
            ->when($request->invoice_number, fn($q) => $q->where('invoice_number', $request->invoice_number));
        $batches = $query->get();
        $summary = [
            'period' => ($request->start_date && $request->end_date) ? (\Carbon\Carbon::parse($request->start_date)->format('d-m-Y') . ' s.d. ' . \Carbon\Carbon::parse($request->end_date)->format('d-m-Y')) : '-',
            'total_batches' => $batches->count(),
            'total_purchases' => $batches->sum(fn($batch) => $batch->supplyPurchases->count()),
            'total_suppliers' => $batches->unique('supplier_id')->count(),
            'total_farms' => $batches->pluck('farm_id')->unique()->count(),
            'total_value' => $batches->sum(fn($batch) => $batch->supplyPurchases->sum(fn($purchase) => $purchase->quantity * $purchase->price_per_unit)),
            'total_quantity' => $batches->sum(fn($batch) => $batch->supplyPurchases->sum('quantity')),
        ];
        $invoiceDetail = null;
        if ($request->invoice_number) {
            $invoiceDetail = \App\Models\SupplyPurchaseBatch::with(['supplier', 'supplyPurchases.supply', 'supplyPurchases.unit'])
                ->where('invoice_number', $request->invoice_number)
                ->first();
        }
        return view('pages.reports.pembelian-supply-html', compact('batches', 'summary', 'invoiceDetail'));
    });

    route::name('sample.')->group(function () {
        Route::get('/sample/mortality-chart-refactored', function () {
            return view('sample.mortality-chart-refactored');
        })->name('mortality-chart-refactored');
        Route::get('/sample/simple-notification-test', function () {
            return view('sample.simple_notification_test');
        })->name('simple_notification_test');
        Route::get('/sample/browser-notification-debug', function () {
            return view('sample.browser_notification_debug');
        })->name('browser_notification_debug');
    });

    // Role Management Routes
    Route::middleware(['auth'])->group(function () {
        Route::resource('roles', RoleController::class);
        Route::post('roles/backup', [RoleController::class, 'createBackup'])->name('roles.backup');
    });

    // Supply Data Integrity Check
    Route::get('/supply/integrity-check', function () {
        return view('supply.integrity-check');
    })->name('supply.integrity-check');

    // Audit Trail Routes
    Route::get('/audit-trail', function () {
        return view('pages.admin.audit-trail.index');
    })->name('audit-trail.index');
    // Route::get('/audit-trail', AuditTrail::class)->name('audit-trail.index');

    // Batch Worker Report
    Route::get('/reports/batch-worker', [ReportsController::class, 'indexBatchWorker'])->name('reports.batch-worker');
    Route::get('/reports/batch-worker/export', [ReportsController::class, 'exportBatchWorker'])->name('reports.batch-worker.export');

    Route::name('setting.')->prefix('setting')->group(function () {
        // Company Management
        Route::get('/companies', [App\Http\Controllers\Pages\CompanyController::class, 'index'])->name('companies.index');
        Route::get('/companies/data', [App\Http\Controllers\Pages\CompanyController::class, 'getData'])->name('companies.data');
        Route::get('/company-user-mapping', [App\Http\Controllers\Pages\CompanyController::class, 'mappingIndex'])->name('companyuser.mapping');
        Route::post('/companies/{company}', [App\Http\Controllers\Pages\CompanyController::class, 'update'])->name('companies.update');
    });

    // Alert Preview Routes
    Route::middleware(['auth'])->prefix('alerts')->name('alerts.')->group(function () {
        Route::get('/', [App\Http\Controllers\Pages\AlertPreviewController::class, 'index'])->name('index');
        Route::get('/preview/{type}', [App\Http\Controllers\Pages\AlertPreviewController::class, 'preview'])->name('preview');
        Route::post('/test', [App\Http\Controllers\Pages\AlertPreviewController::class, 'test'])->name('test');
    });

    // Transaction Clear routes
    Route::prefix('transaction-clear')->name('transaction-clear.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\TransactionClearController::class, 'index'])->name('index');
        Route::get('/preview', [App\Http\Controllers\Admin\TransactionClearController::class, 'preview'])->name('preview');
        Route::post('/clear', [App\Http\Controllers\Admin\TransactionClearController::class, 'clear'])->name('clear');
        Route::get('/history', [App\Http\Controllers\Admin\TransactionClearController::class, 'history'])->name('history');
    });

    // SuperAdmin Company Permission Manager
    Route::middleware(['auth', 'role:SuperAdmin'])->prefix('superadmin')->name('superadmin.')->group(function () {
        Route::get('/companies', \App\Http\Controllers\Pages\CompanyController::class . '@index')->name('companies');
        Route::get('/company/{company}/permissions', \App\Livewire\Superadmin\CompanyPermissionManager::class)->name('company.permissions');
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
