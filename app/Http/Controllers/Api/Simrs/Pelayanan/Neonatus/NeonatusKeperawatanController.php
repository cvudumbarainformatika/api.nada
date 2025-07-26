<?php

namespace App\Http\Controllers\Api\Simrs\Pelayanan\Neonatus;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Pelayanan\NeonatusKeperawatan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NeonatusKeperawatanController extends Controller
{
  public function index(Request $request)
  {
    $data = NeonatusKeperawatan::where('norm', $request->norm)->with('pegawai:id,nama')->first();
    return new JsonResponse($data, 200);
  }
    public function store(Request $request)
    {
      $user = auth()->user()->pegawai_id;

      $request->request->add(['user_input' => $user]);
      $formSave = $request->all();
      
      $saved = NeonatusKeperawatan::updateOrCreate(['norm' => $request->norm],$formSave);

      if (!$saved) {
        return new JsonResponse(['message'=> 'failed'], 500);
      }

      $data = NeonatusKeperawatan::where('norm', $request->norm)->with('pegawai:id,nama')->first();

      return new JsonResponse($data, 200);  
    }

    public function deletedata(Request $request)
    {
      
      $data = NeonatusKeperawatan::find($request->id);

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
