<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Magama;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AgamaControllerar extends Controller
{
    public function index()
    {
        // $data = Magama::query()
        // ->selectRaw('rs1 kode,rs2 keterangan,kodemap kodemapping,ketmap keteranganmapping')
        // ->where('flag','<>','1')
        // ->get();

        $data = Cache::remember('agama', now()->addDays(7), function () {
            return Magama::query()
            ->selectRaw('rs1 kode,rs2 keterangan,kodemap kodemapping,ketmap keteranganmapping')
            ->where('flag','<>','1')
            ->get();
        });

        return new JsonResponse($data);
    }

    public function store(Request $request)
    {
        $simpan = Magama::updateOrCreate(['rs1' =>$request->kode],
            [
                'rs2' => $request->agama,
                'kodemap' => $request->kodemaping,
                'ketmap' => $request->keteranganmaping
            ]
        );

        if(!$simpan){
            return new JsonResponse(['message' => 'TIDAK TERSIMPAN...!!'], 500);
        }

        return new JsonResponse(['message' => 'BERHASIL DISIMPAN', $simpan], 200);
    }
}
