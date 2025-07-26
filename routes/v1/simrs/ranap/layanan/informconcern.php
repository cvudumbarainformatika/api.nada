<?php

use App\Http\Controllers\Api\Simrs\InformConcern\InformConcernController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/ranap/layanan/informconcern'
], function () {

  // Route::get('/getmaster', [HaisController::class, 'getmaster']);
    Route::post('/simpandata', [InformConcernController::class, 'simpandata']);
    Route::post('/hapusdata', [InformConcernController::class, 'hapusdata']);



    // Route::get('/notifNotRead', [KonsultasiController::class, 'notifRkd']);
    // Route::get('/getdatarkd', [KonsultasiController::class, 'getdatarkd']);
    // Route::post('/updateFlag', [KonsultasiController::class, 'updateFlag']);
    // Route::post('/updateJawaban', [KonsultasiController::class, 'updateJawaban']);


});
