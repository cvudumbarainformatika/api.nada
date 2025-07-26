<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Gudang\BarangRusakController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/penunjang/farmasinew/barangrusak/'
], function () {
    Route::get('obat', [BarangRusakController::class, 'cariObat']);
    Route::get('bacth', [BarangRusakController::class, 'cariBatch']);
    Route::get('penerimaan', [BarangRusakController::class, 'cariPenerimaan']);
    Route::get('list-belum', [BarangRusakController::class, 'getListBelumKunci']);
    Route::get('list-sudah', [BarangRusakController::class, 'getListSudahKunci']);

    Route::post('/simpan', [BarangRusakController::class, 'simpan']);
    Route::post('/hapus', [BarangRusakController::class, 'hapusData']);
    Route::post('/kunci', [BarangRusakController::class, 'kunci']);
    Route::post('/pemusnahan', [BarangRusakController::class, 'pemusnahan']);
    Route::post('/penghapusan', [BarangRusakController::class, 'penghapusan']);
    Route::post('/penerimaan', [BarangRusakController::class, 'penerimaan']);

    // kartu stok barang rusak
    Route::get('/kartu-stok', [BarangRusakController::class, 'kartuStok']);
});
