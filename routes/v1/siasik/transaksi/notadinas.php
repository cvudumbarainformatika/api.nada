<?php

use App\Http\Controllers\Api\Siasik\TransaksiLS\NotadinasController;
use Illuminate\Support\Facades\Route;
Route::group([
    // 'middleware' => 'auth:api',
    'prefix' => 'transaksi/notadinas'
], function () {
    Route::get('/listdata', [NotadinasController::class, 'listdata']);

    Route::get('/selectnpd', [NotadinasController::class, 'selectNpd']);
    Route::post('/savedata', [NotadinasController::class, 'savedata']);
    Route::get('/getrincian', [NotadinasController::class, 'getlistform']);
    Route::post('/deleterinci', [NotadinasController::class, 'deleterinci']);
    Route::post('/kuncidata', [NotadinasController::class, 'kuncidata']);
    Route::get('/laprealisasi', [NotadinasController::class, 'laprealisasi']);
});
