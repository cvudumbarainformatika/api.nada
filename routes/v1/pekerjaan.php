<?php

use App\Http\Controllers\Api\PekerjaanController;
use Illuminate\Support\Facades\Route;


Route::middleware('auth:api')
->group(function () {
    Route::get('/pekerjaan', [PekerjaanController::class, 'index']);
});


