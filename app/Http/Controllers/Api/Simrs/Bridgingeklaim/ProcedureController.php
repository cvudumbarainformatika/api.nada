<?php

namespace App\Http\Controllers\Api\Simrs\Bridgingeklaim;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Ews\ProcedureM;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProcedureController extends Controller
{
    public function simpanprocedure(Request $request)
    {
        $simpan = ProcedureM::create(
            [
                'noreg' => $request->noreg,
                'kd_prosedur' => $request->kdprocedure,
                'prosedur' => $request->procedure,
                'tgl_input' => date('Y-m-d H:i:s')
            ]
        );
        if (!$simpan) {
            return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!'], 500);
        }
        return new JsonResponse(['message' => 'Data Berhasil Disimpan...!!!', 'result' => $simpan], 200);
    }

    public function listprocedure()
    {
        $list = ProcedureM::where('rs1', request('noreg'))->get();
        return new JsonResponse($list);
    }

    public function hapusprocedure(Request $request)
    {
        $cari = ProcedureM::find($request->id);

        if (!$cari) {
            return new JsonResponse(['message' => 'data tidak ditemukan'], 501);
        }
        $hapus = $cari->delete();

        return new JsonResponse(['message' => 'berhasil dihapus'], 200);
    }
}
