<?php

use App\Http\Controllers\Api\Logistik\Sigarang\Transaksi\DistribusiController;
use App\Http\Controllers\Api\Logistik\Sigarang\Transaksi\PermintaanruanganController;
use App\Http\Controllers\Api\Logistik\Sigarang\Transaksi\VerifPermintaanruanganController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'transaksi/permintaanruangan'
], function () {
    Route::get('/draft', [PermintaanruanganController::class, 'draft']);
    Route::post('/store', [PermintaanruanganController::class, 'store']);
    Route::post('/selesai-input', [PermintaanruanganController::class, 'selesaiInput']);
    Route::post('/hapus-detail', [PermintaanruanganController::class, 'deleteDetails']);
    // verif permintaan
    Route::get('/get-permintaan', [VerifPermintaanruanganController::class, 'getPermintaan']);
    Route::post('/update-permintaan', [VerifPermintaanruanganController::class, 'updatePermintaan']);
    Route::post('/tolak-permintaan', [VerifPermintaanruanganController::class, 'tolakPermintaan']);
    // distribusi
    Route::get('/get-permintaan-verified', [DistribusiController::class, 'getPermintaanVerified']);
    Route::post('/update-distribusi', [DistribusiController::class, 'updateDistribusi']);
});
