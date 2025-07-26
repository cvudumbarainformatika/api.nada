<?php

use App\Http\Controllers\Api\Simrs\Laporan\Farmasi\Persediaan\BarangRusakController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/laporan/farmasi/barangrusak'
], function () {
    Route::get('/get-data', [BarangRusakController::class, 'getData']);
});
