<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Farmasi\Penerimaan;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Pemesanan\PemesananRinci;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanRinci;
use App\Models\Simrs\Penunjang\Farmasinew\RencanabeliH;
use App\Models\Simrs\Penunjang\Farmasinew\RencanabeliR;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanPerencanaanController extends Controller
{
    //
    public function perencanaanDanPenerimaan()
    {
        $no_rencana = RencanabeliH::select('no_rencbeliobat')
            ->whereBetween('tgl', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
            ->where('flag', '2')
            ->pluck('no_rencbeliobat');
        $no_terima = PenerimaanHeder::select('nopenerimaan', 'nopemesanan')
            ->whereBetween('tglpenerimaan', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
            ->where('kunci', '1')
            ->get();
        if (request('reference') == 'rencana') {
            $noperencanaan = $no_rencana;
            $nopemesanan = PemesananRinci::select('nopemesanan')->whereIn('noperencanaan', $no_rencana)->pluck('nopemesanan');
            $nopenerimaan = PenerimaanHeder::select('nopenerimaan')->whereIn('nopemesanan', $nopemesanan)->distinct('nopenerimaan')->pluck('nopenerimaan')->toArray();
        } else {
            $nopenerimaan = collect($no_terima)->pluck('nopenerimaan')->toArray();
            $nopemesanan = collect($no_terima)->pluck('nopemesanan')->toArray();
            $noperencanaan = PemesananRinci::select('noperencanaan')->whereIn('nopemesanan', $nopemesanan)->distinct('noperencanaan')->pluck('noperencanaan')->toArray();
        }

        $raw = Mobatnew::query()->select('kd_obat', 'nama_obat', 'satuan_k', 'uraian50')
            ->with([
                'penerimaanrinci' => function ($rinci) use ($nopenerimaan) {
                    $rinci->select(
                        'penerimaan_h.tglpenerimaan as tanggal',
                        'penerimaan_h.nopemesanan',
                        'penerimaan_r.nopenerimaan',
                        'penerimaan_r.kdobat',
                        'penerimaan_r.jml_terima_k as jumlah',
                        'penerimaan_r.harga_netto_kecil as harga'
                    )
                        ->join('penerimaan_h', 'penerimaan_r.nopenerimaan', '=', 'penerimaan_h.nopenerimaan')
                        ->whereIn('penerimaan_r.nopenerimaan', $nopenerimaan);
                },
                'perencanaanrinci' => function ($rinci) use ($noperencanaan) {
                    $rinci->select(
                        'perencana_pebelian_h.tgl as tanggal',
                        'perencana_pebelian_r.no_rencbeliobat as noperencanaan',
                        'perencana_pebelian_r.kdobat',
                        'perencana_pebelian_r.jumlah_diverif as jumlah',
                        'perencana_pebelian_r.jumlahdirencanakan as diajukan'
                    )
                        ->join('perencana_pebelian_h', 'perencana_pebelian_r.no_rencbeliobat', '=', 'perencana_pebelian_h.no_rencbeliobat')
                        ->whereIn('perencana_pebelian_r.no_rencbeliobat', $noperencanaan);
                },
                'pemesananrinci' => function ($rinci) use ($nopemesanan) {
                    $rinci->select(
                        'pemesanan_h.tgl_pemesanan as tanggal',
                        'pemesanan_r.nopemesanan',
                        'pemesanan_r.noperencanaan',
                        'pemesanan_r.kdobat',
                        'pemesanan_r.harga',
                        'pemesanan_r.jumlahdpesan as jumlah'
                    )
                        ->join('pemesanan_h', 'pemesanan_r.nopemesanan', '=', 'pemesanan_h.nopemesanan')
                        ->where('pemesanan_r.flag', '!=', '2') // ini
                        ->whereIn('pemesanan_r.nopemesanan', $nopemesanan);
                },
            ])
            ->addSelect([
                'hargapenerimaan' => PenerimaanRinci::query()
                    ->selectRaw('harga_netto_kecil as harga')
                    // ->selectRaw('nopenerimaan, kdobat, harga_netto_kecil as harga')
                    // ->select(
                    //     DB::raw('SUBSTRING_INDEX(penerimaan_r.nopenerimaan, ".", 1) as kode1'),
                    // )
                    ->whereColumn('new_masterobat.kd_obat', '=', 'penerimaan_r.kdobat')
                    ->join('penerimaan_h', 'penerimaan_r.nopenerimaan', '=', 'penerimaan_h.nopenerimaan')
                    ->whereDate('penerimaan_h.tglpenerimaan', '<', request('from'))
                    ->orderBy('penerimaan_h.tglpenerimaan', 'desc')
                    ->limit(1)
            ])
            ->addSelect([
                'tglpenerimaan' => PenerimaanRinci::query()
                    ->selectRaw('tglpenerimaan')
                    // ->selectRaw('nopenerimaan, kdobat, harga_netto_kecil as harga')
                    // ->select(
                    //     DB::raw('SUBSTRING_INDEX(penerimaan_r.nopenerimaan, ".", 1) as kode1'),
                    // )
                    ->whereColumn('new_masterobat.kd_obat', '=', 'penerimaan_r.kdobat')
                    ->join('penerimaan_h', 'penerimaan_r.nopenerimaan', '=', 'penerimaan_h.nopenerimaan')
                    ->whereDate('penerimaan_h.tglpenerimaan', '<', request('from'))
                    ->orderBy('penerimaan_h.tglpenerimaan', 'desc')
                    ->limit(1)
            ])
            ->when(request('q'), function ($q) {
                $q->where('kd_obat', 'like', '%' . request('q') . '%')
                    ->orWhere('nama_obat', 'like', '%' . request('q') . '%');
            })
            ->paginate(request('per_page'));
        $data = collect($raw)['data'];
        $meta = collect($raw)->except('data');
        return new JsonResponse([
            'data' => $data,
            'meta' => $meta,
            'nopenerimaan' => $nopenerimaan,
            'nopemesanan' => $nopemesanan,
            'noperencanaan' => $noperencanaan,
            'req' => request()->all()
        ]);
    }
}
