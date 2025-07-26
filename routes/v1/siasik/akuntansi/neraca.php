<?php

use App\Http\Controllers\Api\Siasik\Akuntansi\BukuBesar\BukubesarController;
use App\Http\Controllers\Api\Siasik\Akuntansi\Laporan\NeracaController;
use Illuminate\Support\Facades\Route;

Route::group([
    // 'middleware' => 'auth:api',
    'prefix' => 'akuntansi/neraca'
], function () {
    Route::get('/getneraca', [NeracaController::class, 'getNeraca']);

});
