<?php

use App\Http\Controllers\Api\Logistik\Sigarang\KontrakPengerjaanController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'kontrak-pengerjaan'
], function () {
    Route::get('/index', [KontrakPengerjaanController::class, 'index']);
    Route::get('/paged-kontrak-aktif', [KontrakPengerjaanController::class, 'pagedKontrakAktif']);
    Route::get('/kontrak-aktif', [KontrakPengerjaanController::class, 'kontrakAktif']);
    // Route::post('/store', [KontrakPengerjaanController::class, 'store']);
    // Route::post('/destroy', [KontrakPengerjaanController::class, 'destroy']);
});
Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'siasik'
], function () {
    Route::get('/index', [KontrakPengerjaanController::class, 'index']);
    Route::post('/paged-kontrak-aktif', [KontrakPengerjaanController::class, 'pagedKontrakAktif']);
    Route::get('/kontrak-aktif', [KontrakPengerjaanController::class, 'kontrakAktif']);
    // Route::post('/store', [KontrakPengerjaanController::class, 'store']);
    // Route::post('/destroy', [KontrakPengerjaanController::class, 'destroy']);
});
