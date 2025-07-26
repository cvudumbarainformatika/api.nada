<?php

use App\Http\Controllers\Api\Simrs\Igd\PemberianObatController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/igd/pemberianobat'
],function () {

    Route::get('/resepobat', [PemberianObatController::class, 'obatdariresep']);
    Route::post('/simpanpemberianobat', [PemberianObatController::class, 'simpanpemberianobat']);
    Route::post('/hapuspemberianobat', [PemberianObatController::class, 'hapuspemberianobat']);
});
