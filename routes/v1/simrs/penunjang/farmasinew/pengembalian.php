<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Gudang\PengembalianPinjamanController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/penunjang/farmasinew/pengembalian'
], function () {
    // form
    Route::get('/get-pbf', [PengembalianPinjamanController::class, 'getPbfPeminjam']);
    Route::get('/get-noper', [PengembalianPinjamanController::class, 'getNopenerimaan']);
    Route::post('/simpan', [PengembalianPinjamanController::class, 'simpan']);

    // list
    Route::get('/get-list', [PengembalianPinjamanController::class, 'getList']);

    Route::post('/hapus-rinci', [PengembalianPinjamanController::class, 'hapusRinci']);
    Route::post('/hapus-header', [PengembalianPinjamanController::class, 'hapusHeader']);
    Route::post('/kunci', [PengembalianPinjamanController::class, 'kunci']);
});
