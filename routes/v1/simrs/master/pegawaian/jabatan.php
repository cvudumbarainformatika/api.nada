<?php

use App\Http\Controllers\Api\Simrs\Master\Pegawai\MjabatanController;
use Illuminate\Support\Facades\Route;


Route::group([
//    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/master/pegawai/jabatan'
], function () {
    Route::get('/get-list',[MjabatanController::class, 'index']);
    Route::post('/simpan',[MjabatanController::class, 'store']);
    Route::post('/hapus',[MjabatanController::class, 'hapus']);
});
