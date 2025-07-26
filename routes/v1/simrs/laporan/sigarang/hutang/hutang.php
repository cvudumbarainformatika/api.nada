<?php

use App\Http\Controllers\Api\Simrs\Laporan\Sigarang\LaporanHutangController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/laporan/sigarang'
], function () {
    Route::get('/laphutang', [LaporanHutangController::class, 'lapHutang']);
});
