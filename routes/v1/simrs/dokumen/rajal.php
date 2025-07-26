<?php

use App\Http\Controllers\Api\Simrs\Dokumen\Rajal\CatatanRawatJalanController;
use App\Http\Controllers\Api\Simrs\Dokumen\Rajal\ResumeController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'simrs/dokumen/rajal'
], function () {
    Route::get('/resume', [ResumeController::class, 'resume']);
    Route::get('/catatanrawatjalan', [CatatanRawatJalanController::class, 'catatanRawatJalan']);
});
