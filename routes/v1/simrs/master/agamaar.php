<?php

use App\Http\Controllers\Api\Simrs\Master\AgamaControllerar;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/master'
], function () {
    Route::get('/agama',[AgamaControllerar::class, 'index']);
    Route::post('/agamaSimpan',[AgamaControllerar::class, 'store']);
});
