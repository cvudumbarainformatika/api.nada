<?php

use App\Http\Controllers\Api\Antrean\master\PoliController;
use App\Http\Controllers\Api\Simrs\Pendaftaran\Rajal\Bridbpjscontroller;
use App\Http\Controllers\Api\Simrs\Rajal\EditsuratbpjsController;
use App\Http\Controllers\Api\Simrs\Rajal\KunjunganPoliController;
use App\Http\Controllers\Api\Simrs\Rajal\PoliController as RajalPoliController;
use App\Http\Controllers\Api\Simrs\Rajal\RmeRajalController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/rajal/poli'
], function () {
    // Route::get('/listminmaxobat', [MinmaxobatController::class, 'listminmaxobat']);
    // Route::get('/kunjunganpoli', [RajalPoliController::class, 'kunjunganpoli']); // ini yang lama
    Route::get('/kunjunganpoli', [KunjunganPoliController::class, 'index']); // ini yang baru
    Route::post('/save-pemeriksaanfisik', [RajalPoliController::class, 'save_pemeriksaanfisik']);
    Route::post('/flagfinish', [RajalPoliController::class, 'flagfinish']);
    Route::post('/terimapasien', [RajalPoliController::class, 'terimapasien']);
    Route::post('/updatewaktubpjs', [RajalPoliController::class, 'updatewaktubpjs']);

    Route::get('/listsuratkontrol', [EditsuratbpjsController::class, 'listsuratkontrol']);
    Route::post('/editsuratkontrol', [EditsuratbpjsController::class, 'editsuratkontrol']);
    Route::post('/jadwal', [EditsuratbpjsController::class, 'jadwaldokter']);
    Route::get('/surat-kontrol-by-noka', [EditsuratbpjsController::class, 'suratKOntrolByNoka']);

    Route::get('/listrujukankeluarrs', [EditsuratbpjsController::class, 'listrujukankeluarrs']);
    Route::post('/updaterujukan', [EditsuratbpjsController::class, 'updaterujukan']);

    Route::post('/konsulpoli', [RajalPoliController::class, 'konsulpoli']);

    Route::post('/tidakhadir', [RajalPoliController::class, 'tidakhadir']);

    Route::get('/datamrerajal', [RmeRajalController::class, 'rmerajal']);

    Route::get('/suratkontrolbysuratkontrol', [RmeRajalController::class, 'suratkontrolbysuratkontrol']);
});
