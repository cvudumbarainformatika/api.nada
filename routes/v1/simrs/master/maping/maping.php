<?php

use App\Http\Controllers\Api\Simrs\Master\Maping\MapnakesController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/maping/simpegsimrs'
], function () {
    Route::get('/listnakes', [MapnakesController::class, 'listnakes']);
    Route::get('/pegawaisimpeg', [MapnakesController::class, 'pegawaisimpeg']);
    Route::get('/datatermaping', [MapnakesController::class, 'datatermaping']);
    Route::post('/simpanmaping', [MapnakesController::class, 'simpanmaping']);
    Route::post('/simpanmapingbpjs', [MapnakesController::class, 'simpanmapingbpjs']);
    Route::get('/datatermapingbpjs', [MapnakesController::class, 'datatermapingbpjs']);


    Route::get('/listdokterbpjs', [MapnakesController::class, 'listdokterbpjs']);
});
