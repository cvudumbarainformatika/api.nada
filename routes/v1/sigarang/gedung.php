<?php

use App\Http\Controllers\Api\Logistik\Sigarang\GedungController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'gedung'
], function () {
    Route::get('/index', [GedungController::class, 'index']);
    Route::get('/gedung', [GedungController::class, 'gedung']);
    Route::post('/store', [GedungController::class, 'store']);
    Route::post('/destroy', [GedungController::class, 'destroy']);
});
