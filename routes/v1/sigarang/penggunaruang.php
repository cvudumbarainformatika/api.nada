<?php

use App\Http\Controllers\Api\Logistik\Sigarang\PenggunaRuangController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'penggunaruang'
], function () {
    Route::get('/index', [PenggunaRuangController::class, 'index']);
    Route::get('/pengguna-ruang', [PenggunaRuangController::class, 'penggunaRuang']);
    Route::post('/store', [PenggunaRuangController::class, 'store']);
    Route::post('/destroy', [PenggunaRuangController::class, 'destroy']);
});
