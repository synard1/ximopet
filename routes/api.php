<?php

use App\Actions\SamplePermissionApi;
use App\Actions\SampleRoleApi;
use App\Actions\SampleUserApi;
use App\Actions\AppApi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')->group(function () {

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


    Route::get('/farms-list', function (Request $request) {
        return app(AppApi::class)->getFarm($request);
    });

    Route::get('/farms', function (Request $request) {
        return app(AppApi::class)->datatableListFarm($request);
    });

    

    Route::post('/farms-list', function (Request $request) {
        return app(AppApi::class)->datatableListFarm($request);
    });

    Route::post('/farms', function (Request $request) {
        return app(AppApi::class)->create($request);
    });

    Route::get('/farms/{id}', function ($id) {
        return app(AppApi::class)->get($id);
    });

    Route::put('/farms/{id}', function ($id, Request $request) {
        return app(AppApi::class)->update($id, $request);
    });

    Route::delete('/farms/{id}', function ($id) {
        return app(AppApi::class)->delete($id);
    });

    Route::get('/transaksi/details/{id}', function ($id) {
        return app(AppApi::class)->getTransaksiDetail($id);
    });

    Route::get('/farm/operators', function () {
        return app(AppApi::class)->getFarmOperator();
    });

    Route::delete('/farm/operators/{id}', function ($id) {
        return app(AppApi::class)->deleteFarmOperator($id);
    });

    Route::get('/get-operators/{farm}', [AppApi::class, 'getOperators']);
    Route::get('/get-farm-stocks/{farm}', [AppApi::class, 'getFarmStoks']);


});
