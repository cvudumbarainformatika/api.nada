<?php

namespace App\Http\Controllers\Api\Siasik\Akuntansi\Laporan;

use App\Http\Controllers\Controller;
use App\Models\Siasik\Akuntansi\Jurnal\Create_JurnalPosting;
use App\Models\Siasik\Akuntansi\Jurnal\JurnalUmum_Header;
use App\Models\Siasik\Anggaran\PergeseranPaguRinci;
use App\Models\Siasik\Anggaran\Tampung_pendapatan;
use App\Models\Siasik\Master\Akun50_2024;
use App\Models\Siasik\TransaksiSilpa\SisaAnggaran;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LRAjurnalController extends Controller
{
    public function get_lra () {
        $thnpagu=date('Y');
        $thn=Carbon::createFromFormat('Y-m-d', request('tgl'))->format('Y');
        $awal=request('tgl', 'Y-m-d');
        $akhir=request('tglx', 'Y-m-d');
        $sebelum = Carbon::createFromFormat('Y-m-d', $awal)->subDay();
        $thnakhir=Carbon::createFromFormat('Y-m-d', request('tglx'))->format('Y');
        if($thn !== $thnakhir){
         return response()->json(['message' => 'Tahun Tidak Sama'], 500);
        }

        $pagupendapatan = Tampung_pendapatan::where('tahun', $thn)
        ->select('t_tampung_pendapatan.koderekeningblud',
                'akun50_2024.kodeall3 as kode6',
                'akun50_2024.uraian',
                DB::raw('sum(t_tampung_pendapatan.pagu) as pagupendapatan'),
                DB::raw('SUBSTRING_INDEX(t_tampung_pendapatan.koderekeningblud, ".", 1) as kode1'),
                DB::raw('SUBSTRING_INDEX(t_tampung_pendapatan.koderekeningblud, ".", 2) as kode2'),
                DB::raw('SUBSTRING_INDEX(t_tampung_pendapatan.koderekeningblud, ".", 3) as kode3'),
                DB::raw('SUBSTRING_INDEX(t_tampung_pendapatan.koderekeningblud, ".", 4) as kode4'),
                DB::raw('SUBSTRING_INDEX(t_tampung_pendapatan.koderekeningblud, ".", 5) as kode5'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(t_tampung_pendapatan.koderekeningblud, ".", 1) LIMIT 1) as uraian1'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(t_tampung_pendapatan.koderekeningblud, ".", 2) LIMIT 1) as uraian2'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(t_tampung_pendapatan.koderekeningblud, ".", 3) LIMIT 1) as uraian3'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(t_tampung_pendapatan.koderekeningblud, ".", 4) LIMIT 1) as uraian4'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(t_tampung_pendapatan.koderekeningblud, ".", 5) LIMIT 1) as uraian5'),
                )
        ->join('akun50_2024', 'akun50_2024.kodeall3', 't_tampung_pendapatan.koderekeningblud')
        ->groupBy('t_tampung_pendapatan.koderekeningblud')
        ->get();

        $datareklas = JurnalUmum_Header::where('jurnalumum_heder.verif', '=', '1')
        ->whereBetween('jurnalumum_heder.tanggal', [$awal, $akhir])
        ->join('jurnalumum_rinci', 'jurnalumum_rinci.nobukti', 'jurnalumum_heder.nobukti')
        ->select('jurnalumum_heder.tanggal',
                'jurnalumum_heder.nobukti',
                'jurnalumum_rinci.nobukti',
                'jurnalumum_rinci.kodepsap13 as kode6',
                'jurnalumum_rinci.uraianpsap13 as uraian',
                DB::raw('sum(jurnalumum_rinci.kredit-jurnalumum_rinci.debet) as totalpenyesuaian'),
                DB::raw('SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 1) as kode1'),
                DB::raw('SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 2) as kode2'),
                DB::raw('SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 3) as kode3'),
                DB::raw('SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 4) as kode4'),
                DB::raw('SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 5) as kode5'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 1) LIMIT 1) as uraian1'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 2) LIMIT 1) as uraian2'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 3) LIMIT 1) as uraian3'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 4) LIMIT 1) as uraian4'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 5) LIMIT 1) as uraian5'))
        ->join('akun50_2024', 'akun50_2024.kodeall3', 'jurnalumum_rinci.kodepsap13')
        ->groupBy( 'jurnalumum_rinci.kodepsap13')
        ->get();

         $datareklas_sblm = JurnalUmum_Header::where('jurnalumum_heder.verif', '=', '1')
        ->whereBetween('jurnalumum_heder.tanggal', [$thn.'-01-01', $sebelum])
        ->join('jurnalumum_rinci', 'jurnalumum_rinci.nobukti', 'jurnalumum_heder.nobukti')
        ->select('jurnalumum_heder.tanggal',
                'jurnalumum_heder.nobukti',
                'jurnalumum_rinci.nobukti',
                'jurnalumum_rinci.kodepsap13 as kode6',
                'jurnalumum_rinci.uraianpsap13 as uraian',
                DB::raw('sum(jurnalumum_rinci.kredit-jurnalumum_rinci.debet) as totalpenyesuaian'),
                DB::raw('SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 1) as kode1'),
                DB::raw('SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 2) as kode2'),
                DB::raw('SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 3) as kode3'),
                DB::raw('SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 4) as kode4'),
                DB::raw('SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 5) as kode5'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 1) LIMIT 1) as uraian1'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 2) LIMIT 1) as uraian2'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 3) LIMIT 1) as uraian3'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 4) LIMIT 1) as uraian4'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 5) LIMIT 1) as uraian5'))
        ->join('akun50_2024', 'akun50_2024.kodeall3', 'jurnalumum_rinci.kodepsap13')
        ->groupBy( 'jurnalumum_rinci.kodepsap13')
        ->get();

        $pendapatan = Create_JurnalPosting::select(
            'jurnal_postingotom.tanggal',
            'jurnal_postingotom.kode as kode6',
            'jurnal_postingotom.uraian',
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 1) as kode1'),
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 2) as kode2'),
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 3) as kode3'),
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 4) as kode4'),
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 5) as kode5'),
            DB::raw('sum(jurnal_postingotom.kredit-jurnal_postingotom.debit) as subtotal'))
        ->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
        ->where('jurnal_postingotom.kode', 'LIKE', '4.' . '%')
        ->where('jurnal_postingotom.verif', '=', '1')
        ->groupBy( 'kode6')
        ->orderBy('kode6', 'asc')
        ->get();

        $pendapatansblm = Create_JurnalPosting::select(
            'jurnal_postingotom.tanggal',
            'jurnal_postingotom.kode as kode6',
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 1) as kode1'),
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 2) as kode2'),
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 3) as kode3'),
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 4) as kode4'),
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 5) as kode5'),
            DB::raw('sum(jurnal_postingotom.kredit-jurnal_postingotom.debit) as pendpsebelumnya')
            )
        ->where('jurnal_postingotom.kode', 'LIKE', '4.' . '%')
        ->where('jurnal_postingotom.verif', '=', '1')
        ->whereBetween('tanggal', [$thn.'-01-01', $sebelum])
        ->groupBy( 'kode6')
        ->orderBy('kode6', 'asc')
        ->get();

        $pagu = PergeseranPaguRinci::select(
            't_tampung.koderek50',
            't_tampung.uraian50 as uraian',
            't_tampung.tgl as tanggal',
            'akun50_2024.kodeall3 as kode6',
            DB::raw('sum(t_tampung.pagu) as pagu'),
            DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 1) as kode1'),
            DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 2) as kode2'),
            DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 3) as kode3'),
            DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 4) as kode4'),
            DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 5) as kode5'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(kode1, ".", 1) LIMIT 1) as uraian1'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(kode2, ".", 2) LIMIT 1) as uraian2'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(kode3, ".", 3) LIMIT 1) as uraian3'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(kode4, ".", 4) LIMIT 1) as uraian4'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(kode5, ".", 5) LIMIT 1) as uraian5'))
        ->join('akun50_2024', 'akun50_2024.kodeall2', 't_tampung.koderek50')
        ->where('t_tampung.pagu', '!=', 0)
        ->where('t_tampung.tgl',  $thn)
        ->groupBy('t_tampung.koderek50')
        ->get();

        $belanja = Create_JurnalPosting::select(
            'jurnal_postingotom.tanggal',
            'jurnal_postingotom.kode as kode6',
            'jurnal_postingotom.uraian',
            DB::raw('sum(jurnal_postingotom.debit-jurnal_postingotom.kredit) as subtotalx'),
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 1) as kode1'),
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 2) as kode2'),
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 3) as kode3'),
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 4) as kode4'),
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 5) as kode5')
            )
        ->where('jurnal_postingotom.verif', '=', '1')
        ->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
        ->where('jurnal_postingotom.kode', 'LIKE', '5.' . '%')
        ->groupBy( 'kode6')
        ->orderBy('kode6', 'asc')
        ->get();

        $belanjasblm = Create_JurnalPosting::select(
            'jurnal_postingotom.tanggal',
            'jurnal_postingotom.kode as kode6',
            'jurnal_postingotom.uraian',
            DB::raw('sum(jurnal_postingotom.debit-jurnal_postingotom.kredit) as nilaisebelumnya'),
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 1) as kode1'),
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 2) as kode2'),
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 3) as kode3'),
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 4) as kode4'),
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 5) as kode5')
            )
        ->where('jurnal_postingotom.verif', '=', '1')
        ->whereBetween('jurnal_postingotom.tanggal', [$thn.'-01-01', $sebelum])
        ->where('jurnal_postingotom.kode', 'LIKE', '5.' . '%')
        ->groupBy( 'kode6')
        ->orderBy('kode6', 'asc')
        ->get();

        $silpapagu = SisaAnggaran::where('tahun', $thn)
        ->select('silpa.koderek50 as kode6',
                'silpa.uraian50 as uraian',
                'silpa.tanggal',
                DB::raw('sum(silpa.nominal) as pagu'),
                DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 1) as kode1'),
                DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 2) as kode2'),
                DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 3) as kode3'),
                DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 4) as kode4'),
                DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 5) as kode5'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(kode1, ".", 1) LIMIT 1) as uraian1'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(kode2, ".", 2) LIMIT 1) as uraian2'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(kode3, ".", 3) LIMIT 1) as uraian3'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(kode4, ".", 4) LIMIT 1) as uraian4'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(kode5, ".", 5) LIMIT 1) as uraian5'))
        ->join('akun50_2024', 'akun50_2024.kodeall3', 'silpa.koderek50')
        ->groupBy('silpa.koderek50')
        ->get();

        $silpasblm = SisaAnggaran::where('tahun', $thn)
        ->select('silpa.koderek50 as kode6',
                'silpa.uraian50 as uraian',
                'silpa.tanggal',
                DB::raw('sum(silpa.nominal) as nilaisblm'))
        ->whereBetween('tanggal', [$thn.'-01-01', $sebelum])
        ->groupBy('silpa.koderek50')
        ->get();

        $silpaskg = SisaAnggaran::where('tahun', $thn)
        ->select('silpa.koderek50 as kode6',
                'silpa.uraian50 as uraian',
                'silpa.tanggal',
                DB::raw('sum(silpa.nominal) as nilaiskg'))
        ->whereBetween('silpa.tanggal', [$awal, $akhir])
        ->groupBy('silpa.koderek50')
        ->get();


        // $psappagupendapatan = Tampung_pendapatan::where('tahun', $thn)
        // ->select('t_tampung_pendapatan.koderekeningblud as kode',
        // 't_tampung_pendapatan.pagu')
        // ->where('t_tampung_pendapatan.koderekeningblud', 'LIKE', '4.1.04.16' . '%')
        // ->get();

        // $psaprealisasipendapatan = Create_JurnalPosting::select(
        //     'jurnal_postingotom.tanggal', 'jurnal_postingotom.kode', 'jurnal_postingotom.kode as kode6',
        //     DB::raw('sum(jurnal_postingotom.kredit-jurnal_postingotom.debit) as realisasi')
        // )
        // ->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
        // ->where('jurnal_postingotom.verif', '=', '1')
        // ->where('jurnal_postingotom.kode', 'LIKE', '4.1.04.16' . '%')
        // ->with('penyesuaian',  function($sel) use ($awal,$akhir){
        //     $sel->join('jurnalumum_heder', 'jurnalumum_heder.nobukti', 'jurnalumum_rinci.nobukti')
        //     ->select('jurnalumum_rinci.kodepsap13',
        //             'jurnalumum_heder.tanggal',
        //             DB::raw('sum(jurnalumum_rinci.kredit-jurnalumum_rinci.debet) as totalpenyesuaian'))
        //     ->where('jurnalumum_heder.verif', '=', '1')
        //     ->whereBetween('jurnalumum_heder.tanggal', [$awal, $akhir])

        //     ->where('jurnalumum_rinci.kodepsap13', 'LIKE', '4.' . '%')
        //     ->where('jurnalumum_heder.keterangan', 'NOT LIKE','Reklas Pendapatan' . '%')
        //     ->groupBy( 'kodepsap13');
        // })
        // ->get();

        // $psaprealisasipendapatanx = JurnalUmum_Header::select(
        //     'jurnalumum_heder.nobukti',
        //     'jurnalumum_heder.tanggal',
        //     'jurnalumum_heder.keterangan',
        //     'jurnalumum_rinci.kodepsap13 as kode',
        //     'jurnalumum_rinci.uraianpsap13 as uraian',
        //     'jurnalumum_rinci.kredit as realisasix',
        // )
        // ->where('jurnalumum_heder.keterangan', 'LIKE', 'Reklas Pendapatan' . '%')
        // ->whereBetween('jurnalumum_heder.tanggal', [$awal, $akhir])
        // ->leftJoin('jurnalumum_rinci', function($join)  {
        //     $join->on('jurnalumum_rinci.nobukti', '=', 'jurnalumum_heder.nobukti')
        //     ->where('jurnalumum_rinci.kodepsap13', '!=', '4.1.04.16.02.0001')
        //     ;
        //   })
        // ->get();


        // $psappagubarjas = Akun50_2024::select('akun50_2024.kodeall2',
        // 'akun50_2024.uraian', 'akun50_2024.kodeall3',
        // DB::raw('ifnull(sum(t_tampung.pagu), 0) as pagu'),
        // // DB::raw('sum(jurnal_postingotom.debit-jurnal_postingotom.kredit) as realisasi')
        // )->addSelect(DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 1) as kode1'),
        //             DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 2) as kode2'),
        //             DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 3) as kode3'),
        //             DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 6) as kode'),
        //             )
        //             ->with('kode1',function($gg){
        //                 $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
        //                 })
        //                 ->with('kode2',function($gg){
        //                     $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
        //                     })
        //                     ->with('kode3',function($gg){
        //                         $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
        //                         })
        // // ->crossJoin('t_tampung', 't_tampung.koderek50', '=', 'akun50_2024.kodeall2')
        // ->leftJoin('t_tampung', function($join) use ($thn) {
        //     $join->on('t_tampung.koderek50', '=', 'akun50_2024.kodeall2')
        //     ->where('t_tampung.tgl', $thn);
        //   })
        // // ->leftJoin('jurnal_postingotom', function($join) use ($awal, $akhir) {
        // //     $join->on('jurnal_postingotom.kode', '=', 'akun50_2024.kodeall3')
        // //     ->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
        // //     ->where('jurnal_postingotom.kode', 'LIKE', '5.' . '%');
        // //     })
        // ->where('akun50_2024.kodeall3', 'LIKE', '5.1.' . '%')
        // ->groupBy('kode3')
        // ->orderBy('kode', 'asc')
        // ->get();

        // $psaprealisasibarjas = Akun50_2024::select('akun50_2024.kodeall2',
        // 'akun50_2024.uraian', 'akun50_2024.kodeall3',
        // // DB::raw('sum(t_tampung.pagu) as pagu'),
        // DB::raw('ifnull(sum(jurnal_postingotom.debit-jurnal_postingotom.kredit),0) as realisasi')
        // )->addSelect(DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 1) as kode1'),
        //             DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 2) as kode2'),
        //             DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 3) as kode3'),
        //             DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 6) as kode'),
        //             )
        //             ->with('kode1',function($gg){
        //                 $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
        //                 })
        //                 ->with('kode2',function($gg){
        //                     $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
        //                     })
        //                     ->with('kode3',function($gg){
        //                         $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
        //                         })
        // // ->crossJoin('t_tampung', 't_tampung.koderek50', '=', 'akun50_2024.kodeall2')
        // // ->leftJoin('t_tampung', function($join) use ($thn) {
        // //     $join->on('t_tampung.koderek50', '=', 'akun50_2024.kodeall2')
        // //     ->where('t_tampung.tgl', $thn);
        // //   })
        // ->leftJoin('jurnal_postingotom', function($join) use ($awal, $akhir) {
        //     $join->on('jurnal_postingotom.kode', '=', 'akun50_2024.kodeall3')
        //     ->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
        //     ->where('jurnal_postingotom.kode', 'LIKE', '5.' . '%')
        //     ;
        //     })
        // ->where('akun50_2024.kodeall3', 'LIKE', '5.1.' . '%')
        // ->groupBy('kode3')
        // ->orderBy('kode', 'asc')
        // ->get();

        // $psappagumodal = Akun50_2024::select('akun50_2024.kodeall2',
        // 'akun50_2024.uraian', 'akun50_2024.kodeall3',
        // DB::raw('ifnull(sum(t_tampung.pagu), 0) as pagu'),
        // // DB::raw('sum(jurnal_postingotom.debit-jurnal_postingotom.kredit) as realisasi')
        // )->addSelect(DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 1) as kode1'),
        //             DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 2) as kode2'),
        //             DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 3) as kode3'),
        //             DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 6) as kode'))
        //             ->with('kode1',function($gg){
        //                 $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
        //                 })
        //                 ->with('kode2',function($gg){
        //                     $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
        //                     })
        //                     ->with('kode3',function($gg){
        //                         $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
        //                         })
        // // ->crossJoin('t_tampung', 't_tampung.koderek50', '=', 'akun50_2024.kodeall2')
        // ->leftJoin('t_tampung', function($join) use ($thn) {
        //     $join->on('t_tampung.koderek50', '=', 'akun50_2024.kodeall2')
        //     ->where('t_tampung.tgl', $thn);
        //   })
        // // ->leftJoin('jurnal_postingotom', function($join) use ($awal, $akhir) {
        // //     $join->on('jurnal_postingotom.kode', '=', 'akun50_2024.kodeall3')
        // //     ->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
        // //     ->where('jurnal_postingotom.kode', 'LIKE', '5.' . '%');
        // //     })
        // ->where('akun50_2024.kodeall3', 'LIKE', '5.2.' . '%')
        // ->groupBy('kode3')
        // ->orderBy('kode', 'asc')
        // ->get();

        // $psaprealisasimodal = Akun50_2024::select('akun50_2024.kodeall2',
        // 'akun50_2024.uraian', 'akun50_2024.kodeall3',
        // // DB::raw('sum(t_tampung.pagu) as pagu'),
        // DB::raw('ifnull(sum(jurnal_postingotom.debit-jurnal_postingotom.kredit), 0) as realisasi')
        // )->addSelect(DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 1) as kode1'),
        //             DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 2) as kode2'),
        //             DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 3) as kode3'),
        //             DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 6) as kode'),
        //             )
        //             ->with('kode1',function($gg){
        //                 $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
        //                 })
        //                 ->with('kode2',function($gg){
        //                     $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
        //                     })
        //                     ->with('kode3',function($gg){
        //                         $gg->select('akun50_2024.kodeall2','akun50_2024.uraian');
        //                         })
        // // ->crossJoin('t_tampung', 't_tampung.koderek50', '=', 'akun50_2024.kodeall2')
        // // ->leftJoin('t_tampung', function($join) use ($thn) {
        // //     $join->on('t_tampung.koderek50', '=', 'akun50_2024.kodeall2')
        // //     ->where('t_tampung.tgl', $thn);
        // //   })
        // ->leftJoin('jurnal_postingotom', function($join) use ($awal, $akhir) {
        //     $join->on('jurnal_postingotom.kode', '=', 'akun50_2024.kodeall3')
        //     ->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
        //     ->where('jurnal_postingotom.kode', 'LIKE', '5.' . '%')
        //     ;
        //     })
        // ->where('akun50_2024.kodeall3', 'LIKE', '5.2.' . '%')
        // ->groupBy('kode3')
        // ->orderBy('kode', 'asc')
        // ->get();

    $data = [
        'pagu' => $pagu,
        'pagupendapatan' => $pagupendapatan,
        'pendapatan' => $pendapatan,
        'pendapatansblm' => $pendapatansblm,
        'belanja' => $belanja,
        'belanjasblm' => $belanjasblm,
        'pagusilpa' => $silpapagu,
        'silpasblm' => $silpasblm,
        'silpaskg' => $silpaskg,

        // 'psappagupendapatan' => $psappagupendapatan,
        // 'psaprealisasipendapatan' => $psaprealisasipendapatan,
        // 'psaprealisasipendapatanx' => $psaprealisasipendapatanx,
        // 'psappagubarjas' => $psappagubarjas,
        // 'psaprealisasibarjas' => $psaprealisasibarjas,
        // 'psappagumodal' => $psappagumodal,
        // 'psaprealisasimodal' => $psaprealisasimodal,

        //pengganti penyesuaian -> filter di front berdasarkan kode rek karena header bisa saja tidak ada rekeningnya
        'datareklas'=> $datareklas,
        'datareklas_sblm'=> $datareklas_sblm,
    ];
    return new JsonResponse ($data);
    }
}
