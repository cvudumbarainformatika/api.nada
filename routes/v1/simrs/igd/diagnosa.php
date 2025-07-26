<?php

use App\Http\Controllers\Api\Simrs\Igd\DiagnosaController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/igd/diagnosa'
],function () {
    Route::post('/hapusdiagnosa', [DiagnosaController::class, 'hapusdiagnosa']);
    Route::post('/simpandiagnosa', [DiagnosaController::class, 'simpandiagnosa']);
    Route::get('/listdiagnosa', [DiagnosaController::class, 'listdiagnosa']);
});

