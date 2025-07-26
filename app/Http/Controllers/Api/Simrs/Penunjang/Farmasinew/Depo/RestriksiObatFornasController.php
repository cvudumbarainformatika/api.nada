<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Depo;

use App\Http\Controllers\Controller;
use App\Models\RuanganRawatInap;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Obat\RestriksiObat;
use App\Models\Simrs\Penunjang\Farmasinew\Obat\RestriksiObatKecualiRuangan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RestriksiObatFornasController extends Controller
{
    //
    public function cariObat()
    {
        $fornas = request('fornas') == 'true' ? ['1'] : ['', '0'];
        $search = request('q');
        $depo = request('depo');
        $raw = Mobatnew::select(
            'id',
            'kd_obat',
            'nama_obat',
            'satuan_k',
            'kandungan',
            'status_fornas',
            'status_forkid',
            'status_generik',
        )

            ->whereIn('status_fornas',  $fornas)
            ->where(function ($query) use ($search) {
                $query->where('nama_obat', 'LIKE', '%' . $search . '%')
                    ->orWhere('kandungan', 'LIKE', '%' . $search . '%');
            })
            ->with([
                'restriksiobat' => function ($q) use ($depo) {
                    $q->where('depo', $depo)
                        ->orderBy('tgl_mulai_berlaku', 'desc');
                },
                'kecuali' => function ($q) use ($depo) {
                    $q->where('depo', $depo)
                        ->with('ruang:rs1 as kode,rs2 as nama,rs1');
                }
            ])
            ->paginate(request('per_page'));

        $data['fornas'] = $fornas;
        $data['data'] = collect($raw)['data'];
        $data['meta'] = collect($raw)->except('data');

        return new JsonResponse($data);
    }
    public function ambilRuangan()
    {

        $data = RuanganRawatInap::select('rs1 as kode', 'rs2 as nama', 'rs1')->get();
        return new JsonResponse(['data' => $data]);
    }

    public function simpanRestriksi(Request $request)
    {

        $result = RestriksiObat::updateOrCreate(
            [
                'kd_obat' => $request->kd_obat,
                'depo' => $request->depo,
                'tgl_mulai_berlaku' => $request->tgl_mulai_berlaku,
            ],
            [
                'jumlah' => $request->jumlah,

            ]
        );


        $data = self::getObat($request->kd_obat, $request->depo);

        return new JsonResponse([
            'message' => 'Restriksi berhasil disimpan',
            'data' => $data,
            'result' => $result,
            'req' => $request->all(),
        ]);
    }
    public function tambahRuangan(Request $request)
    {
        $result = RestriksiObatKecualiRuangan::firstOrCreate(
            [
                'kd_obat' => $request->kd_obat,
                'depo' => $request->depo,
                'kd_ruang' => $request->kd_ruang,
            ]
        );

        $data = self::getObat($request->kd_obat, $request->depo);

        return new JsonResponse([
            'message' => 'Ruangan sudah berhasil ditambahkan',
            'data' => $data,
            'result' => $result,
            'req' => $request->all(),
        ]);
    }
    public function hapusRestriksi(Request $request)
    {
        $result = RestriksiObat::find($request->id);
        if (!$result) {
            return new JsonResponse([
                'message' => 'Restriksi gagal di hapus',
                'result' => $result,
                'req' => $request->all(),
            ]);
        }
        $result->delete();
        $data = self::getObat($request->kd_obat, $request->depo);

        return new JsonResponse([
            'message' => 'Restriksi sudah di hapus',
            'data' => $data,
            'result' => $result,
            'req' => $request->all(),
        ]);
    }
    public function hapusRuangan(Request $request)
    {
        $result = RestriksiObatKecualiRuangan::find($request->id);
        if (!$result) {
            return new JsonResponse([
                'message' => 'Ruangan gagal di hapus',
                'result' => $result,
                'req' => $request->all(),
            ]);
        }
        $result->delete();
        $data = self::getObat($request->kd_obat, $request->depo);;

        return new JsonResponse([
            'message' => 'Ruangan berhasil di hapus',
            'data' => $data,
            'result' => $result,
            'req' => $request->all(),
        ]);
    }

    public static function getObat($kode, $depo)
    {
        $data = Mobatnew::select(
            'id',
            'kd_obat',
            'nama_obat',
            'satuan_k',
            'kandungan',
            'status_fornas',
            'status_forkid',
            'status_generik',
        )
            ->where('kd_obat', '=', $kode)
            ->with([
                'restriksiobat' => function ($q) use ($depo) {
                    $q->where('depo', $depo)
                        ->orderBy('tgl_mulai_berlaku', 'desc');
                },
                'kecuali' => function ($q) use ($depo) {
                    $q->where('depo', $depo)
                        ->with('ruang:rs1 as kode,rs2 as nama,rs1');
                }
            ])->first();

        return $data;
    }
}
