<?php

use App\Http\Controllers\Api\Simrs\Bpjs\CekkingBpjsController;
use App\Http\Controllers\Api\Simrs\Bpjs\HttpResponController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/bpjs/bridging'
], function () {
    Route::get('/ceksep', [CekkingBpjsController::class, 'ceksep']);
});
