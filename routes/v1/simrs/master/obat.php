<?php

use App\Http\Controllers\Api\Simrs\Master\MobatController;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\ObatnewController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/master'
], function () {
    Route::get('/masterObat', [MobatController::class, 'index']);
    // Route::get('/cariObat', [MobatController::class, 'cariobat']);
    Route::get('/cariObat', [ObatnewController::class, 'cariobat']);
    Route::get('/cari-obat-harga', [ObatnewController::class, 'cariObatHarga']);
});
