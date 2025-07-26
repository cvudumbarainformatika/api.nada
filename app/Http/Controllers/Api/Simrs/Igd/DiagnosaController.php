<?php

namespace App\Http\Controllers\Api\Simrs\Igd;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Master\Diagnosa_m;
use App\Models\Simrs\Pelayanan\Diagnosa\Diagnosa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiagnosaController extends Controller
{
    public function listdiagnosa()
    {
        $listdiagnosa = Diagnosa_m::select('rs1 as kode', 'rs2 as dtd', 'rs4 as keterangan')
            ->where('rs1', 'Like', '%' . request('diagnosa') . '%')
            // ->orWhere('rs4', 'Like', '%' . request('diagnosa') . '%')
            ->where('disable_status','!=','1')
            ->get();
        return new JsonResponse($listdiagnosa);
    }

    public function simpandiagnosa(Request $request)
    {
        $user = Pegawai::find(auth()->user()->pegawai_id);
        $kdpegsimrs = $user->kdpegsimrs;


        if ($request->has('id')) {
            $simpandiagnosa = Diagnosa::where(['id' => $request->id])->update(
                [
                    'rs1' => $request->noreg,
                    'rs2' => $request->norm,
                    'rs3' => $request->kddiagnosa,
                    'rs4' => $request->tipediagnosa,
                    'rs6' => $request->keterangan ?? '',
                    'rs7' => $request->kasus,
                    'rs8'  => $kdpegsimrs,
                    'rs9' => $request->dtd ?? '',
                    'rs10' => $request->kodedokter,
                    'rs11' => $request->jeniskasus,
                    'rs12' => date('Y-m-d'),
                    'rs13' => $request->ruangan
                ]
            );
        } else {
            $simpandiagnosa = Diagnosa::create(
                [
                    'rs1' => $request->noreg,
                    'rs2' => $request->norm,
                    'rs3' => $request->kddiagnosa,
                    'rs4' => $request->tipediagnosa,
                    'rs6' => $request->keterangan ?? '',
                    'rs7' => $request->kasus,
                    'rs8'  => $kdpegsimrs,
                    'rs9' => $request->dtd ?? '',
                    'rs10' => $request->kodedokter,
                    'rs11' => $request->jeniskasus,
                    'rs12' => date('Y-m-d'),
                    'rs13' => $request->ruangan
                ]
            );
        }
        if (!$simpandiagnosa) {
            return new JsonResponse(['message' => 'Diagnosa Gagal Disimpan...!!!'], 500);
        }

        // $inacbg = EwseklaimController::ewseklaimrajal_newclaim($request->noreg);
        return new JsonResponse(
            [
                'message' => 'Diagnosa Berhasil Disimpan...!!!',
                'result' => $simpandiagnosa,
                // 'inacbg' => $inacbg,
            ],
            200
        );
    }


    public function hapusdiagnosa(Request $request)
    {
        $cari = Diagnosa::find($request->id);
        if (!$cari) {
            return new JsonResponse(['message' => 'MAAF DATA TIDAK DITEMUKAN'], 500);
        }
        $hapus = $cari->delete();
        if (!$hapus) {
            return new JsonResponse(['message' => 'gagal dihapus'], 501);
        }

        $data = Diagnosa::where('rs1', $request->noreg)->with('masterdiagnosa')->get();
        // $inacbg = EwseklaimController::ewseklaimrajal_newclaim($request->noreg);
        return new JsonResponse(
            [
                'message' => 'berhasil dihapus',
                'result' => $data
            ], 200);
    }

    public function getDiagnosaByNoreg()
    {
       $noreg=request('noreg');

       $data = Diagnosa::where('rs1', $noreg)->with('masterdiagnosa')->get();

       return new JsonResponse($data);

    }
}
