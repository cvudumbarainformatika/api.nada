<?php

use App\Http\Controllers\Api\Simrs\Pelayanan\Diagnosa\DiagnosatransController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/ranap/layanan/diagnosa'
], function () {
    
  Route::post('/simpandiagnosa', [DiagnosatransController::class, 'simpandiagnosa']);
  Route::get('/getDiagnosaByNoreg', [DiagnosatransController::class, 'getDiagnosaByNoreg']);
  Route::post('/hapusdiagnosa', [DiagnosatransController::class, 'hapusdiagnosa']);
});
