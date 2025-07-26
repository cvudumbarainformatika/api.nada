<?php

use App\Http\Controllers\Api\Simrs\Master\KelaminarrController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/master'
], function () {
    Route::get('/kelamin',[KelaminarrController::class, 'index']);
    Route::post('/simpankelamin',[KelaminarrController::class, 'store']);
    Route::post('/hapuskelamin',[KelaminarrController::class, 'hapus']);
});
