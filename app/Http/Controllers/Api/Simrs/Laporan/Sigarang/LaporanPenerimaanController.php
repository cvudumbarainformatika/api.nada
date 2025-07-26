<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Sigarang;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\BarangRS;
use App\Models\Sigarang\MonthlyStokUpdate;
use App\Models\Sigarang\RecentStokUpdate;
use App\Models\Sigarang\Rekening50;
use App\Models\Sigarang\Transaksi\DistribusiDepo\DetailDistribusiDepo;
use App\Models\Sigarang\Transaksi\DistribusiDepo\DistribusiDepo;
use App\Models\Sigarang\Transaksi\Penerimaan\DetailPenerimaan;
use App\Models\Sigarang\Transaksi\Penerimaan\Penerimaan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanPenerimaanController extends Controller
{
    public function lappenerimaan()
    {
        $tgl = request('tgl');
        $tglx = request('tglx');
        $rek50 = Rekening50::select(
            'rekening50s.kode as kode',
            'rekening50s.uraian as uraian50',
        )->with([
            'barangrs' => function ($rincianpenerimaan) use ($tgl, $tglx) {
                $rincianpenerimaan->select(
                    'barang_r_s.kode_50',
                    // 'detail_penerimaans.kode_108 as kode_108',
                    // 'detail_penerimaans.uraian_108 as uraian_108',
                    // 'detail_penerimaans.nama_barang as nama_barang'
                    // 'detail_penerimaans.penerimaan_id',
                    'penerimaans.tanggal',
                    'detail_penerimaans.kode_rs',
                    'barang_r_s.kode_108 as kode_108',
                    'barang_r_s.uraian_108 as uraian_108',
                    'barang_r_s.kode',
                    'barang_r_s.nama as nama_barang',
                    DB::raw('sum(detail_penerimaans.qty*detail_penerimaans.harga) as subtotal'),
                )
                    ->join('detail_penerimaans', function ($detail) {
                        $detail->on('barang_r_s.kode', '=', 'detail_penerimaans.kode_rs')
                            ->join('penerimaans', 'penerimaans.id', '=', 'detail_penerimaans.penerimaan_id');
                        // ->join('penerimaans', function ($trm) {
                        //     $trm->on('penerimaans.id', '=', 'detail_penerimaans.penerimaan_id')
                        //         ->whereBetween('penerimaans.tanggal', [request('tgl'), request('tglx')]);
                        //     });
                        // ->whereBetween('penerimaans.tanggal', [request('tgl'), request('tglx')]);
                    })
                    ->whereBetween('penerimaans.tanggal', [request('tgl') . ' 00:00:00', request('tglx') . ' 23:59:59'])
                    ->when(request('kode_ruang'), function ($q) {
                        $q->where('barang_r_s.kode_depo', request('kode_ruang'));
                    })
                    ->groupBy('detail_penerimaans.kode_rs');
            }
        ])
            // ->whereHas('barangrs')
            ->Where('rekening50s.jenis', '02')->where('rekening50s.objek', '01')
            ->get();
        //$wew[] = $rek50[0]->kode50cari;
        return $rek50;
        // $rek50x = Rekening50::select(
        //     'rekening50s.kode as kode50',
        //     'rekening50s.uraian as uraian50'

        // )
        //     ->whereIn('rekening50s.kode50', $wew)
        //     ->get();

        // return $rek50x;

        // $judulsatu = Penerimaan::select(
        //     DB::raw('SUBSTRING_INDEX(detail_penerimaans.kode_50,".",4) as kode50'),
        //     'detail_penerimaans.uraian_50 as uraian50',
        //     DB::raw('sum(detail_penerimaans.qty*detail_penerimaans.harga) as total')
        // )
        //     ->join('detail_penerimaans', 'penerimaans.id', '=', 'detail_penerimaans.penerimaan_id')
        //     ->with('details.penerimaan')
        //     ->whereBetween('penerimaans.tanggal', [$tgl, $tglx])
        //     ->groupBy(DB::raw('SUBSTRING_INDEX(detail_penerimaans.kode_50,".",4)'))
        //     ->get();

        // return new JsonResponse($judulsatu);
    }

    public function lappersediaan()
    {
        $date = date_create(request('tahun') . '-' . request('bulan'));
        $date2 = date_create(request('tahun') . '-' . request('bulan'));
        $anu = date_format($date2, 'Y-m');
        $comp = $anu === date('Y-m');
        $temp = date_modify($date, '-1 months');
        $prev = date_format($temp, 'Y-m');
        $from = request('tahun') . '-' . request('bulan') . '-01 00:00:00';
        $to = request('tahun') . '-' . request('bulan') . '-31 23:59:59';
        $fromA = $prev . '-01 00:00:00';
        $toA = $prev . '-31 23:59:59';
        $depo = ['Gd-02010101', 'Gd-02010102', 'Gd-02010103'];
        if ($comp) {
            $recent = RecentStokUpdate::select('kode_rs')
                ->when(!request('kode_ruang'), function ($anu) use ($depo) {
                    $anu->whereIn('kode_ruang', $depo);
                })
                ->when(request('kode_ruang'), function ($anu) {
                    $anu->whereKodeRuang(request('kode_ruang'));
                })
                ->where('sisa_stok', '>', 0)->distinct()->orderBy('kode_rs', 'ASC')->get();
        } else {
            $recent = MonthlyStokUpdate::select('kode_rs')->distinct()
                ->when(!request('kode_ruang'), function ($anu) use ($depo) {
                    $anu->whereIn('kode_ruang', $depo);
                })
                ->when(request('kode_ruang'), function ($anu) {
                    $anu->whereKodeRuang(request('kode_ruang'));
                })
                ->where('sisa_stok', '>', 0)->whereBetween('tanggal', [$from, $to])->orderBy('kode_rs', 'ASC')->get();
        }
        $col = collect($recent);

        $barang = BarangRS::select('kode', 'nama', 'kode_satuan')
            ->whereIn('kode', $col)
            ->filter(request(['q']))
            ->with([
                'satuan:kode,nama',
                'monthly' => function ($m) use ($from, $to, $depo) {
                    $m->select('tanggal', 'harga', 'kode_rs', 'kode_ruang', 'sisa_stok')
                        ->selectRaw('round(sum(sisa_stok),2) as totalStok')
                        ->with('depo')
                        ->whereBetween('tanggal', [$from, $to])
                        ->when(!request('kode_ruang'), function ($anu) use ($depo) {
                            $anu->whereIn('kode_ruang', $depo);
                        })
                        ->when(request('kode_ruang'), function ($anu) {
                            $anu->whereKodeRuang(request('kode_ruang'));
                        })
                        ->groupBy('kode_rs', 'kode_ruang', 'harga');
                },
                'recent' => function ($m) use ($depo) {
                    $m->select('harga', 'kode_rs', 'kode_ruang', 'sisa_stok', 'no_penerimaan')
                        ->selectRaw('round(sum(sisa_stok),2) as totalStok')
                        ->with('depo')
                        ->where('sisa_stok', '>', 0)
                        ->when(!request('kode_ruang'), function ($anu) use ($depo) {
                            $anu->whereIn('kode_ruang', $depo);
                        })
                        ->when(request('kode_ruang'), function ($anu) {
                            $anu->whereKodeRuang(request('kode_ruang'));
                        })
                        ->groupBy('kode_rs', 'kode_ruang', 'harga');
                },
            ]);


        $data = $barang->get();

        return new JsonResponse($data);
    }

    public function lapPenerimaanGudang()
    {
        $data = Penerimaan::select(
            'penerimaans.tanggal',
            'penerimaans.no_penerimaan',
            'penerimaans.status',
            'penerimaans.surat_jalan',
            'penerimaans.faktur',
            'penerimaans.kode_perusahaan',
            'detail_penerimaans.kode_rs',
            'detail_penerimaans.harga_jadi as harga',
            'detail_penerimaans.ppn',
            'detail_penerimaans.sub_total',
            'detail_penerimaans.qty',
            'barang_r_s.nama',
            'satuans.nama as satuan',
            'recent_stok_updates.sisa_stok',
        )
            ->leftJoin('detail_penerimaans', function ($p) {
                $p->on('detail_penerimaans.penerimaan_id', '=', 'penerimaans.id')
                    ->leftJoin('barang_r_s', function ($b) {
                        $b->on('detail_penerimaans.kode_rs', '=', 'barang_r_s.kode')
                            ->leftJoin('satuans', function ($s) {
                                $s->on('satuans.kode', '=', 'barang_r_s.kode_satuan');
                            });
                    });
                // ->leftJoin('recent_stok_updates', function ($s) {
                //     $s->on('recent_stok_updates.kode_rs', '=', 'detail_penerimaans.kode_rs');
                // });
            })
            ->leftJoin('recent_stok_updates', function ($s) {
                $s->on('penerimaans.no_penerimaan', '=', 'recent_stok_updates.no_penerimaan')
                    ->on('detail_penerimaans.kode_rs', '=', 'recent_stok_updates.kode_rs')
                    ->where('recent_stok_updates.kode_ruang', 'Gd-02010100');
            })
            ->when(request('q'), function ($q) {
                $q->where(function ($a) {
                    $a->where('barang_r_s.kode', 'LIKE', '%' . request('q') . '%')
                        ->orWhere('barang_r_s.nama', 'LIKE', '%' . request('q') . '%');
                });
            })
            ->whereBetween('penerimaans.tanggal', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
            ->with('perusahaan')
            ->orderBy('penerimaans.tanggal', 'ASC')
            ->orderBy('penerimaans.no_penerimaan', 'ASC')
            ->paginate(request('per_page'));
        return new JsonResponse($data);
    }
    public function lapPenerimaanDepoNew()
    {
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
            ->whereBetween('tanggal', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
            ->where('status', '>=', 2)
            ->get();
        $kodeB = DetailDistribusiDepo::select('kode_rs')->whereIn('distribusi_depo_id', $idDist)->distinct('kode_rs')->get();

        $data = BarangRS::select('kode', 'nama', 'kode_satuan')
            ->whereIn('kode', $kodeB)
            ->filter(request(['q']))
            ->with([
                'satuan:kode,nama',
                'detailDistribusiDepo' => function ($detDist) use ($idDist, $kodeB) {
                    $detDist->select(
                        'distribusi_depo_id',
                        'kode_rs',
                        'jumlah',
                        'no_penerimaan',
                    )
                        ->whereIn('distribusi_depo_id', $idDist)
                        ->with([
                            'distribusi:id,tanggal,no_distribusi,status',
                            'recent' => function ($re) use ($kodeB) {
                                $re->select(
                                    'no_penerimaan',
                                    'kode_rs',
                                    'harga'
                                )
                                    ->whereIn('kode_rs', $kodeB);
                            }
                        ]);
                }
            ])
            ->orderBy('nama', 'ASC')
            ->paginate(request('per_page'));
        return new JsonResponse($data);
    }
    public function lapPenerimaanDepo()
    {
        $data = DistribusiDepo::select(
            'distribusi_depos.tanggal',
            'distribusi_depos.no_distribusi',
            'distribusi_depos.status',
            'detail_distribusi_depos.no_penerimaan',
            'detail_distribusi_depos.kode_rs',
            'detail_distribusi_depos.jumlah as qty',
            'barang_r_s.nama',
            'satuans.nama as satuan',
            'recent_stok_updates.harga',
            'recent_stok_updates.sisa_stok',
            DB::raw('detail_distribusi_depos.jumlah * recent_stok_updates.harga as sub_total')
        )
            ->leftJoin('detail_distribusi_depos', function ($p) {
                $p->on('detail_distribusi_depos.distribusi_depo_id', '=', 'distribusi_depos.id')
                    ->leftJoin('barang_r_s', function ($b) {
                        $b->on('detail_distribusi_depos.kode_rs', '=', 'barang_r_s.kode')
                            ->leftJoin('satuans', function ($s) {
                                $s->on('satuans.kode', '=', 'barang_r_s.kode_satuan');
                            });
                    })
                    // ->leftJoin('recent_stok_updates', function ($s) {
                    //     $s->on('recent_stok_updates.kode_rs', '=', 'detail_distribusi_depos.kode_rs');
                    // });
                    ->leftJoin('recent_stok_updates', function ($s) {
                        $s->on('recent_stok_updates.no_penerimaan', '=', 'detail_distribusi_depos.no_penerimaan')
                            ->on('recent_stok_updates.kode_rs', '=', 'detail_distribusi_depos.kode_rs')
                            ->when(request('kode_ruang'), function ($q) {
                                $depo = ['Gd-02010101', 'Gd-02010102', 'Gd-02010103'];
                                if (request('kode_ruang') === 'all') {
                                    $q->whereIn('recent_stok_updates.kode_ruang', $depo);
                                } else {
                                    $q->where('recent_stok_updates.kode_ruang', request('kode_ruang'));
                                }
                            });
                    });
            })
            ->when(request('q'), function ($q) {
                $q->where('barang_r_s.kode', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('barang_r_s.nama', 'LIKE', '%' . request('q') . '%');
            })
            ->when(request('kode_ruang'), function ($q) {
                $depo = ['Gd-02010101', 'Gd-02010102', 'Gd-02010103'];
                if (request('kode_ruang') === 'all') {
                    $q->whereIn('distribusi_depos.kode_depo', $depo);
                } else {
                    $q->where('distribusi_depos.kode_depo', request('kode_ruang'));
                }
            })
            ->whereBetween('distribusi_depos.tanggal', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
            ->where('status', 2)
            ->with('depo:nama')
            ->orderBy('distribusi_depos.tanggal', 'ASC')
            ->orderBy('distribusi_depos.tanggal', 'ASC')
            ->orderBy('distribusi_depos.no_distribusi', 'ASC')
            ->paginate(request('per_page'));
        return new JsonResponse($data);
    }
    public function stokOpnameGudang()
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
        $kodeDepo = ['Gd-02010101', 'Gd-02010102', 'Gd-02010103', 'Gd-02010100'];
        $kodeGudang = ['Gd-02010100'];
        if ($comp) {
            $recent = RecentStokUpdate::select('kode_rs')
                ->where('sisa_stok', '>', 0)
                ->whereIn('kode_ruang', $kodeGudang)
                ->distinct('kode_rs')->get();
        } else {
            $recent = MonthlyStokUpdate::select('kode_rs')->distinct('kode_rs')
                ->where('sisa_stok', '>', 0)
                ->whereIn('kode_ruang', $kodeGudang)
                ->whereBetween('tanggal', [$from, $to])->get();
        }
        $col = collect($recent)->map(function ($y) {
            return $y->kode_rs;
        });
        $trm = DetailPenerimaan::select('kode_rs')
            ->leftJoin('penerimaans', function ($p) {
                $p->on('penerimaans.id', '=', 'detail_penerimaans.penerimaan_id');
            })

            ->whereBetween('penerimaans.tanggal', [$fromN, $toN])
            ->where('penerimaans.status', '>', 2)->distinct('kode_rs')->get();
        $clTrm = collect($trm)->map(function ($y) {
            return $y->kode_rs;
        });
        $dist = DetailDistribusiDepo::select('kode_rs')
            ->leftJoin('distribusi_depos', function ($p) {
                $p->on('distribusi_depos.id', '=', 'detail_distribusi_depos.distribusi_depo_id');
            })
            ->whereBetween('distribusi_depos.tanggal', [$fromN, $toN])
            ->where('distribusi_depos.status', '>', 1)->distinct('kode_rs')->get();
        $clDist = collect($dist)->map(function ($y) {
            return $y->kode_rs;
        });

        $mrg = [];
        // array_merge($mrg, $col, $clTrm, $clDist);
        foreach ($col as $key) {
            $mrg[] = $key;
        }
        foreach ($clTrm as $key) {
            $mrg[] = $key;
        }
        foreach ($clDist as $key) {
            $mrg[] = $key;
        }
        $disti = array_unique($mrg);
        $thumb = collect();
        $barang = BarangRS::select('kode', 'nama', 'kode_satuan', 'kode_108', 'uraian_108', 'kode_depo')
            ->whereIn('kode', $disti)
            ->filter(request(['q']))
            ->with([
                'depo:kode,nama',
                'satuan:kode,nama',
                'monthly' => function ($m) use ($from, $to, $kodeGudang) {
                    $m->select('tanggal', DB::raw('sum(sisa_stok) as totalStok'), DB::raw('round(sisa_stok*harga,2) as totalRp'), 'harga', 'no_penerimaan', 'kode_rs', 'kode_ruang')
                        // ->selectRaw('round(sum(sisa_stok),2) as totalStok')
                        // ->selectRaw('round(sisa_stok*harga,2) as totalRp')
                        ->whereIn('kode_ruang', $kodeGudang)
                        ->whereBetween('tanggal', [$from, $to])
                        ->groupBy('kode_rs', 'harga');
                },
                'recent' => function ($m) use ($kodeGudang) {
                    $m->select(DB::raw('sum(sisa_stok) as totalStok'), DB::raw('round(sisa_stok*harga,2) as totalRp'), 'harga', 'kode_rs', 'kode_ruang',  'no_penerimaan')
                        // ->selectRaw('round(sum(sisa_stok),2) as totalStok')
                        // ->selectRaw('round(sisa_stok*harga,2) as totalRp')
                        ->whereIn('kode_ruang', $kodeGudang)
                        ->where('sisa_stok', '>', 0)
                        ->groupBy('kode_rs', 'harga', 'kode_ruang');
                },
                'stok_awal' => function ($m) use ($fromA, $toA, $kodeGudang) {
                    $m->select('tanggal', DB::raw('sum(sisa_stok) as totalStok'), 'harga', 'no_penerimaan', 'kode_rs', 'kode_ruang')
                        // ->selectRaw('round(sum(sisa_stok),2) as totalStok')
                        // ->selectRaw('round(sisa_stok*harga,2) as totalRp')
                        ->whereIn('kode_ruang', $kodeGudang)
                        ->whereBetween('tanggal', [$fromA, $toA])
                        ->groupBy('kode_rs', 'harga', 'kode_ruang');
                },
                'detailDistribusiDepo' => function ($m) use ($fromN, $toN, $col) {
                    $m->select(
                        'detail_distribusi_depos.kode_rs',
                        'detail_distribusi_depos.no_penerimaan',
                        'detail_distribusi_depos.jumlah as total',
                        'gudangs.nama as depo',
                        'distribusi_depos.kode_depo'
                    )
                        // ->selectRaw('round(sum(qty),2) as total')
                        // ->selectRaw('round(qty*harga_jadi,2) as totalRp')
                        ->leftJoin('distribusi_depos', function ($p) {
                            $p->on('distribusi_depos.id', '=', 'detail_distribusi_depos.distribusi_depo_id');
                        })
                        ->leftJoin('gudangs', function ($p) {
                            $p->on('gudangs.kode', '=', 'distribusi_depos.kode_depo');
                        })
                        ->with([
                            'recent' => function ($q) use ($col) {
                                $q->select('kode_rs', 'kode_ruang', 'no_penerimaan', 'harga')
                                    ->whereIn('kode_rs', $col)
                                    ->groupBy('kode_rs', 'no_penerimaan');
                            }
                        ])
                        ->whereBetween('distribusi_depos.tanggal', [$fromN, $toN])
                        ->where('distribusi_depos.status', '>', 1);
                },
                'detailPenerimaan' => function ($m) use ($fromN, $toN, $col) {
                    $m->select(
                        'detail_penerimaans.kode_rs',
                        'penerimaans.no_penerimaan',
                        'detail_penerimaans.qty as total',
                    )
                        // ->selectRaw('round(sum(qty),2) as total')
                        // ->selectRaw('round(qty*harga_jadi,2) as totalRp')
                        ->leftJoin('penerimaans', function ($p) {
                            $p->on('penerimaans.id', '=', 'detail_penerimaans.penerimaan_id');
                        })

                        ->whereBetween('penerimaans.tanggal', [$fromN, $toN])
                        ->where('penerimaans.status', '>=', 2);
                },



            ]);


        // $barang->orderBy('kode_depo', 'ASC')->orderBy('uraian_108', 'ASC')->orderBy('nama', 'ASC')
        //     ->chunk(50, function ($dokters) use ($thumb) {
        //         foreach ($dokters as $q) {
        //             $thumb->push($q);
        //         }
        //     });
        $data = $barang->orderBy('kode_depo', 'ASC')->orderBy('uraian_108', 'ASC')->orderBy('nama', 'ASC')->get();
        // foreach ($data as $barang) {
        //     foreach ($barang->detailPemakaianruangan as $det) {
        //         $det->append('harga');
        //     }
        // }

        // return new JsonResponse($data);
        return new JsonResponse([
            'data' => $data,
            // 'data' => $thumb,
            'col' => $col,
            'clTrm' => $clTrm,
            'clDist' => $clDist,
            'mrg' => $mrg,
            'disti' => $disti,
        ]);
    }
}
