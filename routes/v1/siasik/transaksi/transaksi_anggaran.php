<?php

use App\Http\Controllers\Api\Siasik\TransaksiSisaAnggaran\SilpaController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'transaksi/transaksi_anggaran'
], function () {
    Route::get('/getsilpa', [SilpaController::class, 'getSilpa']);
    Route::post('/transsilpa', [SilpaController::class, 'transSilpa']);
    Route::post('/hapussilpa', [SilpaController::class, 'hapusSilpa']);

});


