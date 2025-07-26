<?php

namespace App\Http\Controllers\Api\Simrs\Master\Maping;

use App\Helpers\BridgingbpjsHelper;
use App\Http\Controllers\Controller;
use App\Models\Pegawai\Mpegawaisimpeg;
use App\Models\Simrs\Master\Mnakes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MapnakesController extends Controller
{
    public function listnakes()
    {
        $listnakes = Mnakes::select('rs1 as kode', 'rs2 as nama', 'rs10 as Spesialis', 'rs13 as kdgroupnakes', 'rs17 as kdruangan')
            ->where('rs18', '')->where('rs1', '!=', '')
            ->where('rs2', 'like', '%' . request('nama') . '%')
            ->get();
        return new JsonResponse($listnakes);
    }

    public function pegawaisimpeg()
    {
        $pegawaisimpeg = Mpegawaisimpeg::select('id', 'nip', 'nik', 'nama', 'kelamin', 'foto', 'kdpegsimrs', 'kddpjp')
            ->where('aktif', 'AKTIF')
            // ->where('nama', 'like', '%' . request('nama') . '%')
            ->get();
        return new JsonResponse($pegawaisimpeg);
    }

    public function simpanmaping(Request $request)
    {
        $simpanmaping = Mpegawaisimpeg::where('nip', $request->nip)->first();
        $simpanmaping->kdpegsimrs = $request->kdpegsimrs ?? '';
        $simpanmaping->statusspesialis = $request->statusspesialis ?? '';
        $simpanmaping->kdgroupnakes = $request->kdgroupnakes ?? '';
        $simpanmaping->kdruangansim = $request->kdruangansim ?? '';
        $simpanmaping->save();

        $collect = Mpegawaisimpeg::select('kdpegsimrs')->whereNotNull('kdpegsimrs')->where('kdpegsimrs', '!=', '')->get();
        return new JsonResponse(
            [
                'message' => 'ok',
                'result' => $collect
            ],
            200
        );
    }

    public function datatermaping()
    {
        $collect = Mpegawaisimpeg::select('kdpegsimrs')->whereNotNull('kdpegsimrs')->where('kdpegsimrs', '!=', '')->get();
        return new JsonResponse($collect);
    }

    public function listdokterbpjs()
    {
        return BridgingbpjsHelper::get_url('antrean', 'ref/dokter');
    }
    public function simpanmapingbpjs(Request $request)
    {
        $simpanmaping = Mpegawaisimpeg::where('nip', $request->nip)->first();
        $simpanmaping->kddpjp = $request->kddpjp ?? '';
        $simpanmaping->save();

        $collect = Mpegawaisimpeg::select('kddpjp')->whereNotNull('kddpjp')->where('kddpjp', '!=', '')->get();
        return new JsonResponse(
            [
                'message' => 'ok',
                'result' => $collect
            ],
            200
        );
    }
    public function datatermapingbpjs()
    {
        $collect = Mpegawaisimpeg::select('kddpjp')->whereNotNull('kddpjp')->where('kddpjp', '!=', '')->get();
        return new JsonResponse($collect);
    }
}
