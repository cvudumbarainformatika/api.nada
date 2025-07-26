<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Jenazah;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Kamarjenazah\KamarjenazahPermintaan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PerawatanJenazahController extends Controller
{
    public function permintaanperawatanjenazah(Request $request)
    {
        $cari = KamarjenazahPermintaan::where('rs1', $request->noreg)->count();
        if($cari > 0){
            return new JsonResponse(['message' => 'Permintaan Ini Sudah Pernah Dilakukan...!!!'], 500);
        }
        $simpan=KamarjenazahPermintaan::firstOrCreate(
            [
                'rs1' => $request->noreg,
            ],
            [
                'rs2' => $request->kodepoli,
                'rs3' => $request->norm,
                'rs4' => date('Y-m-d H:i:s'),
                'rs5' => $request->kodepoli,
                'rs6' => $request->permintaan,
                'rs7' => $request->kodedokter,
                'rs8' => $request->kodesistembayar,
                'rs10' => $request->noreg,
                'rs11' => $request->kodedokter,
            ]
        );

        if (!$simpan) {
            return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!'], 500);
        }

        $nota = KamarjenazahPermintaan::select('rs10 as nota')->where('rs1', $request->noreg)
        ->groupBy('rs10')->orderBy('id', 'DESC')->get();

        return new JsonResponse(
            [
                'message' => 'Permintaan Terkirim...!!!',
                'result' => $simpan,
                'nota' => $request->nota
            ],
            200
        );
    }

    public function getnota()
    {
        $nota = KamarjenazahPermintaan::select('rs10 as nota')->where('rs1', request('noreg'))
            ->groupBy('rs2')->orderBy('id', 'DESC')->get();

        return new JsonResponse($nota);
    }
}
