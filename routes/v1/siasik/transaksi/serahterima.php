<?php

use App\Http\Controllers\Api\Siasik\TransaksiLS\SerahterimaController;
use Illuminate\Support\Facades\Route;
Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'transaksi/serahterima'
], function () {
    Route::get('/listdata', [SerahterimaController::class, 'listdatastp']);
    Route::get('/getkontrak', [SerahterimaController::class, 'getkontrak']);
    Route::post('/savedata', [SerahterimaController::class, 'savedata']);
    Route::get('/getrincian', [SerahterimaController::class, 'getlistform']);
    Route::post('/deleterinci', [SerahterimaController::class, 'deleterinci']);
    Route::post('/kuncidata', [SerahterimaController::class, 'kuncidata']);
});
