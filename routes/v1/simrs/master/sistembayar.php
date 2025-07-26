<?php

use App\Http\Controllers\Api\Simrs\Master\SistemBayarController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/master'
], function () {
    Route::get('/sistembayar',[SistemBayarController::class, 'index']);
    Route::get('/sistembayar2',[SistemBayarController::class, 'sistembayar2']);
    Route::get('/allsistembayar',[SistemBayarController::class, 'allsistembayar']);
});
