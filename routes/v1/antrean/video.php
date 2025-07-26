<?php

use App\Http\Controllers\Api\Antrean\master\VideoController;
use Illuminate\Support\Facades\Route;



Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'video'
], function () {
    Route::get('/data', [VideoController::class, 'index']);
    // Route::get('/synch', [PoliController::class, 'synch']);
    Route::post('/store', [VideoController::class, 'store']);
    Route::post('/destroy', [VideoController::class, 'destroy']);
});

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'display/video'
], function () {
    Route::get('/display', [VideoController::class, 'display']);
});
