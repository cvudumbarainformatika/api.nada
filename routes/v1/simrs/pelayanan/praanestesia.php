<?php

use App\Http\Controllers\Api\Simrs\Pelayanan\Praanastesi\PraAnastesiController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/pelayanan/praanastesi'
], function () {
    Route::get('/master', [PraAnastesiController::class, 'master']);
    Route::post('/savedata', [PraAnastesiController::class, 'savedata']);
    Route::post('/deletedata', [PraAnastesiController::class, 'deletedata']);
    Route::get('/getPraAnastesiKunjunganPoli', [PraAnastesiController::class, 'getPraAnastesiKunjunganPoli']);


    Route::get('/getKunjunganRajalLatest', [PraAnastesiController::class, 'getKunjunganRajalLatest']);
    
});
