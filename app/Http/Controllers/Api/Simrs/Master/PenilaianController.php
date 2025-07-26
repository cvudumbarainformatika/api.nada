<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mpenilaian;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PenilaianController extends Controller
{
    public function index()
    {
        // $data = Mpenilaian::select('kode','group','skor','label')->get();
        $data = Mpenilaian::select('kode','desc','form','grupings')->get();

        // $data = Cache::remember('pendidikan', now()->addDays(7), function () {
        //     return Mpendidikan::query()
        //     ->selectRaw('rs1 as kode,rs2 as pendidikan')
        //     ->get();
        // });

        return new JsonResponse($data);
    }  

}
