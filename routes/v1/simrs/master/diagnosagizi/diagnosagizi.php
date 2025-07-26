<?php

use App\Http\Controllers\Api\Simrs\Master\DiagnosaGizi\MasterDiagnosaGizi;
use App\Http\Controllers\Api\Simrs\Master\DiagnosaKebidanan\MasterDiagnosaKebidanan;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/master/diagnosagizi'
], function () {
    Route::post('/store', [MasterDiagnosaGizi::class, 'store']);
    Route::get('/getall', [MasterDiagnosaGizi::class, 'index']);
    Route::post('/storeintervensi', [MasterDiagnosaGizi::class, 'storeintervensi']);
    Route::post('/delete', [MasterDiagnosaGizi::class, 'delete']);
    Route::post('/deleteintervensi', [MasterDiagnosaGizi::class, 'deleteintervensi']);
});
