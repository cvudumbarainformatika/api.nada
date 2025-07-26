<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Pemesanan;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Pemesanan\PemesananRinci;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use App\Models\Simrs\Penunjang\Farmasinew\RencanabeliH;
use App\Models\Simrs\Penunjang\Farmasinew\RencanabeliR;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DialogrencanapemesananController extends Controller
{
    public function dialogrencanabeli()
    {
        $rencanabeli = RencanabeliH::select(
            'perencana_pebelian_h.no_rencbeliobat',
        )
            ->leftJoin('perencana_pebelian_r', 'perencana_pebelian_h.no_rencbeliobat', '=', 'perencana_pebelian_r.no_rencbeliobat')
            ->leftJoin('new_masterobat', 'perencana_pebelian_r.kdobat', '=', 'new_masterobat.kd_obat')
            ->leftJoin('pemesanan_r', 'new_masterobat.kd_obat', '=', 'pemesanan_r.kdobat')
            ->where('perencana_pebelian_h.flag', '2')
            ->where('perencana_pebelian_r.flag', '')
            ->where('perencana_pebelian_h.no_rencbeliobat', 'Like', '%' . request('no_rencbeliobat') . '%')
            ->when(request('obat'),function($q){
                $obat=Mobatnew::select('kd_obat')->where('nama_obat', 'LIKE', '%' . request('obat') . '%')->pluck('kd_obat');
                $q->whereIn('perencana_pebelian_r.kdobat', $obat);
            })
            ->groupby('perencana_pebelian_h.no_rencbeliobat', 'perencana_pebelian_r.kdobat')
            ->distinct('perencana_pebelian_h.no_rencbeliobat')
            ->pluck('perencana_pebelian_h.no_rencbeliobat');
        // $raw = collect($rencanabeli);
        // $nomor = $raw->map(function ($item) {

        //     return $item->no_rencbeliobat;
        // });

        $data = RencanabeliH::with('gudang:kode,nama')->whereIn('no_rencbeliobat', $rencanabeli)
            ->where('no_rencbeliobat', 'LIKE', '%' . request('no_rencbeliobat') . '%')
            
            ->orderBy('tgl', 'desc')
            ->paginate(request('per_page'));


        return new JsonResponse($data);
    }

    public function dialogrencanabeli_rinci()
    {
        $data = RencanabeliH::select(
            'perencana_pebelian_h.no_rencbeliobat',
            'perencana_pebelian_h.no_rencbeliobat as noperencanaan',
            'perencana_pebelian_h.kd_ruang',
            'perencana_pebelian_h.tgl as tglperencanaan',
            'perencana_pebelian_r.kdobat as kdobat',
            'perencana_pebelian_r.stok_real_gudang as stokgudang',
            'perencana_pebelian_r.stok_real_rs as stokrs',
            'perencana_pebelian_r.stok_max_rs as stomaxkrs',
            'perencana_pebelian_r.jumlah_bisa_dibeli',
            'perencana_pebelian_r.jumlah_diverif',
            'perencana_pebelian_r.jumlahdirencanakan as jumlahdipesandiperencanaan',
            'new_masterobat.nama_obat as namaobat',
            'new_masterobat.status_generik as status_generik',
            'new_masterobat.status_fornas as status_fornas',
            'new_masterobat.status_forkid as status_forkid',
            'new_masterobat.status_kronis',
            'new_masterobat.status_prb',
            'new_masterobat.sistembayar as sistembayar',
            'new_masterobat.satuan_b as satuan_b',
            'new_masterobat.satuan_k as satuan_k',
            'perencana_pebelian_r.flag as flagperobat',
            'perencana_pebelian_h.kd_ruang as gudang',
            'pemesanan_r.flag as flag_pesan',
            'pemesanan_r.jumlahdpesan as jumlah',
            DB::raw('sum(pemesanan_r.jumlahdpesan) as jumlahallpesan'),
            // DB::raw("SUM(CASE WHEN pemesanan_r.flag = '' THEN pemesanan_r.jumlahdpesan ELSE 0 END) as jumlahallpesan"),
            DB::raw("SUM(CASE WHEN pemesanan_r.flag = '1' THEN pemesanan_r.jumlahdpesan ELSE 0 END) as pesananditerimasemua"),
            DB::raw("SUM(CASE WHEN pemesanan_r.flag = '2' THEN pemesanan_r.jumlahdpesan ELSE 0 END) as ditolak"),
        )
            ->leftJoin('perencana_pebelian_r', 'perencana_pebelian_h.no_rencbeliobat', '=', 'perencana_pebelian_r.no_rencbeliobat')
            ->leftJoin('new_masterobat', 'perencana_pebelian_r.kdobat', '=', 'new_masterobat.kd_obat')
            ->leftJoin('pemesanan_r', function ($q) {
                $q->on('pemesanan_r.kdobat', '=', 'perencana_pebelian_r.kdobat')
                    ->on('pemesanan_r.noperencanaan', '=', 'perencana_pebelian_r.no_rencbeliobat');
            })
            ->where('perencana_pebelian_h.flag', '2')
            ->where('perencana_pebelian_r.flag', '')
            // ->where(function ($anu) {
            //     $anu->whereNull('pemesanan_r.flag')
            //         ->orWhere('pemesanan_r.flag', '2');
            // })
            // ->where('perencana_pebelian_h.no_rencbeliobat', 'Like', '%' . request('no_rencbeliobat') . '%')
            ->where('perencana_pebelian_h.no_rencbeliobat', '=',  request('no_rencbeliobat'))
            // ->where('pemesanan_r.noperencanaan', '=',  request('no_rencbeliobat'))
            ->with([
                'rincian' => function ($re) {
                    $re->select('no_rencbeliobat', 'kdobat', 'jumlah_diverif', 'flag')
                        ->with([
                            'penerimaan' => function ($pen) {
                                $pen->select(
                                    'kdobat',
                                    'harga_kcl as harga'
                                )
                                    ->orderBy('id', 'DESC')
                                    ->limit(1);
                            },
                            // 'stok' => function ($pen) {
                            //     $pen->select(
                            //         'kdobat',
                            //         'harga'
                            //     )
                            //         ->orderBy('id', 'DESC')
                            //         ->limit(request('per_page'));
                            // },
                            'harga:harga,kd_obat',
                            // 'pesanan:jumlahdpesan,kdobat,nopemesanan,noperencanaan,flag',
                            // 'pesanan.penerimaan:nopemesanan,nopenerimaan,kunci',
                            // 'pesanan.penerimaan.penerimaanrinci:nopenerimaan,jml_terima_k,kdobat'
                            // 'pesanan' => function ($p) {
                            //     $p->select(
                            //         'pemesanan_r.jumlahdpesan',
                            //         'pemesanan_r.kdobat',
                            //         'pemesanan_r.nopemesanan',
                            //         'pemesanan_r.noperencanaan',
                            //         'pemesanan_r.flag',
                            //     )
                            //         ->with([
                            //             'penerimaan' => function ($q) {
                            //                 $q->select(
                            //                     'penerimaan_h.nopemesanan',
                            //                     'penerimaan_h.kunci',
                            //                     'penerimaan_r.jml_terima_k as jumlah',
                            //                     'penerimaan_r.kdobat',
                            //                 )
                            //                     ->leftJoin('penerimaan_r', 'penerimaan_r.nopenerimaan', '=', 'penerimaan_h.nopenerimaan');
                            //             }
                            //         ]);
                            // }
                        ])
                        ->where('flag', '');
                },

            ])
            ->groupby('perencana_pebelian_h.no_rencbeliobat', 'perencana_pebelian_r.kdobat')
            ->orderBy('perencana_pebelian_h.tgl')
            ->orderBy('new_masterobat.nama_obat')
            ->get();
        // $ren = [];
        // $rren = collect($data)->map(function ($q) {
        //     return $q->noperencanaan;
        // });
        // foreach ($rren as $kuy) {
        //     $ren[] = $kuy;
        // }
        // $uren = array_unique($ren);
        // $rinpe = PemesananRinci::select('nopemesanan')->whereIn('noperencanaan', $uren)->distinct('nopemesanan')->get('nopemesanan');
        // $terima = PenerimaanHeder::select(
        //     'penerimaan_r.kdobat',
        //     DB::raw('sum(jml_terima_k) as jumlah')
        // )
        //     ->leftJoin('penerimaan_r', 'penerimaan_r.nopenerimaan', '=', 'penerimaan_h.nopenerimaan')
        //     ->whereIn('penerimaan_h.nopemesanan', $rinpe)
        //     ->groupby('penerimaan_r.kdobat')
        //     ->get();
        // return new JsonResponse([
        //     'data' => $data,
        //     'terima' => $terima,
        // ]);
        return new JsonResponse($data);
        // ->paginate(request('per_page'));
        // $rencanabelirinci = RencanabeliR::with(['mobat'])->where('no_rencbeliobat', request('norencanabeliobat'))->get();
        // return new JsonResponse($rencanabelirinci);
    }
}
