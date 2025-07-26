<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Jenazah;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Kamarjenazah\KamarjenazahPermintaan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermintaanPerawatanJenazahController extends Controller
{
    public function permintaan(Request $request)
    {
        $cari = KamarjenazahPermintaan::select('rs1')->where('rs1', $request->noreg)->count();
        if($cari > 0){
            return new JsonResponse(['message' => 'Permintaan Ini Sudah Pernah Dilakukan..'], 500);
        }
        $simpan=KamarjenazahPermintaan::firstOrCreate(
            [
                'rs1' => $request->noreg,
            ],
            [
                'rs2' => $request->kodepoli,
                'rs3' => $request->norm,
                'rs4' => date('Y-m-d H:i:s'),
                'rs5' => $request->isRanap ? $request->kdgroup_ruangan ?? '' : $request->kodepoli ?? '',
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

        $nota = KamarjenazahPermintaan::select('rs10 as nota')->where('rs1', $request->noreg)->orderBy('id', 'DESC')->get();

        return new JsonResponse(
            [
                'message' => 'Permintaan Berhasil dikirim ...',
                'result' => $simpan,
                'nota' => $nota
            ],
            200
        );
    }

    public function getnota()
    {
        $nota = KamarjenazahPermintaan::select('rs10 as nota')->where('rs1', request('noreg'))->orderBy('id', 'DESC')->get();

        return new JsonResponse($nota);
    }

    public function hapuspermintaan(Request $request)
    {
      $cari = KamarjenazahPermintaan::find($request->id);
        if (!$cari) {
            return new JsonResponse(['message' => 'data tidak ditemukan'], 501);
        }

        $kunci = $cari->rs9 === '1' || $cari->rs9 === 1;
        if ($kunci) {
            return new JsonResponse(['message' => 'Maaf, Data Ini Telah dikunci Oleh Pihak Instalasi Jenazah !'], 500);
        }

        $hapus = $cari->delete();
        if (!$hapus) {
            return new JsonResponse(['message' => 'gagal dihapus'], 500);
        }
        $nota = KamarjenazahPermintaan::select('rs10 as nota')->where('rs1', $request->noreg)->orderBy('id', 'DESC')->get();
        return new JsonResponse(['message' => 'berhasil dihapus', 'nota' => $nota], 200);
    }
}
