<?php

use App\Http\Controllers\Api\Simrs\Igd\AnamnesisController;
use App\Http\Controllers\Api\Simrs\Igd\AnamnesisKebidananController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/igd/anamnesis'
],function () {
    Route::post('/simpananamnesis', [AnamnesisController::class, 'simpananamnesis']);
    Route::post('/hapusanamnesis', [AnamnesisController::class, 'hapusanamnesis']);

    Route::post('/simpanHistoryPerkawiananPasien', [AnamnesisKebidananController::class, 'simpanHistoryPerkawiananPasien']);
    Route::post('/hapusHistoryPerkawiananPasien', [AnamnesisKebidananController::class, 'hapusHistoryPerkawiananPasien']);

    Route::post('/simpanHistoryKehamilan', [AnamnesisKebidananController::class, 'simpanHistoryKehamilan']);
    Route::post('/hapusHistoryKehamilan', [AnamnesisKebidananController::class, 'hapusHistoryKehamilan']);

    Route::post('/simpanananamesiskebidanan', [AnamnesisKebidananController::class, 'simpanananamesiskebidanan']);
    Route::post('/hapusanamnesiskebidanan', [AnamnesisKebidananController::class, 'hapusanamnesiskebidanan']);

    Route::get('/listanamnesebynoreg', [AnamnesisController::class, 'listanamnesebynoreg']);
});

