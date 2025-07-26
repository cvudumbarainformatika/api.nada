<?php

use App\Http\Controllers\Api\Simrs\Master\TindakanController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/master'
], function () {
    Route::get('/listtindakan', [TindakanController::class, 'listtindakan']);
    Route::post('/simpanmastertindakan', [TindakanController::class, 'simpanmastertindakan']);
    Route::post('/hapusmastertindakan', [TindakanController::class, 'hidden']);
});
