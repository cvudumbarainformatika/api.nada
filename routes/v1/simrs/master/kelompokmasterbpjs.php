<?php

use App\Http\Controllers\Api\Simrs\Master\AssesmentbpjsController;
use App\Http\Controllers\Api\Simrs\Master\JeniskunjunganbpjsController;
use App\Http\Controllers\Api\Simrs\Master\PenunjangbpjsController;
use App\Http\Controllers\Api\Simrs\Master\ProcedurebpjsController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/bpjs/master'
], function () {
    Route::get('/jeniskunjunganbpjs',[JeniskunjunganbpjsController::class, 'jeniskunjunganbpjs']);
    Route::get('/procedurebpjs',[ProcedurebpjsController::class, 'procedurebpjs']);
    Route::get('/assesmenbpjs',[AssesmentbpjsController::class, 'assesmentbpjs']);
    Route::get('/penunjangbpjs',[PenunjangbpjsController::class, 'penunjangbpjs']);
});
