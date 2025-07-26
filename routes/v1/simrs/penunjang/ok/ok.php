<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Kamaroperasi\KamaroperasiController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/penunjang/ok'
], function () {
    Route::get('/getnota', [KamaroperasiController::class, 'getnota']);
    Route::post('/permintaanoperasi', [KamaroperasiController::class, 'permintaanoperasi']);
    Route::post('/hapuspermintaanok', [KamaroperasiController::class, 'hapuspermintaanok']);

    Route::get('/listkamaroperasi', [KamaroperasiController::class, 'listkamaroperasi']);
});
