<?php

use App\Http\Controllers\Api\Logistik\Sigarang\PegawaiController;
use App\Http\Controllers\Api\Pegawai\Master\CutiController;
use Illuminate\Support\Facades\Route;


Route::group([
    // 'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'data'
], function () {
    Route::get('/pegawai', [PegawaiController::class, 'cari']);
    Route::post('/cari-pegawai', [PegawaiController::class, 'cariPegawai']);
});
