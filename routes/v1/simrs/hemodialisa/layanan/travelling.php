<?php

use App\Http\Controllers\Api\Simrs\Hemodialisa\Pelayanan\TravellingHDController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/hemodialisa/layanan/travelling'
], function () {
    Route::get('/list', [TravellingHDController::class, 'list']);
    Route::post('/simpan', [TravellingHDController::class, 'store']);
    Route::post('/hapus', [TravellingHDController::class, 'hapus']);
});
