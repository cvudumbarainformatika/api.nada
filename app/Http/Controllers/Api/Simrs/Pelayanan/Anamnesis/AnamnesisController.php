<?php

namespace App\Http\Controllers\Api\Simrs\Pelayanan\Anamnesis;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Anamnesis\Anamnesis as AnamnesisAnamnesis;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnamnesisController extends Controller
{
    public function simpananamnesis(Request $request)
    {
        $user = Pegawai::find(auth()->user()->pegawai_id);
        $kdpegsimrs = $user->kdpegsimrs;
        if ($request->has('id')) {
            $hasil = AnamnesisAnamnesis::where('id', $request->id)->update(
                [
                    'rs1' => $request->noreg,
                    'rs2' => $request->norm,
                    'rs3' => date('Y-m-d H:i:s'),
                    'rs4' => $request->keluhanutama,
                    'riwayatpenyakit' => $request->riwayatpenyakit ?? '',
                    'riwayatalergi' => $request->riwayatalergi ?? '',
                    'keteranganalergi' => $request->keteranganalergi ?? '',
                    'riwayatpengobatan' => $request->riwayatpengobatan ?? '',
                    'riwayatpenyakitsekarang' => $request->riwayatpenyakitsekarang ?? '',
                    'riwayatpenyakitkeluarga' => $request->riwayatpenyakitkeluarga ?? '',
                    'skreeninggizi' => $request->skreeninggizi ?? 0,
                    'asupanmakan' => $request->asupanmakan ?? 0,
                    'kondisikhusus' => $request->kondisikhusus ?? '',
                    'skor' => $request->skor ?? 0,
                    'scorenyeri' => $request->skornyeri ?? 0,
                    'keteranganscorenyeri' => $request->keteranganscorenyeri ?? '',
                    //    'keteranganscorenyeri' => $request->riwayatpekerjaan ?? '',
                    'user'  => $kdpegsimrs,
                ]
            );
            if ($hasil === 1) { 
                $simpananamnesis = AnamnesisAnamnesis::where('id', $request->id)->first();
            } else {
                $simpananamnesis = null;
            }
        } else {
            $simpananamnesis = AnamnesisAnamnesis::create(
                [
                    'rs1' => $request->noreg,
                    'rs2' => $request->norm,
                    'rs3' => date('Y-m-d H:i:s'),
                    'rs4' => $request->keluhanutama,
                    'riwayatpenyakit' => $request->riwayatpenyakit ?? '',
                    'riwayatalergi' => $request->riwayatalergi ?? '',
                    'keteranganalergi' => $request->keteranganalergi ?? '',
                    'riwayatpengobatan' => $request->riwayatpengobatan ?? '',
                    'riwayatpenyakitsekarang' => $request->riwayatpenyakitsekarang ?? '',
                    'riwayatpenyakitkeluarga' => $request->riwayatpenyakitkeluarga ?? '',
                    'skreeninggizi' => $request->skreeninggizi ?? 0,
                    'asupanmakan' => $request->asupanmakan ?? 0,
                    'kondisikhusus' => $request->kondisikhusus ?? '',
                    'skor' => $request->skor ?? 0,
                    'scorenyeri' => $request->skornyeri ?? 0,
                    'keteranganscorenyeri' => $request->keteranganscorenyeri ?? '',
                    'user'  => $kdpegsimrs,
                ]
            );
        }
        if (!$simpananamnesis) {
            return new JsonResponse(['message' => 'GAGAL DISIMPAN'], 500);
        }
        return new JsonResponse([
            'message' => 'BERHASIL DISIMPAN',
            'result' => $simpananamnesis
        ], 200);
    }

    public function hapusanamnesis(Request $request)
    {
        $cari = AnamnesisAnamnesis::find($request->id);
        if (!$cari) {
            return new JsonResponse(['message' => 'MAAF DATA TIDAK DITEMUKAN'], 500);
        }
        $hapus = $cari->delete();
        if (!$hapus) {
            return new JsonResponse(['message' => 'gagal dihapus'], 501);
        }
        return new JsonResponse(['message' => 'berhasil dihapus'], 200);
        // return new JsonResponse($cari, 200);
    }

    public function historyanamnesis()
    {
        $raw = [];
        $history = AnamnesisAnamnesis::select(
            'id',
            'rs2 as norm',
            'rs3 as tgl',
            'rs4 as keluhanutama',
            'riwayatpenyakit',
            'riwayatalergi',
            'keteranganalergi',
            'riwayatpengobatan',
            'riwayatpenyakitsekarang',
            'riwayatpenyakitkeluarga',
            'skreeninggizi',
            'asupanmakan',
            'kondisikhusus',
            'skor',
            'scorenyeri',
            'keteranganscorenyeri',
            'user',
        )
            ->where('rs2', request('norm'))
            ->where('rs3', '<', Carbon::now()->toDateString())
            ->with('datasimpeg:id,nip,nik,nama,kelamin,foto,kdpegsimrs')
            ->orderBy('tgl', 'DESC')
            ->get()
            ->chunk(10);
        // ->chunk(10, function ($q) use ($raw) {
        //     foreach ($q as $x) {
        //         $raw[] = $x;
        //     }
        // });
        // ->simplePaginate(10);
        // ->cursorPaginate(5)->through(function ($item) {
        //     return $item->makeHidden('id');
        // });
        // ->limit(10)->offset(0)->get();

        $collapsed = $history->collapse();


        return new JsonResponse($collapsed->all());
    }
}
