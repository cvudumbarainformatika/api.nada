<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaandepoheder;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaanresep;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaanresepracikan;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarrinci;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarrinciracikan;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Mutasi\Mutasigudangkedepo;
use App\Models\Simrs\Penunjang\Farmasinew\Obatoperasi\PersiapanOperasi;
use App\Models\Simrs\Penunjang\Farmasinew\Obatoperasi\PersiapanOperasiDistribusi;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanRinci;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PenyesuaianController extends Controller
{
    public function index()
    {
        $koderuangan = request('koderuangan');
        $bulan = '11';
        // $bulan = request('bulan');
        $tahun = request('tahun');
        $x = $tahun . '-' . $bulan;
        $tglAwal = $x . '-01';
        $tglAkhir = $x . date('-t', strtotime($x . '-01'));
        $dateAwal = Carbon::parse($tglAwal);
        $dateAkhir = Carbon::parse($tglAkhir);
        $blnLaluAwal = $dateAwal->subMonth()->format('Y-m');
        $blnLaluAkhir = $dateAkhir->subMonth()->format('Y-m-t');
        // $date->format('Y-m-d')
        // return new JsonResponse($dateAwal);
        // return new JsonResponse([
        //     'lalu awal' => $blnLaluAwal,
        //     'lalu Akhir' => $blnLaluAkhir,
        //     'Akhir' => $tglAkhir,
        // ]);

        // $ruangan = Ruang::select('uraian')->where('kode', $koderuangan)->first()->uraian ?? null ;
        // $gudang=Gudang::select('nama')->where('kode', $koderuangan)->first()->nama ?? null;

        // $ruang= $ruangan?? $gudang ?? null;

        $list = Mobatnew::query()
            ->select('kd_obat', 'nama_obat', 'satuan_k', 'satuan_b', 'id', 'flag', 'merk', 'kandungan')
            ->with([
                'saldoawal' => function ($saldo) use ($blnLaluAwal, $blnLaluAkhir, $x) {
                    $saldo
                        // ->whereBetween('tglopname', [$blnLaluAwal . ' 00:00:00', $blnLaluAkhir . ' 23:59:59'])
                        ->where('tglopname', 'LIKE', $blnLaluAwal . '%')
                        ->where('kdruang', request('koderuangan'))->select('tglopname', 'kdobat', DB::raw('sum(jumlah) as jumlah'))
                        ->groupBy('kdobat', 'tglopname');
                },
                'fisik' => function ($saldo) use ($tglAwal, $tglAkhir) {
                    $saldo->whereBetween('tglopname', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('kdruang', request('koderuangan'))->select('tglopname', 'kdobat', 'jumlah');
                },
                'saldoakhir' => function ($saldo) use ($tglAwal, $tglAkhir, $x) {
                    $saldo
                        // ->whereBetween('tglopname', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('tglopname', 'LIKE', $x . '%')
                        ->where('kdruang', request('koderuangan'))->select('tglopname', 'kdobat', DB::raw('sum(jumlah) as jumlah'))
                        ->groupBy('kdobat', 'tglopname');
                },
                // untuk ambil penyesuaian stok awal
                'stok' => function ($stok) use ($koderuangan, $tglAwal, $tglAkhir, $x) {
                    $stok->select('id', 'kdobat', 'nopenerimaan', 'nobatch', 'jumlah')
                        ->with([
                            'ssw' => function ($q) use ($tglAwal, $tglAkhir, $x) {
                                $q->where('tgl_penyesuaian', 'LIKE', $x . '%');
                                // $q->whereBetween('tgl_penyesuaian', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59']);
                            }
                        ])
                        ->where('jumlah', '!=', 0)
                        ->where('kdruang', $koderuangan);
                },
                'penyesuaian' => function ($q) use ($koderuangan, $x) {
                    $q->join('stokreal', 'stokreal.id', '=', 'penyesuaian_stoks.stokreal_id')
                        ->where('kdruang', $koderuangan)
                        ->where('tgl_penyesuaian', 'LIKE', $x . '%');
                },
                // hanya ada jika koderuang itu adalah gudang
                'penerimaanrinci' => function ($q) use ($tglAwal, $tglAkhir, $koderuangan) {
                    $q->select(
                        'penerimaan_r.kdobat as kdobat',
                        'penerimaan_r.jml_all_penerimaan as jml_all_penerimaan',
                        'penerimaan_r.jml_terima_b as jml_terima_b',
                        'penerimaan_r.jml_terima_k as jml_terima_k',
                        'penerimaan_h.nopenerimaan as nopenerimaan',
                        'penerimaan_h.tglpenerimaan as tglpenerimaan',
                        'penerimaan_h.gudang as gudang',
                        'penerimaan_h.jenissurat as jenissurat',
                        'penerimaan_h.jenis_penerimaan as jenis_penerimaan',
                        'penerimaan_h.kunci as kunci',
                    )
                        ->join('penerimaan_h', 'penerimaan_r.nopenerimaan', '=', 'penerimaan_h.nopenerimaan')
                        ->whereBetween('penerimaan_h.tglpenerimaan', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('penerimaan_h.gudang', $koderuangan);
                },


                // mutasi masuk baik dari gudang, ataupun depo termasuk didalamnya mutasi antar depo dan antar gudang÷
                'mutasimasuk' => function ($q) use ($tglAwal, $tglAkhir, $koderuangan) {
                    $q->select(
                        'mutasi_gudangdepo.kd_obat as kd_obat',
                        // 'mutasi_gudangdepo.jml as jml',
                        DB::raw('sum(mutasi_gudangdepo.jml) as jml'),
                        'permintaan_h.tgl_permintaan as tgl_permintaan',
                        'permintaan_h.tujuan as tujuan',
                        'permintaan_h.dari as dari',
                        'permintaan_h.no_permintaan as no_permintaan'
                    )
                        ->join('permintaan_h', 'permintaan_h.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                        ->whereBetween('permintaan_h.tgl_terima_depo', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('dari', $koderuangan)
                        ->groupBy('mutasi_gudangdepo.kd_obat');
                },


                // mutasi keluar baik ke gudang(mutasi antar gudang), ataupun ke depo dan juga ke ruangan
                'mutasikeluar' => function ($q) use ($tglAwal, $tglAkhir, $koderuangan) {
                    $q->select(
                        'mutasi_gudangdepo.kd_obat as kd_obat',
                        // 'mutasi_gudangdepo.jml as jml',
                        DB::raw('sum(mutasi_gudangdepo.jml) as jml'),
                        'permintaan_h.tgl_permintaan as tgl_permintaan',
                        'permintaan_h.tujuan as tujuan',
                        'permintaan_h.dari as dari',
                        'permintaan_h.no_permintaan as no_permintaan'
                    )
                        ->join('permintaan_h', 'permintaan_h.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                        ->whereBetween('permintaan_h.tgl_kirim_depo', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('tujuan', $koderuangan)
                        ->groupBy('mutasi_gudangdepo.kd_obat');
                },

                // retur
                'returpenjualan' => function ($q) use ($tglAwal, $tglAkhir, $koderuangan) {
                    $q->select(
                        'retur_penjualan_r.kdobat',
                        DB::raw('sum(retur_penjualan_r.jumlah_retur) as jumlah_retur'),
                    )
                        ->join('retur_penjualan_h', 'retur_penjualan_r.noretur', '=', 'retur_penjualan_h.noretur')
                        ->join('resep_keluar_h', 'retur_penjualan_r.noresep', '=', 'resep_keluar_h.noresep')
                        ->whereBetween('retur_penjualan_h.tgl_retur', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('resep_keluar_h.depo', $koderuangan)
                        ->groupBy('retur_penjualan_r.kdobat');
                },

                'resepkeluar' => function ($q) use ($tglAwal, $tglAkhir, $koderuangan) {
                    $q->select(
                        'resep_keluar_r.kdobat',
                        DB::raw('sum(resep_keluar_r.jumlah) as jumlah')
                    )
                        ->join('resep_keluar_h', 'resep_keluar_r.noresep', '=', 'resep_keluar_h.noresep')
                        ->when($koderuangan === 'Gd-04010103', function ($kd) {
                            $kd->leftJoin('persiapan_operasi_rincis', function ($q) {
                                $q->on('persiapan_operasi_rincis.noresep', '=', 'resep_keluar_r.noresep')
                                    ->on('persiapan_operasi_rincis.kd_obat', '=', 'resep_keluar_r.kdobat');
                            })
                                ->whereNull('persiapan_operasi_rincis.noresep');
                        })
                        ->whereBetween('resep_keluar_h.tgl_selesai', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('resep_keluar_h.depo', $koderuangan)
                        ->where('resep_keluar_r.jumlah', '>', 0)
                        ->whereIn('resep_keluar_h.flag', ['3', '4'])
                        ->groupBy('resep_keluar_r.kdobat');
                    // ->with('retur.rinci');
                },

                'resepkeluarracikan' => function ($q) use ($tglAwal, $tglAkhir, $koderuangan) {
                    $q->select(
                        'resep_keluar_racikan_r.kdobat',
                        DB::raw('sum(resep_keluar_racikan_r.jumlah) as jumlah')
                    )
                        ->join('resep_keluar_h', 'resep_keluar_racikan_r.noresep', '=', 'resep_keluar_h.noresep')
                        ->whereBetween('resep_keluar_h.tgl_selesai', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('resep_keluar_h.depo', $koderuangan)
                        ->where('resep_keluar_racikan_r.jumlah', '>', 0)
                        ->whereIn('resep_keluar_h.flag', ['3', '4'])
                        ->groupBy('resep_keluar_racikan_r.kdobat');

                    // ->with('retur.rinci');
                },

                'distribusipersiapan' => function ($dist) use ($tglAwal, $tglAkhir) {
                    $dist->select(
                        'persiapan_operasi_distribusis.kd_obat',
                        'persiapan_operasis.nopermintaan',
                        'persiapan_operasis.tgl_distribusi',
                        'persiapan_operasi_distribusis.tgl_retur',
                        'persiapan_operasi_rincis.noresep',
                        'persiapan_operasi_rincis.created_at',
                        DB::raw('sum(persiapan_operasi_distribusis.jumlah) as keluar'),
                        DB::raw('sum(persiapan_operasi_distribusis.jumlah_retur) as retur'),

                    )
                        ->leftJoin('persiapan_operasis', 'persiapan_operasis.nopermintaan', '=', 'persiapan_operasi_distribusis.nopermintaan')
                        ->leftJoin('persiapan_operasi_rincis', function ($join) {
                            $join->on('persiapan_operasi_rincis.nopermintaan', '=', 'persiapan_operasi_distribusis.nopermintaan')
                                ->on('persiapan_operasi_rincis.kd_obat', '=', 'persiapan_operasi_distribusis.kd_obat');
                        })
                        ->whereBetween('persiapan_operasis.tgl_distribusi', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->whereIn('persiapan_operasis.flag', ['2', '3', '4'])

                        ->groupBy('persiapan_operasi_distribusis.kd_obat');
                },
                'persiapanretur' => function ($dist) use ($tglAwal, $tglAkhir) {
                    $dist->select(
                        'persiapan_operasi_distribusis.kd_obat',
                        'persiapan_operasis.nopermintaan',
                        'persiapan_operasis.tgl_distribusi',
                        'persiapan_operasi_distribusis.tgl_retur',
                        'persiapan_operasi_rincis.noresep',
                        'persiapan_operasi_rincis.created_at',
                        DB::raw('sum(persiapan_operasi_distribusis.jumlah) as keluar'),
                        DB::raw('sum(persiapan_operasi_distribusis.jumlah_retur) as retur'),

                    )
                        ->leftJoin('persiapan_operasis', 'persiapan_operasis.nopermintaan', '=', 'persiapan_operasi_distribusis.nopermintaan')
                        ->leftJoin('persiapan_operasi_rincis', function ($join) {
                            $join->on('persiapan_operasi_rincis.nopermintaan', '=', 'persiapan_operasi_distribusis.nopermintaan')
                                ->on('persiapan_operasi_rincis.kd_obat', '=', 'persiapan_operasi_distribusis.kd_obat');
                        })
                        ->whereBetween('persiapan_operasis.tgl_retur', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->whereIn('persiapan_operasis.flag', ['2', '3', '4'])

                        ->groupBy('persiapan_operasi_distribusis.kd_obat');
                },
                'barangrusak' => function ($ru) use ($tglAwal, $tglAkhir) {
                    $ru->select(
                        'kd_obat',
                        DB::raw('sum(jumlah) as jumlah')
                    )->whereBetween('tgl_kunci', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('kunci', '1')
                        ->groupBy('kd_obat');
                },
                // retur gudang (masuk gudang)
                'returgudang' => function ($ru) use ($tglAwal, $tglAkhir) {
                    $ru->select(
                        'retur_gudang_details.kd_obat',
                        'retur_gudangs.tgl_retur',
                        DB::raw('sum(retur_gudang_details.jumlah_retur) as jumlah')
                    )
                        ->leftJoin('retur_gudangs', 'retur_gudangs.no_retur', '=', 'retur_gudang_details.no_retur')
                        ->where('retur_gudangs.gudang', request('koderuangan'))
                        ->whereBetween('retur_gudangs.tgl_retur', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('retur_gudangs.kunci', '1')
                        ->groupBy('retur_gudang_details.kd_obat', 'retur_gudangs.gudang');
                },
                // retur depo (keluar depo)
                'returdepo' => function ($ru) use ($tglAwal, $tglAkhir) {
                    $ru->select(
                        'retur_gudang_details.kd_obat',
                        'retur_gudangs.tgl_retur',
                        DB::raw('sum(retur_gudang_details.jumlah_retur) as jumlah')
                    )
                        ->leftJoin('retur_gudangs', 'retur_gudangs.no_retur', '=', 'retur_gudang_details.no_retur')
                        ->where('retur_gudangs.depo', request('koderuangan'))
                        ->whereBetween('retur_gudangs.tgl_retur', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('retur_gudangs.kunci', '1')
                        ->groupBy('retur_gudang_details.kd_obat', 'retur_gudangs.depo');
                }

            ])

            // ->withCount('penerimaanrinci')
            // ->addSelect([
            //     'ruangan' => $ruang
            // ])
            ->where(function ($q) {
                $q->where('nama_obat', 'Like', '%' . request('q') . '%')
                    ->orWhere('kd_obat', 'Like', '%' . request('q') . '%')
                    ->orWhere('merk', 'Like', '%' . request('q') . '%')
                    ->orWhere('kandungan', 'Like', '%' . request('q') . '%');
            })->orderBy('id', 'asc')
            ->where('flag', '')
            ->paginate(request('rowsPerPage'));



        return new JsonResponse($list);
        // return new JsonResponse([
        //     'lalu awal'=>$blnLaluAwal,
        //     'lalu Akhir'=>$blnLaluAkhir,
        // ]);
    }
    /**
     * rules penyesuaian:
     * 1. tampil di kartu stok pada tanggal transaksi yang mau diperbaiki.
     * 2. bisa melihat versi setelah revisi dan sebelum revisi.
     * 3. maka, tidak perlu merubah tabel data yang sudah ada, tapi tabel penyesuaian itu di ikutkan
     *  dari tanggal transaksi yang salah sampai tanggal penyesuaian dibuat.
     */
    public function getObat()
    {
        $koderuangan = request('kdruang');
        $tglAwal = date('Y-m') . '-01';
        $tglAkhir = date('Y-m-t');

        /**
         * mock date
         */
        // $mcAwal = Carbon::now()->format('Y-m') . '-01';
        // $mcAkhir = Carbon::now()->format('Y-m-t');
        // $tglAwal = Carbon::parse($mcAwal)->subMonth()->format('Y-m') . '-01';
        // $tglAkhir = Carbon::parse($mcAkhir)->subMonth()->format('Y-m-t');
        /**
         * mock date
         */

        $dateAwal = Carbon::parse($tglAwal);
        $dateAkhir = Carbon::parse($tglAkhir);
        $blnLaluAwal = $dateAwal->subMonth()->format('Y-m-d');
        $blnLaluAkhir = $dateAkhir->subMonth()->format('Y-m-t');
        // return new JsonResponse([
        //     'tglAwal' => $tglAwal,
        //     'tglAkhir' => $tglAkhir,
        //     'blnLaluAwal' => $blnLaluAwal,
        //     'blnLaluAkhir' => $blnLaluAkhir
        // ]);
        $data = Mobatnew::select('kd_obat', 'nama_obat')
            ->with([
                'saldoawal' => function ($saldo) use ($blnLaluAwal, $blnLaluAkhir) {
                    $saldo->whereBetween('tglopname', [$blnLaluAwal . ' 00:00:00', $blnLaluAkhir . ' 23:59:59'])
                        ->where('kdruang', request('kdruang'))->select('tglopname', 'kdobat', DB::raw('sum(jumlah) as jumlah'))
                        ->groupBy('kdobat', 'tglopname');
                },
                'fisik' => function ($saldo) use ($tglAwal, $tglAkhir) {
                    $saldo->whereBetween('tglopname', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('kdruang', request('kdruang'))->select('tglopname', 'kdobat', 'jumlah');
                },
                'saldoakhir' => function ($saldo) use ($tglAwal, $tglAkhir) {
                    $saldo->whereBetween('tglopname', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('kdruang', request('kdruang'))->select('tglopname', 'kdobat', DB::raw('sum(jumlah) as jumlah'))
                        ->groupBy('kdobat', 'tglopname');
                },
                // untuk ambil penyesuaian stok awal
                'stok' => function ($stok) use ($koderuangan, $tglAwal, $tglAkhir) {
                    $stok->select('id', 'kdobat', 'nopenerimaan', 'nobatch', 'jumlah')
                        ->with([
                            'ssw' => function ($q) use ($tglAwal, $tglAkhir) {
                                $q->whereBetween('tgl_penyesuaian', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59']);
                            }
                        ])
                        ->where('kdruang', $koderuangan);
                },
                // hanya ada jika koderuang itu adalah gudang
                'penerimaanrinci' => function ($q) use ($tglAwal, $tglAkhir, $koderuangan) {
                    $q->select(
                        'penerimaan_r.kdobat as kdobat',
                        'penerimaan_r.jml_all_penerimaan as jml_all_penerimaan',
                        'penerimaan_r.jml_terima_b as jml_terima_b',
                        'penerimaan_r.jml_terima_k as jml_terima_k',
                        'penerimaan_h.nopenerimaan as nopenerimaan',
                        'penerimaan_h.tglpenerimaan as tglpenerimaan',
                        'penerimaan_h.gudang as gudang',
                        'penerimaan_h.jenissurat as jenissurat',
                        'penerimaan_h.jenis_penerimaan as jenis_penerimaan',
                        'penerimaan_h.kunci as kunci',
                    )
                        ->join('penerimaan_h', 'penerimaan_r.nopenerimaan', '=', 'penerimaan_h.nopenerimaan')
                        ->whereBetween('penerimaan_h.tglpenerimaan', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('penerimaan_h.gudang', $koderuangan);
                },


                // mutasi masuk baik dari gudang, ataupun depo termasuk didalamnya mutasi antar depo dan antar gudang÷
                'mutasimasuk' => function ($q) use ($tglAwal, $tglAkhir, $koderuangan) {
                    $q->select(
                        'mutasi_gudangdepo.kd_obat as kd_obat',
                        // 'mutasi_gudangdepo.jml as jml',
                        DB::raw('sum(mutasi_gudangdepo.jml) as jml'),
                        'permintaan_h.tgl_permintaan as tgl_permintaan',
                        'permintaan_h.tujuan as tujuan',
                        'permintaan_h.dari as dari',
                        'permintaan_h.no_permintaan as no_permintaan'
                    )
                        ->join('permintaan_h', 'permintaan_h.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                        ->whereBetween('permintaan_h.tgl_terima_depo', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('dari', $koderuangan)
                        ->groupBy('mutasi_gudangdepo.kd_obat');
                },


                // mutasi keluar baik ke gudang(mutasi antar gudang), ataupun ke depo dan juga ke ruangan
                'mutasikeluar' => function ($q) use ($tglAwal, $tglAkhir, $koderuangan) {
                    $q->select(
                        'mutasi_gudangdepo.kd_obat as kd_obat',
                        // 'mutasi_gudangdepo.jml as jml',
                        DB::raw('sum(mutasi_gudangdepo.jml) as jml'),
                        'permintaan_h.tgl_permintaan as tgl_permintaan',
                        'permintaan_h.tujuan as tujuan',
                        'permintaan_h.dari as dari',
                        'permintaan_h.no_permintaan as no_permintaan'
                    )
                        ->join('permintaan_h', 'permintaan_h.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                        ->whereBetween('permintaan_h.tgl_kirim_depo', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('tujuan', $koderuangan)
                        ->groupBy('mutasi_gudangdepo.kd_obat');
                },

                // retur
                'returpenjualan' => function ($q) use ($tglAwal, $tglAkhir, $koderuangan) {
                    $q->select(
                        'retur_penjualan_r.kdobat',
                        DB::raw('sum(retur_penjualan_r.jumlah_retur) as jumlah_retur'),
                    )
                        ->join('retur_penjualan_h', 'retur_penjualan_r.noretur', '=', 'retur_penjualan_h.noretur')
                        ->join('resep_keluar_h', 'retur_penjualan_r.noresep', '=', 'resep_keluar_h.noresep')
                        ->whereBetween('retur_penjualan_h.tgl_retur', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('resep_keluar_h.depo', $koderuangan)
                        ->groupBy('retur_penjualan_r.kdobat');
                },

                'resepkeluar' => function ($q) use ($tglAwal, $tglAkhir, $koderuangan) {
                    $q->select(
                        'resep_keluar_r.kdobat',
                        DB::raw('sum(resep_keluar_r.jumlah) as jumlah')
                    )
                        ->join('resep_keluar_h', 'resep_keluar_r.noresep', '=', 'resep_keluar_h.noresep')
                        ->when($koderuangan === 'Gd-04010103', function ($kd) {
                            $kd->leftJoin('persiapan_operasi_rincis', function ($q) {
                                $q->on('persiapan_operasi_rincis.noresep', '=', 'resep_keluar_r.noresep')
                                    ->on('persiapan_operasi_rincis.kd_obat', '=', 'resep_keluar_r.kdobat');
                            })
                                ->whereNull('persiapan_operasi_rincis.noresep');
                        })
                        ->whereBetween('resep_keluar_h.tgl_selesai', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('resep_keluar_h.depo', $koderuangan)
                        ->where('resep_keluar_r.jumlah', '>', 0)
                        ->whereIn('resep_keluar_h.flag', ['3', '4'])
                        ->groupBy('resep_keluar_r.kdobat');
                    // ->with('retur.rinci');
                },

                'resepkeluarracikan' => function ($q) use ($tglAwal, $tglAkhir, $koderuangan) {
                    $q->select(
                        'resep_keluar_racikan_r.kdobat',
                        DB::raw('sum(resep_keluar_racikan_r.jumlah) as jumlah')
                    )
                        ->join('resep_keluar_h', 'resep_keluar_racikan_r.noresep', '=', 'resep_keluar_h.noresep')
                        ->whereBetween('resep_keluar_h.tgl_selesai', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('resep_keluar_h.depo', $koderuangan)
                        ->where('resep_keluar_racikan_r.jumlah', '>', 0)
                        ->whereIn('resep_keluar_h.flag', ['3', '4'])
                        ->groupBy('resep_keluar_racikan_r.kdobat');

                    // ->with('retur.rinci');
                },

                'distribusipersiapan' => function ($dist) use ($tglAwal, $tglAkhir) {
                    $dist->select(
                        'persiapan_operasi_distribusis.kd_obat',
                        'persiapan_operasis.nopermintaan',
                        'persiapan_operasis.tgl_distribusi',
                        'persiapan_operasi_distribusis.tgl_retur',
                        'persiapan_operasi_rincis.noresep',
                        'persiapan_operasi_rincis.created_at',
                        DB::raw('sum(persiapan_operasi_distribusis.jumlah) as keluar'),
                        DB::raw('sum(persiapan_operasi_distribusis.jumlah_retur) as retur'),

                    )
                        ->leftJoin('persiapan_operasis', 'persiapan_operasis.nopermintaan', '=', 'persiapan_operasi_distribusis.nopermintaan')
                        ->leftJoin('persiapan_operasi_rincis', function ($join) {
                            $join->on('persiapan_operasi_rincis.nopermintaan', '=', 'persiapan_operasi_distribusis.nopermintaan')
                                ->on('persiapan_operasi_rincis.kd_obat', '=', 'persiapan_operasi_distribusis.kd_obat');
                        })
                        ->whereBetween('persiapan_operasis.tgl_distribusi', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->whereIn('persiapan_operasis.flag', ['2', '3', '4'])

                        ->groupBy('persiapan_operasi_distribusis.kd_obat');
                },
                'persiapanretur' => function ($dist) use ($tglAwal, $tglAkhir) {
                    $dist->select(
                        'persiapan_operasi_distribusis.kd_obat',
                        'persiapan_operasis.nopermintaan',
                        'persiapan_operasis.tgl_distribusi',
                        'persiapan_operasi_distribusis.tgl_retur',
                        'persiapan_operasi_rincis.noresep',
                        'persiapan_operasi_rincis.created_at',
                        DB::raw('sum(persiapan_operasi_distribusis.jumlah) as keluar'),
                        DB::raw('sum(persiapan_operasi_distribusis.jumlah_retur) as retur'),

                    )
                        ->leftJoin('persiapan_operasis', 'persiapan_operasis.nopermintaan', '=', 'persiapan_operasi_distribusis.nopermintaan')
                        ->leftJoin('persiapan_operasi_rincis', function ($join) {
                            $join->on('persiapan_operasi_rincis.nopermintaan', '=', 'persiapan_operasi_distribusis.nopermintaan')
                                ->on('persiapan_operasi_rincis.kd_obat', '=', 'persiapan_operasi_distribusis.kd_obat');
                        })
                        ->whereBetween('persiapan_operasis.tgl_retur', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->whereIn('persiapan_operasis.flag', ['2', '3', '4'])

                        ->groupBy('persiapan_operasi_distribusis.kd_obat');
                },
                'barangrusak' => function ($ru) use ($tglAwal, $tglAkhir) {
                    $ru->select(
                        'kd_obat',
                        DB::raw('sum(jumlah) as jumlah')
                    )->whereBetween('tgl_rusak', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('kunci', '1')
                        ->groupBy('kd_obat');
                }

            ])
            ->where(function ($q) {
                $q->where('nama_obat', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('kd_obat', 'LIKE', '%' . request('q') . '%');
            })
            ->where('flag', '')
            ->limit(10)
            ->get();
        return new JsonResponse($data);
    }
    public function getTransaksi()
    {
        $gudangs = ['Gd-05010100', 'Gd-03010100'];
        // $now = date('Y-m');
        $now = Carbon::now()->subMonth()->format('Y-m');
        $koderuangan = request('kdruang');
        $kdobat = request('kdobat');
        $penerimaan = null;
        if (in_array($koderuangan, $gudangs)) {

            $noperRinci = PenerimaanRinci::select(
                'penerimaan_r.nopenerimaan'
            )
                ->join('penerimaan_h', 'penerimaan_r.nopenerimaan', '=', 'penerimaan_h.nopenerimaan')
                ->where('penerimaan_h.tglpenerimaan', 'LIKE', '%' . $now . '%')
                ->where('penerimaan_h.gudang', $koderuangan)
                ->where('penerimaan_h.kunci', '1')
                ->where('penerimaan_r.kdobat', $kdobat)
                ->distinct('penerimaan_r.nopenerimaan')
                ->pluck('penerimaan_r.nopenerimaan');

            $penerimaan = PenerimaanHeder::whereIn('nopenerimaan', $noperRinci)->with([
                'penerimaanrinci.masterobat:kd_obat,nama_obat,satuan_k',
                'pihakketiga:kode,nama',
                'faktur'
            ])->get();
        }
        $data['penerimaan'] = $penerimaan;
        $noPerMutasiMasuk = Mutasigudangkedepo::select(
            'mutasi_gudangdepo.no_permintaan'
        )
            ->join('permintaan_h', 'permintaan_h.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
            ->where('permintaan_h.tgl_terima_depo',  'LIKE', '%' . $now . '%')
            ->where('permintaan_h.dari', $koderuangan)
            ->where('mutasi_gudangdepo.kd_obat', $kdobat)
            ->distinct('no_permintaan')
            ->pluck('no_permintaan');

        $noPerMutasiKeluar = Mutasigudangkedepo::select(
            'mutasi_gudangdepo.no_permintaan'
        )
            ->join('permintaan_h', 'permintaan_h.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
            ->where('permintaan_h.tgl_kirim_depo', 'LIKE', '%' . $now . '%')
            ->where('permintaan_h.tujuan', $koderuangan)
            ->where('mutasi_gudangdepo.kd_obat', $kdobat)
            ->distinct('no_permintaan')
            ->pluck('no_permintaan');

        $data['noperMa'] = $noPerMutasiMasuk;
        $data['noperKe'] = $noPerMutasiKeluar;
        $data['mutasiMasuk'] = Permintaandepoheder::with([
            'permintaanrinci' => function ($q) use ($kdobat) {
                $q->with([
                    'mutasi' => function ($mu) use ($kdobat) {
                        $mu->where('kd_obat', $kdobat)
                            ->with('obat:kd_obat,nama_obat,satuan_k');
                    },
                    'masterobat:kd_obat,nama_obat,satuan_k'
                ])
                    ->where('kdobat', $kdobat);
            },
            'asal:nama,kode',
            'menuju:nama,kode'
        ])
            ->where('dari', $koderuangan)
            ->whereIn('no_permintaan', $noPerMutasiMasuk)
            ->where('tgl_terima_depo', 'LIKE', '%' . $now  . '%')
            ->get();
        $data['mutasiKeluar'] = Permintaandepoheder::with([
            'permintaanrinci' => function ($q) use ($kdobat) {
                $q->with([
                    'mutasi' => function ($mu) use ($kdobat) {
                        $mu->where('kd_obat', $kdobat)
                            ->with('obat:kd_obat,nama_obat,satuan_k');
                    },
                    'masterobat:kd_obat,nama_obat,satuan_k'
                ])
                    ->where('kdobat', $kdobat);
            },
            'asal:nama,kode',
            'menuju:nama,kode'
        ])
            ->where('tujuan', $koderuangan)
            ->whereIn('no_permintaan', $noPerMutasiKeluar)
            ->where('tgl_kirim_depo', 'LIKE', '%' . $now  . '%')
            ->get();

        $norePer = Permintaanresep::select('resep_permintaan_keluar.noresep')
            ->join('resep_keluar_h',  'resep_keluar_h.noresep', '=', 'resep_permintaan_keluar.noresep')
            ->where('resep_permintaan_keluar.kdobat', $kdobat)
            ->whereIn('resep_keluar_h.flag', ['3', '4'])
            ->where('resep_keluar_h.tgl_selesai', 'LIKE', '%' . $now  . '%')
            ->where('resep_keluar_h.depo', $koderuangan)
            ->distinct('resep_permintaan_keluar.noresep')
            ->pluck('resep_permintaan_keluar.noresep');
        $norePerRac = Permintaanresepracikan::select('resep_permintaan_keluar_racikan.noresep')
            ->join('resep_keluar_h',  'resep_keluar_h.noresep', '=', 'resep_permintaan_keluar_racikan.noresep')
            ->where('resep_permintaan_keluar_racikan.kdobat', $kdobat)
            ->whereIn('resep_keluar_h.flag', ['3', '4'])
            ->where('resep_keluar_h.tgl_selesai', 'LIKE', '%' . $now  . '%')
            ->where('resep_keluar_h.depo', $koderuangan)
            ->distinct('resep_permintaan_keluar_racikan.noresep')
            ->pluck('resep_permintaan_keluar_racikan.noresep');
        $noreKel = Resepkeluarrinci::select('resep_keluar_r.noresep')
            ->join('resep_keluar_h',  'resep_keluar_h.noresep', '=', 'resep_keluar_r.noresep')
            ->where('resep_keluar_r.kdobat', $kdobat)
            ->whereIn('resep_keluar_h.flag', ['3', '4'])
            ->where('resep_keluar_h.tgl_selesai', 'LIKE', '%' . $now  . '%')
            ->where('resep_keluar_h.depo', $koderuangan)
            ->distinct('resep_keluar_r.noresep')
            ->pluck('resep_keluar_r.noresep');
        $noreKelRac = Resepkeluarrinciracikan::select('resep_keluar_racikan_r.noresep')
            ->join('resep_keluar_h',  'resep_keluar_h.noresep', '=', 'resep_keluar_racikan_r.noresep')
            ->where('resep_keluar_racikan_r.kdobat', $kdobat)
            ->whereIn('resep_keluar_h.flag', ['3', '4'])
            ->where('resep_keluar_h.tgl_selesai', 'LIKE', '%' . $now  . '%')
            ->where('resep_keluar_h.depo', $koderuangan)
            ->distinct('resep_keluar_racikan_r.noresep')
            ->pluck('resep_keluar_racikan_r.noresep');
        $noresep = array_unique(array_merge($norePer->toArray(), $norePerRac->toArray(), $noreKel->toArray(), $noreKelRac->toArray()));

        $data['resep'] = Resepkeluarheder::with([
            'permintaanresep' => function ($q) use ($kdobat) {
                $q->where('kdobat', $kdobat)
                    ->with('mobat:kd_obat,nama_obat,satuan_k');
            },
            'permintaanracikan' => function ($q) use ($kdobat) {
                $q->where('kdobat', $kdobat)
                    ->with('mobat:kd_obat,nama_obat,satuan_k');
            },
            'rincian' => function ($q) use ($kdobat) {
                $q->where('kdobat', $kdobat)
                    ->with('mobat:kd_obat,nama_obat,satuan_k');
            },
            'rincianracik' => function ($q) use ($kdobat) {
                $q->where('kdobat', $kdobat)
                    ->with('mobat:kd_obat,nama_obat,satuan_k');
            },
            'datapasien:rs1,rs2',
            'poli:rs1,rs2',
            'ruanganranap:rs1,rs2',
        ])
            ->whereIn('noresep', $noresep)
            ->whereIn('flag', ['3', '4'])
            ->where('tgl_selesai', 'LIKE', '%' . $now  . '%')
            ->where('depo', $koderuangan)
            ->get();
        $data['operasi'] = [];
        if ($koderuangan == 'Gd-04010103') {
            $noperOp = PersiapanOperasiDistribusi::select('persiapan_operasi_distribusis.nopermintaan')
                ->join('persiapan_operasis', 'persiapan_operasis.nopermintaan', '=', 'persiapan_operasi_distribusis.nopermintaan')
                ->where('persiapan_operasi_distribusis.kd_obat', $kdobat)
                ->where('persiapan_operasis.tgl_distribusi', 'Like', '%' . $now . '%')
                ->distinct('persiapan_operasi_distribusis.nopermintaan')
                ->pluck('persiapan_operasi_distribusis.nopermintaan');
            $data['operasi'] = PersiapanOperasi::with([
                'rinci' => function ($q) use ($kdobat) {
                    $q->where('kd_obat', $kdobat);
                },
                'distribusi' => function ($q) use ($kdobat) {
                    $q->where('kd_obat', $kdobat);
                },
            ])
                ->whereIn('nopermintaan', $noperOp)
                ->whereIn('flag', ['3', '4'])
                ->get();
        }
        // end


        $data['req'] = request()->all();
        return new JsonResponse($data);
    }
}
