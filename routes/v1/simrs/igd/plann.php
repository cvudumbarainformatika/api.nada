<?php

use App\Http\Controllers\Api\Simrs\Igd\PlannController;
use App\Http\Controllers\Api\Simrs\Igd\SkalaTransferIgd;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/planing/igd'
],function () {
    Route::post('/simpanranap', [PlannController::class, 'simpanranap']);
    Route::post('/simpanskalatransfer', [SkalaTransferIgd::class, 'simpan']);

    Route::post('/hapusskalatransfer', [SkalaTransferIgd::class, 'hapusskalatransfer']);
    Route::post('/hapusplann', [PlannController::class, 'hapusplann']);

    Route::get('/suratkematian', [PlannController::class, 'suratkematian']);

    Route::get('/indikasimasuknicuinter', [PlannController::class, 'indikasimasuknicuinter']);
});


