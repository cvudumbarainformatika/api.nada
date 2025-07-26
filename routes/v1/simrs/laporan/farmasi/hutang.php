<?php

use App\Http\Controllers\Api\Simrs\Laporan\Farmasi\Hutang\HutangKonsinyasiController;
use App\Http\Controllers\Api\Simrs\Laporan\Farmasi\Hutang\HutangObatPesan;
use App\Http\Controllers\Api\Simrs\Laporan\Farmasi\Hutang\MutasiHutangObat;
use App\Http\Controllers\Api\Simrs\Laporan\Farmasi\Penerimaan\PenerimaanObatController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/laporan/farmasi/hutang'
], function () {
    Route::get('/get-hutang-konsinyasi', [HutangKonsinyasiController::class, 'getHutangKonsinyasi']);
    Route::get('/reportObatPesananBytanggal', [HutangObatPesan::class, 'reportObatPesananBytanggal']);
    Route::get('/reportObatPesananBytanggalBast', [HutangObatPesan::class, 'reportObatPesananBytanggalBast']);
    Route::get('/reportHutangByTransaksi', [HutangObatPesan::class, 'reportHutangByTransaksi']);


    Route::get('/reportMutasiHutangObat', [MutasiHutangObat::class, 'reportMutasiHutangObat']);
    Route::get('/caripenerimaanobat', [PenerimaanObatController::class, 'caripenerimaanobat']);
    Route::get('/caripenerimaanobatrinci', [PenerimaanObatController::class, 'caripenerimaanobatrinci']);

});
