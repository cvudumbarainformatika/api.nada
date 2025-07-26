<?php

use App\Http\Controllers\Api\Simrs\Hemodialisa\Pelayanan\IntradialitikController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/hemodialisa/layanan/intradialitik'
], function () {
    Route::get('/list', [IntradialitikController::class, 'list']);
    Route::post('/simpan', [IntradialitikController::class, 'simpan']);
    Route::post('/hapus', [IntradialitikController::class, 'hapus']);
});
