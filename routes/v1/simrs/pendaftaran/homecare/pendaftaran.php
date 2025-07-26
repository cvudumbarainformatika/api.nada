<?php

use App\Http\Controllers\Api\Simrs\Pendaftaran\Homecare\PendaftaranHomeCareController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/pendaftaran/homecare'
], function () {
    Route::get('/list', [PendaftaranHomeCareController::class, 'listKunjungan']);
    Route::post('/simpan-daftar', [PendaftaranHomeCareController::class, 'simpanKunjungan']);
});
