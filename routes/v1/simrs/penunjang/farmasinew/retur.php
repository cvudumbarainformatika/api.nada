<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Gudang\ReturkepbfController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/penunjang/farmasinew/retur'
], function () {
    Route::get('/perusahaan', [ReturkepbfController::class, 'cariPerusahaan']);
    // Route::get('/obat', [ReturkepbfController::class, 'cariObat']);
    Route::get('/obat', [ReturkepbfController::class, 'newCariObat']);
    Route::get('/ambil-data', [ReturkepbfController::class, 'ambilData']);
    Route::get('/list-retur', [ReturkepbfController::class, 'listRetur']);

    Route::post('/simpan', [ReturkepbfController::class, 'simpanretur']);
    Route::post('/simpan-edit_faktur', [ReturkepbfController::class, 'simpanEditFaktur']);
    Route::post('/simpan-edit-rinci', [ReturkepbfController::class, 'simpanEditRinci']);
    Route::post('/kunci', [ReturkepbfController::class, 'kunciRetur']);
    Route::post('/delete-header', [ReturkepbfController::class, 'deleteHeader']);
    Route::post('/delete-rinci', [ReturkepbfController::class, 'deleteRinci']);
});
