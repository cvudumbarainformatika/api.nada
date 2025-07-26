<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\PenjualanBebas\PenjualanBebasController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/farmasinew/penjualanbebas'
], function () {
    Route::get('/pihak-tiga', [PenjualanBebasController::class, 'getPihakTiga']);
    Route::get('/karyawan', [PenjualanBebasController::class, 'getKaryawan']);
    Route::get('/pesien', [PenjualanBebasController::class, 'getPasien']);
    Route::get('/daftar-kunj', [PenjualanBebasController::class, 'getDaftarKunjungan']);
    Route::get('/cari-obat', [PenjualanBebasController::class, 'pencarianObat']);
    Route::get('/list-kunjungan', [PenjualanBebasController::class, 'listKunjungan']);
    Route::post('/simpan', [PenjualanBebasController::class, 'simpan']);
    Route::post('/hapus', [PenjualanBebasController::class, 'hapus']);
});
