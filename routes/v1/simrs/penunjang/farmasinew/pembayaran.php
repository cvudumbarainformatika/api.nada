<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Uang\PembayaranController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/penunjang/farmasinew/pembayaran'
], function () {
    Route::get('/cari-bast', [PembayaranController::class, 'cariBast']);
    Route::get('/ambil-bast', [PembayaranController::class, 'ambilBast']);

    Route::post('/simpan', [PembayaranController::class, 'simpan']);

    Route::get('/list-pembayaran', [PembayaranController::class, 'listPembayaran']);
});
