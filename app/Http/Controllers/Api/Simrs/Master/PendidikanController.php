<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mpendidikan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PendidikanController extends Controller
{
    public function index()
    {
        // $data = Mpendidikan::query()
        // ->selectRaw('rs1 as kode,rs2 as pendidikan')
        // ->get();

        $data = Cache::remember('pendidikan', now()->addDays(7), function () {
            return Mpendidikan::query()
            ->selectRaw('rs1 as kode,rs2 as pendidikan')
            ->get();
        });

        return new JsonResponse($data);
    }

    public function store(Request $request)
    {
        $simpan = Mpendidikan::updateOrCreate(['rs1' => $request->kode],

            [
                'rs1' => $request->kode,
                'rs2' => $request->pendidikan,
                'rs3' => $request->mapkode,
                'rs4' => $request->mapket
            ]

        );

        if(!$simpan){
            return new JsonResponse(['message' => 'TIDAK TERSIMPAN...!!!'], 500);
        }

        return new JsonResponse(['message' => 'BERHASIL TERSIMPAN...!!!',  $simpan], 200);
    }

}
