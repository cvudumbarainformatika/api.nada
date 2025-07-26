<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Sigarang;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\BarangRS;
use App\Models\Sigarang\MonthlyStokUpdate;
use App\Models\Sigarang\RecentStokUpdate;
use App\Models\Sigarang\Transaksi\DistribusiDepo\DetailDistribusiDepo;
use App\Models\Sigarang\Transaksi\DistribusiDepo\DistribusiDepo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanMutasiGudangController extends Controller
{
    //
    public function lapMutasi()
    {
        $date = date_create(request('tahun') . '-' . request('bulan'));
        $date2 = date_create(request('tahun') . '-' . request('bulan'));
        $anu = date_format($date2, 'Y-m');
        $comp = $anu === date('Y-m');
        $temp = date_modify($date, '-1 months');
        $prev = date_format($temp, 'Y-m');
        $from = request('tahun') . '-' . request('bulan') . '-01 00:00:00';
        $to = request('tahun') . '-' . request('bulan') . '-31 23:59:59';
        $fromN = request('tahun') . '-' . request('bulan') . '-01';
        $toN = request('tahun') . '-' . request('bulan') . '-31';
        $fromA = $prev . '-01 00:00:00';
        $toA = $prev . '-31 23:59:59';
        if ($comp) {
            $recent = RecentStokUpdate::select('kode_rs')
                ->where('sisa_stok', '>', 0)->distinct()->orderBy('kode_rs', 'ASC')->get();
        } else {
            $recent = MonthlyStokUpdate::select('kode_rs')->distinct()
                ->where('sisa_stok', '>', 0)->whereBetween('tanggal', [$from, $to])->orderBy('kode_rs', 'ASC')->get();
        }
        $col = collect($recent);

        $barang = BarangRS::select('kode', 'nama', 'kode_satuan', 'kode_108', 'uraian_108')
            ->whereIn('kode', $col)
            ->when(request('kode_ruang'), function ($q) {
                $q->where('kode_depo', request('kode_ruang'));
            })
            ->filter(request(['q']))
            ->with([
                'satuan:kode,nama',
                'monthly' => function ($m) use ($from, $to) {
                    $m->select('tanggal', 'sisa_stok as totalStok', 'harga', 'no_penerimaan', 'kode_rs', 'kode_ruang')
                        // ->selectRaw('round(sum(sisa_stok),2) as totalStok')
                        ->selectRaw('round(sisa_stok*harga,2) as totalRp')
                        ->whereBetween('tanggal', [$from, $to]);
                    // ->groupBy('kode_rs', 'harga');
                },
                'recent' => function ($m) {
                    $m->select('sisa_stok as totalStok', 'harga', 'kode_rs', 'kode_ruang',  'no_penerimaan')
                        // ->selectRaw('round(sum(sisa_stok),2) as totalStok')
                        ->selectRaw('round(sisa_stok*harga,2) as totalRp')
                        ->where('sisa_stok', '>', 0);
                    // ->groupBy('kode_rs', 'harga', 'kode_ruang');
                },
                'stok_awal' => function ($m) use ($fromA, $toA) {
                    $m->select('tanggal', 'sisa_stok as totalStok', 'harga', 'no_penerimaan', 'kode_rs', 'kode_ruang')
                        // ->selectRaw('round(sum(sisa_stok),2) as totalStok')
                        ->selectRaw('round(sisa_stok*harga,2) as totalRp')
                        ->whereBetween('tanggal', [$fromA, $toA]);
                    // ->groupBy('kode_rs', 'harga', 'kode_ruang');
                },
                'detailPenerimaan' => function ($m) use ($fromN, $toN) {
                    $m->select(
                        'detail_penerimaans.kode_rs',
                        'detail_penerimaans.qty as total',
                    )
                        // ->selectRaw('round(sum(qty),2) as total')
                        ->selectRaw('round(qty*harga_jadi,2) as totalRp')
                        ->leftJoin('penerimaans', function ($p) {
                            $p->on('penerimaans.id', '=', 'detail_penerimaans.penerimaan_id');
                        })
                        ->whereBetween('penerimaans.tanggal', [$fromN, $toN])
                        ->where('penerimaans.status', '>', 1);
                },
                'detailDistribusiLangsung' => function ($m) use ($from, $to) {
                    $m->select(
                        'distribusi_langsungs.ruang_tujuan',
                        'detail_distribusi_langsungs.kode_rs',
                        'detail_distribusi_langsungs.no_penerimaan',
                        'detail_distribusi_langsungs.jumlah as total',
                    )
                        ->leftJoin('distribusi_langsungs', function ($p) {
                            $p->on('distribusi_langsungs.id', '=', 'detail_distribusi_langsungs.distribusi_langsung_id');
                        })
                        ->whereBetween('distribusi_langsungs.tanggal', [$from, $to])
                        // ->with('recentstok')
                        ->where('distribusi_langsungs.status', '>', 1);
                },
                'detailPemakaianruangan' => function ($m) use ($from, $to) {
                    $m->select(
                        'details_pemakaianruangans.kode_rs',
                        'details_pemakaianruangans.no_penerimaan',
                        'details_pemakaianruangans.jumlah as total',
                        'pemakaianruangans.tanggal',
                        'pemakaianruangans.kode_ruang',
                    )
                        ->leftJoin('pemakaianruangans', function ($p) {
                            $p->on('pemakaianruangans.id', '=', 'details_pemakaianruangans.pemakaianruangan_id');
                        })
                        // ->with([
                        //     'barangrs' => function ($q) {
                        //         $q->select('kode');
                        //     }
                        // ])
                        ->whereBetween('pemakaianruangans.tanggal', [$from, $to])
                        ->where('pemakaianruangans.status', '>', 1);
                },

            ]);


        $data = $barang->orderBy('kode_108', 'ASC')->withTrashed()->get();
        // foreach ($data as $barang) {
        //     foreach ($barang->detailPemakaianruangan as $det) {
        //         $det->append('harga');
        //     }
        // }

        return new JsonResponse($data);
    }
    public function lapMutasiDepo()
    {
        $date = date_create(request('tahun') . '-' . request('bulan'));
        $date2 = date_create(request('tahun') . '-' . request('bulan'));
        $anu = date_format($date2, 'Y-m');
        $comp = $anu === date('Y-m');
        $temp = date_modify($date, '-1 months');
        $prev = date_format($temp, 'Y-m');
        $from = request('tahun') . '-' . request('bulan') . '-01 00:00:00';
        $to = request('tahun') . '-' . request('bulan') . '-31 23:59:59';
        $fromN = request('tahun') . '-' . request('bulan') . '-01';
        $toN = request('tahun') . '-' . request('bulan') . '-31';
        $fromA = $prev . '-01 00:00:00';
        $toA = $prev . '-31 23:59:59';
        $kodeDepo = request('kode_ruang') === 'all' ? ['Gd-02010101', 'Gd-02010102', 'Gd-02010103'] : request(['kode_ruang']);
        if ($comp) {
            $recent = RecentStokUpdate::select('kode_rs')
                ->where('sisa_stok', '>', 0)
                ->whereIn('kode_ruang', $kodeDepo)
                ->distinct('kode_rs')->orderBy('kode_rs', 'ASC')->get();
        } else {
            $recent = MonthlyStokUpdate::select('kode_rs')->distinct('kode_rs')
                ->where('sisa_stok', '>', 0)
                ->whereIn('kode_ruang', $kodeDepo)
                // ->whereBetween('tanggal', [$from, $to])
                ->where('tanggal', 'LIKE', '%' . $anu . '%')
                ->orderBy('kode_rs', 'ASC')->get();
        }
        $col = collect($recent);

        $barang = BarangRS::select('kode', 'nama', 'kode_satuan', 'kode_108', 'uraian_108')
            ->whereIn('kode', $col)
            ->filter(request(['q']))
            ->with([
                'satuan:kode,nama',
                'monthly' => function ($m) use ($from, $to, $kodeDepo, $anu) {
                    $m->select('tanggal', 'sisa_stok as totalStok', 'harga', 'no_penerimaan', 'kode_rs', 'kode_ruang')
                        // ->selectRaw('round(sum(sisa_stok),2) as totalStok')
                        ->selectRaw('round(sisa_stok*harga,2) as totalRp')
                        ->whereIn('kode_ruang', $kodeDepo)
                        // ->whereBetween('tanggal', [$from, $to]);
                        ->where('tanggal', 'LIKE', '%' . $anu . '%');
                    // ->groupBy('kode_rs', 'harga');
                },
                'recent' => function ($m) use ($kodeDepo) {
                    $m->select('sisa_stok as totalStok', 'harga', 'kode_rs', 'kode_ruang',  'no_penerimaan')
                        // ->selectRaw('round(sum(sisa_stok),2) as totalStok')
                        ->selectRaw('round(sisa_stok*harga,2) as totalRp')
                        ->whereIn('kode_ruang', $kodeDepo)
                        ->where('sisa_stok', '>', 0);
                    // ->groupBy('kode_rs', 'harga', 'kode_ruang');
                },
                'stok_awal' => function ($m) use ($fromA, $toA, $kodeDepo, $prev) {
                    $m->select('tanggal', 'sisa_stok as totalStok', 'harga', 'no_penerimaan', 'kode_rs', 'kode_ruang')
                        // ->selectRaw('round(sum(sisa_stok),2) as totalStok')
                        ->selectRaw('round(sisa_stok*harga,2) as totalRp')
                        ->whereIn('kode_ruang', $kodeDepo)
                        ->where('tanggal', 'LIKE', '%' . $prev . '%');
                    // ->whereBetween('tanggal', [$fromA, $toA]);
                    // ->groupBy('kode_rs', 'harga', 'kode_ruang');
                },
                'detailDistribusiDepo' => function ($m) use ($fromN, $toN, $col, $anu) {
                    $m->select(
                        'detail_distribusi_depos.kode_rs',
                        'detail_distribusi_depos.no_penerimaan',
                        'detail_distribusi_depos.jumlah as total',
                    )
                        // ->selectRaw('round(sum(qty),2) as total')
                        // ->selectRaw('round(qty*harga_jadi,2) as totalRp')
                        ->leftJoin('distribusi_depos', function ($p) {
                            $p->on('distribusi_depos.id', '=', 'detail_distribusi_depos.distribusi_depo_id');
                        })
                        ->with([
                            'recent' => function ($q) use ($col) {
                                $q->select('kode_rs', 'kode_ruang', 'no_penerimaan', 'harga')
                                    ->whereIn('kode_rs', $col)
                                    ->groupBy('kode_rs', 'no_penerimaan');
                            }
                        ])
                        ->where('distribusi_depos.tanggal', 'LIKE', '%' . $anu . '%')
                        // ->whereBetween('distribusi_depos.tanggal', [$fromN, $toN])
                        ->where('distribusi_depos.status', '>', 1);
                },
                'detailDistribusiLangsung' => function ($m) use ($from, $to, $col, $anu) {
                    $m->select(
                        'distribusi_langsungs.ruang_tujuan',
                        'detail_distribusi_langsungs.kode_rs',
                        'detail_distribusi_langsungs.no_penerimaan',
                        'detail_distribusi_langsungs.jumlah as total',
                    )
                        ->leftJoin('distribusi_langsungs', function ($p) {
                            $p->on('distribusi_langsungs.id', '=', 'detail_distribusi_langsungs.distribusi_langsung_id');
                        })
                        ->when(request('kode_ruang') !== 'all' && request('kode_ruang') !== 'Gd-02010102', function ($q) {
                            $q->where('distribusi_langsungs.ruang_tujuan', request('kode_ruang'));
                        })
                        ->where('distribusi_langsungs.tanggal', 'LIKE', '%' . $anu . '%')
                        // ->whereBetween('distribusi_langsungs.tanggal', [$from, $to])
                        // ->with('recentstok')
                        ->with([
                            'stokruangan' => function ($q) use ($col) {
                                $q->select('kode_rs', 'kode_ruang', 'no_penerimaan', 'harga')
                                    ->whereIn('kode_rs', $col)
                                    ->groupBy('kode_rs', 'no_penerimaan');
                            }
                        ])
                        ->where('distribusi_langsungs.status', '>', 1);
                },
                'detailPermintaanruangan' => function ($m) use ($from, $to, $col, $anu) {

                    $m->select(
                        'detail_permintaanruangans.kode_rs',
                        'detail_permintaanruangans.no_penerimaan',
                        'detail_permintaanruangans.tujuan',
                        'detail_permintaanruangans.jumlah_distribusi as total',
                        'detail_permintaanruangans.permintaanruangan_id',
                        'permintaanruangans.kode_ruang',
                        'permintaanruangans.tanggal_verif as tanggal',
                        'permintaanruangans.id',
                    )
                        ->leftJoin('permintaanruangans', function ($p) {
                            $p->on('permintaanruangans.id', '=', 'detail_permintaanruangans.permintaanruangan_id');
                        })
                        ->with([

                            'permintaanruangan' => function ($per) use ($col) {
                                $per->select('id', 'no_distribusi')
                                    ->with([
                                        'penerimaan' => function ($anu) use ($col) {
                                            $anu->select('id', 'kode_rs', 'no_distribusi', 'no_penerimaan', 'jumlah')
                                                ->with([
                                                    'stokruangan' => function ($q) use ($col) {
                                                        $q->select('kode_rs', 'kode_ruang', 'no_penerimaan', 'harga')
                                                            ->whereIn('kode_rs', $col)
                                                            ->groupBy('kode_rs', 'no_penerimaan');
                                                    },
                                                ]);
                                        }
                                    ]);
                            }
                        ])
                        ->where('permintaanruangans.tanggal_verif', 'LIKE', '%' . $anu . '%')
                        // ->whereBetween('permintaanruangans.tanggal_verif', [$from, $to])
                        ->where('permintaanruangans.status', '>=', 7)
                        ->where('detail_permintaanruangans.jumlah_distribusi', '>', 0);
                },


            ]);


        $data = $barang->orderBy('kode_108', 'ASC')->orderBy('nama', 'ASC')->get();
        // $data = $anu;
        // foreach ($data as $barang) {
        //     foreach ($barang->detailPemakaianruangan as $det) {
        //         $det->append('harga');
        //     }
        // }

        return new JsonResponse($data);
    }

    public function lapRekapMasukDepo()
    {
        $from = request('year') . '-01-01 00:00:00';
        $to = request('year') . '-12-31 23:59:59';
        $idDist = DistribusiDepo::select(
            'id'
        )

            ->when(request('kode_ruang'), function ($q) {
                $depo = ['Gd-02010101', 'Gd-02010102', 'Gd-02010103'];
                if (request('kode_ruang') === 'all') {
                    $q->whereIn('distribusi_depos.kode_depo', $depo);
                } else {
                    $q->where('distribusi_depos.kode_depo', request('kode_ruang'));
                }
            })
            ->whereBetween('tanggal', [$from, $to])
            ->where('status', '>=', 2)
            ->get();
        $kodeB = DetailDistribusiDepo::select('kode_rs')->whereIn('distribusi_depo_id', $idDist)->distinct('kode_rs')->get();

        $data = BarangRS::select('kode', 'nama', 'kode_satuan')
            ->whereIn('kode', $kodeB)
            ->filter(request(['q']))
            ->with([
                'satuan:kode,nama',
                'detailDistribusiDepo' => function ($detDist) use ($idDist, $from, $to) {
                    $detDist->select(
                        'detail_distribusi_depos.distribusi_depo_id',
                        'detail_distribusi_depos.kode_rs',
                        'detail_distribusi_depos.jumlah',
                        'detail_distribusi_depos.no_penerimaan',
                        'distribusi_depos.tanggal',
                    )
                        ->join('distribusi_depos', 'distribusi_depos.id', '=', 'detail_distribusi_depos.distribusi_depo_id')
                        ->whereBetween('distribusi_depos.tanggal', [$from, $to])
                        ->whereIn('detail_distribusi_depos.distribusi_depo_id', $idDist)
                        // ->with([
                        // 'distribusi:id,tanggal,no_distribusi,status',
                        // 'recent' => function ($re) use ($kodeB) {
                        //     $re->select(
                        //         'no_penerimaan',
                        //         'kode_rs',
                        //         'harga'
                        //     )
                        //         ->whereIn('kode_rs', $kodeB);
                        // }
                        // ])
                    ;
                }
            ])
            ->orderBy('nama', 'ASC')
            ->paginate(request('per_page'));
        return new JsonResponse($data);
    }
}
