<?php

namespace App\Http\Controllers\Api\Simrs\Pelayanan\LaporanTindakan;

use App\Http\Controllers\Controller;
use App\Models\Pegawai\Mpegawaisimpeg;
use App\Models\Simrs\Pelayanan\LaporanTindakan;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LaporanTindakanController extends Controller
{
  public function caridokter()
  {
    $dokter = Mpegawaisimpeg::select('kdpegsimrs', 'nama')
      ->where('aktif', 'AKTIF')->where('kdgroupnakes', '1')
      ->when(request('q'), function($q){
        $q->where('nama', 'LIKE', '%'.request('q').'%');
      })->limit(20)->get();


    return new JsonResponse($dokter);
    
  }
  public function listdokter()
  {
    $thumb = collect();
    Mpegawaisimpeg::select('id','kdpegsimrs', 'nama')
    ->where('aktif', 'AKTIF')->where('kdgroupnakes', '1')
    ->orderBy('id')
    ->chunk(50, function($dokters) use ($thumb){
        foreach ($dokters as $q) {
            $thumb->push($q);
        }
    });

    return new JsonResponse($thumb);
    
  }

  public function simpan(Request $request)
  {
      // $data = new LaporanTindakan();

      // $data->norm = $request->norm;
      // $data->noreg = $request->noreg;
      // $data->kddokter = $request->kddokter;
      // $data->jenistindakan = $request->jenistindakan;
      // $data->dikirimuntukpemeriksaanpa = $request->dikirimuntukpemeriksaanpa;
      // $data->tanggal = $request->tanggal;
      // $data->jammulai = $request->jammulai;
      // $data->jamselesai = $request->jamselesai;
      // $data->lamatindakan = $request->lamatindakan;
      // $data->catatankomplikasi = $request->catatankomplikasi;
      // $data->laporantindakan = $request->laporantindakan;

      // $saved = $data->save();

      $saved = LaporanTindakan::create($request->all());

      if (!$saved) {
        return new JsonResponse(['message'=> 'failed'], 500);
      }

      return new JsonResponse($saved, 200);
  }

  public function hapus(Request $request)
  {
    $data = LaporanTindakan::find($request->id);
    if (!$data) {
      return new JsonResponse(['message'=> 'Maaf Data tidak ditemukan'], 500);
    }
    $data->delete();
    return new JsonResponse(['message'=> 'Success dihapus'], 200);
  }
}
