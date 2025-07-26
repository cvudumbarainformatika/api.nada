<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Mjenisprodukx;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class Mjenisprodukcontroller extends Controller
{
    public function simpan(Request $request)
    {
        $simpan = Mjenisprodukx::firstOrCreate([
            'jenisproduk' => $request->jenisproduk
        ]);

        if (!$simpan) {
            return new JsonResponse(['message' => 'TIDAK TERSIMPAN...!!'], 500);
        }
        return new JsonResponse([
            'message' => 'BERHASIL DISIMPAN',
            'data' => $simpan
        ], 200);
    }

    public function list()
    {
        $list = Mjenisprodukx::all();
        return new JsonResponse($list);
    }

    public function hapus(Request $request)
    {
        $cari = Mjenisprodukx::find($request->id);
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
