<?php

use App\Http\Controllers\Api\Simrs\Hemodialisa\Pelayanan\PemeriksaanPenilaianHDController;
use App\Http\Controllers\Api\Simrs\Hemodialisa\Pelayanan\PemeriksaanUmumHDController;
use App\Http\Controllers\Api\Simrs\Ranap\Pelayanan\AnatomyController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/hemodialisa/layanan/pemeriksaan'
], function () {
    Route::post('/simpan', [PemeriksaanUmumHDController::class, 'simpan']);
    Route::get('/pemeriksaanumum', [PemeriksaanUmumHDController::class, 'list']);

    Route::get('/penilaian', [PemeriksaanPenilaianHDController::class, 'list']);
    Route::post('/penilaian/simpan', [PemeriksaanPenilaianHDController::class, 'simpan']);
    Route::post('/penilaian/hapus', [PemeriksaanPenilaianHDController::class, 'delete']);


    Route::get('/getmasteranatomys', [AnatomyController::class, 'getmasteranatomys']);
});
