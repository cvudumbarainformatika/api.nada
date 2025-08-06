<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\BentuksediaanController;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\KandungannamagenerikController;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\KekuatandosisController;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\KelasterapiController;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\KelompokpenyimpananController;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\MasterSignaController;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\MerkController;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\MjenisperbekalanController;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Mjenisprodukcontroller;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\MkodebelanjaController;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Mruangancontroller;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\ObatnewController;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\RkoController;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\VolumesediaanController;
use App\Models\Pegawai\Akses\Role;
use App\Models\Simrs\Penunjang\Farmasinew\Mkelasterapi;
use App\Models\Simrs\Penunjang\Farmasinew\Mkelompokpenyimpanan;
use App\Models\Simrs\Penunjang\Farmasinew\Mmerk;
use App\Models\Simrs\Penunjang\Farmasinew\Mrko;
use Illuminate\Support\Facades\Route;


Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/farmasi/master'
], function () {
    Route::post('/simpanjenisperbekalan', [MjenisperbekalanController::class, 'simpan']);
    Route::post('/hapusjenisperbekalan', [MjenisperbekalanController::class, 'hapus']);
    Route::get('/listmjenisperbekalan', [MjenisperbekalanController::class, 'list']);

    Route::post('/simpankodebelanjaobat', [MkodebelanjaController::class, 'simpan']);
    Route::post('/hapuskodebelanjaobat', [MkodebelanjaController::class, 'hapus']);
    Route::get('/listkodebelanjaobat', [MkodebelanjaController::class, 'list']);

    Route::post('/simpankandungan_namagenerik', [KandungannamagenerikController::class, 'simpan']);
    Route::post('/hapuskandungan_namagenerik', [KandungannamagenerikController::class, 'hapus']);
    Route::get('/listkandungan_namagenerik', [KandungannamagenerikController::class, 'list']);

    Route::post('/simpanbentuksediaan', [BentuksediaanController::class, 'simpan']);
    Route::post('/hapusbentuksediaan', [BentuksediaanController::class, 'hapus']);
    Route::get('/listbentuksediaan', [BentuksediaanController::class, 'list']);

    Route::post('/simpankekuatandosis', [KekuatandosisController::class, 'simpan']);
    Route::post('/hapuskekuatandosis', [KekuatandosisController::class, 'hapus']);
    Route::get('/listkekuatandosis', [KekuatandosisController::class, 'list']);

    Route::post('/simpanvolumesediaan', [VolumesediaanController::class, 'simpan']);
    Route::post('/hapusvolumesediaan', [VolumesediaanController::class, 'hapus']);
    Route::get('/listvolumesediaan', [VolumesediaanController::class, 'list']);

    Route::post('/simpanobat', [ObatnewController::class, 'simpan']);
    Route::post('/hapusobat', [ObatnewController::class, 'hapus']);
    Route::get('/listobat', [ObatnewController::class, 'list']);

    // hapus maping list kelas terapi
    Route::post('/hapus-maping-terapi', [ObatnewController::class, 'hapusMapingTerapi']);
    // hapus maping indikasi
    Route::post('/hapus-maping-indikasi', [ObatnewController::class, 'hapusMapingIndikasi']);
    // maping obat bpjs
    Route::get('/maping-bpjs', [ObatnewController::class, 'mapingBpjs']);
    Route::post('/insert-maping-bpjs', [ObatnewController::class, 'insertMapingBpjs']);


    Route::post('/simpanmerk', [MerkController::class, 'simpan']);
    Route::post('/hapusmerk', [MerkController::class, 'hapus']);
    Route::get('/listmerk', [MerkController::class, 'list']);

    Route::post('/simpankelompokpenyimpanan', [KelompokpenyimpananController::class, 'simpan']);
    Route::post('/hapuskelompokpenyimpanan', [KelompokpenyimpananController::class, 'hapus']);
    Route::get('/listkelompokpenyimpanan', [KelompokpenyimpananController::class, 'list']);

    Route::post('/simpankelasterapi', [KelasterapiController::class, 'simpan']);
    Route::post('/hapuskelasterapi', [KelasterapiController::class, 'hapus']);
    Route::get('/listkelasterapi', [KelasterapiController::class, 'list']);

    Route::post('/simpanrko', [RkoController::class, 'simpan']);
    Route::post('/hapusrko', [RkoController::class, 'hapus']);
    Route::get('/listrko', [RkoController::class, 'list']);

    Route::post('/simpanjenisproduk', [Mjenisprodukcontroller::class, 'simpan']);
    Route::post('/hapusjenisproduk', [Mjenisprodukcontroller::class, 'hapus']);
    Route::get('/listjenisproduk', [Mjenisprodukcontroller::class, 'list']);

    Route::get('/listruangan', [Mruangancontroller::class, 'listruangan']);

    // signa

    Route::post('/simpan-signa', [MasterSignaController::class, 'simpan']);
    Route::post('/hapus-signa', [MasterSignaController::class, 'hapus']);
    Route::get('/list-signa', [MasterSignaController::class, 'list']);
});
