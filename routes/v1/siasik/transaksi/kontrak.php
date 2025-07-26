<?php

use App\Http\Controllers\Api\Siasik\TransaksiLS\KontrakController;
use Illuminate\Support\Facades\Route;
Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'transaksi/kontrak'
], function () {

    Route::get('/listkontrak', [KontrakController::class, 'listkontrak']);
    Route::post('/simpankontrak', [KontrakController::class, 'simpankontrak']);
    Route::post('/deletekontrak', [KontrakController::class, 'deletedata']);
});
