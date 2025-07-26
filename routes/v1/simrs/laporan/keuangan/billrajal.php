<?php

use App\Http\Controllers\Api\Simrs\Laporan\Keuangan\AllbillrajalController;
use App\Http\Controllers\Api\Simrs\Laporan\Keuangan\AllbillrajalperpoliController;
use App\Http\Controllers\Api\Simrs\Laporan\Keuangan\AllbillranapController;
use App\Http\Controllers\Api\Simrs\Laporan\Keuangan\AllBillRekapByRuanganController;
// use App\Http\Controllers\Api\Simrs\Laporan\Keuangan\InacbgController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/laporan/keuangan'
], function () {
    //RAJAL
    // Route::get('/laporanallbillrajal', [AllbillrajalController::class, 'kumpulanbillpasien']);
    Route::get('/laporanallbillrajal', [AllbillrajalController::class, 'rekapanbill']);
    Route::get('/allbillperlopi', [AllbillrajalperpoliController::class, 'allbillperlopi']);
    Route::get('/billpoli', [AllbillrajalperpoliController::class, 'billpoli']);
    Route::get('/allBillRekapByRuanganperPoli', [AllBillRekapByRuanganController::class, 'allBillRekapByRuanganperPoli']);

    //RANAP
    Route::get('/allbillranap', [AllbillranapController::class, 'allbillranap']);
    Route::get('/allBillRekapByRuangan', [AllBillRekapByRuanganController::class, 'allBillRekapByRuangan']);
    Route::get('/allBillRekapByRuanganperruangan', [AllBillRekapByRuanganController::class, 'allBillRekapByRuanganperruangan']);

    //incbg
    // Route::get('/incbglap', [InacbgController::class, 'incbglap']);
});
