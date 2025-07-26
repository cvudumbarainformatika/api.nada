<?php

use App\Http\Controllers\Api\Logistik\Sigarang\Transaksi\KartustokController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'transaksi/kartustok'
], function () {
    Route::get('/lihatkartustokgudang', [KartustokController::class, 'kartustokgudang']);
    Route::get('/lihatkartustokdepo', [KartustokController::class, 'kartustokdepo']);
    Route::get('/lihatkartustokruangan', [KartustokController::class, 'kartustokruangan']);
});
