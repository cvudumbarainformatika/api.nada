<?php

use App\Http\Controllers\Api\Simrs\Penjaminan\Klaim;
use Illuminate\Support\Facades\Route;



Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/penjaminan/klaim'
], function () {
    Route::get('/getdataklaim', [Klaim::class, 'getdataklaim']);
    Route::post('/terimapasien', [Klaim::class, 'terimapasien']);
});
