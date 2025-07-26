<?php

use App\Http\Controllers\Api\Siasik\Akuntansi\Laporan\LpsalController;
use App\Http\Controllers\Api\Siasik\Akuntansi\SaldoawalController;
use App\Http\Controllers\Api\Siasik\Anggaran\CetakAnggaranController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'siasik/anggaran'
], function () {
    Route::get('/getbidangkegiatan', [CetakAnggaranController::class, 'bidangbidangkegiatan']);
    Route::get('/getanggaran', [CetakAnggaranController::class, 'getAnggaran']);
    Route::get('/getrkapergeseran', [CetakAnggaranController::class, 'getRka']);

});
