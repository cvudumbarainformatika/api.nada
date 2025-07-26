<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Kamaroperasi\KamaroperasiController;
use App\Http\Controllers\Api\Simrs\Penunjang\Operasi\OperasiController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/penunjang/operasi'
], function () {
    Route::get('/getnota', [OperasiController::class, 'getnota']);
    Route::get('/getdata', [OperasiController::class, 'getdata']);
    Route::post('/permintaanoperasi', [OperasiController::class, 'simpandata']);
    Route::post('/hapuspermintaan', [OperasiController::class, 'hapusdata']);
});
