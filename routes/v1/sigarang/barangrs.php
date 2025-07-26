<?php

use App\Http\Controllers\Api\Logistik\Sigarang\BarangRSController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'barangrs'
], function () {
    Route::get('/index', [BarangRSController::class, 'index']);
    Route::get('/count-index', [BarangRSController::class, 'countIndex']);
    Route::get('/index-pemesanan', [BarangRSController::class, 'indexForPemesanan']);
    Route::get('/barangrs', [BarangRSController::class, 'barangrs']);
    Route::post('/store', [BarangRSController::class, 'store']);
    Route::post('/store-by-kode', [BarangRSController::class, 'storeByKode']);
    Route::post('/destroy', [BarangRSController::class, 'destroy']);
});
