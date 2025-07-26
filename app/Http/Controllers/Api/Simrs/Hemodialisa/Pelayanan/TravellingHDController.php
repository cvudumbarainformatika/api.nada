<?php

namespace App\Http\Controllers\Api\Simrs\Hemodialisa\Pelayanan;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Hemodialisa\HdTravelling;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TravellingHDController extends Controller
{
    public function store(Request $request)
    {
        $request['tgl'] = date("Y-m-d");
        try {
            $travelling = HdTravelling::create($request->all());

            return new JsonResponse([
                'message' => 'Data berhasil disimpan',
                'data' => $travelling
            ], 201);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => 'Terjadi kesalahan saat menyimpan data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function list()
    {
        $data = HdTravelling::where('norm', request('norm'))->orderBy('created_at', 'desc')->get();

        return new JsonResponse(['data' => $data], 200);
    }
    public function hapus(Request $request)
    {
        $data = HdTravelling::find($request->id);

        if (!$data) return new JsonResponse(['message' => 'Data Tidak Ditemukan'], 410);
        $data->delete();

        return new JsonResponse(['message' => 'Data Berhasil dihapus', 'data' => $data], 200);
    }
}
