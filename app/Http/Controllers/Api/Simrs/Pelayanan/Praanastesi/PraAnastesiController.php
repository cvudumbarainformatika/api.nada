<?php

namespace App\Http\Controllers\Api\Simrs\Pelayanan\Praanastesi;

use App\Http\Controllers\Controller;
use App\Models\KunjunganPoli;
use App\Models\Simrs\Master\MpraAnastesi;
use App\Models\Simrs\Pelayanan\PraAnastesi;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PraAnastesiController extends Controller
{
  public function master()
  {
     $data = MpraAnastesi::all();
     return new JsonResponse($data);
  }

  public function savedata(Request $request)
  {
      $data = null;
      if ($request->has('id')) {
        $data = PraAnastesi::find($request->id);
      } else{
        $data = new PraAnastesi();
      }

      $data->abdomen = $request->abdomen;
      $data->catatan = $request->catatan;
      $data->ekstremitas = $request->ekstremitas;
      $data->jantung = $request->jantung;
      $data->keteranganKajianSistem = $request->keteranganKajianSistem;
      $data->keteranganLaborat = $request->keteranganLaborat;
      $data->neurologi = $request->neurologi;
      $data->noreg = $request->noreg;
      $data->norm = $request->norm;
      $data->paruparu = $request->paruparu;
      $data->perencanaan = $request->perencanaan;
      $data->skorMallampati = $request->skorMallampati;
      $data->tulangbelakang = $request->tulangbelakang;
      $data->asaClasification = $request->asaClasification;
      $data->kajianSistem = $request->kajianSistem;
      $data->laboratorium = $request->laboratorium;
      $data->penyulitAnastesi = $request->penyulitAnastesi;
      $data->pegawai_id = auth()->user()->pegawai_id;

      // baru
      $data->teknikAnestesia = $request->teknikAnestesia;
      $data->teknikKhusus = $request->teknikKhusus;
      $data->pascaAnastesi = $request->pascaAnastesi;

      $data->keteranganLainlainRawatKhusus = $request->keteranganLainlainRawatKhusus;

      $data->mulaiPuasaTgl = $request->mulaiPuasaTgl;
      $data->mulaiPuasajam = $request->mulaiPuasajam;
      $data->preMedikasiTgl = $request->preMedikasiTgl;
      $data->preMedikasiJam = $request->preMedikasiJam;
      $data->transKeKamarBedahTgl = $request->transKeKamarBedahTgl;
      $data->transKeKamarBedahJam = $request->transKeKamarBedahJam;
      $data->rencanaOperasiTgl = $request->rencanaOperasiTgl;
      $data->rencanaOperasiJam = $request->rencanaOperasiJam;
        
      $data->catatanPersiapanPraAnastesi = $request->catatanPersiapanPraAnastesi;
      $data->kolomTindakLanjut = $request->kolomTindakLanjut;
      $data->kdruang = $request->kodepoli ?? null;

      
      $saved = $data->save();

      if (!$saved) {
        return new JsonResponse(['message'=> 'ada kesalahan'], 500);
      }

      return new JsonResponse($data);

  }

  public function deletedata(Request $request)
  {
     $id = $request->id;
     $data = PraAnastesi::find($id);
     if (!$data) {
        return new JsonResponse(['message'=> 'Data tidak ditemukan'], 500);
     }
     $data->delete();
     return new JsonResponse(['message'=> 'Sukses dihapus!'],200);
  }

  public function getPraAnastesiKunjunganPoli()
  {
      $data = PraAnastesi::where('noreg','=', request('noreg'))->get();
      if (!$data) {
        return new JsonResponse(['message'=> 'Ada Kesalahan'], 500);
      }

      return new JsonResponse($data);
  }

  public function getKunjunganRajalLatest()
  {
     $norm = request('norm');

     $cekRajal = DB::table('rs17')->select('rs1','rs1 as noreg')
      ->where('rs2', $norm)
      ->where('rs8', 'POL001')
      ->where('rs19', '=', '1')
      ->orderBy('rs3', 'desc')
      ->first();

      $data = [];

      if ($cekRajal) {
        $data = PraAnastesi::where('norm', $norm)->where('noreg', $cekRajal->rs1)
        ->with(['pemeriksaanfisik', 
        'diagnosa' => function ($d) {
            $d->with('masterdiagnosa');
        },
        'kunjunganpoli' => function ($k) {
            $k->select('rs17.rs1', 'rs17.rs9 as kodedokter', 'rs21.rs2 as dokter')
            ->leftjoin('rs21', 'rs21.rs1', '=', 'rs17.rs9'); //dokter
          }
        ])
        ->orderBy('created_at', 'desc')->limit(1)->get();
      }

      return new JsonResponse($data);

  }
}
