<?php

use App\Http\Controllers\Api\Antrean\master\DisplayController;
use Illuminate\Support\Facades\Route;



Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'fordisplay'
], function () {
    // Route::get('/display', [DisplayController::class, 'display']);
    Route::get('/display', [DisplayController::class, 'displayWithFarmasi']);
    Route::post('/send_panggilan', [DisplayController::class, 'send_panggilan']);
    Route::get('/get_weather', [DisplayController::class, 'get_weather']);
    // Route::post('/delete_panggilan', [DisplayController::class, 'delete_panggilan']);
});
