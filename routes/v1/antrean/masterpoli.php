<?php

use App\Http\Controllers\Api\Antrean\master\PoliController;
use Illuminate\Support\Facades\Route;



Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'masterpoli'
], function () {
    Route::get('/data', [PoliController::class, 'index']);
    Route::get('/synch', [PoliController::class, 'synch']);
    Route::post('/store', [PoliController::class, 'store']);
    Route::post('/destroy', [PoliController::class, 'destroy']);
});
