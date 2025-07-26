<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Mmerk;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MerkController extends Controller
{
    public function simpan(Request $request)
    {
        $simpan = Mmerk::firstOrCreate(['merk' => $request->merk]);
        if (!$simpan) {
            return new JsonResponse(['message' => 'gagal disimpan'], 501);
        }
        return new JsonResponse(
            [
                'message' => 'berhasil disimpan',
                'data' => $simpan
            ],
            200
        );
    }

    public function hapus(Request $request)
    {
        $cari = Mmerk::find($request->id);
        if (!$cari) {
            return new JsonResponse(['message' => 'data tidak ditemukan'], 501);
        }

        $hapus = $cari->delete();
        if (!$hapus) {
            return new JsonResponse(['message' => 'gagal dihapus'], 401);
        }
        return new JsonResponse(['message' => 'berhasil dihapus'], 200);
    }

    public function list()
    {
        $list = Mmerk::all();
        return new JsonResponse($list);
    }
}
