<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\MinmaxobatController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/farmasinew'
], function () {
    // Route::get('/listminmaxobat', [MinmaxobatController::class, 'listminmaxobat']);
    Route::get('/carilistminmaxbyobat', [MinmaxobatController::class, 'caribynamaobat']);
    Route::post('/minmaxobat', [MinmaxobatController::class, 'simpan']);
    Route::post('/simpanminta', [MinmaxobatController::class, 'simpan']); // smentara
    Route::get('/carilistminmaxbyruang', [MinmaxobatController::class, 'caribyruang']);
});
