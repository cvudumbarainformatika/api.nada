<?php

use App\Http\Controllers\Api\v1\LisController;
use Illuminate\Support\Facades\Route;


// Route::get('/test', [AuthController::class, 'test']);

Route::middleware('auth:api')
->group(function () {
    Route::post('/get_token', [LisController::class, 'get_token']);
    Route::post('/list_post', [LisController::class, 'order_lis']);
});

Route::middleware('lis.verify')
->group(function () {
    Route::post('/post_from_lis', [LisController::class, 'store']);
});

// Route::post('/post_from_lis', [LisController::class, 'store']);


