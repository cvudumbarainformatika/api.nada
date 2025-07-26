<?php

use App\Http\Controllers\Api\Pegawai\Absensi\ProtaController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'pegawai/absensi/prota'
], function () {
    Route::get('/index', [ProtaController::class, 'index']);
    Route::get('/all', [ProtaController::class, 'all']);
    Route::get('/tahun', [ProtaController::class, 'tahunProta']);
    Route::post('/store', [ProtaController::class, 'store']);
    Route::post('/destroy', [ProtaController::class, 'destroy']);
});
