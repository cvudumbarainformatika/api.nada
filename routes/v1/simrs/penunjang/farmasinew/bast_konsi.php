<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Gudang\KonsinyasiController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/penunjang/farmasinew/bast-konsi'
], function () {
    Route::get('/penyedia', [KonsinyasiController::class, 'newGetPenyedia']);
    // Route::get('/penyedia', [KonsinyasiController::class, 'getPenyedia']);
    Route::get('/perusahaan', [KonsinyasiController::class, 'perusahaan']);
    Route::get('/notrans', [KonsinyasiController::class, 'notranskonsi']);
    Route::get('/transaksi', [KonsinyasiController::class, 'transkonsiwithrinci']);

    Route::post('/simpan-list', [KonsinyasiController::class, 'simpanListKonsinyasi']);
    Route::post('/simpan-bast', [KonsinyasiController::class, 'simpanBast']);

    Route::get('/list-konsi', [KonsinyasiController::class, 'listKonsinyasi']);
    Route::get('/bast-konsi', [KonsinyasiController::class, 'bastKonsinyasi']);
    Route::get('/list-belum', [KonsinyasiController::class, 'belumKonsinyasi']);
    Route::post('/hapus-dibayar', [KonsinyasiController::class, 'hapusDibayar']);
});
