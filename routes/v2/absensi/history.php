<?php

use App\Http\Controllers\Api\Mobile\Absensi\HistoryMobile;
use Illuminate\Support\Facades\Route;


Route::group([
    // 'middleware' => 'auth:api',
    'middleware' => 'jwt.verify',
    'prefix' => 'absensi/history'
], function () {
    Route::get('/data', [HistoryMobile::class, 'data']);
});
