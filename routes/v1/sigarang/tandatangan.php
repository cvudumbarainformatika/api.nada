<?php

use App\Http\Controllers\Api\Logistik\Sigarang\TandatanganController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'tandatangan'
], function () {
    Route::get('/index', [TandatanganController::class, 'index']);
    Route::get('/get-ptk', [TandatanganController::class, 'getPtk']);
    Route::get('/get-ppk', [TandatanganController::class, 'getPpk']);
    Route::get('/get-gudang', [TandatanganController::class, 'getGudang']);
    Route::get('/get-mengetahui', [TandatanganController::class, 'getMengetahui']);
    Route::get('/get-pegawai', [TandatanganController::class, 'getPegawai']);
    Route::post('/store', [TandatanganController::class, 'store']);
});
