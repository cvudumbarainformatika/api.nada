<?php

use App\Http\Controllers\Api\Simrs\Master\DiagnosaController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/master'
], function () {
    Route::get('/listdiagnosa', [DiagnosaController::class, 'listdiagnosa']);
    Route::get('/diagnosa-autocomplete', [DiagnosaController::class, 'diagnosa_autocomplete']);
    Route::get('/listtipekhasus', [DiagnosaController::class, 'listtipekhasus']);
});
