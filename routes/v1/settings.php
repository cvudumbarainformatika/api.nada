<?php

use App\Http\Controllers\Api\settings\MenuController;
use App\Http\Controllers\Api\v1\AuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')
    ->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::get('/appmenu/aplikasi', [MenuController::class, 'aplikasi']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
