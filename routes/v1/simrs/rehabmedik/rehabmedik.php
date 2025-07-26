<?php

use App\Http\Controllers\Api\Simrs\Rehabmedik\PengunjungController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/rehabmedik'
], function () {

    Route::get('/kunjunganpasien', [PengunjungController::class, 'index']);
});
