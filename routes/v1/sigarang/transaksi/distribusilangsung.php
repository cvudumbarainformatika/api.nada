<?php

use App\Http\Controllers\Api\Logistik\Sigarang\Transaksi\DistribusiLangsungController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'transaksi/distribusilangsung'
], function () {
    Route::get('/index', [DistribusiLangsungController::class, 'index']);
    Route::get('/get-stok-depo', [DistribusiLangsungController::class, 'getStokDepo']);
    Route::get('/get-ruang', [DistribusiLangsungController::class, 'getRuang']);
    Route::get('/get-barang-with-transaksi', [DistribusiLangsungController::class, 'getDataBarangWithTransaksi']);
    Route::get('/get-transaksi-with-barang', [DistribusiLangsungController::class, 'getDataTransaksiWithBarang']);
    Route::post('/basah', [DistribusiLangsungController::class, 'habiskanBahanBasah']);
    // Route::post('/store', [DistribusiLangsungController::class, 'store']);
    Route::post('/store', [DistribusiLangsungController::class, 'storeFifo']);
    Route::post('/selesai', [DistribusiLangsungController::class, 'selesai']);
});
