<?php


use App\Http\Controllers\Api\Simrs\Konsultasi\KonsultasiController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/ranap/layanan/konsultasi'
], function () {

  // Route::get('/getmaster', [HaisController::class, 'getmaster']);
    Route::post('/simpandata', [KonsultasiController::class, 'simpandata']);
    Route::post('/hapusdata', [KonsultasiController::class, 'hapusdata']);



    // Route::get('/notifNotRead', [KonsultasiController::class, 'notifRkd']);
    Route::get('/getdatarkd', [KonsultasiController::class, 'getdatarkd']);
    Route::post('/updateFlag', [KonsultasiController::class, 'updateFlag']);
    Route::post('/updateJawaban', [KonsultasiController::class, 'updateJawaban']);


});
