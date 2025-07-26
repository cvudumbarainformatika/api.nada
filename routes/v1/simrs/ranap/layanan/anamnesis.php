<?php

use App\Http\Controllers\Api\Simrs\Ranap\Pelayanan\AnamnesisController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/ranap/layanan/anamnesis'
], function () {
    Route::post('/simpananamnesis', [AnamnesisController::class, 'simpananamnesis']);
    Route::get('/list', [AnamnesisController::class, 'list']);
});
