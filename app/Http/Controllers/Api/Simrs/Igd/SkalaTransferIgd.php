<?php

namespace App\Http\Controllers\Api\Simrs\Igd;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Planing\SkalaTransferIgd as PlaningSkalaTransferIgd;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SkalaTransferIgd extends Controller
{
    public function simpan(Request $request)
    {
        $cari = PlaningSkalaTransferIgd::where('noreg',$request->noreg)->count();
        if($cari > 0){
            return new JsonResponse(['Maaf SKala Transfer Hanya bisa Di isi Sekali...!!'],500);
        }
        $wew = FormatingHelper::session_user();
        $kdpegsimrs = $wew['kodesimrs'];
        $simpan = PlaningSkalaTransferIgd::create(
            [
                'noreg' => $request->noreg,
                'norm' => $request->norm,
                'tgl' => date('Y-m-d H:i;s'),
                'airways' => $request->airways,
                 'cardio' => $request->cardio,
                 'drajattransfer' => $request->drajattransfer,
                 'ecgmonitor' => $request->ecgmonitor,
                 'haemodinamik' => $request->haemodinamik,
                 'intravenusline' => $request->intravenusline,
                 'kesadaran' => $request->kesadaran,
                 'lanjutusia' => $request->lanjutusia,
                 'prematurias' => $request->prematurias,
                 'provesionalpacemaker' => $request->provesionalpacemaker,
                'respirasi' => $request->respirasi,
                'respritarorysupport' => $request->respritarorysupport,
                'scoreairways' => $request->scoreairways,
                'scorecardio' => $request->scorecardio,
                'scoreecgmonitor' => $request->scoreecgmonitor,
                'scorehaemodinamik' => $request->scorehaemodinamik,
                'scoreintravenusline' => $request->scoreintravenusline,
                'scorekesadaran' => $request->scorekesadaran,
                'scorelanjutusia' => $request->scorelanjutusia,
                'scoreprematurias' => $request->scoreprematurias,
                'scoreprovesionalpacemaker' => $request->scoreprovesionalpacemaker,
                'scorerespirasi' => $request->scorerespirasi,
                'scorerespiratorysupport' => $request->scorerespiratorysupport,
                'scoretotal' => $request->scoretotal,
                'kdruang' => 'POL014',
                'user' => $kdpegsimrs,

            ]
        );
        return new JsonResponse(
            [
                'message' => 'Data Sudah Tersimpan',
                'result' => $simpan
            ],
        200);
    }

    public function hapusskalatransfer(Request $request)
    {

        try{
            DB::beginTransaction();
            $dataskalatransfer = PlaningSkalaTransferIgd::where('id', $request->id);

            $hapusskalatransfer = $dataskalatransfer->delete();

            DB::commit();

            return new JsonResponse([
                'message' => 'BERHASIL DIHAPUS',
                'result' => $dataskalatransfer
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return new JsonResponse(['message' => 'ada kesalahan', 'error' => $e], 500);
        }
    }
}
