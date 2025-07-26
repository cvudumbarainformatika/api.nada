<?php

use App\Http\Controllers\Api\settings\AksesUserController;
use Illuminate\Support\Facades\Route;

Route::group(
    [
        'middleware' => 'auth:api',
        // 'middleware' => 'jwt.verify',
        'prefix' => 'settings/appakses'
    ],
    function () {
        Route::get('/migrasi', [AksesUserController::class, 'migrasiAkses']);
        Route::get('/akses', [AksesUserController::class, 'userAkses']);
        Route::get('/role', [AksesUserController::class, 'userRole']);
        Route::get('/poli', [AksesUserController::class, 'getPoli']);
        Route::get('/penunjang', [AksesUserController::class, 'getPenunjang']);
        Route::post('/store-akses', [AksesUserController::class, 'storeAkses']);
        Route::post('/store-role', [AksesUserController::class, 'storeRole']);
        Route::post('/store-poli', [AksesUserController::class, 'storePoli']);
        Route::post('/store-ruang', [AksesUserController::class, 'storeRuang']);
        Route::post('/store-unitpengolaharsip', [AksesUserController::class, 'storeunitpengolaharsip']);
        // Route::post('/store-akses-menu-only', [AksesUserController::class, 'storeAksesMenuOnly']);
    }
);
