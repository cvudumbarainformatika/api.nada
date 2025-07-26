<?php

use App\Http\Controllers\Api\Arsip\Master\MkelasifikasiController;
use App\Http\Controllers\Api\Arsip\Master\MlokasiarsipController;
use App\Http\Controllers\Api\Arsip\Master\MmediaController;
use App\Http\Controllers\Api\Arsip\Master\MunitpengelolahController;
use App\Models\Arsip\Master\Mlokasiarsip;
use App\Models\Arsip\Master\MmediaArsip;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'arsip/master'
],function () {
    Route::post('/simpankelasifikasi', [MkelasifikasiController::class, 'simpan']);
    Route::post('/deletekelasifikasi', [MkelasifikasiController::class, 'hapuskelasifikasi']);
    Route::get('/getmasterarsip', [MkelasifikasiController::class, 'listmkelasifikasi']);

    Route::post('/simpanmedia', [MmediaController::class, 'simpan']);
    Route::post('/deletemastermedia', [MmediaController::class, 'hapusmastermedia']);

    Route::get('/getmastermedia', [MmediaController::class, 'listmastermedia']);
    Route::get('/getmasterlokasiarsip', [MlokasiarsipController::class, 'index']);

    Route::get('/getMunitpengelolah', [MunitpengelolahController::class, 'unitpengelolah']);
});



