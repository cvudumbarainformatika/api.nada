<?php

use App\Http\Controllers\Api\Simrs\Laporan\Sigarang\LaporanMutasiGudangController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/laporan/sigarang'
], function () {
    Route::get('/lap-mutasi', [LaporanMutasiGudangController::class, 'lapMutasi']);
    Route::get('/lap-mutasi-depo', [LaporanMutasiGudangController::class, 'lapMutasiDepo']);
    Route::get('/lap-masuk-depo', [LaporanMutasiGudangController::class, 'lapRekapMasukDepo']);
});
