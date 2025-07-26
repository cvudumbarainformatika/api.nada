<?php

use App\Http\Controllers\Api\Pegawai\Absensi\JadwalController;
use App\Http\Controllers\Api\Pegawai\Absensi\TransaksiAbsenController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'pegawai/absensi/jadwal'
], function () {
    Route::get('/index', [JadwalController::class, 'index']);
    Route::get('/kategori', [JadwalController::class, 'getKategories']);
    Route::get('/hari', [JadwalController::class, 'getDays']);
    Route::get('/by-user', [JadwalController::class, 'getByUserDesk']);
    Route::get('/rekap-per-user', [TransaksiAbsenController::class, 'getRekapPerUser']);
    Route::post('/store', [JadwalController::class, 'store']);
    Route::post('/destroy', [JadwalController::class, 'destroy']);
});
