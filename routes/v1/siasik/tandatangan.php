<?php

use App\Http\Controllers\Api\Siasik\TtdController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'siasik/ttd'
], function () {
    Route::get('/ttdpengesahan', [TtdController::class, 'ttdpengesahan']);


});
