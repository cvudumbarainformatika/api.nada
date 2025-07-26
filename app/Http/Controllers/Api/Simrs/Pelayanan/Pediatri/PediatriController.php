<?php

namespace App\Http\Controllers\Api\Simrs\Pelayanan\Pediatri;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\MwhocdcAnak;
use App\Models\Simrs\Master\Mwhocdcv2;
use App\Models\Simrs\Pelayanan\Pediatri;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PediatriController extends Controller
{
  public function index(Request $request)
  {
    $data = Pediatri::where('norm', $request->norm)->with('pegawai:id,nama')->get();
    return new JsonResponse($data, 200);
  }
    public function store(Request $request)
    {
      $user = auth()->user()->pegawai_id;
      $request->request->add(['user_input' => $user]);

      $formSave = $request->all();


      // $saved = Pediatri::create($formSave);

      $saved = Pediatri::updateOrCreate(['noreg'=>$request->noreg], $request->all());

      if (!$saved) {
        return new JsonResponse(['message'=> 'failed'], 500);
      }

      $data = Pediatri::where('norm', $request->norm)->with('pegawai:id,nama')->get();

      return new JsonResponse($data, 200);  
    }

    public function deletedata(Request $request)
    {
      
      $data = Pediatri::find($request->id);

      if (!$data) {
        return new JsonResponse(['message'=> 'Data tidak ditemukan'], 500);
      }

      $del = $data->delete();

      if (!$del) {
        return new JsonResponse(['message'=> 'Failed'], 500);
      }

      return new JsonResponse(['message'=> 'Data Berhasil dihapus'], 200); 
    }

    public function master_who_cdc()
    {
      //  $thumb = collect();
      //   MwhocdcAnak::select('id','age_m','3rd','10rd','25rd','50rd','75rd','85rd','90rd','95rd','97rd','gender','jns','dr_tanggal')
      //   ->where('dr_tanggal', '=', null)
      //     ->orderBy('id')
      //     ->chunk(50, function ($dokters) use ($thumb) {
      //         foreach ($dokters as $q) {
      //             $thumb->push($q);
      //         }
      //     });
      //   $thumb2 = collect();
      //   Mwhocdcv2::orderBy('id')
      //     ->chunk(50, function ($dokters) use ($thumb2) {
      //         foreach ($dokters as $q) {
      //             $thumb2->push($q);
      //         } 
      //     });

      $thumb= MwhocdcAnak::select('id','age_m','3rd','10rd','25rd','50rd','75rd','85rd','90rd','95rd','97rd','gender','jns','dr_tanggal')
      ->where('dr_tanggal', '=', null)
        ->orderBy('id')->get();
        $thumb2= Mwhocdcv2::orderBy('id')->get();

          $data = (object)  [
            'v1'=>$thumb,
            'v2'=>$thumb2
          ];



        return new JsonResponse($data);
    }

    
}
