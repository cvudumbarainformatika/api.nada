<?php

use App\Http\Controllers\Api\penunjang\InterpretasiController;
use Illuminate\Support\Facades\Route;


// Route::get('/test', [AuthController::class, 'test']);

Route::middleware('auth:api')
->group(function () {
    Route::post('/interpretasi/store', [InterpretasiController::class, 'store']);
});


