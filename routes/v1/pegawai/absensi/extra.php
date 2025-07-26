<?php

use App\Http\Controllers\Api\Pegawai\Absensi\ExtraController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'pegawai/absensi/extra'
], function () {
    Route::get('/index', [ExtraController::class, 'index']);
    Route::post('/store', [ExtraController::class, 'store']);
    Route::post('/destroy', [ExtraController::class, 'destroy']);
});
