<?php

// use App\Http\Controllers\api\penunjang\TransaksiLaboratController;
use App\Http\Controllers\Api\penunjang\TransaksiLaboratLuarController;
use Illuminate\Support\Facades\Route;


// Route::get('/test', [AuthController::class, 'test']);

Route::middleware('auth:api')
->group(function () {
    Route::get('/transaksi_laborat_luar', [TransaksiLaboratLuarController::class, 'index']);
    Route::get('/transaksi_laborats_luar_details', [TransaksiLaboratLuarController::class, 'get_details']);
    Route::post('/transaksi_laborat_luar/store', [TransaksiLaboratLuarController::class, 'store']);
    Route::post('/transaksi_laborat_luar/destroy', [TransaksiLaboratLuarController::class, 'destroy']);

    // tto lis
    Route::get('/transaksi_laborats_luar_kunci_dan_kiri_ke_lis', [TransaksiLaboratLuarController::class, 'kunci_dan_kirim_ke_lis']);
});


