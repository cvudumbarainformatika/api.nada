<?php

use App\Http\Controllers\Api\Simrs\Hemodialisa\Pelayanan\AnamnesisHomodialisaController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/hemodialisa/layanan/anamnesis'
], function () {
    Route::post('/simpananamnesis', [AnamnesisHomodialisaController::class, 'simpananamnesis']);
    Route::get('/list', [AnamnesisHomodialisaController::class, 'list']);
});
