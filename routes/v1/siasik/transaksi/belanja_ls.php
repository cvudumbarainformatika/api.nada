<?php

use App\Http\Controllers\Api\Siasik\TransaksiLS\NPD_LSController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'transaksi/belanja_ls'
], function () {
    Route::get('/bidang', [NPD_LSController::class, 'bidang']);

    Route::get('/listnpdls', [NPD_LSController::class, 'listnpdls']);
    Route::get('/perusahaan', [NPD_LSController::class, 'perusahaan']);
    // Route::get('/ptk', [NPD_LSController::class, 'ptk']);
    Route::get('/anggaran', [NPD_LSController::class, 'anggaran']);
    Route::get('/bastfarmasi', [NPD_LSController::class, 'bastfarmasi']);
    Route::get('/bastpekerjaan', [NPD_LSController::class, 'bastpekerjaan']);
    Route::get('/getrincian', [NPD_LSController::class, 'getlistformnpd']);
    Route::post('/simpannpd', [NPD_LSController::class, 'simpannpd']);
    Route::post('/kuncinpd', [NPD_LSController::class, 'kuncinpd']);
    Route::post('/deleterinci', [NPD_LSController::class, 'deleterinci']);
    Route::get('/selectpajak', [NPD_LSController::class, 'selectpajak']);
    Route::post('/savepajakls', [NPD_LSController::class, 'savepajakls']);
    Route::get('/listpajak', [NPD_LSController::class, 'listpajak']);
    Route::post('/deletepajak', [NPD_LSController::class, 'deletepajak']);
    Route::post('/updateuser', [NPD_LSController::class, 'updateuser']);

    Route::get('/coba', [NPD_LSController::class, 'coba']);

});
