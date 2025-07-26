<?php

use App\Http\Controllers\Api\Logistik\Sigarang\TransactionController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'transaksi'
], function () {
    Route::get('/index', [TransactionController::class, 'index']);
    Route::get('/draft', [TransactionController::class, 'draft']);
    Route::get('/cari-pemesanan', [TransactionController::class, 'cariPemesanan']);
    Route::get('/penerimaan', [TransactionController::class, 'penerimaan']);
    Route::get('/detail', [TransactionController::class, 'withDetail']);
    Route::post('/store', [TransactionController::class, 'store']);
    Route::post('/simpan-penerimaan', [TransactionController::class, 'simpanPenerimaan']);
    Route::post('/destroy', [TransactionController::class, 'destroy']);
});
