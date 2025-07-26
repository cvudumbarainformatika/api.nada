<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Depo\RestriksiObatFornasController;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'simrs/penunjang/farmasinew/restriksi'
], function () {
    Route::get('/obat', [RestriksiObatFornasController::class, 'cariObat']);
    Route::get('/ruangan', [RestriksiObatFornasController::class, 'ambilRuangan']);

    // save
    Route::post('/save-restriksi', [RestriksiObatFornasController::class, 'simpanRestriksi']);
    Route::post('/add-ruangan', [RestriksiObatFornasController::class, 'tambahRuangan']);

    Route::post('/delete-restriksi', [RestriksiObatFornasController::class, 'hapusRestriksi']);
    Route::post('/remove-ruangan', [RestriksiObatFornasController::class, 'hapusRuangan']);
});
