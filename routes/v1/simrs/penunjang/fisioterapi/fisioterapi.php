<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Fisioterapi\FisioterapiController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/penunjang/fisioterapi'
], function () {
    Route::get('/getnota', [FisioterapiController::class, 'getnota']);
    Route::get('/getdata', [FisioterapiController::class, 'getdata']);
    Route::post('/permintaanfisioterapipoli', [FisioterapiController::class, 'permintaanfisioterapipoli']);
    Route::post('/hapuspermintaan', [FisioterapiController::class, 'hapuspermintaan']);
});
