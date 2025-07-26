<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Bast\BastController;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Bast\PembebasanpajakController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/penunjang/farmasinew/bast'
], function () {
    Route::get('/perusahaan', [BastController::class, 'perusahaan']);
    Route::get('/pemesanan', [BastController::class, 'pemesanan']);
    Route::get('/penerimaan', [BastController::class, 'penerimaan']);

    Route::post('/simpan', [BastController::class, 'simpan']);

    Route::get('/list-bast', [BastController::class, 'listBast']);

    Route::post('/hapus-bast', [BastController::class, 'hapusBast']);

    Route::get('/dialogsppajak', [PembebasanpajakController::class, 'dialogsppajak']);
});
