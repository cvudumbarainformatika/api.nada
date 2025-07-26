<?php

use App\Http\Controllers\Api\Simrs\Ranap\Pelayanan\AnatomyController;
use App\Http\Controllers\Api\Simrs\Ranap\Pelayanan\PemeriksaanPenilaianController;
use App\Http\Controllers\Api\Simrs\Ranap\Pelayanan\PemeriksaanUmumController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/ranap/layanan/pemeriksaan'
], function () {
    Route::post('/simpan', [PemeriksaanUmumController::class, 'simpan']);
    Route::get('/pemeriksaanumum', [PemeriksaanUmumController::class, 'list']);

    Route::get('/penilaian', [PemeriksaanPenilaianController::class, 'list']);
    Route::post('/penilaian/simpan', [PemeriksaanPenilaianController::class, 'simpan']);
    Route::post('/penilaian/hapus', [PemeriksaanPenilaianController::class, 'delete']);


    Route::get('/getmasteranatomys', [AnatomyController::class, 'getmasteranatomys']);

});
