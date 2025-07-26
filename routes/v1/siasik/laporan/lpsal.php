<?php

use App\Http\Controllers\Api\Siasik\Akuntansi\Laporan\LpsalController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'laporan/lpsal'
], function () {
    Route::get('/index', [LpsalController::class, 'index']);
    Route::post('/saved', [LpsalController::class, 'saved']);

});
