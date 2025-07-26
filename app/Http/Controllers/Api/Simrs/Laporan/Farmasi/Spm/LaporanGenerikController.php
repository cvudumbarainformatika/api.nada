<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Farmasi\Spm;

use App\Http\Controllers\Controller;

use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaandepoheder;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaanresep;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\SistemBayar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanGenerikController extends Controller
{
    //
    public function getLaporanGenerik()
    {
        $raw = Resepkeluarheder::select(
            'noresep',
            'tgl_permintaan',
            'dokter',
            'depo',
            'sistembayar',
        )->where('tgl_permintaan', 'LIKE', '%' . request('tahun') . '-' . request('bulan') . '%')
            ->when(request('sistem_bayar'), function ($query) {
                $query->whereIn('sistembayar', request('sistem_bayar'));
            })
            ->when(request('depo'), function ($query) {
                $query->whereIn('depo', request('depo'));
            })
            ->with([
                'rincian:noresep,kdobat,jumlah',
                'rincianracik:noresep,kdobat,jumlah',
                'permintaanresep:noresep,kdobat,jumlah',
                'permintaanracikan:noresep,kdobat,jumlah',

                'permintaanresep.mobat:kd_obat,nama_obat,jenis_perbekalan,kelompok_penyimpanan,status_generik,status_forkid,status_fornas,obat_program',
                'permintaanracikan.mobat:kd_obat,nama_obat,jenis_perbekalan,kelompok_penyimpanan,status_generik,status_forkid,status_fornas,obat_program',

                'sistembayar:rs1,rs2',
                'ketdokter:kdpegsimrs,nama',
            ])
            ->whereIn('flag', ['1', '2', '3', '4'])
            // ->limit(100)
            ->paginate(100);
        $data = collect($raw)['data'];
        $meta = collect($raw)->except('data');
        return new JsonResponse([
            'req' => request()->all(),
            'data' => $data,
            'meta' => $meta,
        ]);
    }
    public function getLaporanResponseTime()
    {
        $response_time = request('response_time');
        $bulan = request('bulan');
        $tahun = request('tahun');
        if ($response_time == 'Obat') {
            $raw = Resepkeluarheder::select(
                'noresep',
                'tgl_permintaan',
                'tgl_diterima',
                'tgl_kirim',
                'tgl_selesai',
                'dokter',
                'depo',
                'ruangan',
                'sistembayar',
                DB::raw('((TIMESTAMPDIFF(MINUTE,resep_keluar_h.tgl_kirim,resep_keluar_h.tgl_selesai))) AS rt_menit'),
            )->where('tgl_permintaan', 'LIKE', '%' . $tahun . '-' . $bulan . '%')
                ->when(request('sistem_bayar'), function ($query) {
                    $query->whereIn('sistembayar', request('sistem_bayar'));
                })
                ->when(request('depo'), function ($query) {
                    $query->whereIn('depo', request('depo'));
                })

                ->with([
                    'rincian:noresep,kdobat,jumlah',
                    'rincianracik:noresep,kdobat,jumlah',

                    'poli:rs1,rs2 as nama',
                    'ruanganranap:rs1,rs2 as nama',
                    'sistembayar:rs1,rs2',
                    'ketdokter:kdpegsimrs,nama',
                ])
                ->whereIn('flag', ['1', '2', '3', '4'])

                ->paginate(100);
        } else {
            $raw = Permintaandepoheder::select(
                'no_permintaan',
                'tgl_permintaan',
                'tgl_terima',
                'tgl_kirim',
                'tgl_kirim_depo',
                'tgl_terima_depo',
                'dari',
                DB::raw('((TIMESTAMPDIFF(MINUTE,permintaan_h.tgl_kirim,permintaan_h.tgl_terima_depo))) AS tt_menit'),
                DB::raw('((TIMESTAMPDIFF(HOUR,permintaan_h.tgl_kirim,permintaan_h.tgl_terima_depo))%24) AS rt_jam'),
                DB::raw('((TIMESTAMPDIFF(MINUTE,permintaan_h.tgl_kirim,permintaan_h.tgl_terima_depo))%60) AS rt_menit'),

            )
                ->with([
                    'ruangan:kode,uraian as nama',
                    'asal:kode,nama',
                ])->where('tgl_permintaan', 'LIKE', '%' . $tahun . '-' . $bulan . '%')
                ->whereIn('flag', ['1', '2', '3', '4'])
                ->paginate(100);
        }

        $data = collect($raw)['data'];
        $meta = collect($raw)->except('data');
        return new JsonResponse([
            'req' => request()->all(),
            'data' => $data,
            'meta' => $meta,

        ]);
    }
    public function getLaporanKesesuaianObat()
    {
        // yang diambil hanya yang sistem bayar bpjs
        $sisba = SistemBayar::select('rs1')->where('groups', '1')->pluck('rs1')->toArray();
        $raw = Resepkeluarheder::select(
            'noresep',
            'tgl_permintaan',
            'dokter',
            'depo',
            'ruangan',
        )
            ->whereIn('sistembayar', $sisba)
            ->where('tgl_permintaan', 'LIKE', '%' . request('tahun') . '-' . request('bulan') . '%')
            // ->when(request('sistem_bayar'), function ($query) {
            //     $query->whereIn('sistembayar', request('sistem_bayar'));
            // })
            ->when(request('depo'), function ($query) {
                $query->whereIn('depo', request('depo'));
            })
            ->with([
                'rincian:noresep,kdobat,jumlah',
                'rincianracik:noresep,kdobat,jumlah',
                'permintaanresep:noresep,kdobat,jumlah',
                'permintaanracikan:noresep,kdobat,jumlah',

                'permintaanresep.mobat:kd_obat,nama_obat,kelompok_penyimpanan,status_generik,status_forkid,status_fornas,obat_kebijakan,jenis_perbekalan',
                'permintaanracikan.mobat:kd_obat,nama_obat,kelompok_penyimpanan,status_generik,status_forkid,status_fornas,obat_kebijakan,jenis_perbekalan',
                'ketdokter:kdpegsimrs,nama',
                'poli:rs1,rs2 as nama',
                'ruanganranap:rs1,rs2 as nama',
            ])
            ->whereIn('flag', ['1', '2', '3', '4'])

            ->paginate(100);
        $data = collect($raw)['data'];
        $meta = collect($raw)->except('data');
        return new JsonResponse([
            'req' => request()->all(),
            'data' => $data,
            'meta' => $meta,
            // 'sisba' => $sisba,
        ]);
    }
    public function getOptionKelompok()
    {
        $data = Mobatnew::select('jenis_perbekalan as kode', 'jenis_perbekalan as nama')
            ->distinct('jenis_perbekalan')
            ->groupBy('jenis_perbekalan')
            ->get();

        return new JsonResponse($data);
    }
    public function getOptionSistemBayar()
    {

        $data = SistemBayar::select('rs1 as kode', 'rs2 as nama', 'groups')
            ->where('hidden', '')
            ->get();

        return new JsonResponse($data);
    }
}
