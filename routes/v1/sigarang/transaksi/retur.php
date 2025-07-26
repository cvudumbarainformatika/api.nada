<?php

use App\Http\Controllers\Api\Logistik\Sigarang\Transaksi\ReturController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'transaksi/retur'
], function () {
    Route::post('/simpan', [ReturController::class, 'simpan']);
});
