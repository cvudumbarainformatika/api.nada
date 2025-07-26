<?php

use App\Http\Controllers\Api\Anjungan\AnjunganController;
use Illuminate\Support\Facades\Route;


Route::group([
    // 'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify' ,
    'prefix' => 'anjungan'
], function () {
    Route::get('/cari-rujukan', [AnjunganController::class, 'cari_rujukan']);
    Route::get('/cari-rujukan-rs', [AnjunganController::class, 'cari_rujukan_rs']);
    Route::get('/cari-rencana-kontrol', [AnjunganController::class, 'cariRencanaKontrol']);
    Route::get('/cek-jumlah-sep', [AnjunganController::class, 'cek_jumlah_sep']);
    Route::get('/cari-noka', [AnjunganController::class, 'cari_noka']);
    Route::get('/cari-dokter', [AnjunganController::class, 'cari_dokter']);

    Route::get('/norm', [AnjunganController::class, 'cari_norm']);
});
