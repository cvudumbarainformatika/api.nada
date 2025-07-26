<?php

use App\Http\Controllers\Api\Simrs\Master\Pemeriksaanfisik\MasterPemeriksaanFisikController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/master/pemeriksaanfisik'
], function () {
    Route::post('/simpanmasterpemeriksaan', [MasterPemeriksaanFisikController::class, 'simpanmasterpemeriksaan']);
    Route::get('/data', [MasterPemeriksaanFisikController::class, 'index']);
    Route::post('/uploads', [MasterPemeriksaanFisikController::class, 'uploads']);
    Route::post('/deletetemplate', [MasterPemeriksaanFisikController::class, 'deletetemplate']);
});
