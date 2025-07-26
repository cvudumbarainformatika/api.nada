<?php

use App\Http\Controllers\Api\Simrs\Pendaftaran\Ranap\HistoryKunjunganController;
use App\Http\Controllers\Api\Simrs\Pendaftaran\Ranap\PendaftaranRanapController;
use App\Http\Controllers\Api\Simrs\Pendaftaran\Ranap\RegistrasiRanapController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/pendaftaran/ranap'
], function () {
    Route::get('list-pendaftararan-ranap', [PendaftaranRanapController::class, 'list_pendaftaran_ranap']);
    // Route::get('list-pendaftararan-ranap', [PendaftaranRanapController::class, 'list_tunggu_pendaftaran_ranap']);
    Route::get('wheatherapi-country', [PendaftaranRanapController::class, 'wheatherapi_country']);
    Route::get('cek-peserta-bpjs', [PendaftaranRanapController::class, 'cekPesertaBpjs']);
    Route::get('history-kunjungan', [HistoryKunjunganController::class, 'index']);
    Route::post('simpanpendaftaran-byform', [RegistrasiRanapController::class, 'registrasiranap']);
    Route::post('simpanpendaftaran-byigd', [RegistrasiRanapController::class, 'registrasiranapIgd']);
    Route::post('simpanpendaftaran-byspri', [RegistrasiRanapController::class, 'registrasiranapSpri']);
});
