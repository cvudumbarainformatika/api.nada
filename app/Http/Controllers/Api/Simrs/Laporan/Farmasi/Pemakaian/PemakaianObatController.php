<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Farmasi\Pemakaian;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\SistemBayar;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PemakaianObatController extends Controller
{
    function getAllPemakaianObat()
    {

        $dateAwal = Carbon::parse(request('from'));
        // $dateAkhir = Carbon::parse(request('to'));
        // $blnLaluAwal = $dateAwal->subMonth()->format('Y-m-d');
        $blnLaluAkhir = $dateAwal->subMonth()->format('Y-m');
        $gudangdepo = ['Gd-03010100', 'Gd-03010101', 'Gd-05010100', 'Gd-04010102', 'Gd-04010103', 'Gd-05010101', 'Gd-04010104']; //
        $obat = Mobatnew::select(
            'kd_obat',
            'nama_obat',
            'bentuk_sediaan',
            'status_forkid',
            'status_fornas',
            'status_generik',
            'status_prb',
            'status_konsinyasi',
            'status_kronis',
            'kelompok_psikotropika',
            'kode108',
        )
            ->with([
                'kodebelanja:kode,uraian,uraianB',
                'saldoawal' => function ($sal) use ($blnLaluAkhir, $gudangdepo) {
                    $sal->select(
                        'kdobat',
                        DB::raw('sum(jumlah) as jumlah')
                    )
                        ->where('tglopname', 'LIKE', '%' . $blnLaluAkhir . '%')
                        ->whereIn('kdruang', $gudangdepo)
                        ->groupBy('tglopname', 'kdobat');
                },
                'penerimaanrinci' => function ($ter) {
                    $ter->select(
                        'penerimaan_r.kdobat',
                        DB::raw('sum(penerimaan_r.jml_terima_k) as jumlah')
                    )
                        ->leftJoin('penerimaan_h', 'penerimaan_h.nopenerimaan', '=', 'penerimaan_r.nopenerimaan')
                        ->whereBetween('tglpenerimaan', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
                        ->groupBy('penerimaan_r.kdobat');
                },
                // 'mutasikeluar' => function ($mut) {
                //     $mut->select(
                //         'mutasi_gudangdepo.kd_obat',
                //         DB::raw('sum(mutasi_gudangdepo.jml) as jumlah'),
                //         DB::raw('sum(mutasi_gudangdepo.jml * mutasi_gudangdepo.harga) as subtotal'),
                //     )
                //         ->leftJoin('permintaan_h', 'permintaan_h.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                //         ->whereBetween('permintaan_h.tgl_kirim_depo', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
                //         ->where('permintaan_h.dari', 'LIKE', '%R-%');
                // },

                'mutasikeluar' => function ($mut) {
                    $mut->select(
                        'mutasi_gudangdepo.kd_obat',
                        DB::raw('sum(mutasi_gudangdepo.jml) as jumlah'),
                        DB::raw('sum(mutasi_gudangdepo.jml * mutasi_gudangdepo.harga) as subtotal'),
                    )
                        ->leftJoin('permintaan_h', 'permintaan_h.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                        ->whereBetween('permintaan_h.tgl_kirim_depo', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
                        ->where('permintaan_h.dari', 'LIKE', '%R-%')
                        ->groupBy('mutasi_gudangdepo.kd_obat');
                },

                'resepkeluar' => function ($kel) {
                    $kel->select(
                        'resep_keluar_r.kdobat',
                        'resep_keluar_h.sistembayar',
                        DB::raw('sum(resep_keluar_r.jumlah) as jumlah'),
                        DB::raw('sum(resep_keluar_r.jumlah * resep_keluar_r.harga_jual) as subtotal'),
                    )
                        ->leftJoin('resep_keluar_h', 'resep_keluar_h.noresep', '=', 'resep_keluar_r.noresep')
                        ->whereBetween('resep_keluar_h.tgl_selesai', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
                        ->groupBy('resep_keluar_h.sistembayar', 'resep_keluar_r.kdobat');
                },

                'resepkeluarracikan' => function ($kel) {
                    $kel->select(
                        'resep_keluar_racikan_r.kdobat',
                        'resep_keluar_h.sistembayar',
                        DB::raw('sum(resep_keluar_racikan_r.jumlah) as jumlah'),
                        DB::raw('sum(resep_keluar_racikan_r.jumlah * resep_keluar_racikan_r.harga_jual) as subtotal'),
                    )
                        ->leftJoin('resep_keluar_h', 'resep_keluar_h.noresep', '=', 'resep_keluar_racikan_r.noresep')
                        ->whereBetween('resep_keluar_h.tgl_selesai', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
                        ->groupBy('resep_keluar_h.sistembayar', 'resep_keluar_racikan_r.kdobat');
                },
                'returpenjualan' => function ($kel) {
                    $kel->select(
                        'retur_penjualan_r.kdobat',
                        'resep_keluar_h.sistembayar',
                        DB::raw('sum(retur_penjualan_r.jumlah_keluar) as jumlah'),
                        DB::raw('sum(retur_penjualan_r.jumlah_keluar * retur_penjualan_r.harga_jual) as subtotal'),
                    )
                        ->leftJoin('retur_penjualan_h', 'retur_penjualan_h.noretur', '=', 'retur_penjualan_r.noretur')
                        ->leftJoin('resep_keluar_h', 'resep_keluar_h.noresep', '=', 'retur_penjualan_h.noresep')
                        ->whereBetween('retur_penjualan_h.tgl_retur', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
                        ->groupBy('resep_keluar_h.sistembayar', 'retur_penjualan_r.kdobat');
                }
            ])
            // ->where('nama_obat', 'LIKE', '%oksi%')
            ->get();
        $obat->append('harga');
        return new JsonResponse([
            'data' => $obat,
            'req' => request()->all(),
            'blnLaluAkhir' => $blnLaluAkhir
        ]);
    }
    function getPemakaianObat()
    {

        $dateAwal = Carbon::parse(request('from'));
        // $dateAkhir = Carbon::parse(request('to'));
        // $blnLaluAwal = $dateAwal->subMonth()->format('Y-m-d');
        $blnLaluAkhir = $dateAwal->subMonth()->format('Y-m');
        $gudangdepo = ['Gd-03010100', 'Gd-03010101', 'Gd-05010100', 'Gd-04010102', 'Gd-04010103', 'Gd-05010101', 'Gd-04010104']; //
        $obat = Mobatnew::select(
            'kd_obat',
            'nama_obat',
            'bentuk_sediaan',
            'status_forkid',
            'status_fornas',
            'status_generik',
            'status_prb',
            'status_konsinyasi',
            'status_kronis',
            'kelompok_psikotropika',
            'kode108',
        )
            ->with([
                'kodebelanja:kode,uraian,uraianB',
                'saldoawal' => function ($sal) use ($blnLaluAkhir, $gudangdepo) {
                    $sal->select(
                        'kdobat',
                        DB::raw('sum(jumlah) as jumlah')
                    )
                        ->where('tglopname', 'LIKE', '%' . $blnLaluAkhir . '%')
                        ->whereIn('kdruang', $gudangdepo)
                        ->groupBy('tglopname', 'kdobat');
                },
                'penerimaanrinci' => function ($ter) {
                    $ter->select(
                        'penerimaan_r.kdobat',
                        DB::raw('sum(penerimaan_r.jml_terima_k) as jumlah')
                    )
                        ->leftJoin('penerimaan_h', 'penerimaan_h.nopenerimaan', '=', 'penerimaan_r.nopenerimaan')
                        ->whereBetween('tglpenerimaan', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
                        ->groupBy('penerimaan_r.kdobat');
                },
                'mutasikeluar' => function ($mut) {
                    $mut->select(
                        'mutasi_gudangdepo.kd_obat',
                        DB::raw('sum(mutasi_gudangdepo.jml) as jumlah'),
                        DB::raw('sum(mutasi_gudangdepo.jml * mutasi_gudangdepo.harga) as subtotal'),
                    )
                        ->leftJoin('permintaan_h', 'permintaan_h.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                        ->whereBetween('permintaan_h.tgl_kirim_depo', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
                        ->where('permintaan_h.dari', 'LIKE', '%R-%')
                        ->groupBy('mutasi_gudangdepo.kd_obat');
                },

                'resepkeluar' => function ($kel) {
                    $kel->select(
                        'resep_keluar_r.kdobat',
                        'resep_keluar_h.sistembayar',
                        DB::raw('sum(resep_keluar_r.jumlah) as jumlah'),
                        DB::raw('sum(resep_keluar_r.jumlah * resep_keluar_r.harga_jual) as subtotal'),
                    )
                        ->leftJoin('resep_keluar_h', 'resep_keluar_h.noresep', '=', 'resep_keluar_r.noresep')
                        ->whereBetween('resep_keluar_h.tgl_selesai', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
                        ->groupBy('resep_keluar_h.sistembayar', 'resep_keluar_r.kdobat');
                },

                'resepkeluarracikan' => function ($kel) {
                    $kel->select(
                        'resep_keluar_racikan_r.kdobat',
                        'resep_keluar_h.sistembayar',
                        DB::raw('sum(resep_keluar_racikan_r.jumlah) as jumlah'),
                        DB::raw('sum(resep_keluar_racikan_r.jumlah * resep_keluar_racikan_r.harga_jual) as subtotal'),
                    )
                        ->leftJoin('resep_keluar_h', 'resep_keluar_h.noresep', '=', 'resep_keluar_racikan_r.noresep')
                        ->whereBetween('resep_keluar_h.tgl_selesai', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
                        ->groupBy('resep_keluar_h.sistembayar', 'resep_keluar_racikan_r.kdobat');
                },
                'returpenjualan' => function ($kel) {
                    $kel->select(
                        'retur_penjualan_r.kdobat',
                        'resep_keluar_h.sistembayar',
                        DB::raw('sum(retur_penjualan_r.jumlah_retur) as jumlah'),
                        DB::raw('sum(retur_penjualan_r.jumlah_retur * retur_penjualan_r.harga_jual) as subtotal'),
                    )
                        ->leftJoin('retur_penjualan_h', 'retur_penjualan_h.noretur', '=', 'retur_penjualan_r.noretur')
                        ->leftJoin('resep_keluar_h', 'resep_keluar_h.noresep', '=', 'retur_penjualan_h.noresep')
                        ->whereBetween('retur_penjualan_h.tgl_retur', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
                        ->groupBy('resep_keluar_h.sistembayar', 'retur_penjualan_r.kdobat');
                }
            ])
            ->where('nama_obat', 'LIKE', '%' . request('q') . '%')
            ->orWhere('kd_obat', 'LIKE', '%' . request('q') . '%')
            ->paginate(request('per_page'));
        $obat->append('harga');
        $data = collect($obat)['data'];
        $meta = collect($obat)->except('data');
        return new JsonResponse([
            'data' => $data,
            'meta' => $meta,
            'req' => request()->all(),
            'blnLaluAkhir' => $blnLaluAkhir
        ]);
    }
    public function getSistemBayar()
    {
        $data = SistemBayar::get();
        return new JsonResponse($data);
    }
    public function getPemakaianObatProgram()
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

            ->where(function ($q) {
                $q->where('nama_obat', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('kd_obat', 'LIKE', '%' . request('q') . '%');
            })
            ->where('obat_program', '1');
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
                    'persiapan_operasis.tgl_distribusi as tgl',
                    'persiapan_operasis.norm',
                    'persiapan_operasi_rincis.noresep',
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
                    'persiapan_operasi_distribusis.tgl_retur as tgl',
                    'persiapan_operasis.norm',
                    DB::raw('sum(persiapan_operasi_distribusis.jumlah_retur) as jumlah'),

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
            'daftarharga:kd_obat,nopenerimaan,harga',


        ]);
        // }
        $kirim = [];
        $obat = $rwobat->paginate(30);
        $anu = collect($obat)['data'];
        $meta = collect($obat)->except('data');
        foreach ($anu as $it) {
            $it['saldo'] = $it['saldoawal'];
            $it['terima'] = $it['penerimaanrinci'];
            $it['retur'] = $it['returpenjualan'] ?? [];
            $kirim[] = $it;
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
