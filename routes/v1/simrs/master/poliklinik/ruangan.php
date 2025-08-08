<?php

use App\Http\Controllers\Api\Simrs\Master\Poliklinik\RuanganPoliController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/master/poliklinik/ruangan'
], function () {
    Route::post('/simpan', [RuanganPoliController::class, 'simpan']);
    Route::post('/hapus', [RuanganPoliController::class, 'hapus']);
    Route::get('/get-list', [RuanganPoliController::class, 'list']);
});
