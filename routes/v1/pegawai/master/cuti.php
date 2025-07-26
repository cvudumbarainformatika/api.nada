<?php

use App\Http\Controllers\Api\Pegawai\Master\CutiController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'pegawai/master'
], function () {
    Route::get('/index', [CutiController::class, 'index']);
    Route::get('/jenis-pegawai', [CutiController::class, 'jenisPegawai']);
    Route::get('/pegawai', [CutiController::class, 'pegawai']);
    Route::post('/store', [CutiController::class, 'store']);
    Route::post('/destroy', [CutiController::class, 'destroy']);
});
