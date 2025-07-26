<?php

use App\Http\Controllers\Api\Logistik\Sigarang\Transaksi\PenerimaanruanganController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'transaksi/penerimaanruangan'
], function () {
    Route::get('/index', [PenerimaanruanganController::class, 'index']);
    Route::get('/to-accept', [PenerimaanruanganController::class, 'distributedPenerimaan']);
    Route::get('/koders', [PenerimaanruanganController::class, 'getItems']);
    Route::get('/pj', [PenerimaanruanganController::class, 'getPj']);
    Route::post('/store', [PenerimaanruanganController::class, 'store']);
    Route::post('/distribusi-diterima', [PenerimaanruanganController::class, 'distribusiDiterima']);
});
