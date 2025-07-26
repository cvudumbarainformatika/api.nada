<?php

use App\Http\Controllers\Api\Antrean\master\UnitController;
use Illuminate\Support\Facades\Route;



Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'masterunit'
], function () {
    Route::get('/data', [UnitController::class, 'index']);
    Route::get('/layanans', [UnitController::class, 'getLayanans']);
    Route::get('/displays', [UnitController::class, 'getDisplays']);
    // Route::get('/synch', [PoliController::class, 'synch']);
    Route::post('/store', [UnitController::class, 'store']);
    Route::post('/destroy', [UnitController::class, 'destroy']);
});
