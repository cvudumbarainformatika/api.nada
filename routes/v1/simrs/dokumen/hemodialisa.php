<?php

use App\Http\Controllers\Api\Simrs\Hemodialisa\Pelayanan\DokumenHd;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'simrs/dokumen/hemodialisa'
], function () {
    Route::get('/resume', [DokumenHd::class, 'resume']);
});
