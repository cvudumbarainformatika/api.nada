<?php

use App\Http\Controllers\Api\Simrs\Bridgingeklaim\EwseklaimController;
use App\Http\Controllers\Api\Simrs\Bridgingeklaim\ProcedureController;
use App\Http\Controllers\Api\Simrs\Pelayanan\Anamnesis\AnamnesisController;
use App\Http\Controllers\Api\Simrs\Pelayanan\Diagnosa\DiagnosaGiziController;
use App\Http\Controllers\Api\Simrs\Pelayanan\Diagnosa\DiagnosaKebidananController;
use App\Http\Controllers\Api\Simrs\Pelayanan\Diagnosa\DiagnosaKeperawatanController;
use App\Http\Controllers\Api\Simrs\Pelayanan\Diagnosa\DiagnosatransController;
use App\Http\Controllers\Api\Simrs\Pelayanan\Edukasi\EdukasiController;
use App\Http\Controllers\Api\Simrs\Pelayanan\Edukasi\ImplementasiEdukasiController;
use App\Http\Controllers\Api\Simrs\Pelayanan\Eresep\EresepController;
use App\Http\Controllers\Api\Simrs\Pelayanan\Pemeriksaanfisik\PemeriksaanfisikController;
use App\Http\Controllers\Api\Simrs\Pelayanan\PemeriksaanRMKhusus\PemeriksaankhususMataController;
use App\Http\Controllers\Api\Simrs\Pelayanan\Tindakan\TindakanController;
use App\Http\Controllers\Api\Simrs\Penunjang\Diet\DietController;
use App\Http\Controllers\Api\Simrs\Planing\BridbpjsplanController;
use App\Http\Controllers\Api\Simrs\Planing\PlaningController;
use App\Http\Controllers\Api\Simrs\Rajal\PoliController;
use App\Http\Controllers\Api\Simrs\Sharing\SharingRajalController;
use App\Models\Simrs\Sharing\SharingTrans;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => 'auth:api',
    // 'middleware' => 'jwt.verify',
    'prefix' => 'simrs/pelayanan'
], function () {
    Route::post('/simpananamnesis', [AnamnesisController::class, 'simpananamnesis']);
    Route::post('/hapusanamnesis', [AnamnesisController::class, 'hapusanamnesis']);
    Route::get('/historyanamnesis', [AnamnesisController::class, 'historyanamnesis']);

    Route::post('/simpanpemeriksaanfisik', [PemeriksaanfisikController::class, 'simpan']);
    Route::post('/hapuspemeriksaanfisik', [PemeriksaanfisikController::class, 'hapuspemeriksaanfisik']);
    Route::post('/simpangambar', [PemeriksaanfisikController::class, 'simpangambar']);
    Route::post('/hapusgambar', [PemeriksaanfisikController::class, 'hapusgambar']);
    Route::get('/historypemeriksaanfisik', [PemeriksaanfisikController::class, 'historypemeriksaanfisik']);

    Route::post('/hapusdiagnosa', [DiagnosatransController::class, 'hapusdiagnosa']);
    Route::post('/simpandiagnosa', [DiagnosatransController::class, 'simpandiagnosa']);
    Route::get('/listdiagnosa', [DiagnosatransController::class, 'listdiagnosa']);

    Route::get('/diagnosakeperawatan', [DiagnosaKeperawatanController::class, 'diagnosakeperawatan']);
    Route::get('/listdiagnosakeperawatan', [DiagnosaKeperawatanController::class, 'listdiagnosakeperawatan']);
    Route::post('/simpandiagnosakeperawatan', [DiagnosaKeperawatanController::class, 'simpandiagnosakeperawatan']);
    Route::post('/deletediagnosakeperawatan', [DiagnosaKeperawatanController::class, 'deletediagnosakeperawatan']);

    Route::get('/diagnosakebidanan', [DiagnosaKebidananController::class, 'diagnosakebidanan']);
    Route::post('/simpandiagnosakebidanan', [DiagnosaKebidananController::class, 'simpandiagnosakebidanan']);
    Route::post('/deletediagnosakebidanan', [DiagnosaKebidananController::class, 'deletediagnosakebidanan']);

    Route::get('/diagnosagizi', [DiagnosaGiziController::class, 'diagnosagizi']);
    Route::post('/simpandiagnosagizi', [DiagnosaGiziController::class, 'simpandiagnosagizi']);
    Route::post('/deletediagnosagizi', [DiagnosaGiziController::class, 'deletediagnosagizi']);

    Route::get('/dialogtindakanpoli', [TindakanController::class, 'dialogtindakanpoli']);
    Route::get('/dialogtindakanIgd', [TindakanController::class, 'dialogtindakanIgd']);
    Route::get('/dialogoperasi', [TindakanController::class, 'dialogoperasi']);
    Route::get('/notatindakan', [TindakanController::class, 'notatindakan']);
    Route::get('/notatindakanIgd', [TindakanController::class, 'notatindakanIgd']);
    Route::get('/notatindakanranap', [TindakanController::class, 'notatindakanranap']);
    Route::post('/simpantindakanpoli', [TindakanController::class, 'simpantindakanpoli']);
    Route::post('/simpantindakanIgd', [TindakanController::class, 'simpantindakanIgd']);
    Route::post('/hapustindakanpoli', [TindakanController::class, 'hapustindakanpoli']);
    Route::post('/hapustindakanIgd', [TindakanController::class, 'hapustindakanIgd']);
    Route::post('/simpandokumentindakanpoli', [TindakanController::class, 'simpandokumentindakanpoli']);
    Route::post('/hapusdokumentindakan', [TindakanController::class, 'hapusdokumentindakan']);

    // Simpan keterangan tidakan saja
    Route::post('/simpan-ket-tindakan', [TindakanController::class, 'simpanKetTindakan']);

    Route::post('/ewseklaimrajal_newclaim', [EwseklaimController::class, 'ewseklaimrajal_newclaim']);
    Route::get('/caridiagnosa', [EwseklaimController::class, 'caridiagnosa']);
    Route::get('/carisimulasi', [EwseklaimController::class, 'carisimulasi']);

    Route::get('/cari-sep', [PlaningController::class, 'cariSep']);
    Route::get('/mpalningrajal', [PlaningController::class, 'mpalningrajal']);
    Route::get('/mpoli', [PlaningController::class, 'mpoli']);

    Route::post('/simpanplaningpasien', [PlaningController::class, 'simpanplaningpasien']);
    Route::post('/update-planning-pasien', [PlaningController::class, 'updatePlanningPasien']);
    Route::post('/hapusplaningpasien', [PlaningController::class, 'hapusplaningpasien']);

    // jawaban konsul
    Route::post('/update-jawaban-konsul', [PlaningController::class, 'updatePengantarAtauJawabanKonsul']);
    Route::post('/update-dibaca', [PlaningController::class, 'updateDibaca']);
    Route::post('/update-noreg', [PlaningController::class, 'updateNoreg']);

    Route::get('/faskes', [BridbpjsplanController::class, 'faskes']);
    Route::get('/polibpjs', [BridbpjsplanController::class, 'polibpjs']);
    Route::get('/diag-prb', [BridbpjsplanController::class, 'diagPrb']);

    Route::post('/simpanedukasi', [EdukasiController::class, 'simpanedukasi']);
    Route::post('/hapusedukasi', [EdukasiController::class, 'hapusedukasi']);
    Route::get('/mpenerimaedukasi', [EdukasiController::class, 'mpenerimaedukasi']);
    Route::get('/mkebutuhanedukasi', [EdukasiController::class, 'mkebutuhanedukasi']);

    Route::post('/simpanimplementasi-edukasi', [ImplementasiEdukasiController::class, 'saveData']);
    Route::post('/hapusimplementasi-edukasi', [ImplementasiEdukasiController::class, 'hapusData']);
    Route::get('/simpanimplementasi-edukasi/list', [ImplementasiEdukasiController::class, 'list']);

    Route::get('/listdokter', [PoliController::class, 'listdokter']);
    Route::post('/gantidpjp', [PoliController::class, 'gantidpjp']);
    Route::post('/gantimemo', [PoliController::class, 'gantimemo']);

    Route::post('/pemeriksaanmatakhusus', [PemeriksaankhususMataController::class, 'pemeriksaanmatakhusus']);

    Route::get('/bridbpjslistrujukan', [BridbpjsplanController::class, 'bridbpjslistrujukan']);
    Route::get('/icare', [PoliController::class, 'icare']);

    Route::get('/masterdiet', [DietController::class, 'masterdiet']);
    Route::post('/simpandiet', [DietController::class, 'simpandiet']);
    Route::post('/hapusdiet', [DietController::class, 'hapusdiet']);

    Route::get('/dialogmaster', [SharingRajalController::class, 'dialogmaster']);
    Route::post('/simpansharing', [SharingRajalController::class, 'simpansharing']);
    Route::post('/updatesimpansharing', [SharingRajalController::class, 'updatesimpansharing']);
    Route::get('/listpermintaansharing', [SharingRajalController::class, 'listpermintaansharing']);

    Route::post('/simpanprocedure', [ProcedureController::class, 'simpanprocedure']);
    Route::get('/listprocedure', [ProcedureController::class, 'listprocedure']);
    Route::post('/hapusprocedure', [ProcedureController::class, 'hapusprocedure']);

    // Route::get('/cariprocedure', [EwseklaimController::class, 'cariprocedure']);

    Route::get('/listresepbynorm', [EresepController::class, 'listresepbynorm']);
    Route::get('/lihatstokobateresepBydokter', [EresepController::class, 'lihatstokobateresepBydokter']);
    // Route::post('/copiresep', [EresepController::class, 'copiresep']);
    Route::post('/copiresep', [EresepController::class, 'newCopiResep']);
    Route::post('/simpan-edit-obat', [EresepController::class, 'editResep']);
    Route::get('/listresepbynoreg', [EresepController::class, 'listresepbynoreg']);

    Route::post('/kirimpenjaminan', [PoliController::class, 'kirimpenjaminan']);
});
