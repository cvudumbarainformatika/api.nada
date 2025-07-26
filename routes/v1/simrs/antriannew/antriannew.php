<?php

use App\Http\Controllers\Api\Simrs\Antriannew\AmbilantrianController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/antriannew/antriannew'
], function () {
    Route::post('/ambilantrianadmisi', [AmbilantrianController::class, 'ambilantrianadmisi']);
});
