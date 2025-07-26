<?php

use App\Http\Controllers\Api\Simrs\Laporan\Rekammedik\LapcarakeluarpasienIgdController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/laporan/rekammdeik'
], function () {
   Route::get('/carakeluarpasienigd',[LapcarakeluarpasienIgdController::class, 'laporancarakeluarpasienigd'] );
});
