<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Kamaroperasi\KamaroperasiController;
use App\Http\Controllers\Api\Simrs\Penunjang\Operasi\OperasiIrdController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/penunjang/operasiird'
], function () {
    Route::get('/getnota', [OperasiIrdController::class, 'getnota']);
    Route::get('/getdata', [OperasiIrdController::class, 'getdata']);
    Route::post('/permintaanoperasi', [OperasiIrdController::class, 'simpandata']);
    Route::post('/hapuspermintaan', [OperasiIrdController::class, 'hapusdata']);
});
