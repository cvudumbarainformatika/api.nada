<?php

use App\Http\Controllers\Api\Mjkn\AmbilAntreanController;
use App\Http\Controllers\Api\Mjkn\BatalAntreanController;
use App\Http\Controllers\Api\Mjkn\CheckInController;
use App\Http\Controllers\Api\Mjkn\PasienBaruController;
use App\Http\Controllers\Api\Mjkn\SisaAntreanController;
use App\Http\Controllers\Api\Mjkn\StatuslayananController;
use Illuminate\Support\Facades\Route;



Route::group([
    // 'middleware' => 'auth:api',
    'middleware' => 'jkn.auth',
    'prefix' => 'mjkn'
], function () {
    Route::post('/status-antrean', [StatuslayananController::class, 'byLayanan']);  //mJkn (2)
    Route::post('/ambil-antrean', [AmbilAntreanController::class, 'byLayanan']);  //mJkn (3)
    Route::post('/sisa-antrean-pasien', [SisaAntreanController::class, 'byKodebooking']);  //mJkn (4)
    Route::post('/batal-antrean', [BatalAntreanController::class, 'byKodebooking']);  //mJkn (5)
    Route::post('/check-in', [CheckInController::class, 'byKodebooking']);  //mJkn (6)
    Route::post('/pasien-baru', [PasienBaruController::class, 'store']);  //mJkn (7)
});
