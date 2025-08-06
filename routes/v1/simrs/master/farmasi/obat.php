<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\ObatnewController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/master/farmasi'
], function () {

    Route::post('/simpan', [ObatnewController::class, 'simpan']);
    Route::post('/hapus', [ObatnewController::class, 'hapus']);
    Route::get('/get-list', [ObatnewController::class, 'list']);
});
