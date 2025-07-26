<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Penerimaan\ListstokgudangController;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanController;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Stok\StokrealController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/farmasinew/penerimaan'
], function () {
    Route::get('/listepenerimaan', [PenerimaanController::class, 'listepenerimaan']);
    Route::get('/listepenerimaanBynomor', [PenerimaanController::class, 'listepenerimaanBynomor']);
    Route::get('/dialogpemesananobat', [PenerimaanController::class, 'listpemesananfix']);
    Route::get('/stokgudang', [ListstokgudangController::class, 'stokgudang']);
    Route::post('/simpan', [PenerimaanController::class, 'simpanpenerimaan']);
    Route::post('/kuncipenerimaan', [PenerimaanController::class, 'kuncipenerimaan']);
    Route::post('/tolak-rinci-pesanan', [PenerimaanController::class, 'tolakRinciPesanan']);
    Route::post('/buka-kunci-penerimaan', [PenerimaanController::class, 'bukaKunciPenerimaan']);

    Route::post('/simpanpenerimaanlangsung', [PenerimaanController::class, 'simpanpenerimaanlangsung']);

    Route::post('/batal-header', [PenerimaanController::class, 'batalHeader']);
    Route::post('/batal-rinci', [PenerimaanController::class, 'batalRinci']);

    Route::post('/simpaneditnomorfaktur', [PenerimaanController::class, 'simpanEditNomorFaktur']);

    Route::post('/insertsementara', [StokrealController::class, 'insertsementara']);
    Route::post('/updatestoksementara', [StokrealController::class, 'updatestoksementara']);

    // Route::get('/liststokreal', [StokrealController::class, 'liststokreal']); // ini list stok opname
    Route::get('/liststokreal', [StokrealController::class, 'liststokopname']); // ini list stok opname

    Route::get('/list-stok-sekarang', [StokrealController::class, 'listStokSekarang']);
    Route::get('/obat-mau-disesuaikan', [StokrealController::class, 'obatMauDisesuaikan']);

    Route::post('/update-stok-sekarang', [StokrealController::class, 'updatehargastok']);

    Route::get('/data-alokasi', [StokrealController::class, 'dataAlokasi']);
    Route::get('/list-ruang-ranap', [StokrealController::class, 'getRuangRanap']);
    Route::get('/list-stok-min-depo', [StokrealController::class, 'listStokMinDepo']);

    // simpan fisik
    Route::post('/simpan-fisik', [StokrealController::class, 'simpanFisik']);
    Route::post('/simpan-keterangan', [StokrealController::class, 'simpanKeterangan']);
    Route::post('/simpan-baru', [StokrealController::class, 'simpanBaru']);
    Route::post('/tutup-opname', [StokrealController::class, 'tutupOpname']);

    Route::get('/list-blangko', [StokrealController::class, 'listBlangko']);
});
