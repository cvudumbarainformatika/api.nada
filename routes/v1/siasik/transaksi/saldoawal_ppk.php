<?php

use App\Http\Controllers\Api\Siasik\TransaksiSaldo\TransSaldoController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'transaksi/saldoawal_ppk'
], function () {
    Route::get('/lihatrekening', [TransSaldoController::class, 'lihatrekening']);
    Route::post('/transsaldo', [TransSaldoController::class, 'transSaldo']);
    Route::post('/hapussaldo', [TransSaldoController::class, 'hapussaldo']);
    Route::get('/tabelrek', [TransSaldoController::class, 'tabelrek']);

});
