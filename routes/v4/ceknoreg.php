<?php

use App\Http\Controllers\Api\v4\CeknoregController;
use Illuminate\Support\Facades\Route;



Route::group([
    // 'middleware' => 'auth:api',
    // 'middleware' => 'jkn.auth',
    'prefix' => 'ceknoreg'
], function () {
    Route::post('/rajal', [CeknoregController::class, 'cek']);
});
