<?php

use App\Http\Controllers\Api\Logistik\Sigarang\Transaksi\BastController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'transaksi/bast'
], function () {
    Route::get('/no-bast', [BastController::class, 'jumlahNomorBast']);
    Route::get('/perusahaan', [BastController::class, 'cariPerusahaan']);
    Route::get('/kontrak-pemesanan', [BastController::class, 'cariKontrak']);
    // Route::get('/nomor-pemesanan', [BastController::class, 'cariPemesanan']);
    Route::get('/pemesanan', [BastController::class, 'ambilPemesanan']);
    Route::post('/simpan-bast', [BastController::class, 'simpanBast']);
    // Route::get('/list-bast', [BastController::class, 'listBast']);
    Route::get('/list-bast', [BastController::class, 'listBastByKwitansi']);
});
