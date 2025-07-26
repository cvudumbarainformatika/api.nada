<?php

namespace App\Http\Controllers\Api\Siasik\Laporan;

use App\Http\Controllers\Controller;
use App\Models\Pegawai\Mpegawaisimpeg;
use App\Models\Siasik\Anggaran\Anggaran_Pendapatan;
use App\Models\Siasik\Anggaran\PergeseranPaguRinci;
use App\Models\Siasik\Anggaran\Tampung_pendapatan;
use App\Models\Siasik\Master\Akun50_2024;
use App\Models\Siasik\Master\Akun_Kepmendg50;
use App\Models\Siasik\Master\Mapping_Bidang_Ptk_Kegiatan;
use App\Models\Siasik\TransaksiPendapatan\PengeluaranKas;
use App\Models\Siasik\TransaksiPendapatan\TranskePPK;
use App\Models\Siasik\TransaksiSilpa\SisaAnggaran;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Calculation\TextData\Format;
use PhpParser\Node\Expr\AssignOp\Concat;

class LRAController extends Controller
{
    public function bidang(){
        $thn= Carbon::createFromFormat('Y-m-d', request('tgl'))->format('Y');
        $bidang=Mapping_Bidang_Ptk_Kegiatan::where('tahun', $thn)
        ->where('alias', '!=', '')
        ->when(request('bidang'),function($keg) {
            $keg->where('kodebidang', request('bidang'));
        })
        ->select('kodebidang', 'bidang', 'kodekegiatan', 'kegiatan', 'kodepptk', 'namapptk')
        ->groupBy('kodekegiatan')
        ->get();

        // $bidangx = [
        //     'belanja' => $thn,
        //     'pendapatan' => $bidang,

        // ];

        return new JsonResponse($bidang);

    }

