<?php

use App\Http\Controllers\Api\Simrs\Igd\IgdController;
use App\Http\Controllers\Api\Simrs\Pendaftaran\Rajal\DaftarigdController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/pendaftaran/igd'
], function () {
    Route::get('/kunjunganpasienigd', [IgdController::class, 'kunjunganpasienigd']);
    Route::post('/simpankunjunganigd', [DaftarigdController::class, 'simpankunjunganigd']);
    Route::post('/hapuspasien', [DaftarigdController::class, 'hapuskunjunganpasienigd']);
});

