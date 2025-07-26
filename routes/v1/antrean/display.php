<?php

use App\Http\Controllers\Api\Antrean\master\DisplayController;
use Illuminate\Support\Facades\Route;



Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'display'
], function () {
    Route::get('/data', [DisplayController::class, 'index']);
    // Route::get('/synch', [PoliController::class, 'synch']);
    Route::post('/store', [DisplayController::class, 'store']);
    Route::post('/destroy', [DisplayController::class, 'destroy']);
});
