<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Ambulan;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Ambulan\ReqAmbulan;
use App\Models\Simrs\Penunjang\Ambulan\TujuanAmbulan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AmbulanController extends Controller
{
    public function getTujuanAmbulan() {
        $tujuan = TujuanAmbulan::where('flag','')->get();
        return new JsonResponse($tujuan);
    }

    public function simpanreqambulan(Request $request)
    {

        $tujuan = TujuanAmbulan::where('rs1', $request->tujuan)->first();
        if($request->pelperawat === 'Rujukan')
        {
            $jp_perawat1 = $tujuan->rs6;
        }elseif($request->pelperawat === 'Emergency')
        {
            $jp_perawat1 = $tujuan->rs7;
        }else{
            $jp_perawat1 = $tujuan->rs8;
        }
        DB::select('call nota_ambulan(@nomor)');
        $x = DB::table('rs1')->select('rs283')->get();
        $wew = $x[0]->rs283;
        if($request->notaambulan === 'BARU'){
            $notatindakan = FormatingHelper::notatindakan($wew, 'AMB-RI');
        }else{
            $notatindakan = $request->notaambulan;
        }
        $reqambulan = ReqAmbulan::create(
            [
                'rs1' => $request->noreg,
                'rs2' => $request->norm,
                'rs3' => date('Y-m-d H:i:s'),
                'rs4' => $request->koderuang,
                'rs5' => $request->koderuang,
                'rs6' => $request->kodesistembayar,
                // 'rs7' => $request->noreg,
                // 'rs8' => $request->noreg,
                'rs9' => $request->kodedokter,
                'rs10' => $request->tujuan,
                'rs11' => $request->keterangan ?? '',
                'rs12' => $request->pelsupir,
                'rs13' => $request->perawatpendamping1 ?? '',
                'rs14' => $request->perawatpendamping2 ?? '',
                'rs15' => $request->pelperawat ?? '',
                'rs16' => $jp_perawat1,
                // 'rs17' => $request->noreg,
                'nota' => $notatindakan
            ]
        );

        $nota = ReqAmbulan::select('nota as nota')->where('rs1', $request->noreg)
        ->groupBy('nota')
        ->orderBy('id', 'DESC')->get();

        $ambulan = ReqAmbulan::with(
            [
                'tujuan',
                'perawat',
                'perawat2'
            ]
        )->where('id', $reqambulan['id'])
        ->orderBy('id','Desc')
        ->get();

        return new JsonResponse([
            'message' => 'Permintaan Ambulan Berhasil Dikirim...!!!',
            'data' => $ambulan,
            'nota' => $nota
        ]);
    }

    public function getnotaAmbulans()
    {
        $data = ReqAmbulan::with(
            [
                'tujuan',
                'perawat'
            ]
        )
        ->where('rs1',request('noreg'))
        ->get();
        return new JsonResponse($data);
    }

    public function getnota()
    {
        $nota = ReqAmbulan::select('nota as nota')->where('rs1', request('noreg'))
            ->groupBy('nota')->orderBy('id', 'DESC')->get();
        return new JsonResponse($nota);
    }

    public function hapusreqambulan(Request $request)
    {
        $cari = ReqAmbulan::find($request->id);
        $delete = $cari->delete();
        if (!$delete) {
            return new JsonResponse(['message' => 'gagal dihapus'], 500);
        }

        $nota = ReqAmbulan::select('nota as nota')->where('rs1', $request->noreg)
        ->groupBy('nota')
        ->orderBy('id', 'DESC')->get();
        return new JsonResponse(['message' => 'berhasil dihapus', 'nota' => $nota], 200);
    }

}
