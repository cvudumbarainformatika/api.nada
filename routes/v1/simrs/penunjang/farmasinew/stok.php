<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Stok\CekPerbaikanHargaController;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Stok\SetNewStokController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/farmasinew/stok'
], function () {
    Route::get('/new-stok', [SetNewStokController::class, 'setNewStok']);
    Route::get('/cek-harga', [SetNewStokController::class, 'cekHargaGud']);
    Route::get('/isi-harga', [SetNewStokController::class, 'insertHarga']);
    Route::get('/new-stok-opname', [SetNewStokController::class, 'setStokOpnameAwal']);
    Route::post('/perbaikan-stok', [SetNewStokController::class, 'newPerbaikanStok']);
    Route::post('/perbaikan-stok-per-depo', [SetNewStokController::class, 'PerbaikanStokPerDepo']);
    Route::post('/cek-penerimaan', [SetNewStokController::class, 'cekPenerimaan']);
    Route::post('/perbaikan-data', [SetNewStokController::class, 'perbaikanData']);
    Route::post('/perbaikan-data-depo', [SetNewStokController::class, 'PerbaikanDataPerDepo']);

    // with fornt end
    Route::post('/fr-perbaikan-data-depo', [SetNewStokController::class, 'frontPerbaikanDataPerDepo']);
    Route::post('/fr-perbaikan-data-opname', [SetNewStokController::class, 'frontPerbaikanDataOpname']);
    Route::post('/fr-perbaikan-data', [SetNewStokController::class, 'frontPerbaikanData']);
    Route::post('/fr-data-mutasi', [SetNewStokController::class, 'frontDataMutasi']);
    Route::post('/fr-data-resep', [SetNewStokController::class, 'frontDataResep']);
    Route::post('/fr-get-perbaikan-harga', [CekPerbaikanHargaController::class, 'getPerbaikanHarga']);
    Route::post('/fr-simpan-perbaikan-harga', [CekPerbaikanHargaController::class, 'simpanPerbaikanHarga']);
    Route::post('/fr-simpan-pecah-nomor', [CekPerbaikanHargaController::class, 'simpanPecahNomor']);
    Route::post('/fr-ganti-nomor', [CekPerbaikanHargaController::class, 'gantiNomor']);
});
