<?php

use App\Http\Controllers\Api\Simrs\Laporan\Farmasi\Pemakaian\PemakaianObatController;
use App\Http\Controllers\Api\Simrs\Laporan\Farmasi\Persediaan\PersediaanFiFoController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/laporan/farmasi/pemakaian'
], function () {
    Route::get('/get-pemakaian', [PemakaianObatController::class, 'getPemakaianObat']);
    Route::get('/get-all-pemakaian', [PemakaianObatController::class, 'getAllPemakaianObat']);
    Route::get('/get-sistembayar', [PemakaianObatController::class, 'getSistemBayar']);
    Route::get('/get-pemakaian-program', [PemakaianObatController::class, 'getPemakaianObatProgram']);

    // url lama sebelum update fron end

    Route::get('/get-persediaan', [PersediaanFiFoController::class, 'getPersediaan']);
    Route::get('/get-mutasi', [PersediaanFiFoController::class, 'getMutasi']);
});
