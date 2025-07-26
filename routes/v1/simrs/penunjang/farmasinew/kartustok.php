<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Kartustok\KartustokController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/farmasinew/kartustok'
], function () {
    Route::get('/listobat', [KartustokController::class, 'index']);
    Route::get('/rinciobat', [KartustokController::class, 'rinci']);
    // Route::get('/listobat', [KartustokController::class, 'listkartuStok']); //ooo
});
