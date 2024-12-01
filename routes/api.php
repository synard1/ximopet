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

Route::middleware('auth:sanctum')->get('/check-auth', function (Request $request) {
    if ($request->user()) {
        return response()->json(['user' => $request->user()]);
    } else {
        return response()->json(['error' => 'Unauthenticated'], 401);
    }
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('auth:sanctum')->prefix('v2')->group(function () {
    Route::post('/data/{sources}/{operators?}', [DataController::class, 'index']);
    Route::post('/d/item/location', [DataController::class, 'index'])->name('api.v2.item_location_mapping');

    Route::post('/d/transaksi/{details?}', [DataController::class, 'transaksi']);

});

Route::middleware('auth:sanctum')->prefix('v1')->group(function () {

    Route::get('/users', function (Request $request) {
        return app(SampleUserApi::class)->datatableList($request);
    });

    Route::post('/users-list', function (Request $request) {
        return app(SampleUserApi::class)->datatableList($request);
    });

    Route::post('/users', function (Request $request) {
        return app(SampleUserApi::class)->create($request);
    });

    Route::get('/users/{id}', function ($id) {
        return app(SampleUserApi::class)->get($id);
    });

    Route::put('/users/{id}', function ($id, Request $request) {
        return app(SampleUserApi::class)->update($id, $request);
    });

    Route::delete('/users/{id}', function ($id) {
        return app(SampleUserApi::class)->delete($id);
    });


    Route::get('/roles', function (Request $request) {
        return app(SampleRoleApi::class)->datatableList($request);
    });

    Route::post('/roles-list', function (Request $request) {
        return app(SampleRoleApi::class)->datatableList($request);
    });

    Route::post('/roles', function (Request $request) {
        return app(SampleRoleApi::class)->create($request);
    });

    Route::get('/roles/{id}', function ($id) {
        return app(SampleRoleApi::class)->get($id);
    });

    Route::put('/roles/{id}', function ($id, Request $request) {
        return app(SampleRoleApi::class)->update($id, $request);
    });

    Route::delete('/roles/{id}', function ($id) {
        return app(SampleRoleApi::class)->delete($id);
    });

    Route::post('/roles/{id}/users', function (Request $request, $id) {
        $request->merge(['id' => $id]);
        return app(SampleRoleApi::class)->usersDatatableList($request);
    });

    Route::delete('/roles/{id}/users/{user_id}', function ($id, $user_id) {
        return app(SampleRoleApi::class)->deleteUser($id, $user_id);
    });



    Route::get('/permissions', function (Request $request) {
        return app(SamplePermissionApi::class)->datatableList($request);
    });

    Route::post('/permissions-list', function (Request $request) {
        return app(SamplePermissionApi::class)->datatableList($request);
    });

    Route::post('/permissions', function (Request $request) {
        return app(SamplePermissionApi::class)->create($request);
    });

    Route::get('/permissions/{id}', function ($id) {
        return app(SamplePermissionApi::class)->get($id);
    });

    Route::put('/permissions/{id}', function ($id, Request $request) {
        return app(SamplePermissionApi::class)->update($id, $request);
    });

    Route::delete('/permissions/{id}', function ($id) {
        return app(SamplePermissionApi::class)->delete($id);
    });

    // Route::apiResources('/get-operators/{farm}', [AppApi::class, 'getOperators']);



    Route::get('/farms-list', function (Request $request) {
        return app(AppApi::class)->getFarm($request);
    });

    Route::get('/farms', function (Request $request) {
        return app(AppApi::class)->datatableListFarm($request);
    });

    Route::post('/farms-list', function (Request $request) {
        return app(AppApi::class)->datatableListFarm($request);
    });

    Route::post('/data/{source}', function (Request $request) {
        return app(FarmController::class)->getDataAjax($request);
    });

    Route::post('/farms', function (Request $request) {
        return app(FarmController::class)->getDataAjax($request);
    });

    Route::post('/kandangs', function (Request $request) {
        return app(KandangController::class)->getDataAjax($request);
    });

    Route::post('/ternaks', function (Request $request) {
        return app(TernakController::class)->getDataAjax($request);
    });
    
    // Route::post('/kandangs', function (Request $request) {
    //     return app(KandangController::class)->getKandangs($request);
    // });

    Route::post('/transaksi', function (Request $request) {
        return app(TransaksiController::class)->getTransaksi($request);
    });

    Route::post('/stocks', function (Request $request) {
        return app(StockController::class)->stoks($request);
    })->name('api.v1.stoks');

    Route::get('/farms/{id}', function ($id) {
        return app(AppApi::class)->get($id);
    });

    Route::put('/farms/{id}', function ($id, Request $request) {
        return app(AppApi::class)->update($id, $request);
    });

    Route::delete('/farms/{id}', function ($id) {
        return app(AppApi::class)->delete($id);
    });

    Route::get('/transaksi_beli/details/{id}', function ($id) {
        return app(AppApi::class)->getTransaksiBeliDetail($id);
    });

    Route::get('/transaksi/details/{id}', function ($id) {
        return app(AppApi::class)->getTransaksiDetail($id);
    });

    Route::get('/farm/operators', function () {
        return app(AppApi::class)->getFarmOperator();
    });

    Route::post('/farm/operators', function (Request $request) {
        return app(AppApi::class)->farmOperator($request);
    });

    Route::delete('/farm/operators', function (Request $request) {
        return app(AppApi::class)->deleteFarmOperator($request);
    });

    Route::get('/get-operators/{farm}', [AppApi::class, 'getOperators']);
    Route::get('/get-farms', [AppApi::class, 'getFarms']);
    Route::get('/get-kandangs/{farm}/{status}', [AppApi::class, 'getKandangs']);
    Route::get('/get-farm-stocks/{farm}', [AppApi::class, 'getFarmStoks']);
    Route::post('/transaksi-ternak/details', [TransaksiTernakController::class, 'getDetails']);

    // Route::post('/stocks-edit', function (Request $request) {
    //     return app(AppApi::class)->postStockEdit($request);
    // });

    Route::post('/stoks/mutasi', function (Request $request) {
        return app(AppApi::class)->createMutasiStok($request);
    });

    // Route::post('/transaksi', function (Request $request) {
    //     return app(AppApi::class)->getTransaksi($request);
    // });

    Route::get('/resetDemo', [AppApi::class, 'resetDemo']);

    

});
