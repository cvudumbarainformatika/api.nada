<?php

use App\Http\Controllers\Api\Simrs\Master\PekerjaanController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/master'
], function () {
    Route::get('/pekerjaan', [PekerjaanController::class, 'index']);
    Route::post('/simpanPekerjaan', [PekerjaanController::class, 'store']);
    Route::post('/hapusPekerjaan', [PekerjaanController::class, 'hapus']);
});
