<?php

use App\Http\Controllers\Api\Logistik\Sigarang\MapingBarangDepoController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'mapingdepo'
], function () {
    Route::get('/maping', [MapingBarangDepoController::class, 'allMapingDepo']);
    Route::get('/barang', [MapingBarangDepoController::class, 'allMapingBarangDepo']);
});
