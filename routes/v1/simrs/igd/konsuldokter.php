<?php

use App\Http\Controllers\Api\Simrs\Igd\KonsulDokterController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/konsuldokter/igd'
],function () {
    Route::post('/simpandata', [KonsulDokterController::class, 'simpandata']);
    Route::post('/hapusdata', [KonsulDokterController::class, 'hapusdata']);
});

