<?php

use App\Http\Controllers\Api\Logistik\Sigarang\Transaksi\PembayaranController;
use Illuminate\Support\Facades\Route;

Route::group(
    [
        'middleware' => 'auth:api',
        'prefix' => 'transaksi/pembayaran'
    ],
    function () {
        Route::get('/cari-kontrak', [PembayaranController::class, 'cariKontrak']);
        Route::get('/cari-penyedia', [PembayaranController::class, 'cariPenyedia']);
        Route::get('/cari-bast', [PembayaranController::class, 'cariBast']);
        Route::get('/ambil-penerimaan-by-bast', [PembayaranController::class, 'ambilPenerimaanByBast']);
        Route::get('/ambil-kontrak', [PembayaranController::class, 'ambilKontrak']);
        Route::get('/ambil-penerimaan', [PembayaranController::class, 'ambilPenerimaan']);
        Route::get('/ambil-no-bayar', [PembayaranController::class, 'ambilNoBayar']);
        Route::post('/simpan-bayar', [PembayaranController::class, 'simpanBayar']);
        Route::get('/list-bayar', [PembayaranController::class, 'listBayar']);
        // Route::get('/list-bayar', [PembayaranController::class, 'listBayarByKwitansi']);
    }
);
