<?php

use App\Http\Controllers\Api\Simrs\Ranap\Pelayanan\DischargePlanningController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/ranap/layanan/dischargeplanning'
], function () {

  Route::get('/getmasterprognosis', [DischargePlanningController::class, 'getmasterprognosis']);
    Route::post('/simpandata', [DischargePlanningController::class, 'simpandata']);
    Route::post('/skrining/simpandata', [DischargePlanningController::class, 'simpandataskrining']);
    Route::post('/summary/simpandata', [DischargePlanningController::class, 'simpandatasummary']);
    // Route::get('/pemeriksaanumum', [PemeriksaanUmumController::class, 'list']);

    // Route::get('/penilaian', [PemeriksaanPenilaianController::class, 'list']);
    // Route::post('/penilaian/simpan', [PemeriksaanPenilaianController::class, 'simpan']);
    Route::post('/hapusdata', [DischargePlanningController::class, 'hapusdata']);
    Route::post('/skrining/hapusdata', [DischargePlanningController::class, 'hapusdataskrining']);
    Route::post('/summary/hapusdata', [DischargePlanningController::class, 'hapusdatasummary']);

});
