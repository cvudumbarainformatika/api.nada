<?php

namespace App\Http\Controllers\Api\Siasik\Akuntansi\Laporan;

use App\Http\Controllers\Controller;
use App\Models\Siasik\Akuntansi\Laporan\LpSal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LpsalController extends Controller
{
    public function index(){
        $time = strtotime("-1 year", time());
        $year=date('Y');
        $yearx=date('Y', $time);
        $data=LpSal::whereBetween('tahun', [$yearx, $year])->get();
        return new JsonResponse($data);
    }
    public function save(Request $request){
        $saved = LpSal::create($request->all());
        return new JsonResponse(
            [
                'message' => 'Data Berhasil disimpan...!!!',
                'result' => $saved
            ], 200);
    }
}
