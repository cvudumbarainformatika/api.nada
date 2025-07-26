<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Bankdarah\BankDarahController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/penunjang/bankdarah'
], function () {
    Route::get('/getmaster', [BankDarahController::class, 'getmaster']);
    Route::get('/getnota', [BankDarahController::class, 'getnota']);
    // Route::get('/getdata', [OperasiIrdController::class, 'getdata']);
    Route::post('/simpanpermintaan', [BankDarahController::class, 'simpandata']);
    Route::post('/hapuspermintaan', [BankDarahController::class, 'hapusdata']);

    //IGD
    Route::post('/simpanbankdarah', [BankDarahController::class, 'simpanPermintaanDarahIgd']);
    Route::post('/hapusdataIgd', [BankDarahController::class, 'hapusdataIgd']);

});
