<?php

use App\Http\Controllers\Api\Simrs\Master\Ranap\RuanganRanapController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/master/ranap/ruangan'
], function () {
    // ruangan
    Route::post('/simpan', [RuanganRanapController::class, 'simpan']);
    Route::post('/hapus', [RuanganRanapController::class, 'hapus']);
    Route::get('/get-list', [RuanganRanapController::class, 'list']);
    // group
    Route::post('/group/simpan', [RuanganRanapController::class, 'simpanGroup']);
    Route::post('/group/hapus', [RuanganRanapController::class, 'hapusGroup']);
    Route::get('/group/get-list', [RuanganRanapController::class, 'listGroup']);
});
