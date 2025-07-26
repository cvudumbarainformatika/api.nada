<?php

use App\Http\Controllers\Api\Simrs\Igd\AnamnesisController;
use App\Http\Controllers\Api\Simrs\Igd\AnamnesisKebidananController;
use App\Http\Controllers\Api\Simrs\Igd\RencanaTerapiDokterController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/igd/assesment/'
],function () {
    Route::post('/simpanrencanaterapidokter', [RencanaTerapiDokterController::class, 'simpan']);
    Route::post('/hapusrencanaterapidokter', [RencanaTerapiDokterController::class, 'hapus']);
});

