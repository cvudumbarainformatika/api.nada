<?php

use App\Http\Controllers\Api\Logistik\Sigarang\SatuanController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'satuan'
], function () {
    Route::get('/index', [SatuanController::class, 'index']);
    Route::get('/satuan', [SatuanController::class, 'satuan']);
    Route::get('/count', [SatuanController::class, 'satuanCount']);
    Route::post('/store', [SatuanController::class, 'store']);
    Route::post('/destroy', [SatuanController::class, 'destroy']);
});
