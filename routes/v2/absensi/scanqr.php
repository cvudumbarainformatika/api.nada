<?php

use App\Http\Controllers\Api\Mobile\Absensi\ScanQrController;
use Illuminate\Support\Facades\Route;


Route::group([
    // 'middleware' => 'auth:api',
    'middleware' => 'jwt.verify',
    'prefix' => 'absensi/scan'
], function () {
    Route::post('/qr', [ScanQrController::class, 'data']);
});
