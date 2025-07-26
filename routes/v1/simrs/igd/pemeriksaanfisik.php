<?php

use App\Http\Controllers\Api\Simrs\Igd\PemeriksaanFisikController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/igd/pemeriksaanfisik'
],function () {

    Route::post('/simpanpemeriksaanfisik', [PemeriksaanFisikController::class, 'simpanpemeriksaanfisikigd']);
    Route::post('/hapuspemeriksaanfisik', [PemeriksaanFisikController::class, 'hapuspemeriksaanfisik']);
});

