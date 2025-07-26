<?php

use App\Http\Controllers\Api\Simrs\Laporan\Farmasi\Billing\BillingFarmasiController;
use App\Http\Controllers\Api\Simrs\Laporan\Farmasi\Hutang\HutangKonsinyasiController;
use App\Http\Controllers\Api\Simrs\Laporan\Farmasi\Hutang\HutangObatPesan;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/laporan/farmasi/billing'
], function () {
    Route::get('/get-billing-farmasi', [BillingFarmasiController::class, 'gettagihanpasien']);

});