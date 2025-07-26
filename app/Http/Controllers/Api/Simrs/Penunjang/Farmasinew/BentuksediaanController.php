<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Mbentuksediaan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BentuksediaanController extends Controller
{
    public function simpan(Request $request)
    {
        $simpan = Mbentuksediaan::firstOrCreate(['bentuksediaan' => $request->bentuksediaan]);
        if (!$simpan) {
            return new JsonResponse(['message' => 'gagal disimpan'], 500);
        }
        return new JsonResponse([
            'message' => 'berhasil disimpan',
            'data' => $simpan
        ], 200);
    }

    public function hapus(Request $request)
    {
        $cari = Mbentuksediaan::find($request->id);
        if (!$cari) {
            return new JsonResponse(['message' => 'data tidak ditemukan'], 501);
        }
        $hapus = $cari->delete();
        if (!$hapus) {
            return new JsonResponse(['message' => 'gagal dihapus'], 501);
        }
        return new JsonResponse(['message' => 'berhasil dihapus'], 200);
    }

    public function list()
    {
        $list = Mbentuksediaan::where('bentuksediaan', 'LIKE', '%' . request('q') . '%')->get();
        return new JsonResponse($list);
    }
}
