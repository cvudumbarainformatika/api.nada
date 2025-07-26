<?php

use App\Http\Controllers\Api\Simrs\Hemodialisa\HemodialisaController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/hemodialisa/hemodialisa'
], function () {
    Route::get('/pasienhemodialisa', [HemodialisaController::class, 'index']); // ini yang baru
    Route::post('/terima-pasien', [HemodialisaController::class, 'terima']); // ini yang baru
});
