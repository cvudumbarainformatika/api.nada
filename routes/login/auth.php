<?php

use App\Http\Controllers\Api\AccessLogin\AccessLoginController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AccessLoginController::class, 'login']);
