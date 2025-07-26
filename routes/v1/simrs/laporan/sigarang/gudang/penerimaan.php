<?php

use App\Http\Controllers\Api\Simrs\Laporan\Sigarang\LaporanPenerimaanController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/laporan/sigarang'
], function () {
    Route::get('/lappenerimaan', [LaporanPenerimaanController::class, 'lappenerimaan']);
    Route::get('/lappersediaan', [LaporanPenerimaanController::class, 'lappersediaan']);
    Route::get('/lappenerimaan-gudang', [LaporanPenerimaanController::class, 'lapPenerimaanGudang']);
    Route::get('/lappenerimaan-depo', [LaporanPenerimaanController::class, 'lapPenerimaanDepo']);
    Route::get('/lappenerimaan-depo-new', [LaporanPenerimaanController::class, 'lapPenerimaanDepoNew']);
    Route::get('/gudang/stok-opname', [LaporanPenerimaanController::class, 'stokOpnameGudang']);
});
