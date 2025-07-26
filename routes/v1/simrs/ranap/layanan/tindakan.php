<?php

use App\Http\Controllers\Api\Simrs\Pelayanan\Tindakan\TindakanController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/ranap/layanan/tindakan'
], function () {
    Route::post('/simpantindakanranap', [TindakanController::class, 'simpantindakanranap']); 
    Route::get('/listtindakanranap', [TindakanController::class, 'getTindakanRanap']); // fixed
});
