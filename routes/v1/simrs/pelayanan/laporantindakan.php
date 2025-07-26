<?php

use App\Http\Controllers\Api\Simrs\Pelayanan\LaporanTindakan\LaporanTindakanController;
use App\Http\Controllers\Api\Simrs\Pelayanan\Praanastesi\PraAnastesiController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/pelayanan/laporantindakan'
], function () {
    Route::get('/caridokter', [LaporanTindakanController::class, 'caridokter']);
    Route::get('/listdokter', [LaporanTindakanController::class, 'listdokter']);
    Route::post('/simpan', [LaporanTindakanController::class, 'simpan']);
    Route::post('/hapus', [LaporanTindakanController::class, 'hapus']);
});
