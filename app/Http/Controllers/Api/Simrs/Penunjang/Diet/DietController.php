<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Diet;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mdiet;
use App\Models\Simrs\Penunjang\DietTrans;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DietController extends Controller
{
    public function masterdiet()
    {
        $listdiet = Mdiet::groupby('rs2')->get();
        return new JsonResponse($listdiet);
    }

    public function simpandiet(Request $request)
    {
        $user = FormatingHelper::session_user();
        $simpan = DietTrans::create(
            [
                'noreg' => $request->noreg,
                'tgl' => date('Y-m-d H:i:s'),
                'diet' => $request->diet,
                'poli' => $request->kodepoli,
                'users' => $user['kodesimrs'],
                'assessmen' => $request->asessmen
            ]
        );
        if (!$simpan) {
            return new JsonResponse(['message' => 'Maaf Data Gagal Disimpan...!!!'], 500);
        }
        return new JsonResponse(['message' => 'Data Berhasil Disimpan...!!!', 'result' => $simpan], 200);
    }

    public function hapusdiet(Request $request)
    {
        $cari = DietTrans::find($request->id);
        $hapus = $cari->delete();
        if (!$hapus) {
            return new JsonResponse(['message' => 'gagal dihapus'], 500);
        }
        return new JsonResponse(['message' => 'berhasil dihapus'], 200);
    }
}
