<?php

use App\Http\Controllers\Api\Simrs\Pendaftaran\GeneralconsentController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/pendaftaran/generalconscent'
], function () {
    Route::get('/mastergeneralconsent', [GeneralconsentController::class, 'mastergeneralconsent']);
    Route::post('/simpangeneralcontent', [GeneralconsentController::class, 'simpangeneralcontent']);
    Route::post('/simpanpdf', [GeneralconsentController::class, 'simpanpdf']);
    Route::post('/simpanmaster', [GeneralconsentController::class, 'simpanmaster']);
});
    