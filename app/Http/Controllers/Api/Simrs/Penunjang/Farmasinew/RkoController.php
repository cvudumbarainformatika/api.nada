<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Mrko;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RkoController extends Controller
{
    public function simpan(Request $request)
    {
        $simpan = Mrko::firstOrCreate(
            [
                'rs1' => $request->kode,
                'rs2' => $request->namaobat,
                'rs3' => $request->satuan,
            ]
        );
        if (!$simpan) {
            return new JsonResponse(['message' => 'gagal terismpan'], 500);
        }
        return new JsonResponse(['message' => 'berhasil tersimpan'], 200);
    }

    public function hapus(Request $request)
    {
        $cari = Mrko::find($request->id);
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
        $list = Mrko::where('rs2', 'Like', '%' . request('q') . '%')->get();
        return new JsonResponse($list);
    }
}
