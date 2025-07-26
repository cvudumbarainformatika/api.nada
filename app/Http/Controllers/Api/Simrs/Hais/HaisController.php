<?php

namespace App\Http\Controllers\Api\Simrs\Hais;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Hais\HaisTrans;
use App\Models\Simrs\Master\Mhais;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HaisController extends Controller
{
    public function getmaster()
    {
        // $data = Mhais::all();
        $data = Cache::remember('m_hais', now()->addDays(7), function () {
            return Mhais::all();
        });
        return new JsonResponse($data);
    }

    public function simpandata(Request $request)
    {

        $user = FormatingHelper::session_user();
        

       $hais = new HaisTrans();
       $hais->tgl = date("Y-m-d H:i:s");
       $hais->noreg = $request->noreg ?? '';
       $hais->isk = $request->isk ?? '';
       $hais->iadp = $request->iadp ?? '';
       $hais->ido = $request->ido ?? '';
       $hais->plebitis = $request->plebitis ?? '';
       $hais->vap = $request->vap ?? '';
       $hais->ruang = $request->kodepoli ?? '';
       $hais->ket = $request->antibiotik ?? '';
       $hais->kultur = $request->kultur ?? '';
       $hais->user = $user['kodesimrs'] ?? '';
       $hais->save();

       return new JsonResponse(['message' => 'Data Berhasil Disimpan', 'result' => $hais], 200);
    }

    public function hapusdata(Request $request)
    {
        $cek = HaisTrans::find($request->id);
        if (!$cek) {
          return new JsonResponse(['message' => 'data tidak ditemukan'], 500);
        }

        $hapus = $cek->delete();
        if (!$hapus) {
          return new JsonResponse(['message' => 'gagal dihapus'], 500);
        }
        return new JsonResponse(['message' => 'berhasil dihapus'], 200);
    }
}
