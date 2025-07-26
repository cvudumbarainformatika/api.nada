<?php

use App\Http\Controllers\Api\Simrs\Historypasien\HistorypasienfullController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/historypasien'
], function () {
    Route::get('/historypasienfull', [HistorypasienfullController::class, 'historypasienfull']);
    Route::get('/detail-history', [HistorypasienfullController::class, 'getDetailHistory']);
});
