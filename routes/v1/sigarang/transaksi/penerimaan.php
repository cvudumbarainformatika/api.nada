<?php

use App\Http\Controllers\Api\Logistik\Sigarang\Transaksi\PenerimaanController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'transaksi/penerimaan'
], function () {
    // Route::get('/index', [SupplierController::class, 'index']);
    Route::get('/cari-pemesanan', [PenerimaanController::class, 'cariPemesanan']);
    Route::get('/cari-detail-pesanan', [PenerimaanController::class, 'cariDetailPesanan']);
    Route::get('/cari-detail-penerimaan', [PenerimaanController::class, 'cariDetailPenerimaan']);
    Route::get('/jumlah-penerimaan', [PenerimaanController::class, 'jumlahPenerimaan']);
    Route::get('/penerimaan', [PenerimaanController::class, 'penerimaan']);
    Route::get('/surat-belum-lengkap', [PenerimaanController::class, 'suratBelumLengkap']);
    Route::post('/simpan-penerimaan', [PenerimaanController::class, 'simpanPenerimaan']);
    Route::post('/edit-header-penerimaan', [PenerimaanController::class, 'editHeaderPenerimaan']);
    Route::post('/lengkapi-surat', [PenerimaanController::class, 'lengkapiSurat']);
    Route::post('/destroy', [PenerimaanController::class, 'destroy']);

    // edit detail penerimaan
    Route::post('/edit-detail-penerimaan', [PenerimaanController::class, 'editDetailPenerimaan']);
    Route::post('/hapus-detail-penerimaan', [PenerimaanController::class, 'hapusDetailPenerimaan']);
});
