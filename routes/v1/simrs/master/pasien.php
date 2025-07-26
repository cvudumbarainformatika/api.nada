<?php

use App\Http\Controllers\Api\Simrs\Master\PasienController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/master'
], function () {
    Route::get('/pasien', [PasienController::class, 'pasien']);
    Route::get('/pasienGetNoRM', [PasienController::class, 'index']);
    Route::post('/simpan-pasien', [PasienController::class, 'simpanMaster']);
    Route::get('/cari-pasien', [PasienController::class, 'cariPasien']);
    Route::get('/pasien-by-norm', [PasienController::class, 'caripasienbyrm']);
    // Route::get('/pasienx',[PasienController::class, 'coba']);
});
