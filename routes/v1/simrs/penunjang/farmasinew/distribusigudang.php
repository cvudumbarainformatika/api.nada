<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Gudang\DistribusigudangController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/farmasinew/gudang/distribusi'
], function () {
    Route::get('/listpermintaandepo', [DistribusigudangController::class, 'listpermintaandepo']);
    Route::get('/list-permintaan-ruangan', [DistribusigudangController::class, 'listPermintaanRuangan']);
    Route::post('/verifpermintaanobat', [DistribusigudangController::class, 'verifpermintaanobat']);
    Route::get('/rencanadistribusikedepo', [DistribusigudangController::class, 'rencanadistribusikedepo']);
    // Route::post('/simpandistribusidepo', [DistribusigudangController::class, 'simpandistribusidepo']);
    Route::post('/simpandistribusidepo', [DistribusigudangController::class, 'newSimpandistribusidepo']);

    Route::post('/distribusikan', [DistribusigudangController::class, 'distribusikan']);

    Route::post('/kuncipermintaandaridepo', [DistribusigudangController::class, 'kuncipermintaandaridepo']);
    Route::post('/tolak', [DistribusigudangController::class, 'tolakpermintaandaridepo']);
});
