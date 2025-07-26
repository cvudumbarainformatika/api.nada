<?php

use App\Http\Controllers\Api\Logistik\Sigarang\Transaksi\PemesananController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'transaksi/pemesanan'
], function () {
    Route::get('/draft', [PemesananController::class, 'draft']);
    Route::get('/ada-penerimaan', [PemesananController::class, 'adaPenerimaan']);
    Route::post('/store', [PemesananController::class, 'store']);
    Route::post('/delete-details', [PemesananController::class, 'deleteDetails']);
    Route::post('/store-details', [PemesananController::class, 'storeDetails']);
    Route::post('/ganti-status', [PemesananController::class, 'gantiStatus']);
});
