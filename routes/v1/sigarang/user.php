<?php

use App\Http\Controllers\Api\Logistik\Sigarang\UserController;
use Illuminate\Support\Facades\Route;


Route::group([
    // 'middleware' => 'auth:api',
    'middleware' => 'jwt.verify',
    'prefix' => 'user'
], function () {
    Route::get('/profile', [UserController::class, 'userProfile']);
    Route::get('/index', [UserController::class, 'index']);
    Route::post('/destroy', [UserController::class, 'destroy']);
    Route::post('/update', [UserController::class, 'update']);
});
