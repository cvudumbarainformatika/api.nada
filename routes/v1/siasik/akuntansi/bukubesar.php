<?php

use App\Http\Controllers\Api\Siasik\Akuntansi\BukuBesar\BukubesarController;
use App\Http\Controllers\Api\Siasik\Akuntansi\JurnalUmum\JurnalManualController;
use App\Http\Controllers\Api\Siasik\Akuntansi\JurnalumumController;
use Illuminate\Support\Facades\Route;

Route::group([
    // 'middleware' => 'auth:api',
    'prefix' => 'akuntansi/bukubesar'
], function () {
    Route::get('/getbukubesar', [BukubesarController::class, 'getBukubesar']);
    Route::get('/akun', [BukubesarController::class, 'akunkepmend']);
    Route::get('/getpa', [BukubesarController::class, 'getTtd']);

});
