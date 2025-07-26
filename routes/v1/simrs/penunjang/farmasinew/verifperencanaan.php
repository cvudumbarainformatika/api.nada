<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Verif\VerifController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/farmasinew/verif'
], function () {
    // Route::get('/dialogperencanaanobat', [PerencanaanpembelianController::class, 'perencanaanpembelian']);
    Route::post('/verifpemesananrinci', [VerifController::class, 'verifpemesananrinci']);
    Route::post('/verifpemesanheder', [VerifController::class, 'verifpemesanheder']);
});
