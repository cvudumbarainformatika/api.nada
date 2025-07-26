<?php

use App\Http\Controllers\Api\Simrs\Pelayanan\Kandungan\KandunganController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/pelayanan/kandungan'
], function () {
    Route::get('/kandungan-by-norm', [KandunganController::class, 'index']);
    Route::post('/store', [KandunganController::class, 'store']);
    Route::post('/deletedata', [KandunganController::class, 'deletedata']);
    Route::get('/masterskrining', [KandunganController::class, 'masterskrining']);
    Route::get('/skrining', [KandunganController::class, 'skrining']);
    Route::post('/storeSkrining', [KandunganController::class, 'storeSkrining']);

    Route::get('/riwayat-obsetri', [KandunganController::class, 'riwayatObsetri']);
    Route::post('/store-obsetri', [KandunganController::class, 'storeObsetri']);
    Route::post('/delete-obsetri', [KandunganController::class, 'deleteObsetri']);
});
