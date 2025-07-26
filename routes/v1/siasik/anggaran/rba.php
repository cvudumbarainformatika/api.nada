<?php

use App\Http\Controllers\Api\Siasik\Anggaran\RBAController;
use Illuminate\Support\Facades\Route;

Route::group([
    // 'middleware' => 'auth:api',
    'prefix' => 'siasik/rba'
], function () {
    Route::get('/getdatarba', [RBAController::class, 'getDatarba']);

});
