<?php

use App\Http\Controllers\Api\Mjkn\AuthenticateController;
use Illuminate\Support\Facades\Route;



Route::get('/get-token', [AuthenticateController::class, 'getToken']);

// Route::group([
//     // 'middleware' => 'auth:api',
//     'middleware' => 'jwt.verify',
//     'prefix' => 'user'
// ], function () {
//     Route::post('/reset-device', [AuthController::class, 'resetDevice']);
//     Route::get('/me', [AuthController::class, 'me']);
//     Route::post('/new-password', [AuthController::class, 'newPassword']);
// });
