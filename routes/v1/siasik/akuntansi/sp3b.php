<?php

use App\Http\Controllers\Api\Siasik\Akuntansi\Laporan\LpsalController;
use App\Http\Controllers\Api\Siasik\Akuntansi\SaldoawalController;
use App\Http\Controllers\Api\Siasik\Akuntansi\Sp3b\Sp3bController;
use Illuminate\Support\Facades\Route;

Route::group([
    // 'middleware' => 'auth:api',
    'prefix' => 'akuntansi/sp3b'
], function () {
    Route::get('/getdata', [Sp3bController::class, 'getdata']);
    Route::get('/listdata', [Sp3bController::class, 'listdata']);
    Route::post('/savedata', [Sp3bController::class, 'savedata']);
    Route::post('/delete', [Sp3bController::class, 'delete']);
    // Route::post('/delete', [SaldoawalController::class, 'destroy']);

});
