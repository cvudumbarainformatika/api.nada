<?php

use App\Http\Controllers\Api\Logistik\Sigarang\PegawaiController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'pegawai'
], function () {
    Route::get('/index', [PegawaiController::class, 'index']);
    Route::get('/find', [PegawaiController::class, 'find']);
});
