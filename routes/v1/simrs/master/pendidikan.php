<?php

use App\Http\Controllers\Api\Simrs\Master\PendidikanController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/master'
], function () {
    Route::get('/pendidikan',[PendidikanController::class, 'index']);
    Route::post('/pendidikanSimpan',[PendidikanController::class, 'store']);
});
