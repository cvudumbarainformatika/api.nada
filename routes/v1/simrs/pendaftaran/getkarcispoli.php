<?php

use App\Http\Controllers\Api\Simrs\Pendaftaran\GetkarcisController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/pendaftaran'
], function () {
    Route::get('getkarcispoli', [GetkarcisController::class, 'getkarciscontoller']);
});
