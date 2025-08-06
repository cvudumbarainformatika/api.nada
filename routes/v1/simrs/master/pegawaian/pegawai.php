<?php

use App\Http\Controllers\Api\Simrs\Master\Pegawai\MpegawaiController;
use Illuminate\Support\Facades\Route;


Route::group([
   'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/master/pegawai/pegawai'
], function () {
    Route::get('/get-list',[MpegawaiController::class, 'index']);
    Route::post('/simpan',[MpegawaiController::class, 'store']);
});
