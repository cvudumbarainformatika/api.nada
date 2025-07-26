<?php

use App\Http\Controllers\Api\Logistik\Sigarang\GudangController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'gudang'
], function () {
    Route::get('/index', [GudangController::class, 'index']);
    Route::get('/gudang-habis-pakai', [GudangController::class, 'gudangHabisPakai']);
    Route::get('/gudang', [GudangController::class, 'gudang']);
    Route::get('/depo', [GudangController::class, 'depo']);
    Route::post('/store', [GudangController::class, 'store']);
    Route::post('/destroy', [GudangController::class, 'destroy']);
});
