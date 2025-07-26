<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Oksigen\OksigenController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/penunjang/oksigen'
], function () {
    Route::get('/getmaster', [OksigenController::class, 'getmaster']);
    // Route::get('/getnota', [ApheresisController::class, 'getnota']);
    Route::post('/simpanpermintaan', [OksigenController::class, 'simpandata']);
    Route::post('/hapuspermintaan', [OksigenController::class, 'hapusdata']);
});
