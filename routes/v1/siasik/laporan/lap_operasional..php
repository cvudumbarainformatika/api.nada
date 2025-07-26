<?php

use App\Http\Controllers\Api\Siasik\Akuntansi\Laporan\LapOperasionalController;
use Illuminate\Support\Facades\Route;


Route::group([
    // 'middleware' => 'auth:api',
    'prefix' => 'laporan/lapoperasional'
], function () {
    Route::get('/getlo', [LapOperasionalController::class, 'get_lo']);

});


