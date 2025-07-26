<?php

namespace App\Http\Controllers\Api\Simrs\Pelayanan\Neonatus;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Pelayanan\NeonatusMedis;
use App\Models\Simrs\Pelayanan\RiwayatKehamilan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NeonatusMedisController extends Controller
{
  public function index(Request $request)
  {
    $data = NeonatusMedis::where('norm', $request->norm)->with('pegawai:id,nama')->first();
    return new JsonResponse($data, 200);
  }
    public function store(Request $request)
    {
      $user = auth()->user()->pegawai_id;
      $request->request->add(['user_input' => $user]);

      $formSave = $request->except(['riwayatKehamilan']);

      $saved = NeonatusMedis::updateOrCreate(['norm' => $request->norm],$formSave);

      if (!$saved) {
        return new JsonResponse(['message'=> 'failed'], 500);
      }

      $data = NeonatusMedis::where('norm', $request->norm)->with('pegawai:id,nama')->first();

      return new JsonResponse($data, 200);  
    }

    public function deletedata(Request $request)
    {
      
      $data = NeonatusMedis::find($request->id);

      if (!$data) {
        return new JsonResponse(['message'=> 'Data tidak ditemukan'], 500);
      }

      $del = $data->delete();

      if (!$del) {
        return new JsonResponse(['message'=> 'Failed'], 500);
      }

      return new JsonResponse(['message'=> 'Data Berhasil dihapus'], 200); 
    }

    public function storeRiwayatKehamilan(Request $request)
    {
      $user = auth()->user()->pegawai_id;
      $request->request->add(['user_input' => $user]);
      $saved = RiwayatKehamilan::create($request->all());

      if (!$saved) {
        return new JsonResponse(['message'=> 'failed'], 500);
      }

      return new JsonResponse($saved, 200);  
    }
    public function riwayatKehamilan()
    {
      $data= RiwayatKehamilan::where('norm','=', request('norm'))->get();
      return new JsonResponse($data, 200);  
    }

    public function deleteRiwayatKehamilan(Request $request)
    {
      
      $data = RiwayatKehamilan::find($request->id);

      if (!$data) {
        return new JsonResponse(['message'=> 'Data tidak ditemukan'], 500);
      }

      $del = $data->delete();

      if (!$del) {
        return new JsonResponse(['message'=> 'Failed'], 500);
      }

      return new JsonResponse(['message'=> 'Data Berhasil dihapus'], 200); 
    }
}
