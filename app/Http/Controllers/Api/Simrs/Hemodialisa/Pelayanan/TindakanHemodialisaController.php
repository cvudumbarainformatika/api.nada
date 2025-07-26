<?php

namespace App\Http\Controllers\Api\Simrs\Hemodialisa\Pelayanan;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Api\Simrs\Pelayanan\Tindakan\TindakanController;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Tindakan\Tindakan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TindakanHemodialisaController extends Controller
{
    public function getTindakanHd()
    {

        $data = TindakanController::dataTindakanByNoreg(request('noreg'), request('kodepoli'));
        return new JsonResponse($data);
    }

    public function simpantindakanHd(Request $request)
    {

        $cekKasir = DB::table('rs23')->select('rs42')->where('rs1', $request->noreg)->where('rs41', '=', '1')->get();

        if (count($cekKasir) > 0) {
            return response()->json(['status' => 'failed', 'message' => 'Maaf, data pasien telah dikunci oleh kasir pada tanggal ' . $cekKasir[0]->rs42], 500);
        }

        DB::select('call nota_tindakan(@nomor)');
        $x = DB::table('rs1')->select('rs14')->get();
        $wew = $x[0]->rs14;

        $notatindakan = FormatingHelper::notatindakan($wew, 'T-HD');


        $wew = FormatingHelper::session_user();
        $kdpegsimrs = $wew['kodesimrs'];

        $nota = $request->nota ?? $notatindakan;

        $tindakan = Tindakan::where(['rs1' => $request->noreg, 'rs4' => $request->kdtindakan, 'rs2' => $nota])->first();
        if (!$tindakan) {
            $tindakan = new Tindakan();
            $tindakan->rs5 = $request->jmltindakan ?? '';
        } else {
            $tindakan->rs5 = (int)$tindakan->rs5 + (int)$request->jmltindakan;
        }

        $tindakan->rs2 = $nota;
        $tindakan->rs1 = $request->noreg ?? '';
        $tindakan->rs3 = date('Y-m-d H:i:s');
        $tindakan->rs4 = $request->kdtindakan ?? '';
        $tindakan->rs6 = $request->hargasarana ?? '';
        $tindakan->rs7 = $request->hargasarana ?? '';
        $tindakan->rs8 = $request->pelaksanaSatu ?? '';
        $tindakan->rs9 = $request->kddpjp ?? '';
        $tindakan->rs13 = $request->hargapelayanan ?? '';
        $tindakan->rs14 = $request->hargapelayanan ?? '';
        $tindakan->rs20 = $request->keterangan ?? '';
        $tindakan->rs22 = $request->kdgroup_ruangan  ?? '';
        $tindakan->rs23 = $request->pelaksanaDua ?? '';
        $tindakan->rs24 = $request->kdsistembayar ?? '';
        $tindakan->rs25 = $request->kdpoli  ?? '';
        $tindakan->save();

        if (!$tindakan) {
            return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!'], 500);
        }

        $idTindakan = $tindakan->id;

        $tindakan->sambungan()->updateOrCreate(
            ['rs73_id' => $idTindakan],
            [
                'nota' => $tindakan->rs2,
                'noreg' => $request->noreg,
                'kd_tindakan' => $request->kdtindakan,
                'ket' => $request->keterangan,
                'rs73_id' => $idTindakan
            ],
            // ['ket' => $request->keterangan]
        );


        // $tindakan->save();

        $nota = Tindakan::select('rs2 as nota')->where('rs1', $request->noreg)
            ->where('rs22', $request->kodepoli)
            ->groupBy('rs2')->orderBy('id', 'DESC')->get();

        // EwseklaimController::ewseklaimrajal_newclaim($request->noreg);

        $tindakan->load('mastertindakan:rs1,rs2,rs4');
        return new JsonResponse(
            [
                'message' => 'Tindakan Berhasil Disimpan.',
                'result' => $tindakan,
                'nota' => $nota
            ],
            200
        );
    }
}
