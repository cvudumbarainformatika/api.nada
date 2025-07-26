<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Lain\LainController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/penunjang/lain'
], function () {
    Route::get('/penunjanglain', [LainController::class, 'penunjanglain']);
    Route::get('/getnota', [LainController::class, 'getnota']);
    Route::post('/simpanpenunjanglain', [LainController::class, 'simpanpenunjanglain']);
    Route::post('/hapuspermintaan', [LainController::class, 'hapuspermintaan']);
});
