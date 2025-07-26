<?php

use App\Http\Controllers\Api\Aplikasi\AplikasiController;
use App\Http\Controllers\Api\DataCache\DataCacheController;
use Illuminate\Support\Facades\Route;



Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'datacache'
], function () {
    Route::get('/list-cache', [DataCacheController::class, 'index']);
    Route::post('/hapus-cache', [DataCacheController::class, 'hapusCache']);
});
