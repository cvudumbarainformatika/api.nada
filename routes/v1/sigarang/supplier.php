<?php

use App\Http\Controllers\Api\Logistik\Sigarang\SupplierController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'supplier'
], function () {
    Route::get('/index', [SupplierController::class, 'index']);
});
