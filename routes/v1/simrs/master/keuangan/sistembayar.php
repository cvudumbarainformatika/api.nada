<?php

use App\Http\Controllers\Api\Simrs\Master\Keuangan\SistemBayarController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/master/keuangan/sistembayar'
], function () {
    Route::get('/listsistembayar', [SistemBayarController::class, 'listsistembayar']);
    Route::post('/simpansistembayar', [SistemBayarController::class, 'simpan']);
    Route::post('/delete', [SistemBayarController::class, 'delete']);
});
