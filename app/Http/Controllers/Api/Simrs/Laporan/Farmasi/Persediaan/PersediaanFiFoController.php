<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Farmasi\Persediaan;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PersediaanFiFoController extends Controller
{
    public function getPersediaan()
    {
        $obat = Mobatnew::select(
            'kd_obat',
            'nama_obat',
            'satuan_k',
            'jenis_perbekalan',
            'bentuk_sediaan',
        )
            ->with([
                'stok' => function ($st) {
                    $st->select(
                        'stokreal.kdobat',
                        'stokreal.nopenerimaan',
                        DB::raw('sum(stokreal.jumlah) as jumlah'),
                        DB::raw('sum(stokreal.jumlah * stokreal.harga) as sub'),
                        // 'penerimaan_r.nopenerimaan',
                        'penerimaan_h.jenis_penerimaan',
                        'stokreal.harga',
                        // 'daftar_hargas.harga',
                    )
                        // ->leftJoin('daftar_hargas', function ($jo) {
                        //     $jo->on('daftar_hargas.nopenerimaan', '=', 'stokreal.nopenerimaan')
                        //         ->on('daftar_hargas.kd_obat', '=', 'stokreal.kdobat');
                        // })
                        // ->leftJoin('penerimaan_r', function ($jo) {
                        //     $jo->on('penerimaan_r.nopenerimaan', '=', 'stokreal.nopenerimaan')
                        //         ->on('penerimaan_r.kdobat', '=', 'stokreal.kdobat');
                        // })
                        ->leftJoin('penerimaan_h', 'penerimaan_h.nopenerimaan', '=', 'stokreal.nopenerimaan')
                        ->where('stokreal.jumlah', '!=', 0)
                        ->when(
                            request('kode_ruang') === 'all',
                            function ($re) {
                                $gd = ['Gd-05010100', 'Gd-03010100', 'Gd-03010101', 'Gd-04010102', 'Gd-04010103', 'Gd-05010101', 'Gd-04010104'];
                                $re->whereIn('stokreal.kdruang', $gd);
                            },
                            function ($sp) {
                                $sp->where('stokreal.kdruang', request('kode_ruang'));
                            }
                        )
                        ->groupBy('stokreal.kdobat', 'stokreal.nopenerimaan', 'stokreal.harga');
                },
                // ***** ini untuk testing ****
                // 'saldoawal' => function ($st) {
                //     $st->select(
                //         'stokopname_sementaras.kdobat',
                //         'stokopname_sementaras.nopenerimaan',
                //         DB::raw('sum(stokopname_sementaras.jumlah) as jumlah'),
                //         DB::raw('sum(stokopname_sementaras.jumlah * stokopname_sementaras.harga) as sub'),
                //         'penerimaan_h.jenis_penerimaan',
                //         'stokopname_sementaras.harga',
                //     )
                //         ->leftJoin('penerimaan_h', 'penerimaan_h.nopenerimaan', '=', 'stokopname_sementaras.nopenerimaan')
                //         ->where('stokopname_sementaras.jumlah', '!=', 0)
                //         ->where('stokopname_sementaras.tglopname', 'LIKE', '%' . request('tahun') . '-' . request('bulan') . '%')
                //         ->when(
                //             request('kode_ruang') === 'all',
                //             function ($re) {
                //                 $gd = ['Gd-05010100', 'Gd-03010100', 'Gd-03010101', 'Gd-04010102', 'Gd-04010103', 'Gd-05010101', 'Gd-04010104'];
                //                 $re->whereIn('stokopname_sementaras.kdruang', $gd);
                //             },
                //             function ($sp) {
                //                 $sp->where('stokopname_sementaras.kdruang', request('kode_ruang'));
                //             }
                //         )
                //         ->groupBy('stokopname_sementaras.kdobat', 'stokopname_sementaras.nopenerimaan');
                // }
                // ***** ini Aslinya ****
                'saldoawal' => function ($st) {
                    $st->select(
                        'stokopname.kdobat',
                        'stokopname.nopenerimaan',
                        DB::raw('sum(stokopname.jumlah) as jumlah'),
                        DB::raw('sum(stokopname.jumlah * stokopname.harga) as sub'),
                        'penerimaan_h.jenis_penerimaan',
                        'stokopname.harga',
                    )
                        ->leftJoin('penerimaan_h', 'penerimaan_h.nopenerimaan', '=', 'stokopname.nopenerimaan')
                        ->where('stokopname.jumlah', '!=', 0)
                        ->where('stokopname.tglopname', 'LIKE', '%' . request('tahun') . '-' . request('bulan') . '%')
                        ->when(
                            request('kode_ruang') === 'all',
                            function ($re) {
                                $gd = ['Gd-05010100', 'Gd-03010100', 'Gd-03010101', 'Gd-04010102', 'Gd-04010103', 'Gd-05010101', 'Gd-04010104'];
                                $re->whereIn('stokopname.kdruang', $gd);
                            },
                            function ($sp) {
                                $sp->where('stokopname.kdruang', request('kode_ruang'));
                            }
                        )
                        ->groupBy('stokopname.kdobat', 'stokopname.nopenerimaan');
                }
            ])
            ->where(function ($mo) {
                $mo->where('nama_obat', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('kd_obat', 'LIKE', '%' . request('q') . '%');
            })
            ->where('status_konsinyasi', '=', '')
            ->get();
        // $data = collect($obat)['data'];
        // $meta = collect($obat)->except('data');
        return new JsonResponse([
            'data' => $obat,
            // 'meta' => $meta,
            'req' => request()->all()
        ]);
    }
    public function getMutasi()
    {
        $tglAwal = request('tahun') . '-' . request('bulan') . '-01';
        $dateAwal = Carbon::parse($tglAwal);
        $blnLalu = $dateAwal->subMonth()->format('Y-m');

        $rwobat = Mobatnew::select(
            'kd_obat',
            'nama_obat',
            'satuan_k',
            'uraian50',

        )

            ->when(request('kode_ruang') !== 'all', function ($q) {
                $q->whereIn('gudang', ['', request('kode_ruang')]);
            })
            ->where(function ($q) {
                $q->where('nama_obat', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('kd_obat', 'LIKE', '%' . request('q') . '%');
            });

        $rwobat->with([
            'saldoawal' => function ($st) use ($blnLalu) {
                $st->select(
                    'stokopname.kdobat',
                    'stokopname.nopenerimaan',
                    'stokopname.harga',
                    DB::raw('sum(stokopname.jumlah) as jumlah'),
                    DB::raw('sum(stokopname.jumlah * stokopname.harga) as sub'),
                    // DB::raw('stokopname.harga as harga'),
                    // 'daftar_hargas.harga as dftHar',
                )

                    ->where('stokopname.jumlah', '!=', 0)
                    ->where('stokopname.tglopname', 'LIKE', $blnLalu . '%')
                    ->whereIn('stokopname.kdruang', ['Gd-05010100', 'Gd-03010100', 'Gd-03010101', 'Gd-04010102', 'Gd-04010103', 'Gd-05010101', 'Gd-04010104']);
                if (request('jenis') == 'rekap') {
                    $st->groupBy('stokopname.kdobat', 'stokopname.nopenerimaan', 'stokopname.nobatch');
                } else {
                    $st->groupBy('stokopname.kdobat', 'stokopname.nopenerimaan', 'stokopname.tglopname', 'stokopname.nobatch');
                }
            },
            'penerimaanrinci' => function ($trm) {
                $trm->select(
                    'penerimaan_r.kdobat',
                    'penerimaan_r.nopenerimaan',
                    'penerimaan_h.tglpenerimaan as tgl',
                    'penerimaan_h.jenissurat',
                    'penerimaan_h.nomorsurat',
                    'penerimaan_h.kdpbf',
                    'penerimaan_r.satuan_kcl',
                    'penerimaan_r.harga_netto_kecil as harga',
                    DB::raw('sum(penerimaan_r.jml_terima_k) as jumlah'),
                    DB::raw('sum(penerimaan_r.harga_netto_kecil * penerimaan_r.jml_terima_k) as sub')
                )
                    ->leftJoin('penerimaan_h', 'penerimaan_h.nopenerimaan', '=', 'penerimaan_r.nopenerimaan')
                    ->with('pbf:kode,nama')
                    ->where('penerimaan_h.kunci', '1')
                    ->where('penerimaan_h.tglpenerimaan', 'LIKE', '%' .  request('tahun') . '-' . request('bulan') . '%');
                // if (request('jenis') == 'rekap') {
                //     $trm->groupBy('penerimaan_r.kdobat');
                // } else {
                $trm->groupBy('penerimaan_r.kdobat', 'penerimaan_r.nopenerimaan', 'penerimaan_r.no_batch');
                // }
            },
            'resepkeluar' => function ($kel) {
                $kel->select(
                    'resep_keluar_r.noresep',
                    'resep_keluar_r.kdobat',
                    'resep_keluar_h.tgl_selesai as tgl',
                    'resep_keluar_r.nopenerimaan',
                    'resep_keluar_r.harga_beli as harga',
                    DB::raw('sum(resep_keluar_r.jumlah) as jumlah'),
                    DB::raw('sum(resep_keluar_r.jumlah * resep_keluar_r.harga_beli) as sub')

                )
                    ->join('resep_keluar_h', 'resep_keluar_h.noresep', '=', 'resep_keluar_r.noresep')
                    ->havingRaw('jumlah > 0')
                    ->where('resep_keluar_h.tgl_selesai', 'LIKE', '%' . request('tahun') . '-' . request('bulan') . '%')
                    ->whereIn('resep_keluar_h.depo', ['Gd-04010102', 'Gd-05010101', 'Gd-04010104']) // ambil yang selain OK
                    ->with(
                        'header:noresep,norm',
                        'header.datapasien:rs1,rs2',
                    );
                if (request('jenis') === 'rekap') {
                    $kel->groupBy('resep_keluar_r.kdobat', 'resep_keluar_r.nopenerimaan');
                } else {
                    $kel->groupBy('resep_keluar_r.kdobat', 'resep_keluar_r.nopenerimaan', 'resep_keluar_r.noresep');
                }
            },
            //// tambahan Ok
            'resepkeluarok' => function ($kel) {
                $kel->select(
                    'resep_keluar_r.noresep',
                    'resep_keluar_r.kdobat',
                    'resep_keluar_h.tgl_selesai as tgl',
                    'resep_keluar_r.nopenerimaan',
                    'resep_keluar_r.harga_beli as harga',
                    DB::raw('sum(resep_keluar_r.jumlah) as jumlah'),
                    DB::raw('sum(resep_keluar_r.jumlah * resep_keluar_r.harga_beli) as sub')

                )
                    ->join('resep_keluar_h', 'resep_keluar_h.noresep', '=', 'resep_keluar_r.noresep')
                    ->leftJoin('persiapan_operasi_rincis', function ($q) {
                        $q->on('persiapan_operasi_rincis.noresep', '=', 'resep_keluar_r.noresep')
                            ->on('persiapan_operasi_rincis.kd_obat', '=', 'resep_keluar_r.kdobat');
                    })
                    ->whereNull('persiapan_operasi_rincis.noresep')
                    ->havingRaw('jumlah > 0')
                    ->where('resep_keluar_h.tgl_selesai', 'LIKE', '%' . request('tahun') . '-' . request('bulan') . '%')
                    ->whereIn('resep_keluar_h.depo', ['Gd-04010103'])
                    ->with(
                        'header:noresep,norm',
                        'header.datapasien:rs1,rs2',
                    );
                if (request('jenis') === 'rekap') {
                    $kel->groupBy('resep_keluar_r.kdobat', 'resep_keluar_r.nopenerimaan');
                } else {
                    $kel->groupBy('resep_keluar_r.kdobat', 'resep_keluar_r.nopenerimaan', 'resep_keluar_r.noresep');
                }
                // ->groupBy('resep_keluar_r.kdobat', 'resep_keluar_r.nopenerimaan', 'resep_keluar_r.noresep');
            },

            'distribusipersiapan' => function ($dist) {
                $dist->select(
                    'persiapan_operasi_distribusis.kd_obat as kdobat',
                    'persiapan_operasi_distribusis.kd_obat',
                    'persiapan_operasi_distribusis.nopenerimaan',
                    // 'persiapan_operasis.nopermintaan',
                    'persiapan_operasis.tgl_distribusi as tgl',
                    'persiapan_operasis.norm',
                    // 'persiapan_operasi_distribusis.tgl_retur',
                    'persiapan_operasi_rincis.noresep',
                    // 'daftar_hargas.harga',
                    DB::raw('sum(persiapan_operasi_distribusis.jumlah) as jumlah'),
                    // DB::raw('sum(persiapan_operasi_distribusis.jumlah * daftar_hargas.harga) as sub'),
                    // DB::raw('sum(persiapan_operasi_distribusis.jumlah_retur) as retur'),

                )
                    ->leftJoin('persiapan_operasis', 'persiapan_operasis.nopermintaan', '=', 'persiapan_operasi_distribusis.nopermintaan')
                    ->leftJoin('persiapan_operasi_rincis', function ($join) {
                        $join->on('persiapan_operasi_rincis.nopermintaan', '=', 'persiapan_operasi_distribusis.nopermintaan')
                            ->on('persiapan_operasi_rincis.kd_obat', '=', 'persiapan_operasi_distribusis.kd_obat');
                    })
                    // ->leftJoin('daftar_hargas', function ($join) {
                    //     $join->on('daftar_hargas.nopenerimaan', '=', 'persiapan_operasi_distribusis.nopenerimaan')
                    //         ->on('daftar_hargas.kd_obat', '=', 'persiapan_operasi_distribusis.kd_obat');
                    // })
                    ->where('persiapan_operasis.tgl_distribusi', 'LIKE', '%' .  request('tahun') . '-' . request('bulan') . '%')
                    ->whereIn('persiapan_operasis.flag', ['2', '3', '4'])
                    ->with([
                        'pasien:rs1,rs2',
                    ]);
                if (request('jenis') === 'rekap') {
                    $dist->groupBy('persiapan_operasi_distribusis.kd_obat', 'persiapan_operasi_distribusis.nopenerimaan');
                } else {
                    $dist->groupBy('persiapan_operasi_distribusis.kd_obat', 'persiapan_operasis.nopermintaan', 'persiapan_operasi_distribusis.nopenerimaan');
                }
                // ->groupBy('persiapan_operasi_distribusis.kd_obat', 'persiapan_operasis.nopermintaan', 'persiapan_operasi_distribusis.nopenerimaan');
            },
            'persiapanretur' => function ($dist) {
                $dist->select(
                    'persiapan_operasi_distribusis.kd_obat as kdobat',
                    'persiapan_operasi_distribusis.kd_obat',
                    'persiapan_operasi_distribusis.nopenerimaan',
                    'persiapan_operasis.nopermintaan',
                    // 'persiapan_operasis.tgl_distribusi',
                    'persiapan_operasi_distribusis.tgl_retur as tgl',
                    // 'persiapan_operasi_rincis.noresep',
                    'persiapan_operasis.norm',
                    // 'daftar_hargas.harga',
                    // DB::raw('sum(persiapan_operasi_distribusis.jumlah) as keluar'),
                    DB::raw('sum(persiapan_operasi_distribusis.jumlah_retur) as jumlah'),
                    // DB::raw('sum(persiapan_operasi_distribusis.jumlah_retur * daftar_hargas.harga) as sub'),

                )
                    ->leftJoin('persiapan_operasis', 'persiapan_operasis.nopermintaan', '=', 'persiapan_operasi_distribusis.nopermintaan')
                    ->leftJoin('persiapan_operasi_rincis', function ($join) {
                        $join->on('persiapan_operasi_rincis.nopermintaan', '=', 'persiapan_operasi_distribusis.nopermintaan')
                            ->on('persiapan_operasi_rincis.kd_obat', '=', 'persiapan_operasi_distribusis.kd_obat');
                    })
                    // ->leftJoin('daftar_hargas', function ($join) {
                    //     $join->on('daftar_hargas.nopenerimaan', '=', 'persiapan_operasi_distribusis.nopenerimaan')
                    //         ->on('daftar_hargas.kd_obat', '=', 'persiapan_operasi_distribusis.kd_obat');
                    // })
                    ->where('persiapan_operasis.tgl_retur', 'LIKE', '%' . request('tahun') . '-' . request('bulan') . '%')
                    ->whereIn('persiapan_operasis.flag', ['2', '3', '4'])
                    ->havingRaw('sum(persiapan_operasi_distribusis.jumlah_retur) > 0')
                    ->with([
                        'pasien:rs1,rs2',
                    ]);
                if (request('jenis') === 'rekap') {
                    $dist->groupBy('persiapan_operasi_distribusis.kd_obat', 'persiapan_operasi_distribusis.nopenerimaan');
                } else {
                    $dist->groupBy('persiapan_operasi_distribusis.kd_obat', 'persiapan_operasis.nopermintaan', 'persiapan_operasi_distribusis.nopenerimaan');
                }
                // ->groupBy('persiapan_operasi_distribusis.kd_obat', 'persiapan_operasis.nopermintaan', 'persiapan_operasi_distribusis.nopenerimaan');
            },
            //// akhir OK ////
            'resepkeluarracikan' => function ($kel) {
                $kel->select(
                    'resep_keluar_racikan_r.noresep',
                    'resep_keluar_racikan_r.kdobat',
                    'resep_keluar_h.tgl_selesai as tgl',
                    'resep_keluar_racikan_r.nopenerimaan',
                    'resep_keluar_racikan_r.harga_beli as harga',
                    'resep_keluar_racikan_r.harga_beli as harga',
                    DB::raw('sum(resep_keluar_racikan_r.jumlah) as jumlah'),
                    DB::raw('sum(resep_keluar_racikan_r.jumlah * resep_keluar_racikan_r.harga_beli) as sub')

                )
                    ->join('resep_keluar_h', 'resep_keluar_h.noresep', '=', 'resep_keluar_racikan_r.noresep')
                    ->havingRaw('jumlah > 0')
                    ->where('resep_keluar_h.tgl_selesai', 'LIKE', '%' .  request('tahun') . '-' . request('bulan') . '%')
                    ->with(
                        'header:noresep,norm',
                        'header.datapasien:rs1,rs2',
                    );
                if (request('jenis') === 'rekap') {
                    $kel->groupBy('resep_keluar_racikan_r.kdobat');
                } else {
                    $kel->groupBy('resep_keluar_racikan_r.kdobat', 'resep_keluar_racikan_r.nopenerimaan', 'resep_keluar_racikan_r.noresep');
                }
                // ->groupBy('resep_keluar_racikan_r.kdobat', 'resep_keluar_racikan_r.nopenerimaan', 'resep_keluar_racikan_r.noresep');
            },
            'returpenjualan' => function ($kel) {
                $kel->select(
                    'retur_penjualan_r.noresep',
                    'retur_penjualan_r.kdobat',
                    'retur_penjualan_h.tgl_retur as tgl',
                    'retur_penjualan_r.nopenerimaan',
                    'retur_penjualan_r.harga_beli as harga',
                    DB::raw('sum(retur_penjualan_r.jumlah_retur) as jumlah'),
                    DB::raw('sum(retur_penjualan_r.jumlah_retur * retur_penjualan_r.harga_beli) as sub'),

                )
                    ->join('retur_penjualan_h', 'retur_penjualan_h.noretur', '=', 'retur_penjualan_r.noretur')
                    ->havingRaw('jumlah > 0')
                    ->where('retur_penjualan_h.tgl_retur', 'LIKE', '%' .  request('tahun') . '-' . request('bulan') . '%')
                    ->with(
                        'header:noresep,norm',
                        'header.datapasien:rs1,rs2',
                    );
                if (request('jenis') === 'rekap') {
                    $kel->groupBy('retur_penjualan_r.kdobat', 'retur_penjualan_r.nopenerimaan');
                } else {
                    $kel->groupBy('retur_penjualan_r.kdobat', 'retur_penjualan_r.nopenerimaan', 'retur_penjualan_r.noresep');
                }
                // ->groupBy('retur_penjualan_r.kdobat', 'retur_penjualan_r.nopenerimaan', 'retur_penjualan_r.noresep');
            },
            'mutasikeluar' => function ($mut) {
                $mut->select(
                    'mutasi_gudangdepo.no_permintaan',
                    'mutasi_gudangdepo.kd_obat',
                    'mutasi_gudangdepo.kd_obat as kdobat',
                    'mutasi_gudangdepo.nopenerimaan',
                    'mutasi_gudangdepo.harga',
                    DB::raw('sum(mutasi_gudangdepo.jml) as jumlah'),
                    DB::raw('sum(mutasi_gudangdepo.jml * mutasi_gudangdepo.harga) as sub'),
                    'permintaan_h.dari',
                    'permintaan_h.dari as kdruang',
                    'permintaan_h.tgl_kirim_depo as tgl',
                )
                    ->join('permintaan_h', 'permintaan_h.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                    ->havingRaw('jumlah > 0')
                    ->where('permintaan_h.dari', 'LIKE', 'R-%')
                    ->where('permintaan_h.tgl_kirim_depo', 'LIKE', '%' .  request('tahun') . '-' . request('bulan') . '%')
                    ->with([
                        'ruangan:kode,uraian',
                    ]);
                if (request('jenis') === 'rekap') {
                    $mut->groupBy('mutasi_gudangdepo.kd_obat', 'mutasi_gudangdepo.nopenerimaan');
                } else {
                    $mut->groupBy('mutasi_gudangdepo.kd_obat', 'mutasi_gudangdepo.nopenerimaan', 'mutasi_gudangdepo.no_permintaan');
                }
                // ->groupBy('mutasi_gudangdepo.kd_obat', 'mutasi_gudangdepo.nopenerimaan');
            },
            'penyesuaian' => function ($pak) {
                $pak->select(
                    'penyesuaian_stoks.kdobat',
                    'penyesuaian_stoks.nopenerimaan',
                    'penyesuaian_stoks.tgl_penyesuaian as tgl',
                    'stokreal.harga',
                    DB::raw('sum(penyesuaian_stoks.penyesuaian) as jumlah'),
                    DB::raw('sum(penyesuaian_stoks.penyesuaian * stokreal.harga) as sub'),

                )
                    ->join('stokreal', 'stokreal.id', '=', 'penyesuaian_stoks.stokreal_id')
                    ->where('penyesuaian_stoks.tgl_penyesuaian', 'LIKE', '%' .  request('tahun') . '-' . request('bulan') . '%')
                    ->where('penyesuaian_stoks.penyesuaian', '!=', 0)
                    ->groupBy('penyesuaian_stoks.kdobat', 'penyesuaian_stoks.nopenerimaan');
            },

            'barangrusak' => function ($pak) {
                $pak->select(
                    'kd_obat',
                    'kd_obat as kdobat',
                    'nopenerimaan_default as nopenerimaan',
                    'harga_net_default as harga',
                    'tgl_kunci as tgl',
                    'status as ket',
                    DB::raw('sum(jumlah) as jumlah'),
                    DB::raw('sum(jumlah * harga_net_default) as sub'),

                )
                    ->where('tgl_kunci', 'LIKE', request('tahun') . '-' . request('bulan') . '%')
                    ->where('kunci', '1')
                    ->whereIn('gudang', ['Gd-05010100', 'Gd-03010100'])
                    ->groupBy('kdobat', 'nopenerimaan_default');
            },
            'returpbf' => function ($kel) {
                $kel->select(
                    'retur_penyedia_r.no_retur',
                    'retur_penyedia_r.kd_obat',
                    'retur_penyedia_r.kd_obat as kdobat',
                    'retur_penyedia_h.tgl_kunci as tgl',
                    'retur_penyedia_r.nopenerimaan_default as nopenerimaan',
                    'retur_penyedia_r.harga_net_default as harga',
                    DB::raw('sum(retur_penyedia_r.jumlah_retur) as jumlah'),
                    DB::raw('sum(retur_penyedia_r.jumlah_retur * retur_penyedia_r.harga_net_default) as sub'),

                )
                    ->join('retur_penyedia_h', 'retur_penyedia_h.no_retur', '=', 'retur_penyedia_r.no_retur')
                    ->havingRaw('jumlah > 0')
                    ->where('retur_penyedia_h.tgl_kunci', 'LIKE', request('tahun') . '-' . request('bulan') . '%')
                    ->with(
                        'header.penyedia:kode,nama',
                    );
                if (request('jenis') === 'rekap') {
                    $kel->groupBy('retur_penyedia_r.kd_obat');
                } else {
                    $kel->groupBy('retur_penyedia_r.kd_obat', 'retur_penyedia_r.nopenerimaan_default', 'retur_penyedia_r.no_retur');
                }
                // ->groupBy('retur_penyedia_r.kdobat', 'retur_penyedia_r.nopenerimaan', 'retur_penyedia_r.noresep');
            },
            'pengembalianrincififo' => function ($kel) {
                $kel->select(
                    'pengembalian_rinci_fifos.nopengembalian',
                    'pengembalian_rinci_fifos.kdobat',
                    'pengembalian_rinci_fifos.kdobat as kd_obat',
                    'pengembalians.tgl_kunci as tgl',
                    'pengembalian_rinci_fifos.nopenerimaan',
                    'pengembalian_rinci_fifos.harga',
                    DB::raw('sum(pengembalian_rinci_fifos.jml_dikembalikan) as jumlah'),
                    DB::raw('sum(pengembalian_rinci_fifos.jml_dikembalikan * pengembalian_rinci_fifos.harga) as sub'),

                )
                    ->join('pengembalians', 'pengembalians.nopengembalian', '=', 'pengembalian_rinci_fifos.nopengembalian')
                    ->havingRaw('jumlah > 0')
                    ->where('pengembalians.tgl_kunci', 'LIKE', request('tahun') . '-' . request('bulan') . '%')
                    ->with(
                        'header.penyedia:kode,nama',
                    );
                if (request('jenis') === 'rekap') {
                    $kel->groupBy('pengembalian_rinci_fifos.kdobat');
                } else {
                    $kel->groupBy('pengembalian_rinci_fifos.kdobat', 'pengembalian_rinci_fifos.nopenerimaan', 'pengembalian_rinci_fifos.nopengembalian');
                }
                // ->groupBy('retur_penyedia_r.kdobat', 'retur_penyedia_r.nopenerimaan', 'retur_penyedia_r.noresep');
            },
            'daftarharga:kd_obat,nopenerimaan,harga',
            'mutasikeluarngambang' => function ($kel) {
                $kel->where('permintaan_h.tgl_kirim_depo', 'LIKE', '%' .  request('tahun') . '-' . request('bulan') . '%')
                    ->with([
                        'depo:kode,nama',
                    ]);
                if (request('jenis') === 'rekap') {
                    $kel->groupBy('mutasi_gudangdepo.kd_obat', 'mutasi_gudangdepo.nopenerimaan');
                } else {
                    $kel->groupBy('mutasi_gudangdepo.kd_obat', 'mutasi_gudangdepo.nopenerimaan', 'mutasi_gudangdepo.no_permintaan');
                }
            },
            'mutasimasukngambang' => function ($kel) {
                $kel->where('permintaan_h.tgl_terima_depo', 'LIKE', '%' .  request('tahun') . '-' . request('bulan') . '%')
                    ->with([
                        'depo:kode,nama',
                    ]);

                if (request('jenis') === 'rekap') {
                    $kel->groupBy('mutasi_gudangdepo.kd_obat', 'mutasi_gudangdepo.nopenerimaan');
                } else {
                    $kel->groupBy('mutasi_gudangdepo.kd_obat', 'mutasi_gudangdepo.nopenerimaan', 'mutasi_gudangdepo.no_permintaan');
                }
            },

        ]);
        // }
        $kirim = [];
        if (request('action') === 'download') {
            // $obat = $rwobat->offset(0)
            //     ->limit(300)
            //     ->get();
            $obat = $rwobat->get();
            $obat->map(function ($it) {
                $it->saldo = $it->saldoawal;
                $it->terima = $it->penerimaanrinci;
                $it->retur = $it->returpenjualan ?? [];
                return $it;
            });
            $kirim = $obat;
        } else {
            $obat = $rwobat->paginate(30);
            $anu = collect($obat)['data'];
            $meta = collect($obat)->except('data');
            foreach ($anu as $it) {
                $it['saldo'] = $it['saldoawal'];
                $it['terima'] = $it['penerimaanrinci'];
                $it['retur'] = $it['returpenjualan'] ?? [];
                $kirim[] = $it;
            }
        }


        return new JsonResponse([
            'obat' => $obat,
            'data' => $kirim,
            'blnLalu' => $blnLalu,
            'meta' => $meta ?? null,
            'req' => request()->all()
        ]);
    }
}
