<?php

use App\Http\Controllers\Api\Simrs\Laporan\Farmasi\Pemakaian\PemakaianRuanganFsController;
use Illuminate\Support\Facades\Route;


// persediaan
Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/laporan/farmasi/pemakaian-ruangan'
], function () {
    Route::get('/get-ruangan', [PemakaianRuanganFsController::class, 'getRuangan']);
    Route::get('/get-data', [PemakaianRuanganFsController::class, 'getData']);
});
