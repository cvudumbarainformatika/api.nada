<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mstatuspernikahan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class StatusPernikahanController extends Controller
{
    public function index()
    {
        // $data = Mstatuspernikahan::all();
        $data = Cache::remember('statuspernikahan', now()->addDays(7), function () {
            return Mstatuspernikahan::all();
        });
        return new JsonResponse($data);
    }

    public function store(Request $request)
    {
        $simpan = Mstatuspernikahan::updateOrCreate(['kode' =>$request->kode],
            [
                'statuspernikahan' => $request->statuspernikahan
            ]
        );

        if(!$simpan)
        {
            return new JsonResponse(['message' => 'DATA GAGAL DISIMPAN'], 500);
        }
            return new JsonResponse(['message' => 'DATA BERHASIL DISIMPAN' , $simpan] ,200);
    }
}
