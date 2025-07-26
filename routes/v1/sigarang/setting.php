<?php

use App\Http\Controllers\Api\Logistik\Sigarang\SettingController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'setting'
], function () {
    Route::get('/index', [SettingController::class, 'index']);
    Route::post('/store', [SettingController::class, 'store']);
    Route::post('/appmenu', [SettingController::class, 'appmenu']);
});
