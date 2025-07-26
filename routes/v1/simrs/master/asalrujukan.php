<?php

use App\Http\Controllers\Api\Simrs\Master\AsalrujukanContoller;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/master'
], function () {
    Route::get('/listasalrujukan', [AsalrujukanContoller::class, 'listasalrujukan']);
});
