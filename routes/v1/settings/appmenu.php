<?php

use App\Http\Controllers\Api\settings\MenuController;
use Illuminate\Support\Facades\Route;



Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'settings/appmenu'
], function () {
    Route::get('/aplikasi', [MenuController::class, 'aplikasi']);
    Route::get('/cari_pegawai', [MenuController::class, 'cariPegawai']);
    Route::get('/cari_dokter', [MenuController::class, 'cari_dokter']);
    Route::post('/aplikasi_store', [MenuController::class, 'aplikasi_store']);
    Route::post('/menu-store', [MenuController::class, 'menuStore']);
    Route::post('/submenu-store', [MenuController::class, 'submenuStore']);
});
