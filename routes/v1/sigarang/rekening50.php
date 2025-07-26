<?php

use App\Http\Controllers\Api\Logistik\Sigarang\Rekening50Controller;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'rekening50'
], function () {
    Route::get('/index', [Rekening50Controller::class, 'index']);
    Route::get('/semua', [Rekening50Controller::class, 'semua']);
    Route::post('/store', [Rekening50Controller::class, 'store']);
    Route::post('/store-by-kode', [Rekening50Controller::class, 'storeByKode']);
    Route::post('/destroy', [Rekening50Controller::class, 'destroy']);
});
