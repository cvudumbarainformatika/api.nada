<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Fisioterapi;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Fisioterapi\Fisioterapipermintaan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FisioterapiController extends Controller
{
    public function permintaanfisioterapipoli(Request $request)
    {
        DB::select('call nota_permintaanfisio(@nomor)');
        $x = DB::table('rs1')->select('rs29')->get();
        $wew = $x[0]->rs29;
        if($request->kodepoli === 'POL014')
        {
            $notapermintaanfisioterapi = $request->nota ?? FormatingHelper::notatindakan($wew, '/PFIS-IG');
        }else{
            if ($request->isRanap === true) {
                $notapermintaanfisioterapi = $request->nota ?? FormatingHelper::notatindakan($wew, '/PFIS-RI');
            } else {
                $notapermintaanfisioterapi = $request->nota ?? FormatingHelper::notatindakan($wew, '/PFIS-RJ');

            }
        }

        $user = FormatingHelper::session_user();
        $simpanpermintaan = Fisioterapipermintaan::firstOrCreate(
            [
                'rs1' => $request->noreg,
                'rs2' => $notapermintaanfisioterapi
            ],
            [
                'rs3' => date('Y-m-d H:i:s'),
                'rs4' => $request->permintaan,
                'rs8' => $request->kodedokter,
                'rs9' => 1,
                'rs10' => $request->kodepoli,
                'rs11' => $user['kodesimrs'],
                'rs13' => $request->isRanap ? $request->kdgroup_ruangan : $request->kodepoli, // kodegroup_ruangan
                'rs14' => $request->kodesistembayar,
            ]
        );

        if (!$simpanpermintaan) {
            return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!'], 500);
        }

        $nota = Fisioterapipermintaan::select('rs2 as nota')->where('rs1', $request->noreg)
            ->groupBy('rs2')->orderBy('id', 'DESC')->get();

        return new JsonResponse(
            [
                'message' => 'Permintaan Berhasil Dikirim Ke Fisio Terapi',
                'result' => $simpanpermintaan,
                'nota' => $nota
            ],
            200
        );
    }

    public function getnota()
    {
        $nota = Fisioterapipermintaan::select('rs2 as nota')->where('rs1', request('noreg'))
            ->where('rs2', '!=', '')
            ->groupBy('rs2')->orderBy('id', 'DESC')->get();

        return new JsonResponse($nota);
    }
    public function getdata()
    {
        $nota = Fisioterapipermintaan::select('*')->where('rs1', request('noreg'))
            ->where('rs2', '!=', '')
            ->groupBy('rs2')->orderBy('id', 'DESC')->get();

        return new JsonResponse($nota);
    }

    public function hapuspermintaan(Request $request)
    {
        $cari = Fisioterapipermintaan::find($request->id);
        if (!$cari) {
            return new JsonResponse(['message' => 'data tidak ditemukan'], 501);
        }

        $kunci = $cari->rs12 === '1';
        if ($kunci) {
            return new JsonResponse(['message' => 'Maaf, Data telah dikunci'], 500);
        }

        $hapus = $cari->delete();
        if (!$hapus) {
            return new JsonResponse(['message' => 'gagal dihapus'], 500);
        }
        $nota = Fisioterapipermintaan::select('rs2 as nota')->where('rs1', $request->noreg)
            ->groupBy('rs2')->orderBy('id', 'DESC')->get();
        return new JsonResponse(['message' => 'berhasil dihapus', 'nota' => $nota], 200);
    }
}
