<?php

use App\Http\Controllers\Api\Simrs\Hemodialisa\Pelayanan\DiagnosaHemodialisaController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/hemodialisa/layanan/diagnosa'
], function () {

    Route::post('/simpandiagnosa', [DiagnosaHemodialisaController::class, 'simpandiagnosa']);
    Route::get('/getDiagnosaByNoreg', [DiagnosaHemodialisaController::class, 'getDiagnosaByNoreg']);
    Route::post('/hapusdiagnosa', [DiagnosaHemodialisaController::class, 'hapusdiagnosa']);
});
