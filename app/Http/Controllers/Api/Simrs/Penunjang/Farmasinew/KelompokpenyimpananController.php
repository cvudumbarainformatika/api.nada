<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Mkelompokpenyimpanan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KelompokpenyimpananController extends Controller
{
    public function simpan(Request $request)
    {
        $simpan = Mkelompokpenyimpanan::firstOrCreate(['kelompokpenyimpanan' => $request->kelompokpenyimpanan]);
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
        $cari = Mkelompokpenyimpanan::find($request->id);
        if (!$cari) {
            return new JsonResponse(['message' => 'data tidak ditemukan'], 501);
        }
        $hapus = $cari->delete();
        if (!$hapus) {
            return new JsonResponse(['message' => 'gagal dihapus'], 500);
        }
        return new JsonResponse(['message' => 'berhasil dihapus'], 200);
    }

    public function list()
    {
        $list = Mkelompokpenyimpanan::all();
        return new JsonResponse($list);
    }
}
