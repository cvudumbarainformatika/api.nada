<?php

use App\Http\Controllers\Api\Mobile\Auth\SendqrController;
use App\Http\Controllers\Api\v1\AuthController;
use Illuminate\Support\Facades\Route;



Route::post('/login', [AuthController::class, 'login']);
Route::post('/login-qr', [SendqrController::class, 'loginQr']); //dari hp kirim ke login laravel
Route::get('/test', [AuthController::class, 'test']);
Route::post('/store', [AuthController::class, 'new_reg']);

Route::middleware('auth:api')
    ->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::get('/authuser', [AuthController::class, 'authuser']);
        Route::get('/user', [AuthController::class, 'user']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });


