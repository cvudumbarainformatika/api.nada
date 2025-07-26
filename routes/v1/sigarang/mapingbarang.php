<?php

use App\Http\Controllers\Api\Logistik\Sigarang\MappingBarangController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'mapingbarang'
], function () {
    Route::get('/index', [MappingBarangController::class, 'index']);
    Route::get('/maping', [MappingBarangController::class, 'maping']);
    Route::get('/mapingwith', [MappingBarangController::class, 'mapingwith']);
    Route::post('/store', [MappingBarangController::class, 'store']);
    Route::post('/destroy', [MappingBarangController::class, 'destroy']);
});
