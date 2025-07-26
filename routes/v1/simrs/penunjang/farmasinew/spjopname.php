<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\SpjOpnameController;
use Illuminate\Support\Facades\Route;

Route::group(
    [
        'middleware' => 'auth:api',
        'prefix' => 'simrs/farmasinew/spj'
    ],
    function () {
        Route::get('/get-opname', [SpjOpnameController::class, 'getOpname']);
        Route::get('/get-opname-depo', [SpjOpnameController::class, 'getOpnameDepo']);
        Route::get('/get-kepala', [SpjOpnameController::class, 'getKepala']);
        Route::get('/get-spj', [SpjOpnameController::class, 'getSpj']);
        Route::post('/simpan-pernyataan', [SpjOpnameController::class, 'simpanPernyataan']);
        Route::post('/simpan-ba', [SpjOpnameController::class, 'simpanBa']);
    }
);
