<?php

use App\Actions\SamplePermissionApi;
use App\Actions\SampleRoleApi;
use App\Actions\SampleUserApi;
use App\Actions\AppApi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MasterData\KandangController;
use App\Http\Controllers\Transaksi\TransaksiController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\MasterData\FarmController;
use App\Http\Controllers\TernakController;
use App\Http\Controllers\TransaksiTernakController;
use App\Http\Controllers\DataController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\SupplyController;
use App\Http\Controllers\MutationController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\MenuController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Authentication Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/check-auth', function (Request $request) {
        if ($request->user()) {
            return response()->json(['user' => $request->user()]);
        } else {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
    });

    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});

// API v2 Routes
Route::middleware('auth:sanctum')->prefix('v2')->group(function () {
    // Data Routes
    Route::post('/data/{sources}/{operators?}', [DataController::class, 'index']);
    Route::post('/d/item/location', [DataController::class, 'index'])->name('api.v2.item_location_mapping');
    Route::post('/d/transaksi/{details?}', [DataController::class, 'transaksi']);

    // Reports Routes
    Route::prefix('reports')->group(function () {
        Route::post('/performa-mitra', [ReportsController::class, 'exportPerformancePartner']);
        Route::post('/performa', [ReportsController::class, 'exportPerformance']);
        Route::post('/penjualan', [ReportsController::class, 'exportPenjualan']);
        Route::post('/harian', [ReportsController::class, 'exportHarian']);
        Route::post('/livestock-cost', [ReportsController::class, 'exportCostHarian']);
        Route::post('/batch-worker', [ReportsController::class, 'exportBatchWorker']);
    });

    // Livestock Routes
    Route::prefix('livestock')->group(function () {
        Route::post('/{livestockId}/bonus', [TernakController::class, 'addBonus']);
        Route::post('/{livestockId}/administrasi', [TernakController::class, 'addAdministrasi']);
    });

    // Feed Routes
    Route::prefix('feed')->group(function () {
        Route::get('/purchase/details/{id}', [FeedController::class, 'getFeedPurchaseBatchDetail']);
        Route::post('/usages/details', [FeedController::class, 'getFeedCardByLivestock']);
        Route::post('/mutation/details/{id}', [MutationController::class, 'getMutationDetails']);
        Route::post('/reports/purchase', [FeedController::class, 'exportPembelian']);
        Route::post('/purchase/edit', [FeedController::class, 'stockEdit']);
    });

    // Supply Routes
    Route::prefix('supply')->group(function () {
        Route::get('/purchase/details/{id}', [SupplyController::class, 'getSupplyPurchaseBatchDetail']);
        Route::post('/usages/details', [SupplyController::class, 'getSupplyByFarm']);
        Route::post('/reports/purchase', [FeedController::class, 'exportPembelian']);
        Route::post('/mutation/details/{id}', [MutationController::class, 'getMutationDetails']);
        Route::post('/purchase/edit', [SupplyController::class, 'stockEdit']);
        Route::post('/transfer', [StockController::class, 'transferStock'])->name('transfer');
    });

    Route::prefix('data/farms')->middleware(['auth:sanctum'])->group(function () {
        Route::post('/kandangs', [App\Http\Controllers\Api\V2\FarmController::class, 'getKandangs']);
    });
});

