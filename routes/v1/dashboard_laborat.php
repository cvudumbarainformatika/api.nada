<?php

use App\Http\Controllers\Api\penunjang\DashboardLaboratController;
use Illuminate\Support\Facades\Route;


// Route::get('/test', [AuthController::class, 'test']);

Route::middleware('auth:api')
->group(function () {
    Route::get('/dashboard_laborat', [DashboardLaboratController::class, 'index']);
});


