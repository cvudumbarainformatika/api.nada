<?php

use App\Http\Controllers\Api\Simrs\Master\BridgingbpjsController;
use App\Http\Controllers\Api\Simrs\Master\listsepController;
use App\Http\Controllers\Api\Simrs\Pendaftaran\Rajal\Bridbpjscontroller;
use App\Http\Controllers\Api\Simrs\Pendaftaran\Rajal\DaftarrajalController;
use App\Models\Simrs\Pendaftaran\Rajalumum\Daftarrajalumum;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/bridgingbpjs/pendaftaran'
], function () {
    Route::post('/cekpsertabpjsbynoka', [BridgingbpjsController::class, 'cekpsertabpjsbynoka']);
    Route::post('/cekpsertabpjsbynik', [BridgingbpjsController::class, 'cekpsertabpjsbynik']);
    Route::post('/listrujukanpcare', [BridgingbpjsController::class, 'listrujukanpcare']);
    Route::post('/listrujukanrs', [BridgingbpjsController::class, 'listrujukanrs']);
    Route::post('/listsepmrs', [listsepController::class, 'listsepmrs']);
    Route::post('/diagnosabybpjs', [BridgingbpjsController::class, 'diagnosabybpjs']);
    Route::post('/faskesasalbpjs', [BridgingbpjsController::class, 'faskesasalbpjs']);
    Route::post('/dpjpbpjs', [BridgingbpjsController::class, 'dpjpbpjs']);
    Route::post('/jadwaldokter', [BridgingbpjsController::class, 'jadwaldokter']);
    Route::post('/cekfingerprint', [BridgingbpjsController::class, 'cekfingerprint']);
    Route::post('/provinsibpjs', [BridgingbpjsController::class, 'provinsibpjs']);
    Route::post('/kabupatenbpjs', [BridgingbpjsController::class, 'kabupatenbpjs']);
    Route::post('/kecamatanbpjs', [BridgingbpjsController::class, 'kecamatanbpjs']);
    Route::post('/ceksuplesibpjs', [BridgingbpjsController::class, 'ceksuplesibpjs']);
    Route::post('/rencanakontrolbpjs', [BridgingbpjsController::class, 'rencanakontrolbpjs']);
    Route::post('/carirujukanpcarebynorujukan', [BridgingbpjsController::class, 'carirujukanpcarebynorujukan']);
    Route::post('/listpoli', [BridgingbpjsController::class, 'listpoli']);


    Route::post('/createsep', [Bridbpjscontroller::class, 'createsep']);
    Route::post('/hapussep', [Bridbpjscontroller::class, 'hapussep']);
    Route::post('/pengajuansep', [Bridbpjscontroller::class, 'pengajuansep']);

    Route::post('/re-createsep', [Bridbpjscontroller::class, 'reCreateSep']); // re-create sep

    Route::post('/carisep', [Bridbpjscontroller::class, 'carisep']); // re-create sep

});
