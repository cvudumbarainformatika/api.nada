<?php

// use App\Http\Controllers\Api\Simrs\Pelayanan\Psikiatri\PsikiatriController;

use App\Http\Controllers\Api\Simrs\Pelayanan\Neonatus\NeonatusKeperawatanController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/pelayanan/neonatuskeperawatan'
], function () {
    Route::get('/neonatuskeperawatan-by-norm', [NeonatusKeperawatanController::class, 'index']);
    Route::post('/store', [NeonatusKeperawatanController::class, 'store']);
    Route::post('/deletedata', [NeonatusKeperawatanController::class, 'deletedata']);
});
