<?php

use App\Http\Controllers\Api\PerusahaanController;
use Illuminate\Support\Facades\Route;


Route::middleware('auth:api')
->group(function () {
    Route::get('/perusahaan', [PerusahaanController::class, 'index']);
});


