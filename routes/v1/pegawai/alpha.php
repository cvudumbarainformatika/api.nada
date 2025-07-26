<?php

use App\Http\Controllers\Api\Pegawai\User\LiburController;
use Illuminate\Support\Facades\Route;

Route::group(
    [
        // 'middleware' => 'auth:api',
        // 'middleware' => 'jwt.verify',
        'prefix' => 'absen'
    ],
    function () {
        Route::get('/alpha', [LiburController::class, 'tulisTidakMasuk']);
        // Route::post('/ramadhan', [LiburController::class, 'ramadhan']);
    }
);
