<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Pemesanan\DialogrencanapemesananController;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Pemesanan\PemesananController;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\PihakketigaController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/farmasinew/pemesananobat'
], function () {
    Route::get('/dialogrencanabeli', [DialogrencanapemesananController::class, 'dialogrencanabeli']);
    Route::get('/dialogrencanabeli_rinci', [DialogrencanapemesananController::class, 'dialogrencanabeli_rinci']);
    Route::post('/simpanpemesanan', [PemesananController::class, 'simpan']);
    Route::get('/listpemesanan', [PemesananController::class, 'listpemesanan']);
    Route::get('/listpemesananBynomor', [PemesananController::class, 'listpemesananBynomor']);
    Route::get('/pihakketiga', [PihakketigaController::class, 'pihakketiga']);
    Route::post('/kuncipemesanan', [PemesananController::class, 'kuncipemesanan']);
    Route::post('/batal', [PemesananController::class, 'batal']);
    Route::post('/batal-rinci', [PemesananController::class, 'batalRinci']);

    // anggap selesai rencana
    Route::post('/anggap-selesai', [PemesananController::class, 'anggapSelesai']);
    Route::post('/anggap-selesai-pesanan', [PemesananController::class, 'anggapSelesaiPemesanan']);

    
});
