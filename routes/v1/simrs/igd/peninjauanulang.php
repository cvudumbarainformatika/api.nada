<?php

use App\Http\Controllers\Api\Simrs\Igd\PeninjauanUlangController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/igd/peninjauanulang'
],function () {
    Route::post('/simpanpeninjauanulang', [PeninjauanUlangController::class, 'simpanpeninjauanulang']);
    Route::post('/hapuspeninjauanulang', [PeninjauanUlangController::class, 'hapuspeninjauanulang']);
});
