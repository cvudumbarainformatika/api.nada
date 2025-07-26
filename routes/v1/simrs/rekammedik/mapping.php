<?php

use App\Http\Controllers\Api\Simrs\Rehabmedik\PengunjungController;
use App\Http\Controllers\Api\Simrs\Rekammedik\MappingController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/rekammedik/mapping'
], function () {

    Route::get('/tindakan', [MappingController::class, 'index']);
    Route::post('/get-icd9', [MappingController::class, 'getIcd9']);
    Route::post('/save-icd9', [MappingController::class, 'saveIcd9']);
    Route::post('/delete-icd9', [MappingController::class, 'deleteIcd9']);


    Route::post('/save-snowmed', [MappingController::class, 'saveSnowmed']);
    Route::post('/delete-snowmed', [MappingController::class, 'deleteSnowmed']);



});
