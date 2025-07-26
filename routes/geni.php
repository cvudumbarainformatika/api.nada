<?php

use App\Helpers\Routes\RouteHelper;
use App\Http\Controllers\AutogenController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



Route::prefix('login')->group(function () {
    RouteHelper::includeRouteFiles(__DIR__ . '/login');
});
