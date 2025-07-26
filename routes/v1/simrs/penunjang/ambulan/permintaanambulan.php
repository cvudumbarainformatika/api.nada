<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Ambulan\AmbulanController;
use App\Http\Controllers\Api\Simrs\Penunjang\Ambulan\PermintaanAmbulanController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/penunjang/permintaanambulan'
], function (){
    Route::get('/gettujuanambulan',[PermintaanAmbulanController::class, 'getTujuanAmbulan']);
    Route::post('/simpanpermintaan',[PermintaanAmbulanController::class, 'simpanpermintaan']);
    Route::get('/getnota', [PermintaanAmbulanController::class, 'getnota']);
    Route::post('/hapuspermintaan', [PermintaanAmbulanController::class, 'hapusdata']);
});
