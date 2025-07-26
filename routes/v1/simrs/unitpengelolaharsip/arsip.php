<?php

use App\Http\Controllers\Api\Simrs\UnitPelayananArsip\ListDataArsipController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/unitpengelolaharsip/arsip'
], function () {
    Route::get('/listarsip', [ListDataArsipController::class, 'listdataarsip']);
    Route::post('/simpanarsip', [ListDataArsipController::class, 'simpanarsip']);
    Route::post('/simpanarsipdokumen', [ListDataArsipController::class, 'simpanarsipdokumen']);

});

