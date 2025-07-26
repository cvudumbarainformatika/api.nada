<?php

use App\Http\Controllers\Api\Siasik\Akuntansi\Jurnal\RegJurnalController;
use App\Http\Controllers\Api\Siasik\Akuntansi\JurnalumumController;
use Illuminate\Support\Facades\Route;

Route::group([
    // 'middleware' => 'auth:api',
    'prefix' => 'akuntansi/registerjurnal'
], function () {
    Route::get('/regjurnal', [RegJurnalController::class, 'listjurnal']);
    Route::post('/postingjurnal', [RegJurnalController::class, 'savejurnal']);
    Route::get('/getjurnalpost', [RegJurnalController::class, 'getjurnalpost']);
    Route::post('/verifjurnal', [RegJurnalController::class, 'verifjurnal']);
    Route::post('/verifjurnal/all', [RegJurnalController::class, 'verifAll']);
    Route::post('/cancelverif', [RegJurnalController::class, 'cancelverif']);

});
