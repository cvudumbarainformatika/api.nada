<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Laborat\LaboratController;
use App\Http\Controllers\Api\Simrs\Penunjang\Laborat\LaporanLaboratController;
use App\Http\Controllers\Api\Simrs\Penunjang\Radiologi\RadiologimetaController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/penunjang/laborat'
], function () {
    Route::get('/dialoglaboratpoli', [LaboratController::class, 'listmasterpemeriksaanpoli']);
    Route::get('/getnota', [LaboratController::class, 'getnota']);
    Route::get('/getnotaold', [LaboratController::class, 'getnotaold']);
    Route::get('/getdata', [LaboratController::class, 'getdata']);
    Route::post('/simpanpermintaanlaborat', [LaboratController::class, 'simpanpermintaanlaborat']);
    Route::post('/simpanpermintaanlaboratbaru', [LaboratController::class, 'simpanpermintaanlaboratbaru']);
    Route::post('/hapuspermintaanlaborat', [LaboratController::class, 'hapuspermintaanlaborat']);
    Route::post('/hapuspermintaanlaboratbaru', [LaboratController::class, 'hapuspermintaanlaboratbaru']);

    Route::get('/listmasterpemeriksaanradiologi', [RadiologimetaController::class, 'listmasterpemeriksaanradiologi']);


    Route::post('/simpanpermintaanlaboratbaruIgd', [LaboratController::class, 'simpanpermintaanlaboratbaruIgd']);
    Route::post('/hapuspermintaanlaboratbaruIgd', [LaboratController::class, 'hapuspermintaanlaboratbaruIgd']);
    Route::get('/getnotaIgd', [LaboratController::class, 'getnotaIgd']);
    Route::get('/getnotaoldIgd', [LaboratController::class, 'getnotaoldIgd']);
    Route::get('/getdataIgd', [LaboratController::class, 'getdataIgd']);





    // routing Laporan
    Route::get('/masterlaborat', [LaporanLaboratController::class, 'masterlaborat']);
    Route::get('/pemeriksaan-by-gender', [LaporanLaboratController::class, 'pemeriksaanByGender']);
});
