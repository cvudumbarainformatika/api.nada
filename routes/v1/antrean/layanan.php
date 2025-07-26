<?php

use App\Http\Controllers\Api\Antrean\master\LayananController;
use Illuminate\Support\Facades\Route;



Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'layanan'
], function () {
    Route::get('/data', [LayananController::class, 'index']);
    Route::get('/synch', [LayananController::class, 'synch']);
    Route::post('/store', [LayananController::class, 'store']);
    Route::post('/destroy', [LayananController::class, 'destroy']);
});
