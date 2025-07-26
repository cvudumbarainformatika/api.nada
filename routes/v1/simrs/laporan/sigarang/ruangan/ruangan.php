<?php

use App\Http\Controllers\Api\Simrs\Laporan\Sigarang\LaporanRuanganController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/laporan/sigarang'
], function () {
    Route::get('ruangan/barang', [LaporanRuanganController::class, 'getBarang']);
    Route::get('pengeluaran-depo', [LaporanRuanganController::class, 'lapPengeluaranDepo']);
    Route::get('pengeluaran-depo-new', [LaporanRuanganController::class, 'lapPengeluaranDepoNew']);
    Route::get('rekap-pengeluaran-depo', [LaporanRuanganController::class, 'rekapPengeluaranDepo']);
    Route::get('pemakaian-ruangan', [LaporanRuanganController::class, 'lapPemakaianRuangan']);
});
