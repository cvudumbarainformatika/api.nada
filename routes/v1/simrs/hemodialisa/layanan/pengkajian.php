<?php

use App\Http\Controllers\Api\Simrs\Hemodialisa\Pelayanan\PengkajianController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/hemodialisa/layanan/pengkajian'
], function () {
    Route::post('/simpan', [PengkajianController::class, 'simpan']);
    Route::post('/hapus', [PengkajianController::class, 'hapus']);
});
