<?php

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Depo\CaripasienController;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Depo\DepoController;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Depo\EresepController;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Depo\LihatStokController;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Depo\PencarianObatController;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Depo\ResepkeluarController;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Depo\ReturpenjualanController;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/farmasinew/depo'
], function () {
    Route::get('/lihatstokgudang', [DepoController::class, 'lihatstokgudang']);
    Route::post('/simpanpermintaandepo', [DepoController::class, 'simpanpermintaandepo']);
    Route::get('/listpermintaandepo', [DepoController::class, 'listpermintaandepo']);
    Route::get('/list-permintaan-ruangan', [DepoController::class, 'listPermintaanRuangan']);

    Route::post('/kuncipermintaan', [DepoController::class, 'kuncipermintaan']);
    Route::post('/terimadistribusi', [DepoController::class, 'newterimadistribusi']);

    Route::post('/hapus-permintaan-head', [DepoController::class, 'hapusHead']);
    Route::post('/hapus-permintaan-rinci', [DepoController::class, 'hapusRinci']);

    Route::get('/lihatstokobateresep', [LihatStokController::class, 'lihatstokobateresep']);

    Route::get('/caripasienpoli', [CaripasienController::class, 'caripasienpoli']);
    Route::get('/caripasienranap', [CaripasienController::class, 'caripasienranap']);
    Route::get('/caripasienigd', [CaripasienController::class, 'caripasienigd']);

    Route::post('/resepkeluar', [ResepkeluarController::class, 'resepkeluar']);
    // Route::post('/resepkeluar', [ResepkeluarController::class, 'cekResepKeluar']);
    Route::get('/listresep', [ResepkeluarController::class, 'listresep']);
    Route::get('/get-signa', [ResepkeluarController::class, 'ambilSigna']);

    Route::post('/hapusobat', [ResepkeluarController::class, 'hapusobat']);

    Route::get('/listjenisresep', [ResepkeluarController::class, 'listjenisresep']);

    //--------------ERESEP----------------//
    // Route::get('/lihatstokobateresepBydokter', [EresepController::class, 'lihatstokobateresepBydokter']);
    // Route::get('/lihatstokobateresepBydokter', [EresepController::class, 'pencarianObatResep']);
    Route::get('/lihatstokobateresepBydokter', [PencarianObatController::class, 'pencarianObatResep']);
    Route::get('/get-single-resep', [EresepController::class, 'getSingleResep']);

    Route::get('/ambil-pegawai-farmasi', [EresepController::class, 'getPegawaiFarmasi']);

    Route::post('/pembuatanresep', [EresepController::class, 'pembuatanresep']);
    // Route::get('/listresepbydokter', [EresepController::class, 'listresepbydokter']);
    Route::get('/listresepbydokter', [EresepController::class, 'newlistresepbydokter']);
    Route::post('/kirimresep', [EresepController::class, 'kirimresep']);
    Route::get('/conterracikan', [EresepController::class, 'conterracikan']);
    // Route::post('/eresepobatkeluar', [EresepController::class, 'eresepobatkeluar']);
    Route::post('/eresepobatkeluar', [EresepController::class, 'newEresepobatkeluar']);
    Route::post('/hapus-permintaan-obat', [EresepController::class, 'hapusPermintaanObat']);
    Route::post('/terima-resep', [EresepController::class, 'terimaResep']);
    Route::post('/resep-selesai', [EresepController::class, 'resepSelesai']);
    Route::post('/tolak-resep', [EresepController::class, 'tolakResep']);
    Route::post('/isi-alasan', [EresepController::class, 'isiAlasan']);
    Route::post('/simpan-telaah-resep', [EresepController::class, 'simpanTelaahResep']);

    // hapus head
    Route::post('/hapus-permintaan-resep', [EresepController::class, 'hapusPermintaanResep']);

    // Copy Template
    Route::post('/cek-template-resep', [EresepController::class, 'cekTemplateResep']);

    // iter
    Route::post('/ambil-iter', [EresepController::class, 'ambilIter']);
    Route::post('/copy-resep', [EresepController::class, 'copyResep']);
    Route::post('/ambil-history', [EresepController::class, 'ambilHistory']);

    // pelayanan informasi Obat
    Route::post('/simpan-pelayanan-informasi-obat', [EresepController::class, 'simPelIOnfOb']);

    // list resep by dokter
    Route::get('/ambil-resep-dokter', [EresepController::class, 'getResepDokter']);

    // simpan tgl pelayanan obat
    Route::post('/simpan-tgl-pelayanan', [EresepController::class, 'simpanTglPelayananObat']);

    // ambil history laborat pasien
    Route::get('history-lab-pasien', [EresepController::class, 'historyLabPasien']);
    Route::post('simpan-persyaratan-lab', [EresepController::class, 'simpanPersyaratanLab']);
    //--------------Retur penjualan -------------//
    Route::get('/caribynoresep', [ReturpenjualanController::class, 'caribynoresep']);
    Route::post('/returpenjualan', [ReturpenjualanController::class, 'newreturpenjualan']);

    // ------- Mutasi Antar Depo ----------
    Route::get('/list-mutasi', [DepoController::class, 'listMutasi']);

    // gat for print
    Route::get('/get-for-print', [EresepController::class, 'getForPrint']);
});
