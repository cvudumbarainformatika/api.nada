<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Verif;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\RencanabeliH;
use App\Models\Simrs\Penunjang\Farmasinew\RencanabeliR;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerifController extends Controller
{
    public function verifpemesananrinci(Request $request)
    {
        $wew = FormatingHelper::session_user();
        $kdpegsimrs = $wew['kodesimrs'];
        $verifrinci = RencanabeliR::where('no_rencbeliobat', $request->no_rencbeliobat)
            ->where('kdobat', $request->kdobat)
            ->first();

        $verifrinci->jumlah_diverif = $request->jumlah_diverif;
        $verifrinci->user_verif = $kdpegsimrs;
        $verifrinci->waktu_verif = date('Y-m-d H:i:s');
        if ($request->jumlah_diverif === 0 || $request->jumlah_diverif === '0') {
            $verifrinci->flag = '1';
        }
        $verifrinci->save();
        return new JsonResponse(
            [
                'message' => 'Data Berhasil Disimpan',
                'noperencanaan' => $request->no_rencbeliobat,
                'kdobat' => $request->kdobat,
                'data' => $verifrinci
            ],
            200
        );
    }

    public function verifpemesanheder(Request $request)
    {
        $cek = RencanabeliR::where('user_verif', '')->where('no_rencbeliobat', $request->no_rencbeliobat)->count();
        if ($cek > 0) {
            return new JsonResponse(['message' => 'Ada Obat Yang Belum diverif...!!!'], 500);
        }
        $wew = FormatingHelper::session_user();
        $kdpegsimrs = $wew['kodesimrs'];
        $verifrinci = RencanabeliH::where('no_rencbeliobat', $request->no_rencbeliobat)
            ->first();
        $verifrinci->tglverif = date('Y-m-d H:i:s');
        $verifrinci->userverif = $kdpegsimrs;
        $verifrinci->flag = '2';
        $verifrinci->save();
        return new JsonResponse(
            [
                'message' => 'Data Berhasil Terverif',
                'noperencanaan' => $request->no_rencbeliobat
            ],
            200
        );
    }
}
