<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mpekerjaan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PekerjaanController extends Controller
{
    public function index()
    {
        // $query = Mpekerjaan::all();

        $query = Cache::remember('pekerjaan', now()->addDays(7), function () {
            return Mpekerjaan::all();
        });
        return new JsonResponse($query);

    }


    public function store(Request $request)
    {
        $simpan = Mpekerjaan::updateOrCreate(['kode' => $request->kode],
            [
                'pekerjaan' => $request->pekerjaan
            ]
        );

        if(!$simpan)
        {
            return new JsonResponse(['message' => 'GAGAL DISIMPAN'], 500);
        }
            return new JsonResponse(['message' => 'BERHASIL DISIMPAN', $simpan], 200);
    }

    public function hapus(Request $request)
    {
        $data = Mpekerjaan::where(['kode' => $request->kode])->get();
       if(!count($data)){
            return new JsonResponse(['message' => 'DATA TIDAK DITEMUKAN'], 401);
        }

        foreach($data as $kunci){
            $hapus = $kunci->delete();
        }

        if(!$hapus){
            return new JsonResponse(['message' => 'GAGAL DIHAPUS'], 500);
        }
        return new JsonResponse(['message' => 'BERHASIL DIHAPUS'], 200);
    }
}
