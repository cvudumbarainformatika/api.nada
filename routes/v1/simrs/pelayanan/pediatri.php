<?php

use App\Http\Controllers\Api\Simrs\Pelayanan\Pediatri\PediatriController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/pelayanan/pediatri'
], function () {
    Route::get('/get-pediatri-by-norm', [PediatriController::class, 'index']);
    Route::post('/store', [PediatriController::class, 'store']);
    Route::post('/deletedata', [PediatriController::class, 'deletedata']);
    Route::get('/master-who-cdc', [PediatriController::class, 'master_who_cdc']);
});
