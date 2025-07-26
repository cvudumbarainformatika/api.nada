<?php

use App\Http\Controllers\Api\Siasik\Laporan\BKUController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'laporan/laporan_bku'
], function () {
    Route::get('/ptk', [BKUController::class, 'ptk']);
    Route::get('/bkuppk', [BKUController::class, 'bkuppk']);
    Route::get('/bkupengeluaran', [BKUController::class, 'bkupengeluaran']);
    Route::get('/bkuptk', [BKUController::class, 'bkuptk']);
    Route::get('/bukubank', [BKUController::class, 'bukubank']);
    Route::get('/bukutunai', [BKUController::class, 'bukutunai']);


    // coba
    Route::get('/coba', [BKUController::class,'coba']);
    Route::get('/kode', [BKUController::class,'kode']);
});


