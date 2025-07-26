<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Stok\CekPerbaikanHargaController;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Stok\SetNewStokController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/farmasinew/cekdata'
], function () {
    Route::post('/get-obat', [CekPerbaikanHargaController::class, 'getObat']);
    Route::post('/simpan-perbaikan-harga-dua', [CekPerbaikanHargaController::class, 'simpanPerbaikanHargaDua']);
    Route::post('/simpan-perbaikan-harga-array', [CekPerbaikanHargaController::class, 'simpanPerbaikanHargaArray']);
});
