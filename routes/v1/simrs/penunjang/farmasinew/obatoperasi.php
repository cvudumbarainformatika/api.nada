<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Obatoperasi\PersiapanOperasiController;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\TemplateEresep\TemplateObatOperasiController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'simrs/penunjang/farmasinew/obatoperasi'
], function () {
    Route::get('/get-permintaan', [PersiapanOperasiController::class, 'getPermintaan']);
    Route::get('/get-permintaan-for-dokter', [PersiapanOperasiController::class, 'getPermintaanForDokter']);
    Route::get('/get-obat-persiapan', [PersiapanOperasiController::class, 'getObatPersiapan']);

    Route::post('/simpan-permintaan', [PersiapanOperasiController::class, 'simpanPermintaan']);
    Route::post('/hapus-obat-permintaan', [PersiapanOperasiController::class, 'hapusObatPermintaan']);
    Route::post('/selesai-obat-permintaan', [PersiapanOperasiController::class, 'selesaiObatPermintaan']);

    Route::post('/distribusi', [PersiapanOperasiController::class, 'simpanDistribusi']);
    Route::post('/tambah-distribusi', [PersiapanOperasiController::class, 'tambahDistribusi']);
    Route::post('/terima-pengembalian', [PersiapanOperasiController::class, 'terimaPengembalian']);
    // Route::post('/terima-pengembalian', [PersiapanOperasiController::class, 'terimaPengembalianRf']);
    Route::post('/simpan-resep', [PersiapanOperasiController::class, 'simpanEresep']);
    Route::post('/selesai-resep', [PersiapanOperasiController::class, 'selesaiEresep']);
    Route::post('/batal-obat-resep', [PersiapanOperasiController::class, 'batalObatResep']);

    Route::post('/batal-operasi', [PersiapanOperasiController::class, 'batalOperasi']);

    // karena template operasi ada yang lolos dari pengecekan alokasi maka dibuatkan hapus rincian permintaan obat operasi
    Route::post('/hapus-rincian', [PersiapanOperasiController::class, 'hapusRincianPerpersiapanOperasi']);

    // template obat operasi
    Route::get('/cari-template', [TemplateObatOperasiController::class, 'cari']);
    Route::post('/simpan-template', [TemplateObatOperasiController::class, 'simpan']);
    Route::post('/hapus-rinci-template', [TemplateObatOperasiController::class, 'hapusRinci']);
    Route::post('/kirim-order', [TemplateObatOperasiController::class, 'kirimOrder']);
});
