<?php

use App\Http\Controllers\Api\Simrs\Master\JeniskartukarcisController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/master'
], function () {
    Route::get('/jeniskartukarcis',[JeniskartukarcisController::class, 'jeniskartukarcis']);
});
