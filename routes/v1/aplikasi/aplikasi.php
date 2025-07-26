<?php

use App\Http\Controllers\Api\Aplikasi\AplikasiController;
use Illuminate\Support\Facades\Route;



Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'aplikasi'
], function () {
    Route::get('/data', [AplikasiController::class, 'index']);
});
