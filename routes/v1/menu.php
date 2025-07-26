<?php

use App\Http\Controllers\Api\settings\MenuController;
use Illuminate\Support\Facades\Route;


Route::middleware('auth:api')
    ->group(function () {
        Route::get('/menus', [MenuController::class, 'index']);
        Route::post('/store_menu', [MenuController::class, 'store']);
        Route::post('/store_submenu', [MenuController::class, 'store_submenu']);
        Route::post('/delete_menu', [MenuController::class, 'delete']);
        Route::post('/delete_submenu', [MenuController::class, 'delete_submenu']);
    });
