<?php

// use App\Http\Controllers\Api\Simrs\Pelayanan\Psikiatri\PsikiatriController;

use App\Http\Controllers\Api\Simrs\Pelayanan\Neonatus\NeonatusMedisController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/pelayanan/neonatusmedis'
], function () {
    Route::get('/neonatusmedis-by-norm', [NeonatusMedisController::class, 'index']);
    Route::post('/store', [NeonatusMedisController::class, 'store']);
    Route::post('/deletedata', [NeonatusMedisController::class, 'deletedata']);
    Route::post('/storeRiwayatKehamilan', [NeonatusMedisController::class, 'storeRiwayatKehamilan']);
    Route::get('/riwayatKehamilan', [NeonatusMedisController::class, 'riwayatKehamilan']);
    Route::post('/deleteRiwayatKehamilan', [NeonatusMedisController::class, 'deleteRiwayatKehamilan']);
});
