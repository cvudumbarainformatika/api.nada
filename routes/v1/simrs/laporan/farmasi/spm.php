<?php

use App\Http\Controllers\Api\Simrs\Laporan\Farmasi\Spm\LaporanGenerikController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/laporan/farmasi/spm'
], function () {
    Route::get('/get-option-kelompok', [LaporanGenerikController::class, 'getOptionKelompok']);
    Route::get('/get-option-sistem-bayar', [LaporanGenerikController::class, 'getOptionSistemBayar']);
    Route::get('/laporan-generik', [LaporanGenerikController::class, 'getLaporanGenerik']);
    Route::get('/laporan-response-time', [LaporanGenerikController::class, 'getLaporanResponseTime']);
    Route::get('/laporan-kesesuaian-obat', [LaporanGenerikController::class, 'getLaporanKesesuaianObat']);
});
