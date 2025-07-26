<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Mkodebelanjaobat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MkodebelanjaController extends Controller
{
    public function simpan(Request $request)
    {
        $simpan = Mkodebelanjaobat::updateOrCreate(
            ['kode' => $request->kode108],
            [
                'uraian' => $request->uraian108,
                'kodeB' => $request->kode50,
                'uraianB' => $request->uraian50
            ]
        );
        if (!$simpan) {
            return new JsonResponse(['message' => 'DATA GAGAL DISIMPAN...!!!'], 500);
        }
        return new JsonResponse(['message' => 'DATA BERHASIL DISIMPAN...!!!'], 200);
    }

    public function list()
    {
        $list = Mkodebelanjaobat::all();
        return new JsonResponse($list);
    }

    public function hapus(Request $request)
    {
        $cari = Mkodebelanjaobat::find($request->id);
        if (!$cari) {
            return new JsonResponse(['message' => 'DATA TIDAK DITEMUKAN....!!!'], 501);
        }
        $hapus = $cari->delete();

        if (!$hapus) {
            return new JsonResponse(['message' => 'GAGAL DIHAPUS'], 500);
        }

        return new JsonResponse(['message' => 'BERHASIL DIHAPUS'], 200);
    }
}
