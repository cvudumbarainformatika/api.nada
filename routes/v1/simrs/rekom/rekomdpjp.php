<?php

use App\Http\Controllers\Api\Simrs\Rekom\RekomdpjpController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/rekomdpjp'
], function () {
    Route::get('rekomdpjp', [RekomdpjpController::class, 'rekomdpjpcon']);
});
