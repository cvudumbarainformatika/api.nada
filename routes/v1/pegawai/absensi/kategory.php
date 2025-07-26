<?php

use App\Http\Controllers\Api\Pegawai\Absensi\KategoryController;
use App\Http\Controllers\Api\Pegawai\User\LiburController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'pegawai/absensi/kategori'
], function () {
    Route::get('/index', [KategoryController::class, 'index']);
    Route::get('/all', [KategoryController::class, 'all']);
    Route::post('/store', [KategoryController::class, 'store']);
    Route::post('/destroy', [KategoryController::class, 'destroy']);
    // pergantian jadwal
    Route::get('/get-pegawai', [LiburController::class, 'getPegawai']);
    Route::get('/get-kategory', [LiburController::class, 'getKategori']);
    Route::get('/get-hari', [LiburController::class, 'getHari']);
    Route::get('/get-jadwal', [LiburController::class, 'getJadwal']);
    Route::post('/simpan-perubahan-jadwal', [LiburController::class, 'simpanPerubahanJadwal']);
});
