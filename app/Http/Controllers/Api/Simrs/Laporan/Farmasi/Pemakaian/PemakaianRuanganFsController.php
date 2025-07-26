<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Farmasi\Pemakaian;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Ruang;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaandepoheder;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Mutasi\Mutasigudangkedepo;
use App\Models\Simrs\Penunjang\Farmasinew\Ruangan\PemakaianR;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PemakaianRuanganFsController extends Controller
{
    //

    public function getRuangan()
    {
        $data = Ruang::select(
            'kepegx.ruangs.kode',
            'kepegx.ruangs.uraian'
        )
            ->leftJoin('farmasi.permintaan_h', 'kepegx.ruangs.kode', '=', 'farmasi.permintaan_h.dari')
            ->whereNotNull('farmasi.permintaan_h.dari')
            ->groupBy('kepegx.ruangs.kode')
            ->get();

        return new JsonResponse([
            'data' => $data
        ]);
    }
    public function getData()
    {
        $user = auth()->user();
        $pegawai = Petugas::find($user->pegawai_id);
        $kodeRuanganSims = explode('|', $pegawai->kdruangansim);
        $filteredR = array_values(array_filter($kodeRuanganSims, function ($item) {
            return str_starts_with($item, 'R-');
        }));
        $filteredG = array_values(array_filter($kodeRuanganSims, function ($item) {
            return str_starts_with($item, 'Gd-');
        }));
        $kode_ruang = request('kode_ruang');
        $bulan = request('bulan');
        $tahun = request('tahun');
        $mut = Mutasigudangkedepo::select('kd_obat')
            ->leftJoin('permintaan_h', 'permintaan_h.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
            ->when(
                $kode_ruang == 'all',
                function ($q) use ($filteredR, $filteredG) {
                    if (sizeof($filteredG) > 0) $q->where('dari', 'LIKE', 'R-%');
                    else $q->whereIn('dari', $filteredR);
                },
                function ($q) use ($kode_ruang) {
                    $q->where('dari', '=', $kode_ruang);
                }
            )
            ->where('tgl_kirim_depo', 'LIKE', '%' . $tahun . '-' . $bulan . '%')
            ->distinct()->pluck('kd_obat')->toArray();
        $pake = PemakaianR::select('kd_obat')
            ->leftJoin('pemakaian_h', 'pemakaian_h.nopemakaian', '=', 'pemakaian_r.nopemakaian')
            ->when(
                $kode_ruang == 'all',
                function ($q) use ($filteredR, $filteredG) {
                    if (sizeof($filteredG) > 0) $q->where('kdruang', 'LIKE', 'R-%');
                    else $q->whereIn('kdruang', $filteredR);
                },
                function ($q) use ($kode_ruang) {
                    $q->where('kdruang', '=', $kode_ruang);
                }
            )->where('tgl', 'LIKE', '%' . $tahun . '-' . $bulan . '%')
            ->distinct()->pluck('kd_obat')->toArray();
        $kodeOb = array_unique(array_merge($mut, $pake));
        $obat = Mobatnew::select(
            'kd_obat',
            'nama_obat',
            'jenis_perbekalan',
            'bentuk_sediaan',
            'satuan_k'
        )
            ->with([
                'mutasi' => function ($q) use ($kode_ruang, $bulan, $tahun, $filteredR, $filteredG) {
                    $q->select(
                        'mutasi_gudangdepo.no_permintaan',
                        'kd_obat',
                        'jml',
                        'harga',
                        'nopenerimaan',
                        'dari',
                        'tgl_kirim_depo'
                    )
                        ->leftJoin('permintaan_h', 'permintaan_h.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                        ->when(
                            $kode_ruang == 'all',
                            function ($q) use ($filteredR, $filteredG) {
                                if (sizeof($filteredG) > 0) $q->where('dari', 'LIKE', 'R-%');
                                else $q->whereIn('dari', $filteredR);
                            },
                            function ($q) use ($kode_ruang) {
                                $q->where('dari', '=', $kode_ruang);
                            }
                        )
                        ->where('tgl_kirim_depo', 'LIKE', '%' . $tahun . '-' . $bulan . '%');
                },
                'pemakaian' => function ($q) use ($kode_ruang, $bulan, $tahun, $kodeOb, $filteredR, $filteredG) {
                    $q->select(
                        'pemakaian_r.nopemakaian',
                        'pemakaian_r.kd_obat',
                        'pemakaian_r.jumlah',
                        'pemakaian_r.nopenerimaan',
                        'pemakaian_h.kdruang',
                        'pemakaian_h.tgl'
                    )
                        ->leftJoin('pemakaian_h', 'pemakaian_h.nopemakaian', '=', 'pemakaian_r.nopemakaian')
                        ->when(
                            $kode_ruang == 'all',
                            function ($q) use ($filteredR, $filteredG) {
                                if (sizeof($filteredG) > 0) $q->where('pemakaian_h.kdruang', 'LIKE', 'R-%');
                                else $q->whereIn('pemakaian_h.kdruang', $filteredR);
                            },
                            function ($q) use ($kode_ruang) {
                                $q->where('pemakaian_h.kdruang', '=', $kode_ruang);
                            }
                        )
                        ->with([
                            'penerimaanrinci' => function ($q) use ($kodeOb) {
                                $q->select(
                                    'kdobat',
                                    'nopenerimaan',
                                    'no_batch',
                                    'harga_netto_kecil',
                                )->whereIn('kdobat', $kodeOb);
                            }
                        ])
                        ->where('pemakaian_h.tgl', 'LIKE', '%' . $tahun . '-' . $bulan . '%')
                        ->where('pemakaian_h.flag', '1');
                },
            ])
            ->whereIn('kd_obat', $kodeOb)
            ->paginate(request('per_page'));
        $data['data'] = collect($obat)['data'];
        $data['meta'] = collect($obat)->except('data');
        $data['pegawai'] = $pegawai;
        $data['kodeRuanganSims'] = $kodeRuanganSims;
        $data['filteredR'] = $filteredR;
        $data['filteredG'] = $filteredG;
        $data['count'] = sizeof($filteredG);
        return new JsonResponse($data);
    }
}
