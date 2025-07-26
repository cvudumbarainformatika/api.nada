<?php

use App\Http\Controllers\Api\Anjungan\BookingController;
use Illuminate\Support\Facades\Route;


Route::group([
    // 'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'booking'
], function () {
    Route::post('/store', [BookingController::class, 'store']);
    Route::get('/cetak-antrean', [BookingController::class, 'cetak_antrean']);
});
