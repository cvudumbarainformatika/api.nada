<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Cathlab\PermintaanCathlabController;
use App\Http\Controllers\Api\Simrs\Penunjang\Cathlab\ReqCatlabController;
use App\Http\Controllers\Api\Simrs\Penunjang\Cathlab\TransCatlabController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/penunjang/cathlab/'
], function (){
    Route::get('reqcathlab',[ReqCatlabController::class, 'reqcathlab']);
    Route::post('terimapasien',[ReqCatlabController::class, 'terimapasien']);
    Route::get('tarifcathlab',[ReqCatlabController::class, 'tarifcathlab']);
    Route::post('updateflag',[ReqCatlabController::class, 'updateflag']);

    Route::post('simpancathlab',[TransCatlabController::class, 'simpancathlab']);
    Route::post('hapuscathlab',[TransCatlabController::class, 'deletecathlab']);


    Route::get('/getnota', [PermintaanCathlabController::class, 'getnota']);
    Route::post('/simpanpermintaan', [PermintaanCathlabController::class, 'simpandata']);
    Route::post('/hapuspermintaan', [PermintaanCathlabController::class, 'hapusdata']);
});
