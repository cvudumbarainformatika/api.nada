<?php

use App\Http\Controllers\Api\Logistik\Sigarang\Transaksi\PemakaianruanganController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'transaksi/pemakaianruangan'
], function () {
    Route::get('/all-data', [PemakaianruanganController::class, 'allData']);
    Route::post('/store', [PemakaianruanganController::class, 'store']);
    Route::post('/rusak', [PemakaianruanganController::class, 'simpanRusak']);
});
