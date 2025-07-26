<?php

use App\Http\Controllers\Api\Logistik\Sigarang\PegawaiController;
use App\Http\Controllers\Api\Logistik\Sigarang\RuangController;
use App\Http\Controllers\Api\Simrs\Master\Icd9Controller;
use App\Http\Controllers\Api\Simrs\Ranap\HistoryController;
use App\Http\Controllers\Api\Simrs\Ranap\RanapController;
use App\Http\Controllers\Api\Simrs\Ranap\RuanganController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/ranap/ruangan'
], function () {
    Route::get('/listruanganranap', [RuanganController::class, 'listruanganranap']);
    Route::get('/mastericd9', [Icd9Controller::class, 'mastericd9']);
    Route::get('/allNakes', [PegawaiController::class, 'allNakes']);

    // Route::get('/kunjunganpasien', [RanapController::class, 'kunjunganpasien']);
    Route::get('/kunjunganpasien', [RanapController::class, 'index']);
    Route::post('/gantidpjp', [RanapController::class, 'gantidpjp']);
    Route::get('/listjeniskasus', [RanapController::class, 'listjeniskasus']);
    Route::post('/bukalayanan', [RanapController::class, 'bukalayanan']);
    Route::post('/gantijeniskasus', [RanapController::class, 'gantijeniskasus']);

    Route::get('/data-igd', [HistoryController::class, 'layananigd']);



    Route::get('/no-surat', function () {
       $data = DB::table('rs23_nosurat')
       ->select(
        'noreg','norm','nosrtmeninggal','jamMeninggal','nokll','kddrygmenyatakan'
       )->where('noreg', '=', request('noreg'))->first();


        return response()->json($data);
    });

});
