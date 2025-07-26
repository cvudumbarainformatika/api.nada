<?php

use App\Http\Controllers\Api\Simrs\Master\NakesController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/master'
], function () {
    Route::get('/nakes/selaindokter',[NakesController::class, 'selaindokter']);
    Route::get('/nakes/dokter',[NakesController::class, 'dokter']);
});
