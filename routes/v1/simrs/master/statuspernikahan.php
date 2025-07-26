<?php

use App\Http\Controllers\Api\Simrs\Master\StatusPernikahanController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/master'
], function () {
    Route::get('/statuspernikahan',[StatusPernikahanController::class, 'index']);
    Route::post('/statuspernikahansimpan',[StatusPernikahanController::class, 'store']);
});
