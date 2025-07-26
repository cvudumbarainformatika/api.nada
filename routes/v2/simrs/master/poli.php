<?php

use App\Http\Controllers\Api\settings\AksesUserController;
use Illuminate\Support\Facades\Route;


Route::group([
    // 'middleware' => 'auth:api',
    'middleware' => 'jwt.verify',
    'prefix' => 'simrs/master/poli'
], function () {
    Route::get('/getPoli', [AksesUserController::class, 'getPoli']);
});
