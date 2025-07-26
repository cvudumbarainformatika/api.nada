<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Sigarang;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\BarangRS;
use App\Models\Sigarang\Ruang;
use App\Models\Sigarang\Transaksi\DistribusiLangsung\DetailDistribusiLangsung;
use App\Models\Sigarang\Transaksi\Pemakaianruangan\DetailsPemakaianruangan;
use App\Models\Sigarang\Transaksi\Permintaanruangan\DetailPermintaanruangan;
use App\Models\Sigarang\Transaksi\Permintaanruangan\Permintaanruangan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanRuanganController extends Controller
{
    public function getBarang()
    {
        $minta = DetailPermintaanruangan::distinct()->get('kode_rs');
        $dist = DetailDistribusiLangsung::distinct()->get('kode_rs');
        // return new JsonResponse(['minta' => $minta, 'dist' => $dist]);
        $data = BarangRS::select(
            'kode',
            'nama',
        )
            ->whithTrased()
            ->get();
        return new JsonResponse($data);
    }

    public function lapPengeluaranDepoNew()
    {
        $minta = DetailPermintaanruangan::select('kode_rs')->distinct()
            ->leftJoin('permintaanruangans', function ($p) {
                $p->on('permintaanruangans.id', '=', 'detail_permintaanruangans.permintaanruangan_id');
            })
            ->whereBetween('permintaanruangans.tanggal', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
            ->whereIn('permintaanruangans.status', [7, 8])
            ->where('detail_permintaanruangans.jumlah_distribusi', '>', 0)
            ->when(request('kode_ruang'), function ($q) {
                $q->where('permintaanruangans.dari', request('kode_ruang'));
            })
            ->when(request('q'), function ($q) {
                $anu = BarangRS::select('kode')->where('barang_r_s.kode', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('barang_r_s.nama', 'LIKE', '%' . request('q') . '%')->get();
                $q->whereIn('detail_permintaanruangans.kode_rs', $anu);
            })
            ->get();
        $dist = DetailDistribusiLangsung::select('kode_rs')->distinct()
            ->leftJoin('distribusi_langsungs', function ($p) {
                $p->on('distribusi_langsungs.id', '=', 'detail_distribusi_langsungs.distribusi_langsung_id');
            })
            ->when(request('q'), function ($q) {
                $anu = BarangRS::select('kode')->where('barang_r_s.kode', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('barang_r_s.nama', 'LIKE', '%' . request('q') . '%')->get();
                $q->whereIn('detail_distribusi_langsungs.kode_rs', $anu);
            })
            ->whereBetween('distribusi_langsungs.tanggal', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
            ->where('detail_distribusi_langsungs.jumlah', '>', 0)
            ->get();

        $kode_ruang = request('kode_ruang') ?? false;
        if ($kode_ruang) {
            if ($kode_ruang === 'Gd-02010102') {
                $minta = [];
            } else {
                $dist = [];
            }
        }
        $data = BarangRS::select(
            'kode',
            'nama',
            'kode_satuan',
            'kode_depo',
        )
            ->with([
                'satuan:kode,nama',
                'detailPermintaanruangan' => function ($mi) use ($minta) {
                    $mi->select([
                        'detail_permintaanruangans.no_penerimaan',
                        'detail_permintaanruangans.kode_rs',
                        'permintaanruangans.tanggal',
                        'ruangs.uraian as tujuan',
                        DB::raw('ROUND(sum(detail_permintaanruangans.jumlah),2) as jumlah'),
                        DB::raw('ROUND(sum(detail_permintaanruangans.jumlah_disetujui),2) as jumlah_disetujui'),
                        DB::raw('ROUND(sum(detail_permintaanruangans.jumlah_distribusi),2) as jumlah_distribusi'),
                    ])->leftJoin('permintaanruangans', function ($b) {
                        $b->on('permintaanruangans.id', '=', 'detail_permintaanruangans.permintaanruangan_id');
                    })->leftJoin('ruangs', function ($p) {
                        $p->on('ruangs.kode', '=', 'detail_permintaanruangans.tujuan');
                    })->where(function ($q) use ($minta) {
                        $q->whereBetween('permintaanruangans.tanggal_distribusi', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
                            ->where('detail_permintaanruangans.jumlah_distribusi', '>', 0)
                            ->whereIn('detail_permintaanruangans.kode_rs', $minta);
                    })
                        ->groupBy(
                            'permintaanruangans.tanggal',
                            'detail_permintaanruangans.tujuan',
                            'detail_permintaanruangans.kode_rs',
                        );
                },
                'detailDistribusiLangsung' => function ($la) use ($dist) {
                    $la->select([
                        'distribusi_langsungs.tanggal',
                        'detail_distribusi_langsungs.kode_rs',
                        'detail_distribusi_langsungs.no_penerimaan',
                        'ruangs.uraian as tujuan',
                        DB::raw('ROUND(sum(detail_distribusi_langsungs.jumlah),2) as jumlah_distribusi'),
                    ])
                        ->leftJoin('distribusi_langsungs', function ($b) {
                            $b->on('distribusi_langsungs.id', '=', 'detail_distribusi_langsungs.distribusi_langsung_id');
                        })
                        ->leftJoin('ruangs', function ($p) {
                            $p->on('ruangs.kode', '=', 'distribusi_langsungs.ruang_tujuan');
                        })

                        ->where(function ($q) use ($dist) {
                            $q->whereBetween('distribusi_langsungs.tanggal', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
                                ->whereIn('detail_distribusi_langsungs.kode_rs', $dist)
                                ->where('detail_distribusi_langsungs.jumlah', '>', 0);
                        })
                        ->groupBy(
                            'distribusi_langsungs.tanggal',
                            'distribusi_langsungs.ruang_tujuan',
                            'detail_distribusi_langsungs.kode_rs',
                        );
                }
            ])
            ->filter(request(['q']))
            ->when($kode_ruang, function ($b) {
                $b->where('kode_depo', request('kode_ruang'));
            })
            ->whereIn('kode', $minta)
            ->orWhereIn('kode', $dist)
            ->withTrashed()
            ->paginate(request('per_page'));

        return new JsonResponse($data);
    }
    public function rekapPengeluaranDepo()
    {
        $range = [request('year') . '-01-01 00:00:00', request('year') . '-12-31 23:59:59'];

        $minta = DetailPermintaanruangan::select('kode_rs')->distinct()
            ->leftJoin('permintaanruangans', function ($p) {
                $p->on('permintaanruangans.id', '=', 'detail_permintaanruangans.permintaanruangan_id');
            })
            ->whereBetween('permintaanruangans.tanggal_distribusi', $range)
            ->whereIn('permintaanruangans.status', [7, 8])
            ->where('detail_permintaanruangans.jumlah_distribusi', '>', 0)
            ->when(request('kode_ruang'), function ($q) {
                $q->where('permintaanruangans.dari', request('kode_ruang'));
            })
            ->when(request('ruang'), function ($q) {
                $q->where('permintaanruangans.kode_ruang', request('ruang'));
            })
            ->when(request('q'), function ($q) {
                $anu = BarangRS::select('kode')->where('barang_r_s.kode', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('barang_r_s.nama', 'LIKE', '%' . request('q') . '%')->get();
                $q->whereIn('detail_permintaanruangans.kode_rs', $anu);
            })
            ->get();
        $dist = DetailDistribusiLangsung::select('kode_rs')->distinct()
            ->leftJoin('distribusi_langsungs', function ($p) {
                $p->on('distribusi_langsungs.id', '=', 'detail_distribusi_langsungs.distribusi_langsung_id');
            })
            ->when(request('q'), function ($q) {
                $anu = BarangRS::select('kode')->where('barang_r_s.kode', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('barang_r_s.nama', 'LIKE', '%' . request('q') . '%')->get();
                $q->whereIn('detail_distribusi_langsungs.kode_rs', $anu);
            })
            ->whereBetween('distribusi_langsungs.tanggal', $range)
            ->where('detail_distribusi_langsungs.jumlah', '>', 0)
            ->get();

        $kode_ruang = request('kode_ruang') ?? false;
        if ($kode_ruang) {
            if ($kode_ruang === 'Gd-02010102') {
                $minta = [];
            } else {
                $dist = [];
            }
        }
        $data = BarangRS::select(
            'kode',
            'nama',
            'kode_satuan',
            'kode_depo',
        )
            ->with([
                'satuan:kode,nama',
                'detailPermintaanruangan' => function ($mi) use ($minta, $range) {
                    $mi->select([
                        'detail_permintaanruangans.no_penerimaan',
                        'detail_permintaanruangans.kode_rs',
                        'permintaanruangans.tanggal_distribusi as tanggal',
                        'ruangs.uraian as tujuan',
                        DB::raw('ROUND(sum(detail_permintaanruangans.jumlah_distribusi),2) as jumlah'),
                    ])->leftJoin('permintaanruangans', function ($b) {
                        $b->on('permintaanruangans.id', '=', 'detail_permintaanruangans.permintaanruangan_id');
                    })->leftJoin('ruangs', function ($p) {
                        $p->on('ruangs.kode', '=', 'detail_permintaanruangans.tujuan');
                    })->where(function ($q) use ($minta, $range) {
                        $q->whereBetween('permintaanruangans.tanggal_distribusi', $range)
                            ->where('detail_permintaanruangans.jumlah_distribusi', '>', 0)
                            ->whereIn('detail_permintaanruangans.kode_rs', $minta);
                    })
                        ->when(request('ruang'), function ($q) {
                            $q->where('permintaanruangans.kode_ruang', request('ruang'));
                        })
                        ->groupBy(
                            'permintaanruangans.tanggal_distribusi',
                            'detail_permintaanruangans.tujuan',
                            'detail_permintaanruangans.kode_rs',
                        );
                },
                'detailDistribusiLangsung' => function ($la) use ($dist, $range) {
                    $la->select([
                        'distribusi_langsungs.tanggal',
                        'detail_distribusi_langsungs.kode_rs',
                        'detail_distribusi_langsungs.no_penerimaan',
                        'ruangs.uraian as tujuan',
                        DB::raw('ROUND(sum(detail_distribusi_langsungs.jumlah),2) as jumlah'),
                    ])
                        ->leftJoin('distribusi_langsungs', function ($b) {
                            $b->on('distribusi_langsungs.id', '=', 'detail_distribusi_langsungs.distribusi_langsung_id');
                        })
                        ->leftJoin('ruangs', function ($p) {
                            $p->on('ruangs.kode', '=', 'distribusi_langsungs.ruang_tujuan');
                        })

                        ->where(function ($q) use ($dist, $range) {
                            $q->whereBetween('distribusi_langsungs.tanggal', $range)
                                ->whereIn('detail_distribusi_langsungs.kode_rs', $dist)
                                ->where('detail_distribusi_langsungs.jumlah', '>', 0);
                        })
                        ->groupBy(
                            'distribusi_langsungs.tanggal',
                            'distribusi_langsungs.ruang_tujuan',
                            'detail_distribusi_langsungs.kode_rs',
                        );
                }
            ])
            ->filter(request(['q']))
            ->when($kode_ruang, function ($b) {
                $b->where('barang_r_s.kode_depo', request('kode_ruang'));
            })
            ->whereIn('kode', $minta)
            ->orWhereIn('kode', $dist)
            ->withTrashed()
            ->paginate(request('per_page'));

        return new JsonResponse($data);
    }
    public function lapPengeluaranDepo()
    {
        $minta = DetailPermintaanruangan::select('kode_rs')->distinct()
            ->leftJoin('permintaanruangans', function ($p) {
                $p->on('permintaanruangans.id', '=', 'detail_permintaanruangans.permintaanruangan_id');
            })
            ->whereBetween('permintaanruangans.tanggal', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
            ->whereIn('permintaanruangans.status', [7, 8])
            ->where('detail_permintaanruangans.jumlah_distribusi', '>', 0)
            ->when(request('kode_ruang'), function ($q) {
                $q->where('permintaanruangans.dari', request('kode_ruang'));
            })
            ->when(request('q'), function ($q) {
                $anu = BarangRS::select('kode')->where('barang_r_s.kode', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('barang_r_s.nama', 'LIKE', '%' . request('q') . '%')->get();
                $q->whereIn('detail_permintaanruangans.kode_rs', $anu);
            })
            ->get();
        $dist = DetailDistribusiLangsung::select('kode_rs')->distinct()
            ->leftJoin('distribusi_langsungs', function ($p) {
                $p->on('distribusi_langsungs.id', '=', 'detail_distribusi_langsungs.distribusi_langsung_id');
            })
            ->when(request('q'), function ($q) {
                $anu = BarangRS::select('kode')->where('barang_r_s.kode', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('barang_r_s.nama', 'LIKE', '%' . request('q') . '%')->get();
                $q->whereIn('detail_distribusi_langsungs.kode_rs', $anu);
            })
            ->whereBetween('distribusi_langsungs.tanggal', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
            ->get();


        if (request('kode_ruang') === 'Gd-02010102') {
            $result = BarangRS::select([
                'distribusi_langsungs.tanggal',
                'detail_distribusi_langsungs.kode_rs',
                'detail_distribusi_langsungs.no_penerimaan',
                'barang_r_s.nama',
                'satuans.nama as satuan',
                'ruangs.uraian as tujuan',
                DB::raw('ROUND(sum(detail_distribusi_langsungs.jumlah),2) as jumlah_distribusi'),
            ])
                ->leftJoin('detail_distribusi_langsungs', function ($b) {
                    $b->on('detail_distribusi_langsungs.kode_rs', '=', 'barang_r_s.kode')
                        ->leftJoin('distribusi_langsungs', function ($p) {
                            $p->on('distribusi_langsungs.id', '=', 'detail_distribusi_langsungs.distribusi_langsung_id');
                        });
                })
                ->leftJoin('ruangs', function ($p) {
                    $p->on('ruangs.kode', '=', 'distribusi_langsungs.ruang_tujuan');
                })->leftJoin('satuans', function ($s) {
                    $s->on('satuans.kode', '=', 'barang_r_s.kode_satuan');
                })

                ->where(function ($q) use ($dist) {
                    $q->whereBetween('distribusi_langsungs.tanggal', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
                        ->whereIn('barang_r_s.kode', $dist);
                })
                ->groupBy(
                    'barang_r_s.kode',
                    'distribusi_langsungs.tanggal',
                    'distribusi_langsungs.ruang_tujuan'
                )->orderBy('ruangs.uraian', 'ASC')
                ->orderBy('distribusi_langsungs.tanggal', 'ASC')
                ->withTrashed()
                ->paginate(request('per_page'));
            return new JsonResponse($result);
        } else if (request('kode_ruang') !== 'Gd-02010102') {
            $result = BarangRS::select([
                'permintaanruangans.tanggal',
                'detail_permintaanruangans.no_penerimaan',
                'detail_permintaanruangans.kode_rs',
                'barang_r_s.nama',
                'satuans.nama as satuan',
                'ruangs.uraian as tujuan',
                DB::raw('ROUND(sum(detail_permintaanruangans.jumlah),2) as jumlah'),
                DB::raw('ROUND(sum(detail_permintaanruangans.jumlah_disetujui),2) as jumlah_disetujui'),
                DB::raw('ROUND(sum(detail_permintaanruangans.jumlah_distribusi),2) as jumlah_distribusi'),
            ])->leftJoin('detail_permintaanruangans', function ($b) {
                $b->on('detail_permintaanruangans.kode_rs', '=', 'barang_r_s.kode')
                    ->leftJoin('permintaanruangans', function ($p) {
                        $p->on('permintaanruangans.id', '=', 'detail_permintaanruangans.permintaanruangan_id');
                    });
            })->leftJoin('ruangs', function ($p) {
                $p->on('ruangs.kode', '=', 'detail_permintaanruangans.tujuan');
            })->leftJoin('satuans', function ($s) {
                $s->on('satuans.kode', '=', 'barang_r_s.kode_satuan');
            })

                ->where(function ($q) use ($minta) {
                    $q->whereBetween('permintaanruangans.tanggal', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
                        ->where('detail_permintaanruangans.jumlah_distribusi', '>', 0)
                        ->whereIn('barang_r_s.kode', $minta);
                })
                ->groupBy(
                    'barang_r_s.kode',
                    'permintaanruangans.tanggal',
                    'detail_permintaanruangans.tujuan'
                )->orderBy('ruangs.uraian', 'ASC')
                ->orderBy('permintaanruangans.tanggal', 'ASC')
                ->withTrashed()
                ->paginate(request('per_page'));
            return new JsonResponse($result);
        } else {

            $result = BarangRS::select([
                'permintaanruangans.tanggal',
                'distribusi_langsungs.tanggal as tanggal_l',
                'detail_permintaanruangans.no_penerimaan',
                'detail_permintaanruangans.kode_rs',
                'detail_distribusi_langsungs.kode_rs as kode_rs_l',
                'barang_r_s.nama',
                'satuans.nama as satuan',
                'ruangs.uraian as tujuan',
                DB::raw('ROUND(sum(detail_distribusi_langsungs.jumlah),2) as jumlah_distribusi_l'),
                DB::raw('ROUND(sum(detail_permintaanruangans.jumlah),2) as jumlah'),
                DB::raw('ROUND(sum(detail_permintaanruangans.jumlah_disetujui),2) as jumlah_disetujui'),
                DB::raw('ROUND(sum(detail_permintaanruangans.jumlah_distribusi),2) as jumlah_distribusi'),
            ])
                ->leftJoin('detail_permintaanruangans', function ($b) {
                    $b->on('detail_permintaanruangans.kode_rs', '=', 'barang_r_s.kode')
                        ->leftJoin('permintaanruangans', function ($p) {
                            $p->on('permintaanruangans.id', '=', 'detail_permintaanruangans.permintaanruangan_id');
                        });
                })
                ->leftJoin('detail_distribusi_langsungs', function ($b) {
                    $b->on('detail_distribusi_langsungs.kode_rs', '=', 'barang_r_s.kode')
                        ->leftJoin('distribusi_langsungs', function ($p) {
                            $p->on('distribusi_langsungs.id', '=', 'detail_distribusi_langsungs.distribusi_langsung_id');
                        });
                })
                ->leftJoin('ruangs', function ($p) {
                    $p->on('ruangs.kode', '=', 'detail_permintaanruangans.tujuan')
                        ->orOn('ruangs.kode', '=', 'distribusi_langsungs.ruang_tujuan');
                })
                ->leftJoin('satuans', function ($s) {
                    $s->on('satuans.kode', '=', 'barang_r_s.kode_satuan');
                })
                ->where(function ($q) use ($minta) {
                    $q->whereBetween('permintaanruangans.tanggal', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
                        ->where('detail_permintaanruangans.jumlah_distribusi', '>', 0)
                        ->whereIn('barang_r_s.kode', $minta);
                })
                ->orWhere(function ($q) use ($dist) {
                    $q->whereBetween('distribusi_langsungs.tanggal', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
                        ->whereIn('barang_r_s.kode', $dist);
                })
                ->groupBy(
                    'barang_r_s.kode',
                    'permintaanruangans.tanggal',
                    'distribusi_langsungs.tanggal',
                    'detail_permintaanruangans.tujuan',
                    'distribusi_langsungs.ruang_tujuan'
                )
                ->orderBy('ruangs.uraian', 'ASC')
                ->orderBy('permintaanruangans.tanggal', 'ASC')
                ->orderBy('distribusi_langsungs.tanggal', 'ASC')
                ->withTrashed()
                ->paginate(request('per_page'));
            return new JsonResponse($result);
        }
    }
    public function lapPemakaianRuangan()
    {
        // $minta = DetailsPemakaianruangan::select('kode_rs')->distinct()
        //     ->leftJoin('pemakaianruangans', function ($p) {
        //         $p->on('pemakaianruangans.id', '=', 'detaisl_pemakaianruangans.pemakaianruangan_id');
        //     })
        //     ->whereBetween('pemakaianruangans.tanggal', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
        //     ->when(request('kode_ruang'), function ($q) {
        //         if (request('kode_ruang') !== 'Gd-02010102') {
        //             $q->where('pemakaianruangans.dari', request('kode_ruang'));
        //         }
        //     })
        //     ->when(request('q'), function ($q) {
        //         $anu = BarangRS::select('kode')->where('barang_r_s.kode', 'LIKE', '%' . request('q') . '%')
        //             ->orWhere('barang_r_s.nama', 'LIKE', '%' . request('q') . '%')->get();
        //         $q->whereIn('details_pemakaianruangans.kode_rs', $anu);
        //     })
        //     ->get();
        $result = BarangRS::select([
            'pemakaianruangans.tanggal',
            'details_pemakaianruangans.no_penerimaan',
            'details_pemakaianruangans.kode_rs',
            'barang_r_s.nama',
            'satuans.nama as satuan',
            'ruangs.uraian as ruang',
            DB::raw('ROUND(sum(details_pemakaianruangans.jumlah),2) as jumlah'),
        ])
            ->leftJoin('details_pemakaianruangans', function ($b) {
                $b->on('details_pemakaianruangans.kode_rs', '=', 'barang_r_s.kode')
                    ->leftJoin('pemakaianruangans', function ($p) {
                        $p->on('pemakaianruangans.id', '=', 'details_pemakaianruangans.pemakaianruangan_id');
                    });
            })
            ->leftJoin('ruangs', function ($p) {
                $p->on('ruangs.kode', '=', 'pemakaianruangans.kode_ruang');
            })
            ->leftJoin('satuans', function ($s) {
                $s->on('satuans.kode', '=', 'barang_r_s.kode_satuan');
            })
            ->whereBetween('pemakaianruangans.tanggal', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
            ->when(request('kode_ruang'), function ($q) {
                $anu = Ruang::select('kode')->where('kode', 'LIKE', '%' . request('kode_ruang') . '%')
                    ->orWhere('uraian', 'LIKE', '%' . request('kode_ruang') . '%')->get();
                $q->whereIn('pemakaianruangans.kode_ruang', $anu);
            })
            ->when(request('q'), function ($q) {
                $q->where('barang_r_s.kode', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('barang_r_s.nama', 'LIKE', '%' . request('q') . '%')->get();
            })
            ->groupBy(
                'barang_r_s.kode',
                'pemakaianruangans.tanggal',
                'pemakaianruangans.kode_ruang',
            )
            ->orderBy('ruangs.uraian', 'ASC')
            // ->orderBy('pemakaianruangans.tanggal', 'ASC')
            // ->orderBy('distribusi_langsungs.tanggal', 'ASC')
            ->withTrashed()
            // ->get();
            ->paginate(request('per_page'));
        $data = $result;

        return new JsonResponse($data);
    }
}
