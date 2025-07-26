<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mkelamin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class KelaminarrController extends Controller
{
    public function index()
    {
        // $data = Mkelamin::all();
        $data = Cache::remember('kelamin', now()->addDays(7), function () {
            return Mkelamin::all();
        });
        return new JsonResponse($data);
    }

    public function store(Request $request)
    {
        $cari = Mkelamin::where(['kode' => $request->kode])->get();
        if($cari){
            return new JsonResponse(['message' => 'DATA SUDAH ADA'], 500);
        }

        $simpan = Mkelamin::updateOrCreate(['kode' => $request->kode],
        [
            'kelamin' => $request->kelamin
        ]);

        if(!$simpan){
            return new JsonResponse(['message' => 'GAGAL DISIMPAN'], 500);
        }
        return new JsonResponse(['message' => 'BERHASIL DISIMPAN', $simpan], 200);
    }

    public function hapus(Request $request)
    {
        $cari = Mkelamin::where(['kode' => $request->kode])->get();
        if(!count($cari)){
            return new JsonResponse(['message' => 'DATA TIDAK DITEMUKAN'], 401);
        }

        foreach($cari as $kunci){
            $hapus = $kunci->delete();
        }

        if(!$hapus){
            return new JsonResponse(['message' => 'GAGAL DIHAPUS'], 500);
        }
        return new JsonResponse(['message' => 'BERHASIL DIHAPUS'], 200);
    }
}
