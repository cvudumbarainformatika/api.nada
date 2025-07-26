<?php

use App\Http\Controllers\Api\Simrs\Master\PasienController;
use App\Http\Controllers\Api\Simrs\Master\PegawaiController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth.log:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/master/pegawai'
], function () {
    Route::get('/listnakes', [PegawaiController::class, 'listnakes']);
    Route::get('/listnonnakes', [PegawaiController::class, 'listNonNakes']);
    Route::get('/listall', [PegawaiController::class, 'listAll']);
});
