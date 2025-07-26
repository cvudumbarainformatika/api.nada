<?php

use App\Http\Controllers\Api\Siasik\Akuntansi\Laporan\LpeController;
use App\Http\Controllers\Api\Siasik\Akuntansi\Laporan\LpsalController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'laporan/lpe'
], function () {
    Route::get('/getlpe', [LpeController::class, 'get_lpe']);
    // Route::post('/saved', [LpeController::class, 'saved']);

});
