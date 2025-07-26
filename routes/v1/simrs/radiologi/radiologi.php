<?php

use App\Http\Controllers\Api\Simrs\Radiologi\RadiologiController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/radiologi/radiologi'
], function () {
    Route::get('/pasienradiologi', [RadiologiController::class, 'index']); // ini yang baru
    Route::get('/getDataPasienRadiologiByNota', [RadiologiController::class, 'getDataPasienRadiologiByNota']); // ini yang baru
});