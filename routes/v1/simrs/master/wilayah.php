<?php

use App\Http\Controllers\Api\Simrs\Master\WilayahController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/master'
], function () {
    Route::get('/getwilayah',[WilayahController::class, 'getwilayah']);
    Route::get('/getnegara',[WilayahController::class, 'getnegara']);
    Route::get('/getpropinsi',[WilayahController::class, 'getpropinsi']);
    Route::get('/getkotakabupaten',[WilayahController::class, 'getkotakabupaten']);
    Route::get('/getkecamatan',[WilayahController::class, 'getkecamatan']);
    Route::get('/getkelurahan',[WilayahController::class, 'getkelurahan']);
   // Route::post('/simpanwilayah',[WilayahController::class, 'simpannegara']);
});
