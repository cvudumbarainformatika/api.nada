<?php

use App\Http\Controllers\Api\Simrs\Master\DiagnosaKebidanan\MasterDiagnosaKebidanan;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/master/diagnosakebidanan'
], function () {
    Route::post('/store', [MasterDiagnosaKebidanan::class, 'store']);
    Route::get('/getall', [MasterDiagnosaKebidanan::class, 'index']);
    Route::post('/storeintervensi', [MasterDiagnosaKebidanan::class, 'storeintervensi']);
    // Route::get('/getall', [MasterDiagnosaKeperawatan::class, 'index']);
    Route::post('/delete', [MasterDiagnosaKebidanan::class, 'delete']);
    Route::post('/deleteintervensi', [MasterDiagnosaKebidanan::class, 'deleteintervensi']);
    // Route::post('/deletetemplate', [MasterPemeriksaanFisikController::class, 'deletetemplate']);
});
