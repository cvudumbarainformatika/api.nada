<?php

namespace App\Http\Controllers\Api\Simrs\Pelayanan\PemeriksaanRMKhusus;

use App\Http\Controllers\Controller;
use App\Models\Simrs\PemeriksaanRMkhusus\Polimata;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PemeriksaankhususMataController extends Controller
{
    public function pemeriksaanmatakhusus(Request $request)
    {
        $pemeriksaankhusus = Polimata::updateOrCreate(
            [
                'rs1' => $request->noreg,
                'rs2' => $request->norm,
                'rs3' => date('Y-m-d H:i:s'),
            ],
            [
                'rs4' => $request->vdawal,
                'rs5' => $request->vdrefraksi,
                'rs6' => $request->vdakhir,
                'rs7' => $request->vsawal,
                'rs8' => $request->vsrefraksi,
                'rs9' => $request->vsakhir,
                'rs10' => $request->tod,
                'rs11' => $request->tos,
                'rs12' => $request->fondosod,
                'rs13' => $request->fondosos,
                'user' => auth()->user()->kdpegsimrs
            ]
        );
        if (!$pemeriksaankhusus) {
            return new JsonResponse(['message' => 'Data Gagal disimpan...!!!'], 500);
        }
        return new JsonResponse(
            [
                'message' => 'Data Berhasil disimpan...!!!',
                'result' => $pemeriksaankhusus
            ],
            200
        );
    }

    public function datagridmata()
    {
        $gridmata = Polimata::where('rs1', request('noreg'))->get();
        return new JsonResponse(
            [
                'result' => $gridmata
            ],
            200
        );
    }
}