// API v1 Routes
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    // Livestock Routes
    Route::prefix('livestock')->group(function () {
        Route::post('/{livestockId}/bonus', [TernakController::class, 'getBonusData']);
        Route::post('/{livestockId}/detail-report', [TernakController::class, 'getDetailReportData']);
    });

    // User Management Routes
    Route::prefix('users')->group(function () {
        Route::get('/', [SampleUserApi::class, 'datatableList']);
        Route::post('/list', [SampleUserApi::class, 'datatableList']);
        Route::post('/', [SampleUserApi::class, 'create']);
        Route::get('/{id}', [SampleUserApi::class, 'get']);
        Route::put('/{id}', [SampleUserApi::class, 'update']);
        Route::delete('/{id}', [SampleUserApi::class, 'delete']);
    });

    // Role Management Routes
    Route::prefix('roles')->group(function () {
        Route::get('/', [SampleRoleApi::class, 'datatableList']);
        Route::post('/list', [SampleRoleApi::class, 'datatableList']);
        Route::post('/', [SampleRoleApi::class, 'create']);
        Route::get('/{id}', [SampleRoleApi::class, 'get']);
        Route::put('/{id}', [SampleRoleApi::class, 'update']);
        Route::delete('/{id}', [SampleRoleApi::class, 'delete']);
        Route::post('/{id}/users', [SampleRoleApi::class, 'usersDatatableList']);
        Route::delete('/{id}/users/{user_id}', [SampleRoleApi::class, 'deleteUser']);
    });

    // Permission Management Routes
    Route::prefix('permissions')->group(function () {
        Route::get('/', [SamplePermissionApi::class, 'datatableList']);
        Route::post('/list', [SamplePermissionApi::class, 'datatableList']);
        Route::post('/', [SamplePermissionApi::class, 'create']);
        Route::get('/{id}', [SamplePermissionApi::class, 'get']);
        Route::put('/{id}', [SamplePermissionApi::class, 'update']);
        Route::delete('/{id}', [SamplePermissionApi::class, 'delete']);
    });

    // Farm Management Routes
    Route::prefix('farms')->group(function () {
        Route::get('/list', [AppApi::class, 'getFarm']);
        Route::get('/', [AppApi::class, 'datatableListFarm']);
        Route::post('/list', [AppApi::class, 'datatableListFarm']);
        Route::post('/', [FarmController::class, 'getDataAjax']);
        Route::get('/{id}', [AppApi::class, 'get']);
        Route::put('/{id}', [AppApi::class, 'update']);
        Route::delete('/{id}', [AppApi::class, 'delete']);
        Route::get('/{farm}/operators', [AppApi::class, 'getOperators']);
        Route::post('/{farm}/operators', [AppApi::class, 'farmOperator']);
        Route::delete('/{farm}/operators', [AppApi::class, 'deleteFarmOperator']);
        Route::post('/{farm}/kandangs', [FarmController::class, 'getKandangs'])->name('kandangs');
    });

    // Kandang Routes
    Route::prefix('kandangs')->group(function () {
        Route::post('/', [KandangController::class, 'getDataAjax']);
    });

    // Livestock Routes
    Route::prefix('livestock')->group(function () {
        Route::post('/', [TernakController::class, 'getDataAjax']);
    });

    Route::get('/get-operators/{farm}', [AppApi::class, 'getOperators']);

    // Transaction Routes
    Route::prefix('transactions')->group(function () {
        Route::post('/', [TransaksiController::class, 'getTransaksi']);
        Route::get('/purchase/details/{id}', [AppApi::class, 'getTransaksiBeliDetail']);
        Route::get('/details/{id}', [AppApi::class, 'getTransaksiDetail']);
        Route::post('/livestock/details', [TransaksiTernakController::class, 'getDetails']);
    });

    // Stock Routes
    Route::prefix('stocks')->group(function () {
        Route::post('/', [StockController::class, 'stoks'])->name('api.v1.stoks');
        Route::post('/mutation', [AppApi::class, 'createMutasiStok']);
    });

    // Utility Routes
    Route::get('/get-farms', [AppApi::class, 'getFarms']);
    Route::get('/get-kandangs/{farm}/{status}', [AppApi::class, 'getKandangs']);
    Route::get('/get-farm-stocks/{farm}', [AppApi::class, 'getFarmStoks']);
    Route::get('/sales/{transaksiId}', [AppApi::class, 'getPenjualan']);
    Route::get('/reset-demo', [AppApi::class, 'resetDemo']);
});

// Authentication Routes
Route::prefix('auth')->group(function () {
    // User Registration
    Route::post('/register', [AuthenticatedSessionController::class, 'register']);

    // User Login
    Route::post('/login', [AuthenticatedSessionController::class, 'login']);

    // User Logout
    Route::post('/logout', [AuthenticatedSessionController::class, 'logout'])->middleware('auth:sanctum');

    // Password Reset
    Route::post('/password/reset', [AuthenticatedSessionController::class, 'resetPassword']);
});

// New route for getting menu data
Route::middleware('auth:sanctum')->get('/menu', [MenuController::class, 'getMenu']);

/*
|--------------------------------------------------------------------------
| Legacy Routes (Kept for backward compatibility)
|--------------------------------------------------------------------------
*/

// Route::middleware('auth:sanctum')->prefix('v2')->group(function () {
//     // Original v2 routes
// });

// Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
//     // Original v1 routes
// });
