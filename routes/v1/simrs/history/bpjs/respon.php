<?php

use App\Http\Controllers\Api\Simrs\Bpjs\HttpResponController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/history/bpjs_respon'
], function () {
    Route::get('/data', [HttpResponController::class, 'index']);
});
