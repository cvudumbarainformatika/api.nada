<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Gudang;
use App\Models\Simrs\Master\Mobat;
use App\Models\Simrs\Master\Mruangan;
use App\Models\Simrs\Penunjang\Farmasinew\Mminmaxobat;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MinmaxobatController extends Controller
{
    public function simpan(Request $request)
    {
        $pemilik = Mobatnew::where('kd_obat', $request->kd_obat)->first();

        if ($request->kd_ruang === 'Gd-05010100' || $request->kd_ruang === 'Gd-03010100') {
            if ($pemilik->gudang === '') {
                $simpan = Mminmaxobat::updateOrCreate(
                    ['kd_obat' => $request->kd_obat, 'kd_ruang' => $request->kd_ruang],
                    [
                        'min' => $request->min,
                        'max' => $request->max
                    ]
                );

                if (!$simpan) {
                    return new JsonResponse(['message' => 'DATA TIDAK TERSIMPAN...!!!'], 500);
                }
                return new JsonResponse(['message' => 'DATA TERSIMPAN...!!!'], 200);
            } else {
                if ($pemilik->gudang != $request->kd_ruang) {
                    return new JsonResponse(['message' => 'Maaf tidak ada list obat di gudang ini'], 500);
                }
            }
        }
        $simpan = Mminmaxobat::updateOrCreate(
            ['kd_obat' => $request->kd_obat, 'kd_ruang' => $request->kd_ruang],
            [
                'min' => $request->min,
                'max' => $request->max
            ]
        );

        if (!$simpan) {
            return new JsonResponse(['message' => 'DATA TIDAK TERSIMPAN...!!!'], 500);
        }
        return new JsonResponse(['message' => 'DATA TERSIMPAN...!!!'], 200);
    }

    public function caribynamaobat()
    {
        $id = Mruangan::where('uraian', 'LIKE', '%' . request('r') . '%')->pluck('kode');
        $gd = Gudang::where('gudang', '<>', '')->where('nama', 'LIKE', '%' . request('r') . '%')->pluck('kode');
        $ob = Mobatnew::where('nama_obat', 'LIKE', '%' . request('o') . '%')->where('flag', '')->pluck('kd_obat');
        $qwerty = Mminmaxobat::with([
            'obat:kd_obat,nama_obat as namaobat,satuan_k',
            'ruanganx:kode,uraian as namaruangan',
            'gudang:kode,nama as namaruangan'
        ])
            ->where(function ($f) use ($id, $gd) {
                $f->whereIn('kd_ruang', $id)
                    ->orWhereIn('kd_ruang',  $gd);
            })
            ->when(count($ob), function ($a) use ($ob) {
                $a->whereIn('kd_obat', $ob);
            })
            ->paginate(request('per_page'));
        return new JsonResponse($qwerty, 200);
    }

    public function caribyruang()
    {
        $query = Mobatnew::select(
            'kd_obat',
            'nama_obat as namaobat',
            'satuan_k'
        )
            ->where('flag', '')
            ->where(function ($list) {
                $list->where('nama_obat', 'Like', '%' . request('q') . '%');
            })
            ->orderBy('nama_obat')
            ->with(['stokmaxrs' => function ($anu) {
                $anu->when(request('kd_ruang'), function ($q) {
                    $q->whereKdRuang(request('kd_ruang'));
                });
            }])
            ->limit(50)
            ->get();
        return new JsonResponse($query);
        // $qwerty = Mminmaxobat::with([
        //     'obat:kd_obat,nama_obat as namaobat,satuan_k',
        //     'ruanganx:kode,uraian as namaruangan',
        //     'gudang:kode,nama as namaruangan'
        // ])
        //     ->whereKdRuang(request('kd_ruang'))
        //     ->get();
        // ->paginate(request('per_page'));
        // return new JsonResponse($qwerty, 200);
    }
}
