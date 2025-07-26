<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Oksigen;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mbdrs;
use App\Models\Simrs\Master\Mtindakanapheresis;
use App\Models\Simrs\Master\Rstigapuluhtarif;
use App\Models\Simrs\Penunjang\Oksigen\Oksigen;
use App\Models\Simrs\Ranap\Kunjunganranap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OksigenController extends Controller
{

    public function getmaster()
    {

      //  $data = Cache::remember('m_tindakan_apheresis', now()->addDays(7), function () {
      //     return Mtindakanapheresis::all();
      //   });

      $data = Rstigapuluhtarif::select('*')
      ->where('rs3', 'O2#')
      ->get();
      return new JsonResponse($data);
    }

    

    public function simpandata(Request $request)
    {

      $cekKasir = DB::table('rs23')->select('rs42')->where('rs1', $request->noreg)->where('rs41', '=','1')->get();

      if (count($cekKasir) > 0) {
        return response()->json(['status' => 'failed', 'message' => 'Maaf, data pasien telah dikunci oleh kasir pada tanggal '.$cekKasir[0]->rs42], 500);
      }

      

      $userid = FormatingHelper::session_user();
      
      $tindakan = Oksigen::where(['rs1' => $request->noreg, 'rs3' => $request->tindakan])->first();
        if (!$tindakan) {
            $tindakan = new Oksigen();
            $tindakan->rs6 = $request->jumlah ?? '';
        } else {
            $tindakan->rs6 = (int)$tindakan->rs6 + (int)$request->jumlah;
        }

        $tindakan->rs1 = $request->noreg ?? '';
        $tindakan->rs2 = date('Y-m-d H:i:s');
        $tindakan->rs3 = $request->tindakan ?? '';
        $tindakan->rs4 = $request->js ?? '';
        $tindakan->rs5 = $request->jp ?? '';
        $tindakan->rs8 = $request->kdgroup_ruangan ?? '';
        $tindakan->rs9 = $userid['kodesimrs'];
        
        $tindakan->save();



     

      return new JsonResponse(
          [
              'message' => 'Permintaan Oksigen Berhasil di Simpan',
              'result' => $tindakan
          ],
          200
      );
    }

    public function hapusdata(Request $request)
    {
      $cekKasir = DB::table('rs23')->select('rs42')->where('rs1', $request->noreg)->where('rs41', '=','1')->get();

      if (count($cekKasir) > 0) {
          return response()->json(['status' => 'failed', 'message' => 'Maaf, data pasien telah dikunci oleh kasir pada tanggal '.$cekKasir[0]->rs42], 500);
      }

        $cari = Oksigen::find($request->id);
        if (!$cari) {
            return new JsonResponse(['message' => 'data tidak ditemukan'], 501);
        }

        $kunci = $cari->rs7 === '1' || $cari->rs7 === 1;
        if ($kunci) {
            return new JsonResponse(['message' => 'Maaf, Data telah dikunci !!!'], 500);
        }

        $hapus = $cari->delete();
        if (!$hapus) {
            return new JsonResponse(['message' => 'gagal dihapus'], 500);
        }
        return new JsonResponse(['message' => 'berhasil dihapus'], 200);
    }
}
