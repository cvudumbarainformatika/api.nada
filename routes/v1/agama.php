<?php

use App\Http\Controllers\Api\AgamaController;
use Illuminate\Support\Facades\Route;


Route::middleware('auth:api')
->group(function () {
    Route::get('/agama', [AgamaController::class, 'index']);
});


