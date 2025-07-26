<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Jenazah\PermintaanPerawatanJenazahController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/penunjang/perawatanjenazah'
], function () {
    Route::get('/getnota', [PermintaanPerawatanJenazahController::class, 'getnota']);
    // Route::get('/getdata', [FisioterapiController::class, 'getdata']);
    Route::post('/simpanpermintaan', [PermintaanPerawatanJenazahController::class, 'permintaan']);
    Route::post('/hapuspermintaan', [PermintaanPerawatanJenazahController::class, 'hapuspermintaan']);
});
