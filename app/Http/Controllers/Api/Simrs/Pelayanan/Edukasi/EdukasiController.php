<?php

namespace App\Http\Controllers\Api\Simrs\Pelayanan\Edukasi;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Edukasi\Mkebutuhanedukasi;
use App\Models\Simrs\Edukasi\Mpenerimaedukasi;
use App\Models\Simrs\Edukasi\Transedukasi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EdukasiController extends Controller
{
    public function simpanedukasi(Request $request)
    {
        $simpanedukasi = Transedukasi::create(
            [
                'rs1' => $request->noreg,
                'rs2' => $request->norm,
                'rs3' => date('Y-m-d H:i:s'),
                'rs5' => $request->edukasi,
                'rs6' => $request->koderuang,
                'rs8' => auth()->user()->pegawai_id,
                'rs9' => $request->kepada,
                'perlupenerjemah' => $request->perlupenerjemah,
                'bahasaisyarat' => $request->bahasaisyarat,
                'caraedukasi' => $request->caraedukasi,
                'kesediaan' => $request->kesediaan,
                'kebutuhanedukasi' => $request->kebutuhanedukasi
            ]
        );
        if (!$simpanedukasi) {
            return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!'], 500);
        }
        return new JsonResponse(
            [
                'message' => 'Data Berhasil Disimpan...!!!',
                'result' => $simpanedukasi
            ],
            200
        );
    }

    public function hapusedukasi(Request $request)
    {
        $hapus = Transedukasi::where('id', $request->id)->delete();

        if (!$hapus) {
            return new JsonResponse(['message' => 'Data Gagal Dihapus...!!!'], 500);
        }
        $listedukasi = Transedukasi::where('noreg', $request->noreg);
        return new JsonResponse(
            [
                'message' => 'Data Berhasil Dihapus...!!!',
                'result' => $listedukasi
            ],
            200
        );
    }

    public function mpenerimaedukasi()
    {
        $lispenerimaedukasi = Mpenerimaedukasi::select('id','penerimaedukasi','hidden')->get();
        return new JsonResponse($lispenerimaedukasi);
    }

    public function mkebutuhanedukasi()
    {
        $mkebutuhanedukasi = Mkebutuhanedukasi::select('id','kebutuhanedukasi','hidden')->get();
        return new JsonResponse($mkebutuhanedukasi);
    }
}