    public function laplra(){
        $thn=Carbon::createFromFormat('Y-m-d', request('tgl'))->format('Y');
        // $awal=request('tgl', 'Y'.'-'.'01-01');
        $awal = $thn.'-01-01';
        $akhir=request('tglx', 'Y-m-d');
        $thnakhir =Carbon::createFromFormat('Y-m-d', request('tglx'))->format('Y');
        if($thn !== $thnakhir){
         return response()->json(['message' => 'Tahun Tidak Sama'], 500);
        }

        // return $awal;
        // $thn = DB::table('anggaran')->whereYear('tgl', '=', now()->format('Y'))->get();
        // $thn=PergeseranPaguRinci::whereYear('tgl', '=', now()->format('Y'))->get();
        $belanja = Akun50_2024::select('akun50_2024.kodeall2',
        'akun50_2024.uraian', 'akun50_2024.kodeall3'
        )->addSelect(DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 1) as kode1'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 2) as kode2'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 3) as kode3'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 4) as kode4'),
                    DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 5) as kode5'))
        // ->leftJoin('akun50_2024 as wew', DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 2)'),'=','wew.kodeall3')
        ->with('kode1',function($gg){
            $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
            })
            ->with('kode2',function($gg){
                $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
                })
                ->with('kode3',function($gg){
                    $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
                    })
                    ->with('kode4',function($gg){
                        $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
                        })
                        ->with('kode5',function($gg){
                            $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
                            })
        ->join('t_tampung', 't_tampung.koderek50', '=', 'akun50_2024.kodeall2')
        ->where('tgl', $thn)
        ->where('pagu', '!=', '0' )
        ->when(request('bidang'),function($x) {
            $x->where('t_tampung.bidang', request('bidang'));
        })->when(request('kegiatan'),function($y) {
            $y->where('t_tampung.bidang', request('bidang'))
            ->where('t_tampung.kodekegiatanblud', request('kegiatan'));
        })
        ->with(['anggaran' => function($tgl) use ($thn) {
            $tgl->where('tgl', $thn)
            ->where('pagu', '!=', '0' )
            ->select('t_tampung.koderek50',
                    't_tampung.pagu',
                    't_tampung.kodekegiatanblud',
                    't_tampung.bidang',
                    't_tampung.tgl')
            ->when(request('bidang'),function($x) {
                $x->where('t_tampung.bidang', request('bidang'));
            })->when(request('kegiatan'),function($y) {
                $y->where('t_tampung.bidang', request('bidang'))
                ->where('t_tampung.kodekegiatanblud', request('kegiatan'));
            });
        },'npdls_rinci' => function ($head) use ($awal, $akhir){
            $head->select('npdls_rinci.nonpdls',
                        'npdls_rinci.koderek50',
                        'npdls_rinci.rincianbelanja',
                        'npdls_rinci.nominalpembayaran')
                        // ->groupBy('npdls_rinci.koderek50')
            ->join('npdls_heder', 'npdls_heder.nonpdls', '=', 'npdls_rinci.nonpdls')
            ->join('npkls_rinci', 'npkls_rinci.nonpdls', '=', 'npdls_heder.nonpdls')
            ->join('npkls_heder', 'npkls_heder.nonpk', '=', 'npkls_rinci.nonpk')
            ->where('npdls_heder.nopencairan', '!=', '')
                ->when(request('bidang'),function($x) {
                    $x->where('npdls_heder.kodebidang', request('bidang'));
                })->when(request('kegiatan'),function($y) {
                    $y->where('npdls_heder.kodebidang', request('bidang'))
                    ->where('npdls_heder.kodekegiatanblud', request('kegiatan'));
                })
                ->whereBetween('npkls_heder.tglpindahbuku', [$awal, $akhir])

            ->with('headerls',function($npk) use ($awal, $akhir) {
                $npk->select('npdls_heder.nonpdls',
                            'npdls_heder.tglnpdls',
                            'npdls_heder.nopencairan',
                            'npdls_heder.nonpk',
                            'npdls_heder.kodekegiatanblud',
                            'npdls_heder.kodebidang')
                ->join('npkls_rinci', 'npkls_rinci.nonpdls', '=', 'npdls_heder.nonpdls')
                ->join('npkls_heder', 'npkls_heder.nonpk', '=', 'npkls_rinci.nonpk')
                ->whereBetween('npkls_heder.tglpindahbuku', [$awal, $akhir])
                ->with('npkrinci',function($header) use ($awal, $akhir) {
                    $header->select('npkls_rinci.nonpk','npkls_rinci.nonpdls')
                    ->join('npkls_heder', 'npkls_heder.nonpk', '=', 'npkls_rinci.nonpk')
                    ->whereBetween('npkls_heder.tglpindahbuku', [$awal, $akhir])
                    ->with('header',function($kd){
                        $kd->select('npkls_heder.tglpindahbuku','npkls_heder.nonpk');
                    });
                });
            });
        },'spjpanjar'=>function($head) use ($awal,$akhir){
            $head->select('spjpanjar_rinci.nospjpanjar',
                        'spjpanjar_rinci.koderek50',
                        'spjpanjar_rinci.rincianbelanja50',
                        'spjpanjar_rinci.sisapanjar',
                        'spjpanjar_rinci.jumlahbelanjapanjar')
            ->join('spjpanjar_heder','spjpanjar_heder.nospjpanjar', '=', 'spjpanjar_rinci.nospjpanjar')
            ->whereBetween('spjpanjar_heder.tglspjpanjar', [$awal, $akhir])
            ->with('spjheader', function($nota){
                $nota->select('spjpanjar_heder.nospjpanjar',
                            'spjpanjar_heder.notapanjar',
                            'spjpanjar_heder.tglspjpanjar',
                            'spjpanjar_heder.kegiatanblud',
                            'spjpanjar_heder.kodekegiatanblud',
                            'spjpanjar_heder.kegiatan')
                ->join('notapanjar_heder', 'notapanjar_heder.nonotapanjar', '=', 'spjpanjar_heder.notapanjar')
                ->when(request('bidang'),function($x) {
                    $x->where('notapanjar_heder.kodebidang', request('bidang'));
                })->when(request('kegiatan'),function($y) {
                    $y->where('notapanjar_heder.kodebidang', request('bidang'))
                    ->where('notapanjar_heder.kodekegiatanblud', request('kegiatan'));
                })->with('nota', function($kd){
                    $kd->select('notapanjar_heder.kodebidang',
                            'notapanjar_heder.kodekegiatanblud',
                            'notapanjar_heder.nonotapanjar');
                });
            });
        },'cp' => function($tgl) use ($awal, $akhir){
            $tgl->select('contrapost.nocontrapost',
                        'contrapost.tglcontrapost',
                        'contrapost.kodekegiatanblud',
                        'contrapost.kegiatanblud',
                        'contrapost.koderek50',
                        'contrapost.rincianbelanja',
                        'contrapost.nominalcontrapost')
            ->join('mappingpptkkegiatan', 'mappingpptkkegiatan.kodekegiatan', '=', 'contrapost.kodekegiatanblud')
            ->when(request('bidang'),function($x) {
                $x->where('mappingpptkkegiatan.kodebidang', request('bidang'));
            })->when(request('kegiatan'),function($y) {
                $y->where('mappingpptkkegiatan.kodebidang', request('bidang'))
                ->where('mappingpptkkegiatan.kodekegiatan', request('kegiatan'));
            })
            ->whereBetween('tglcontrapost', [$awal. ' 00:00:00', $akhir. ' 23:59:59'])
            ->with('mapbidang', function($select){
                $select->select('mappingpptkkegiatan.kodekegiatan',
                'mappingpptkkegiatan.kegiatan',
                'mappingpptkkegiatan.kodebidang',
                'mappingpptkkegiatan.bidang');
            });

        }])
        ->where('akun50_2024.akun', '5')
        ->groupBy('akun50_2024.kodeall2')
        ->get();
        $pendapatan = Akun50_2024::select('akun50_2024.kodeall2',
        'akun50_2024.uraian', 'akun50_2024.kodeall3'
        )->whereIn('akun50_2024.kodeall3', ['4',
                                        '4.1',
                                        '4.1.04',
                                        '4.1.04.16',
                                        '4.1.04.16.01',
                                        '4.1.04.16.01.0001'])
        ->get();
        // $nilaipendapatan = Anggaran_Pendapatan::where('tahun', $thn)->select('anggaran_pendapatan.nilai')
        // ->get();
        $nilaipendapatan = Tampung_pendapatan::where('tahun', $thn)->select('t_tampung_pendapatan.pagu as nilai', 't_tampung_pendapatan.notrans')
        ->get();
        $realisasipendapatan=TranskePPK::select('t_terima_ppk.nilai','t_terima_ppk.tgltrans')
        ->orderBy('tgltrans', 'asc')
        ->whereBetween('tgltrans', [$awal, $akhir])
        ->get();
        $kurangikaskecil=PengeluaranKas::where('kd_kas', 'K0002')
        ->select('pengeluarankhaskecil.nominal',
                'pengeluarankhaskecil.tanggalpengeluaran',
                'pengeluarankhaskecil.kd_kas')
        ->whereBetween('tanggalpengeluaran', [$awal. ' 00:00:00', $akhir. ' 23:59:59'])
        ->get();

        $pembiayaan = Akun50_2024::select('akun50_2024.kodeall2',
        'akun50_2024.uraian', 'akun50_2024.kodeall3'
        )->whereIn('akun50_2024.kodeall3', ['6',
                                        '6.1',
                                        '6.1.01',
                                        '6.1.01.08',
                                        '6.1.01.08.01',
                                        '6.1.01.08.01.0001'])
        ->get();

        $silpa = SisaAnggaran::where('tahun', $thn)->select('silpa.koderek50',
        'silpa.kode79',
        'silpa.tanggal',
        'silpa.nominal')->get();

        $pegawai = Mpegawaisimpeg::where('jabatan', 'J00001')
        ->where('aktif', 'AKTIF')
        ->select('pegawai.nip',
                'pegawai.nama')
        ->get();
        $lra = [
            'belanja' => $belanja,
            'pendapatan' => $pendapatan,
            'nilaipendapatan' => $nilaipendapatan,
            'kurangikaskecil' => $kurangikaskecil,
            'realisasipendapatan' => $realisasipendapatan,
            'pembiayaan' => $pembiayaan,
            'pa' => $pegawai,
            'silpa' => $silpa
        ];
        return new JsonResponse ($lra);
    }
    public function pendapatan(){
        $thn=Carbon::createFromFormat('Y-m-d', request('tgl'))->format('Y');
        // $awal=request('tgl', 'Y'.'-'.'01-01');
        $awal = $thn.'-01-01';
        $akhir=request('tglx', 'Y-m-d');
        $thnakhir =Carbon::createFromFormat('Y-m-d', request('tglx'))->format('Y');
        if($thn !== $thnakhir){
         return response()->json(['message' => 'Tahun Tidak Sama'], 500);
        }

        $pendapatan = Akun50_2024::select('akun50_2024.kodeall2',
        'akun50_2024.uraian', 'akun50_2024.kodeall3'
        )->whereIn('akun50_2024.kodeall3', ['4',
                                        '4.1',
                                        '4.1.04',
                                        '4.1.04.16',
                                        '4.1.04.16.01',
                                        '4.1.04.16.01.0001'])
        ->get();
        $nilai = Anggaran_Pendapatan::where('tahun', $thn)->select('anggaran_pendapatan.nilai')
        ->get();
        $lra = [
            'pendapatan' => $pendapatan,
            'nilai' => $nilai,
        ];

        return new JsonResponse ($lra);
    }
    public function coba(){
        $awal=request('tgl');
        $akhir=request('tglx');
        $thn= date('Y');
        // $pembiayaan = Akun50_2024::select('akun50_2024.kodeall2',
        // 'akun50_2024.uraian', 'akun50_2024.kodeall3'
        // )->whereIn('akun50_2024.kodeall3', ['6',
        //                                 '6.1',
        //                                 '6.1.01',
        //                                 '6.1.01.08',
        //                                 '6.1.01.08.01',
        //                                 '6.1.01.08.01.0001'])
        // ->get();

        // $pembiayaan = Akun50_2024::select('akun50_2024.uraian', 'akun50_2024.kodeall3'
        // )
        // ->addSelect(DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 1) as kode1'),
        //             DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 2) as kode2'),
        //             DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 3) as kode3'),
        //             DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 4) as kode4'),
        //             DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 5) as kode5'))
        // // ->leftJoin('akun50_2024 as wew', DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 2)'),'=','wew.kodeall3')
        // ->with('kodebiaya1',function($gg){
        //     $gg->select('akun50_2024.kodeall3','akun50_2024.uraian');
        //     })
        //     ->with('kodebiaya2',function($gg){
        //         $gg->select('akun50_2024.kodeall3','akun50_2024.uraian');
        //         })
        //         ->with('kodebiaya3',function($gg){
        //             $gg->select('akun50_2024.kodeall3','akun50_2024.uraian');
        //             })
        //             ->with('kodebiaya4',function($gg){
        //                 $gg->select('akun50_2024.kodeall3','akun50_2024.uraian');
        //                 })
        //                 ->with('kodebiaya5',function($gg){
        //                     $gg->select('akun50_2024.kodeall3','akun50_2024.uraian');
        //                     })
        // ->join('silpa','silpa.koderek50','=', 'akun50_2024.kodeall3')
        // ->where('tahun', $thn)
        // ->with('silpaanggaran', function($data) use ($thn){
        //     $data->where('tahun', $thn)->select('silpa.koderek50',
        //     'silpa.kode79',
        //     'silpa.tanggal',
        //     'silpa.tahun',
        //     'silpa.nominal');
        // })
        // ->get();

        $kurangikaskecil=PengeluaranKas::where('kd_kas', 'K0002')
        ->select('pengeluarankhaskecil.nominal',
                'pengeluarankhaskecil.tanggalpengeluaran',
                'pengeluarankhaskecil.kd_kas')
        ->whereBetween('tanggalpengeluaran', [$awal. ' 00:00:00', $akhir. ' 23:59:59'])
        ->get();
        // $pegawai = Mpegawaisimpeg::where('jabatan', 'J00001')
        // ->where('aktif', 'AKTIF')
        // ->select('pegawai.nip',
        //         'pegawai.nama')
        // ->get();

        // $kode = Akun50_2024::select('akun50_2024.kodeall2',
        // 'akun50_2024.uraian', 'akun50_2024.kodeall3')
        // ->join('t_tampung', 't_tampung.koderek50', '=', 'akun50_2024.kodeall2')
        // ->where('tgl', $thn)
        // ->when(request('bidang'),function($x) {
        //     $x->where('t_tampung.bidang', request('bidang'));
        // })->when(request('kegiatan'),function($y) {
        //     $y->where('t_tampung.bidang', request('bidang'))
        //     ->where('t_tampung.kodekegiatanblud', request('kegiatan'));
        // })
        // ->with('anggaran',function($tgl) use ($thn) {
        //     $tgl->where('tgl', $thn)
        //     ->select('t_tampung.koderek50',
        //             't_tampung.pagu',
        //             't_tampung.kodekegiatanblud',
        //             't_tampung.bidang',
        //             't_tampung.tgl')
        //     ->when(request('bidang'),function($x) {
        //         $x->where('t_tampung.bidang', request('bidang'));
        //     })->when(request('kegiatan'),function($y) {
        //         $y->where('t_tampung.bidang', request('bidang'))
        //         ->where('t_tampung.kodekegiatanblud', request('kegiatan'));
        //     });
        // })
        // ->join('npdls_rinci', 'npdls_rinci.koderek50', '=', 't_tampung.koderek50')
        // ->join('npdls_heder', 'npdls_heder.nonpdls', '=', 'npdls_rinci.nonpdls')
        // ->join('npkls_rinci', 'npkls_rinci.nonpdls', '=', 'npdls_heder.nonpdls')
        // ->join('npkls_heder', 'npkls_heder.nonpk', '=', 'npkls_rinci.nonpk')

        // ->with(['npdls_rinci' => function ($head) use ($awal, $akhir){
        //     $head->select('npdls_rinci.nonpdls',
        //                 'npdls_rinci.koderek50',
        //                 'npdls_rinci.rincianbelanja',
        //                 'npdls_rinci.nominalpembayaran')
        //                 // ->groupBy('npdls_rinci.koderek50')
        //     ->join('npdls_heder', 'npdls_heder.nonpdls', '=', 'npdls_rinci.nonpdls')
        //     ->join('npkls_rinci', 'npkls_rinci.nonpdls', '=', 'npdls_heder.nonpdls')
        //     ->join('npkls_heder', 'npkls_heder.nonpk', '=', 'npkls_rinci.nonpk')
        //     ->where('npdls_heder.nopencairan', '!=', '')
        //         ->when(request('bidang'),function($x) {
        //             $x->where('npdls_heder.kodebidang', request('bidang'));
        //         })->when(request('kegiatan'),function($y) {
        //             $y->where('npdls_heder.kodebidang', request('bidang'))
        //             ->where('npdls_heder.kodekegiatanblud', request('kegiatan'));
        //         })
        //         ->whereBetween('npkls_heder.tglpencairan', [$awal, $akhir])

        //     ->with('headerls',function($npk) use ($awal, $akhir) {
        //         $npk->select('npdls_heder.nonpdls',
        //                     'npdls_heder.tglnpdls',
        //                     'npdls_heder.nopencairan',
        //                     'npdls_heder.nonpk',
        //                     'npdls_heder.kodekegiatanblud',
        //                     'npdls_heder.kodebidang')
        //         ->join('npkls_rinci', 'npkls_rinci.nonpdls', '=', 'npdls_heder.nonpdls')
        //         ->join('npkls_heder', 'npkls_heder.nonpk', '=', 'npkls_rinci.nonpk')
        //         ->whereBetween('npkls_heder.tglpencairan', [$awal, $akhir])
        //         ->with('npkrinci',function($header) use ($awal, $akhir) {
        //             $header->select('npkls_rinci.nonpk','npkls_rinci.nonpdls')
        //             ->join('npkls_heder', 'npkls_heder.nonpk', '=', 'npkls_rinci.nonpk')
        //             ->whereBetween('npkls_heder.tglpencairan', [$awal, $akhir])
        //             ->with('header',function($kd){
        //                 $kd->select('npkls_heder.tglpencairan','npkls_heder.nonpk');
        //             });
        //         });
        //     });
        // },'spjpanjar'=>function($head) use ($awal,$akhir){
        //     $head->select('spjpanjar_rinci.nospjpanjar',
        //                 'spjpanjar_rinci.koderek50',
        //                 'spjpanjar_rinci.rincianbelanja50',
        //                 'spjpanjar_rinci.sisapanjar',
        //                 'spjpanjar_rinci.jumlahbelanjapanjar')
        //     ->join('spjpanjar_heder','spjpanjar_heder.nospjpanjar', '=', 'spjpanjar_rinci.nospjpanjar')
        //     ->whereBetween('spjpanjar_heder.tglspjpanjar', [$awal, $akhir])
        //     ->with('spjheader', function($nota){
        //         $nota->select('spjpanjar_heder.nospjpanjar',
        //                     'spjpanjar_heder.notapanjar',
        //                     'spjpanjar_heder.tglspjpanjar',
        //                     'spjpanjar_heder.kegiatanblud',
        //                     'spjpanjar_heder.kodekegiatanblud',
        //                     'spjpanjar_heder.kegiatan')
        //         ->join('notapanjar_heder', 'notapanjar_heder.nonotapanjar', '=', 'spjpanjar_heder.notapanjar')
        //         ->when(request('bidang'),function($x) {
        //             $x->where('notapanjar_heder.kodebidang', request('bidang'));
        //         })->when(request('kegiatan'),function($y) {
        //             $y->where('notapanjar_heder.kodebidang', request('bidang'))
        //             ->where('notapanjar_heder.kodekegiatanblud', request('kegiatan'));
        //         })->with('nota', function($kd){
        //             $kd->select('notapanjar_heder.kodebidang',
        //                     'notapanjar_heder.kodekegiatanblud',
        //                     'notapanjar_heder.nonotapanjar');
        //         });
        //     });
        // },'cp' => function($tgl) use ($awal, $akhir){
        //     $tgl->select('contrapost.nocontrapost',
        //                 'contrapost.tglcontrapost',
        //                 'contrapost.kodekegiatanblud',
        //                 'contrapost.kegiatanblud',
        //                 'contrapost.koderek50',
        //                 'contrapost.rincianbelanja',
        //                 'contrapost.nominalcontrapost')
        //     ->join('mappingpptkkegiatan', 'mappingpptkkegiatan.kodekegiatan', '=', 'contrapost.kodekegiatanblud')
        //     ->when(request('bidang'),function($x) {
        //         $x->where('mappingpptkkegiatan.kodebidang', request('bidang'));
        //     })->when(request('kegiatan'),function($y) {
        //         $y->where('mappingpptkkegiatan.kodebidang', request('bidang'))
        //         ->where('mappingpptkkegiatan.kodekegiatan', request('kegiatan'));
        //     })
        //     ->whereBetween('tglcontrapost', [$awal. ' 00:00:00', $akhir. ' 23:59:59'])
        //     ->with('mapbidang', function($select){
        //         $select->select('mappingpptkkegiatan.kodekegiatan',
        //         'mappingpptkkegiatan.kegiatan',
        //         'mappingpptkkegiatan.kodebidang',
        //         'mappingpptkkegiatan.bidang');
        //     });

        // }])
        // ->addSelect(DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 1) as kode1'),
        //             DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 2) as kode2'),
        //             DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 3) as kode3'),
        //             DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 4) as kode4'),
        //             DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 5) as kode5'))
        // // ->leftJoin('akun50_2024 as wew', DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 2)'),'=','wew.kodeall3')
        // ->with('kode1',function($gg){
        //     $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
        //     })
        //     ->with('kode2',function($gg){
        //         $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
        //         })
        //         ->with('kode3',function($gg){
        //             $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
        //             })
        //             ->with('kode4',function($gg){
        //                 $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
        //                 })
        //                 ->with('kode5',function($gg){
        //                     $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
        //                     })
        // ->where('akun50_2024.akun', '5')
        // ->groupBy('akun50_2024.kodeall2')
        // ->get();

        return new JsonResponse ($kurangikaskecil);

    }
}
