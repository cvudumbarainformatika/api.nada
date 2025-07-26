<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Mkelasterapi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KelasterapiController extends Controller
{
    public function simpan(Request $request)
    {
        $simpan = Mkelasterapi::firstOrCreate(['kelasterapi' => $request->kelasterapi]);
        if (!$simpan) {
            return new JsonResponse(['message' => 'gagal disimpan']);
        }
        return new JsonResponse([
            'message' => 'berhasil disimpan',
            'data' => $simpan
        ]);
    }

    public function hapus(Request $request)
    {
        $cari = Mkelasterapi::find($request->id);
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
        $list = Mkelasterapi::all();
        return new JsonResponse($list);
    }
}
