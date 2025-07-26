<?php

use App\Http\Controllers\Api\Simrs\Master\MhambatanController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/master'
], function () {
    Route::get('/listmhambatan', [MhambatanController::class, 'listmhambatan']);
});
