<?php

use App\Http\Controllers\Api\MorganisasiAdministrasiController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/master'
], function () {
    Route::get('/listorganisasi', [MorganisasiAdministrasiController::class, 'listorganisasi']);
});
