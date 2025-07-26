<?php

use App\Http\Controllers\Api\Simrs\Hemodialisa\Pelayanan\DokumenUploadController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/hemodialisa/layanan/dokumenupload'
], function () {

    Route::post('/store', [DokumenUploadController::class, 'store']);
    Route::post('/deletedata', [DokumenUploadController::class, 'deletedata']);
});
