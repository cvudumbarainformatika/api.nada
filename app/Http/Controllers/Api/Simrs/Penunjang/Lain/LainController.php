<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Lain;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Poli;
use App\Models\Simrs\Penunjang\Lain\Lain;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LainController extends Controller
{
    public function penunjanglain()
    {
        $arr = ['POL026', 'POL024', 'PEN005', 'POL029', 'POL030', 'POL031', 'POL037'];
        $data = Poli::select('rs1 as kode', 'rs2 as nama')->whereIn('rs1', $arr)->get();

        return new JsonResponse($data);
    }

    public function simpanpenunjanglain(Request $request)
    {
        DB::select('call nota_permintaanpara(@nomor)');
        $x = DB::table('rs1')->select('rs48')->get();
        $wew = $x[0]->rs48;
        $notaP = $request->nota ?? FormatingHelper::formatallpermintaan($wew, 'G-LAI');

        $userid = FormatingHelper::session_user();
        $simpan = Lain::create(
            // ['rs2' => $notaP],
            [
                'rs1' => $request->noreg,
                'rs2' => $notaP,
                'rs3' => date('Y-m-d H:i:s'),
                'rs4' => '',
                'rs7' => $request->keterangan,
                'rs8' => $request->kodedokter, //kddokter
                'rs9' => 1,
                'rs10' => $request->kodepoli,
                'rs11' => $userid['kodesimrs'],
                'rs13' => $request->kodepenunjang,
                'rs14' => $request->koderuang ?? '',
                'rs15' => $request->kodesistembayar ?? '',
            ],
        );

        if (!$simpan) {
            return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!'], 500);
        }

        $nota = Lain::select('rs2 as nota')->where('rs1', $request->noreg)
            ->groupBy('rs2')->orderBy('id', 'DESC')->get();

        return new JsonResponse(
            [
                'message' => 'Permintaan Berhasil !',
                'result' => $simpan->load('masterpenunjang'),
                'nota' => $nota
            ],
            200
        );
    }

    public function getnota()
    {
        $nota = Lain::select('rs2 as nota')->where('rs1', request('noreg'))
            ->groupBy('rs2')->orderBy('id', 'DESC')->get();
        return new JsonResponse($nota);
    }

    public function hapuspermintaan(Request $request)
    {
        $cari = Lain::find($request->id);
        if (!$cari) {
            return new JsonResponse(['message' => 'data tidak ditemukan'], 501);
        }
        $hapus = $cari->delete();
        if (!$hapus) {
            return new JsonResponse(['message' => 'gagal dihapus'], 500);
        }
        $nota = Lain::select('rs2 as nota')->where('rs1', $request->noreg)
            ->groupBy('rs2')->orderBy('id', 'DESC')->get();
        return new JsonResponse(['message' => 'berhasil dihapus', 'nota' => $nota], 200);
    }
}
