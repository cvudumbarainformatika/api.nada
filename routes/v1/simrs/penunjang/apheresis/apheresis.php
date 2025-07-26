<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Apheresis\ApheresisController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/penunjang/apheresis'
], function () {
    Route::get('/getmaster', [ApheresisController::class, 'getmaster']);
    Route::get('/getnota', [ApheresisController::class, 'getnota']);
    Route::post('/simpanpermintaan', [ApheresisController::class, 'simpandata']);
    Route::post('/hapuspermintaan', [ApheresisController::class, 'hapusdata']);
});
