<?php

use App\Http\Controllers\Api\Simrs\Ranap\Pelayanan\CpptController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/ranap/layanan/cppt'
], function () {
    Route::get('/listcppt', [CpptController::class, 'list']);
    Route::post('/savecppt', [CpptController::class, 'saveCppt']);
    Route::post('/deletecppt', [CpptController::class, 'deleteCppt']);

    Route::post('/editcpptanamnesis', [CpptController::class, 'editCpptAnamnesis']);
    Route::post('/editcpptpemeriksaan', [CpptController::class, 'editCpptPemeriksaan']);
    Route::post('/updateasplaninst', [CpptController::class, 'updateAsPlanInst']);
    Route::post('/updateosambung', [CpptController::class, 'updateosambung']);
    Route::post('/updatessambung', [CpptController::class, 'updatessambung']);

});
