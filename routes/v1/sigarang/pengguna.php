<?php

use App\Http\Controllers\Api\Logistik\Sigarang\PenggunaController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'pengguna'
], function () {
    Route::get('/index', [PenggunaController::class, 'index']);
    Route::get('/pengguna', [PenggunaController::class, 'cariPengguna']);
    Route::get('/pengguna-ruang', [PenggunaController::class, 'pengguna']);
    Route::get('/penanggungjawab', [PenggunaController::class, 'penanggungjawab']);
    Route::post('/store', [PenggunaController::class, 'store']);
    Route::post('/destroy', [PenggunaController::class, 'destroy']);
});
