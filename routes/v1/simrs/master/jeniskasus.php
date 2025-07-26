<?php

use App\Http\Controllers\Api\Simrs\Master\JenisKasusController;
use App\Http\Controllers\Api\Simrs\Master\KamarController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/master'
], function () {
    Route::get('/jeniskasus', [JenisKasusController::class, 'jeniskasus']);
});
