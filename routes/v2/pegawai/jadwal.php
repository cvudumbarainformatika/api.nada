<?php

use App\Http\Controllers\Api\Pegawai\Absensi\JadwalController;
use App\Http\Controllers\Api\Pegawai\Absensi\TransaksiAbsenController;
use Illuminate\Support\Facades\Route;


Route::group([
    // 'middleware' => 'auth:api',
    'middleware' => 'jwt.verify',
    'prefix' => 'absensi/jadwal'
], function () {
    Route::get('/kategori', [JadwalController::class, 'getKategories']);
    Route::get('/hari', [JadwalController::class, 'getDays']);
    Route::get('/by-user', [JadwalController::class, 'getByUser']);
    Route::get('/rekap-by-user', [TransaksiAbsenController::class, 'getRekapByUser']);

    Route::get('/rekap-by-user-libur', [TransaksiAbsenController::class, 'getRekapByUserLibur']); // yang baru pindah ke route absensi/history
    Route::get('/absen-today', [TransaksiAbsenController::class, 'getAbsenToday']);
    Route::post('/simpan', [JadwalController::class, 'create']);
    Route::post('/update', [JadwalController::class, 'update']);
    Route::post('/sync', [JadwalController::class, 'sycncroneJadwal']);
});

Route::group([
    'prefix' => 'absensi/jadwal/perubahan'
], function () {
    Route::post('/sync', [JadwalController::class, 'sycncroneJadwal']);
});
