<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Jenazah\PerawatanJenazahController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/penunjang/jenazah'
], function () {
    Route::post('/permintaanperawatanjenazah', [PerawatanJenazahController::class, 'permintaanperawatanjenazah']);
    Route::get('/getnota', [PerawatanJenazahController::class, 'getnota']);
});
