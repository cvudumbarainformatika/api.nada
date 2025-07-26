<?php

use App\Http\Controllers\Api\Simrs\Pelayanan\Psikiatri\PsikiatriController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/pelayanan/psikiatripoli'
], function () {
    Route::post('/store', [PsikiatriController::class, 'store']);
    Route::post('/deletedata', [PsikiatriController::class, 'deletedata']);
});
