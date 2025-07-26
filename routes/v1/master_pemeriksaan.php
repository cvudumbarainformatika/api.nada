<?php

use App\Http\Controllers\Api\penunjang\PemeriksaanLaboratController;
use Illuminate\Support\Facades\Route;


// Route::get('/test', [AuthController::class, 'test']);

Route::middleware('auth:api')
->group(function () {
    Route::get('/master_laborat_group', [PemeriksaanLaboratController::class, 'groupped']);
});


