<?php

use App\Http\Controllers\Api\Simrs\Master\Diagnosakeperawatan\MasterDiagnosaKeperawatan;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/master/diagnosakeperawatan'
], function () {
    Route::post('/store', [MasterDiagnosaKeperawatan::class, 'store']);
    Route::post('/storeintervensi', [MasterDiagnosaKeperawatan::class, 'storeintervensi']);
    Route::get('/getall', [MasterDiagnosaKeperawatan::class, 'index']);
    Route::post('/delete', [MasterDiagnosaKeperawatan::class, 'delete']);
    Route::post('/deleteintervensi', [MasterDiagnosaKeperawatan::class, 'deleteintervensi']);
    // Route::post('/deletetemplate', [MasterPemeriksaanFisikController::class, 'deletetemplate']);
});
