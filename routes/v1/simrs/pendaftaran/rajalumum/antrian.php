<?php

use App\Http\Controllers\Api\Simrs\Antrian\AntrianController;
use App\Http\Controllers\Api\Simrs\Pendaftaran\Rajal\BridantrianbpjsController;
use App\Http\Controllers\Api\Simrs\Pendaftaran\Rajal\Bridbpjscontroller;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/pendaftaran/antrian'
], function () {
    //bridging mesin antrian
    Route::get('/call_layanan_ruang', [AntrianController::class, 'call_layanan_ruang']);

    //bridging bpjs
    Route::get('/ambilantrean', [BridantrianbpjsController::class, 'addantriantobpjs']);
    Route::get('/batalantrian', [BridantrianbpjsController::class, 'batalantrian']);


    //Route::post('/wewxx', [BridantrianbpjsController::class, 'wewxx']);

});
