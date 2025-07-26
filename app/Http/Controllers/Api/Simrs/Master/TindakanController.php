<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mtindakan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TindakanController extends Controller
{
    public function listtindakan()
    {
        $listtindakan = Mtindakan::select(
            'rs1 as kdtindakan',
            'rs2 as nmtindakan',
            'rs8 as js3',
            'rs9 as jp3',
            'rs10 as habispake3',
            DB::raw('rs8+rs9 as tarif3'),
            'rs11 as js2',
            'rs12 as jp2',
            'rs13 as habispake2',
            DB::raw('rs11+rs12 as tarif2'),
            'rs14 as js1',
            'rs15 as jp1',
            'rs16 as habispake1',
            DB::raw('rs14+rs15 as tarif1'),
            'rs17 as jsutama',
            'rs18 as jputama',
            'rs19 as habispakeutama',
            DB::raw('rs17+rs18 as tarifutama'),
            'rs20 as jsvip',
            'rs21 as jpvip',
            'rs22 as habispakevip',
            DB::raw('rs20+rs21 as tarifvip'),
            'rs23 as jsvvip',
            'rs24 as jpvvip',
            'rs25 as habispakevvip',
            DB::raw('rs23+rs24 as tarifvvip')
        )->where('rs2', 'like', '%' . request('nmtindakan') . '%')
            ->paginate(request('per_page'));
        return new JsonResponse($listtindakan);
    }

    public function simpanmastertindakan(Request $request)
    {

        if ($request->kdtindakan == '' || $request->kdtindakan == null) {
            $ceknama = Mtindakan::where('rs2', $request->nmtindakan)->count();
            if ($ceknama > 0) {
                return new JsonResponse(['message' => 'Maaf Tindakan Sudah Ada...!!!'], 500);
            }

            $cektotal = Mtindakan::count();
            $akhir = (int) $cektotal + (int) 1;

            $has = null;
            $lbr = strlen($akhir);
            for ($i = 1; $i <= 4 - $lbr; $i++) {
                $has = $has . "0";
            }

            $kdtindakan = 'TB' . $has . $akhir;
        } else {
            $kdtindakan = $request->kdtindakan;
        }
        $simpantindakan = Mtindakan::updateOrCreate(
            [
                'rs1' => $kdtindakan
            ],
            [
                'rs2' => $request->nmtindakan,
                'rs3' => 'T1#',
                'rs8' => $request->js3,
                'rs9' => $request->jp3,
                'rs10' => $request->habispake3,
                'rs11' => $request->js2,
                'rs12' => $request->jp2,
                'rs13' => $request->habispake2,
                'rs14' => $request->js1,
                'rs15' => $request->jp1,
                'rs16' => $request->habispake1,
                'rs17' => $request->jsutama,
                'rs18' => $request->jputama,
                'rs19' => $request->habispakeutama,
                'rs20' => $request->jsvip,
                'rs21' => $request->jpvip,
                'rs22' => $request->habispakevip,
                'rs23' => $request->jsvvip,
                'rs24' => $request->jpvvip,
                'rs25' => $request->habispakevvip
            ]
        );
        if (!$simpantindakan) {
            return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!'], 410);
        }
        return new JsonResponse($simpantindakan);
    }

    public function hidden(Request $request)
    {
        $caritindakan = Mtindakan::where('rs1', $request->kdtindakan)->first();
        $caritindakan->hidden = '1';
        $caritindakan->save();
        return new JsonResponse(['message' => 'ok'], 200);
    }
}
