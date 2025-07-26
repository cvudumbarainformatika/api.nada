<?php

namespace App\Http\Controllers\Api\Simrs\Ranap\Pelayanan;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\DischargePlanning\DischargePlanning;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnatomyController extends Controller
{
    
    public function getmasteranatomys()
    {
        $data = DB::table('mpemeriksaanfisik')
        ->select('nama')->get();
        return new JsonResponse($data);
    }
    public function simpandata(Request $request)
    {

      $user = Pegawai::find(auth()->user()->pegawai_id);
      $kdpegsimrs = $user->kdpegsimrs;
      //  return $anamnesis;
       



       $data = DischargePlanning::create([
        'rs1' => $request->noreg,
        'rs2' => $request->norm,
        'rs3' => date('Y-m-d H:i:s'),
        'rs4' => $request->anjuran,
        'rs5' => $request->dokter,
        'rs6' => $request->ruangan,
        'rs7'=> $request->kodesistembayar,
        'kdruang'=> $request->kdruang,
        'lamaPerawatan'=> $request->lamaPerawatan,
        'tglRencanaPlg'=> $request->tglRencanaPlg,
        'bayiTglBersama'=> $request->bayiTglBersama,
        'pldiRumah' => $request->pldiRumah,
        'transportasi' => $request->transportasi,
        'prognosis' => $request->prognosis,
        'user' => $kdpegsimrs

       ]);

       if (!$data) {
        return new JsonResponse([
          'success' => false,
          'message' => 'Gagal menyimpan data'
        ]);
       }

       return new JsonResponse([
        'success' => true,
        'message' => 'success',
        'result' => $data
       ]);
    }


   public function hapusdata(Request $request)
   {
       $cari = DischargePlanning::find($request->id);
       if (!$cari) {
         return new JsonResponse(['message' => 'data tidak ditemukan'], 501);
       }
       $cari->delete();
       return new JsonResponse(['message' => 'berhasil dihapus'], 200);
   }


    
}
