<?php

use App\Http\Controllers\Api\Simrs\Kasir\BillingbynoregController;
use App\Http\Controllers\Api\Simrs\Kasir\FlagingManualVaController;
use App\Http\Controllers\Api\Simrs\Kasir\KasirrajalController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/kasir'
], function () {
    Route::get('/rajal/kunjunganpoli', [KasirrajalController::class, 'kunjunganpoli']);
    Route::get('/rajal/billbynoreg', [BillingbynoregController::class, 'billbynoregrajalx']);

    Route::get('/rajal/tagihanpergolongan', [KasirrajalController::class, 'tagihanpergolongan']);
    Route::post('/rajal/pembayaran', [KasirrajalController::class, 'pembayaran']);


    // kasir igd
    Route::get('/igd/billbynoreg', [BillingbynoregController::class, 'billbynoregigd']);

    Route::get('/va/listva', [FlagingManualVaController::class, 'listva']);
    Route::post('/va/flagingmanualva', [FlagingManualVaController::class, 'flagingmanual']);
});
