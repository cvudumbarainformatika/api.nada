<?php

use App\Http\Controllers\Api\v1\UserController;
use Illuminate\Support\Facades\Route;



Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'user'
], function () {
    Route::get('/user', [UserController::class, 'user']);
    Route::get('/all', [UserController::class, 'userAll']);
    Route::post('/status', [UserController::class, 'updateStatus']);
});
