<?php

use App\Http\Controllers\Api\Simrs\Laporan\Farmasi\Pemakaian\DataResepController;
use Illuminate\Support\Facades\Route;


// persediaan
Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/laporan/farmasi/resep'
], function () {
    Route::get('/get-data-resep', [DataResepController::class, 'getDataResep']);
});
