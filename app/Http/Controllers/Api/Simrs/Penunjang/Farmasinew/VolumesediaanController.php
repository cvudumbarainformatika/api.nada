<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Mvolumesediaan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VolumesediaanController extends Controller
{
    public function simpan(Request $request)
    {
        $simpan = Mvolumesediaan::firstOrCreate(['volumesediaan' => $request->volumesediaan]);
        if (!$simpan) {
            return new JsonResponse(['message' => 'gagal disimpan...!!!'], 501);
        }
        return new JsonResponse([
            'message' => 'berhasil disimpan...!!!',
            'data' => $simpan
        ], 200);
    }

    public function hapus(Request $request)
    {
        $cari = Mvolumesediaan::where(['id' => $request->id])->get();
        if (!count($cari)) {
            return new JsonResponse(['message' => 'data tidak ditemukan'], 501);
        }
        foreach ($cari as $kunci) {
            $hapus = $kunci->delete();
        }
        if (!$hapus) {
            return new JsonResponse(['message' => 'gagal dihapus'], 500);
        }
        return new JsonResponse(['message' => 'berhasil dihapus'], 200);
    }

    public function list()
    {
        $list = Mvolumesediaan::where('volumesediaan', 'Like', '%' . request('q') . '%')->get();
        return new JsonResponse($list);
    }
}
