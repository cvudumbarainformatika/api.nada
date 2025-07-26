<?php

namespace App\Http\Controllers\Api\Siasik\Akuntansi\Laporan;

use App\Http\Controllers\Controller;
use App\Models\Siasik\Akuntansi\Jurnal\Create_JurnalPosting;
use App\Models\Siasik\Akuntansi\Jurnal\JurnalUmum_Header;
use App\Models\Siasik\Anggaran\Tampung_pendapatan;
use App\Models\Siasik\Master\Akun50_2024;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LapOperasionalController extends Controller
{
    private function getKoderekeningPendapatan()
    {
        return "CONCAT('7', SUBSTRING(t_tampung_pendapatan.koderekeningblud, 2))";
    }
    public function get_lo(){
        $thn=Carbon::createFromFormat('Y-m-d', request('tgl'))->format('Y');
        // $awal=request('tgl', 'Y'.'-'.'01-01');
        $awal=request('tgl', 'Y-m-d');
        $akhir=request('tglx', 'Y-m-d');
        $sebelum = date( 'Y-m-d', strtotime( $awal . ' -1 day' ) );
        $thnakhir=Carbon::createFromFormat('Y-m-d', request('tglx'))->format('Y');
        if($thn !== $thnakhir){
         return response()->json(['message' => 'Tahun Tidak Sama'], 500);
        }
        $kodeRekening = $this->getKoderekeningPendapatan();
        $pagupendapatan = Tampung_pendapatan::where('tahun', $thn)
        ->select(
                'akun50_2024.kodeall3 as kode6',
                'akun50_2024.uraian',
                DB::raw("{$kodeRekening} as koderekeningblud"),
                DB::raw('sum(t_tampung_pendapatan.pagu) as pagupendapatan'),
                DB::raw("SUBSTRING_INDEX({$kodeRekening}, '.', 1) as kode1"),
                DB::raw("SUBSTRING_INDEX({$kodeRekening}, '.', 2) as kode2"),
                DB::raw("SUBSTRING_INDEX({$kodeRekening}, '.', 3) as kode3"),
                DB::raw("SUBSTRING_INDEX({$kodeRekening}, '.', 4) as kode4"),
                DB::raw("SUBSTRING_INDEX({$kodeRekening}, '.', 5) as kode5"),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(kode1, ".", 1) LIMIT 1) as uraian1'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(kode2, ".", 2) LIMIT 1) as uraian2'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(kode3, ".", 3) LIMIT 1) as uraian3'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(kode4, ".", 4) LIMIT 1) as uraian4'),
                DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(kode5, ".", 5) LIMIT 1) as uraian5'),
                )
        ->join('akun50_2024', 'akun50_2024.kodeall3', '=', DB::raw("CONCAT('7', SUBSTRING(t_tampung_pendapatan.koderekeningblud, 2))"))
        ->groupBy('t_tampung_pendapatan.koderekeningblud')
        ->get();

         $pendapatan = Create_JurnalPosting::join('akun50_2024', 'akun50_2024.kodeall3', 'jurnal_postingotom.kode')
         ->select(
            'jurnal_postingotom.tanggal',
            'jurnal_postingotom.kode as kode6',
            'jurnal_postingotom.uraian',
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 1) as kode1'),
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 2) as kode2'),
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 3) as kode3'),
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 4) as kode4'),
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 5) as kode5'),
            DB::raw('sum(jurnal_postingotom.kredit-jurnal_postingotom.debit) as subtotal'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(kode1, ".", 1) LIMIT 1) as uraian1'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(kode2, ".", 2) LIMIT 1) as uraian2'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(kode3, ".", 3) LIMIT 1) as uraian3'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(kode4, ".", 4) LIMIT 1) as uraian4'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(kode5, ".", 5) LIMIT 1) as uraian5'))
        ->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
        ->where('jurnal_postingotom.kode', 'LIKE', '7.' . '%')
        ->where('jurnal_postingotom.verif', '=', '1')
        ->groupBy( 'kode6')
        ->orderBy('kode6', 'asc')
        ->get();

        $penyesuaianpendapatan = JurnalUmum_Header::where('jurnalumum_heder.verif', '=', '1')
        ->whereBetween('jurnalumum_heder.tanggal', [$awal, $akhir])
        ->join('jurnalumum_rinci', 'jurnalumum_rinci.nobukti', 'jurnalumum_heder.nobukti')
        ->where('jurnalumum_rinci.kodepsap13', 'LIKE', '7.' . '%')
        ->select('jurnalumum_heder.tanggal',
                'jurnalumum_heder.nobukti',
                'jurnalumum_rinci.nobukti',
                'jurnalumum_rinci.kodepsap13 as kode6',
                'jurnalumum_rinci.uraianpsap13 as uraian',
                DB::raw('sum(jurnalumum_rinci.kredit-jurnalumum_rinci.debet) as subtotal'),
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


        $bebanotom = Create_JurnalPosting::join('akun50_2024', 'akun50_2024.kodeall3', 'jurnal_postingotom.kode')
        ->select(
            'jurnal_postingotom.tanggal',
            'jurnal_postingotom.kode as kode6',
            'jurnal_postingotom.uraian',
            DB::raw('sum(jurnal_postingotom.debit-jurnal_postingotom.kredit) as subtotal'),
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 1) as kode1'),
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 2) as kode2'),
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 3) as kode3'),
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 4) as kode4'),
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 5) as kode5'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(kode1, ".", 1) LIMIT 1) as uraian1'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(kode2, ".", 2) LIMIT 1) as uraian2'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(kode3, ".", 3) LIMIT 1) as uraian3'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(kode4, ".", 4) LIMIT 1) as uraian4'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(kode5, ".", 5) LIMIT 1) as uraian5')
            )
        ->where('jurnal_postingotom.verif', '=', '1')
        ->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
        ->where('jurnal_postingotom.kode', 'LIKE', '8.' . '%')
        ->groupBy( 'kode6')
        ->orderBy('kode6', 'asc')
        ->get();


        $penyesuaianbeban = JurnalUmum_Header::where('jurnalumum_heder.verif', '=', '1')
        ->whereBetween('jurnalumum_heder.tanggal', [$awal, $akhir])
        ->join('jurnalumum_rinci', 'jurnalumum_rinci.nobukti', 'jurnalumum_heder.nobukti')
        ->where('jurnalumum_rinci.kodepsap13', 'LIKE', '8.' . '%')
        ->select('jurnalumum_heder.tanggal',
                'jurnalumum_heder.nobukti',
                'jurnalumum_rinci.nobukti',
                'jurnalumum_rinci.kodepsap13 as kode6',
                'jurnalumum_rinci.uraianpsap13 as uraian',
                DB::raw('sum(jurnalumum_rinci.debet-jurnalumum_rinci.kredit) as subtotal'),
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



        // $psaprealisasipendapatan = Create_JurnalPosting::select(
        //     'jurnal_postingotom.tanggal', 'jurnal_postingotom.kode',
        //     DB::raw('sum(jurnal_postingotom.kredit-jurnal_postingotom.debit) as realisasi')
        // )
        // ->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
        // ->where('jurnal_postingotom.verif', '=', '1')
        // ->where('jurnal_postingotom.kode', 'LIKE', '4.1.04.16' . '%')
        // ->get();

        // $psappendpatanhibah = JurnalUmum_Header::select(
        //     'jurnalumum_rinci.uraianpsap13 as uraian',
        //     'jurnalumum_rinci.kredit as realisasix',
        //     'jurnalumum_heder.keterangan',
        //     'jurnalumum_rinci.kodepsap13 as kode',
        // )
        // // ->where('jurnalumum_heder.keterangan', 'NOT LIKE', 'Reklas Pendapatan' . '%')
        // // ->whereNotIn('jurnalumum_heder.keterangan', ['Reklas Pendapatan'])
        // ->whereNotNull('jurnalumum_rinci.kredit')
        // ->where('jurnalumum_heder.verif', '=', '1')
        // ->whereBetween('jurnalumum_heder.tanggal', [$awal, $akhir])
        // ->leftJoin('jurnalumum_rinci', function($join)  {
        //     $join->on('jurnalumum_rinci.nobukti', '=', 'jurnalumum_heder.nobukti')
        //     ->where('jurnalumum_rinci.kodepsap13', 'LIKE', '7.3.01' . '%');
        //   })
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
        // ->where('jurnalumum_heder.verif', '=', '1')
        // ->whereBetween('jurnalumum_heder.tanggal', [$awal, $akhir])
        // ->leftJoin('jurnalumum_rinci', function($join)  {
        //     $join->on('jurnalumum_rinci.nobukti', '=', 'jurnalumum_heder.nobukti')
        //     ->where('jurnalumum_rinci.kodepsap13', '!=', '4.1.04.16.02.0001')
        //     ;
        //   })
        // ->get();

        // $psappenyesuaianpendp = JurnalUmum_Header::select(
        //     'jurnalumum_rinci.uraianpsap13 as uraian',
        //     'jurnalumum_rinci.debet as nilaix',
        //     'jurnalumum_heder.keterangan',
        //     'jurnalumum_rinci.kodepsap13',
        // )
        // ->where('jurnalumum_heder.keterangan', 'NOT LIKE', 'Reklas Pendapatan' . '%')
        // // ->whereNotIn('jurnalumum_heder.keterangan', ['Reklas Pendapatan'])
        // ->whereNotNull('jurnalumum_rinci.debet')
        // ->where('jurnalumum_heder.verif', '=', '1')
        // ->whereBetween('jurnalumum_heder.tanggal', [$awal, $akhir])
        // ->leftJoin('jurnalumum_rinci', function($join)  {
        //     $join->on('jurnalumum_rinci.nobukti', '=', 'jurnalumum_heder.nobukti')
        //     ->where('jurnalumum_rinci.kodepsap13', '=', '4.1.04.16.02.0001');
        //   })
        // ->get();



        // $psapbebanpegawai = Akun50_2024::select('akun50_2024.kodeall2',
        // 'akun50_2024.uraian', 'akun50_2024.kodeall3',
        // // DB::raw('sum(t_tampung.pagu) as pagu'),
        // DB::raw('ifnull(sum(jurnal_postingotom.debit-jurnal_postingotom.kredit),0) as realisasi')
        // )->addSelect(DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 1) as kode1'),
        //             DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 2) as kode2'),
        //             DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 3) as kode3'),
        //             DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 4) as kode4'),
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
        // ->leftJoin('jurnal_postingotom', function($join) use ($awal, $akhir) {
        //     $join->on('jurnal_postingotom.kode', '=', 'akun50_2024.kodeall3')
        //     ->where('jurnal_postingotom.verif', '=', '1')
        //     ->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
        //     ->where('jurnal_postingotom.kode', 'LIKE', '8.1.01' . '%')
        //     ;
        //     })
        // ->where('akun50_2024.kodeall3', 'LIKE', '8.1.01' . '%')
        // ->groupBy('kode3')
        // ->orderBy('kode', 'asc')
        // ->get();

        // $psapbebanlain = Akun50_2024::select('akun50_2024.kodeall2',
        // 'akun50_2024.uraian', 'akun50_2024.kodeall3',
        // // DB::raw('sum(t_tampung.pagu) as pagu'),
        // DB::raw('ifnull(sum(jurnal_postingotom.debit-jurnal_postingotom.kredit),0) as realisasi')
        // )->addSelect(DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 1) as kode1'),
        //             DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 2) as kode2'),
        //             DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 3) as kode3'),
        //             DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 4) as kode4'),
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
        // ->leftJoin('jurnal_postingotom', function($join) use ($awal, $akhir) {
        //     $join->on('jurnal_postingotom.kode', '=', 'akun50_2024.kodeall3')
        //     ->where('jurnal_postingotom.verif', '=', '1')
        //     ->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
        //     ->where('jurnal_postingotom.kode', 'LIKE', '8.1' . '%')
        //     ;
        //     })
        // ->where('akun50_2024.kodeall3', 'LIKE', '8.1.02' . '%')
        // ->orWhere('akun50_2024.kodeall3', 'LIKE', '8.1.07' . '%')
        // ->orWhere('akun50_2024.kodeall3', 'LIKE', '8.1.08' . '%')
        // ->where('akun50_2024.kodeall3', 'NOT LIKE', '8.1.01')
        // ->groupBy('kode4')
        // ->orderBy('kode', 'asc')
        // ->get();

        // $psappenjualanaset = Akun50_2024::select('akun50_2024.kodeall2',
        // 'akun50_2024.uraian', 'akun50_2024.kodeall3',
        // // DB::raw('sum(t_tampung.pagu) as pagu'),
        // DB::raw('ifnull(sum(jurnal_postingotom.debit-jurnal_postingotom.kredit),0) as realisasi')
        // )->addSelect(DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 1) as kode1'),
        //             DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 2) as kode2'),
        //             DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 3) as kode3'),
        //             DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 4) as kode4'),
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
        // ->leftJoin('jurnal_postingotom', function($join) use ($awal, $akhir) {
        //     $join->on('jurnal_postingotom.kode', '=', 'akun50_2024.kodeall3')
        //     ->where('jurnal_postingotom.verif', '=', '1')
        //     ->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
        //     ->where('jurnal_postingotom.kode', 'LIKE', '7.4' . '%');
        //     })
        // ->where('akun50_2024.kodeall3', 'LIKE', '7.4.01.01' . '%')
        // ->groupBy('kode4')
        // ->orderBy('kode', 'asc')
        // ->get();


        // $psapkerugian = Akun50_2024::select('akun50_2024.kodeall2',
        // 'akun50_2024.uraian', 'akun50_2024.kodeall3',
        // // DB::raw('sum(t_tampung.pagu) as pagu'),
        // DB::raw('ifnull(sum(jurnal_postingotom.debit-jurnal_postingotom.kredit),0) as realisasi')
        // )->addSelect(DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 1) as kode1'),
        //             DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 2) as kode2'),
        //             DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 3) as kode3'),
        //             DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 4) as kode4'),
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
        // ->leftJoin('jurnal_postingotom', function($join) use ($awal, $akhir) {
        //     $join->on('jurnal_postingotom.kode', '=', 'akun50_2024.kodeall3')
        //     ->where('jurnal_postingotom.verif', '=', '1')
        //     ->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
        //     ->where('jurnal_postingotom.kode', 'LIKE', '8.3' . '%');
        //     })
        // ->where('akun50_2024.kodeall3', 'LIKE', '8.3.01.01' . '%')
        // ->groupBy('kode4')
        // ->orderBy('kode', 'asc')
        // ->get();


        // $psapnonoperasional= Akun50_2024::select('akun50_2024.kodeall2',
        // 'akun50_2024.uraian', 'akun50_2024.kodeall3',
        // // DB::raw('sum(t_tampung.pagu) as pagu'),
        // DB::raw('ifnull(sum(jurnal_postingotom.debit-jurnal_postingotom.kredit),0) as realisasi')
        // )->addSelect(DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 1) as kode1'),
        //             DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 2) as kode2'),
        //             DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 3) as kode3'),
        //             DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 4) as kode4'),
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
        // ->leftJoin('jurnal_postingotom', function($join) use ($awal, $akhir) {
        //     $join->on('jurnal_postingotom.kode', '=', 'akun50_2024.kodeall3')
        //     ->where('jurnal_postingotom.verif', '=', '1')
        //     ->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
        //     ->where('jurnal_postingotom.kode', 'LIKE', '7.5' . '%');
        //     })
        // ->where('akun50_2024.kodeall3', 'LIKE', '7.4.03.01' . '%')
        // ->orWhere('akun50_2024.kodeall3', 'LIKE', '8.3.03.01' . '%')
        // // // ->orWhere('akun50_2024.kodeall3', 'LIKE', '8.3' . '%')
        // // ->where('akun50_2024.kodeall3', '!=', '8.3.03.02')
        // // ->orWhere('akun50_2024.kodeall3', '!=', '7.4.01.01')
        // ->groupBy('kode4')
        // ->orderBy('kode', 'asc')
        // ->get();

        // $psappendapatanluarbiasa= Akun50_2024::select('akun50_2024.kodeall2',
        // 'akun50_2024.uraian', 'akun50_2024.kodeall3',
        // // DB::raw('sum(t_tampung.pagu) as pagu'),
        // DB::raw('ifnull(sum(jurnal_postingotom.debit-jurnal_postingotom.kredit),0) as realisasi')
        // )->addSelect(DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 1) as kode1'),
        //             DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 2) as kode2'),
        //             DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 3) as kode3'),
        //             DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 4) as kode4'),
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
        // ->leftJoin('jurnal_postingotom', function($join) use ($awal, $akhir) {
        //     $join->on('jurnal_postingotom.kode', '=', 'akun50_2024.kodeall3')
        //     ->where('jurnal_postingotom.verif', '=', '1')
        //     ->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
        //     ->where('jurnal_postingotom.kode', 'LIKE', '7.5' . '%');
        //     })
        // ->where('akun50_2024.kodeall3', 'LIKE', '7.5.01.01' . '%')
        // ->groupBy('kode4')
        // ->orderBy('kode', 'asc')
        // ->get();

        // $psapbebanluarbiasa= Akun50_2024::select('akun50_2024.kodeall2',
        // 'akun50_2024.uraian', 'akun50_2024.kodeall3',
        // // DB::raw('sum(t_tampung.pagu) as pagu'),
        // DB::raw('ifnull(sum(jurnal_postingotom.debit-jurnal_postingotom.kredit),0) as realisasi')
        // )->addSelect(DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 1) as kode1'),
        //             DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 2) as kode2'),
        //             DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 3) as kode3'),
        //             DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall2, ".", 4) as kode4'),
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
        // ->leftJoin('jurnal_postingotom', function($join) use ($awal, $akhir) {
        //     $join->on('jurnal_postingotom.kode', '=', 'akun50_2024.kodeall3')
        //     ->where('jurnal_postingotom.verif', '=', '1')
        //     ->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
        //     ->where('jurnal_postingotom.kode', 'LIKE', '8.4' . '%');
        //     })
        // ->where('akun50_2024.kodeall3', 'LIKE', '8.4.01.01' . '%')
        // ->orWhere('akun50_2024.kodeall3', 'LIKE', '8.4.01.02' . '%')
        // ->groupBy('kode4')
        // ->orderBy('kode', 'asc')
        // ->get();

        $data = [
            'pagupendapatan' => $pagupendapatan,
            'beban' => $bebanotom,
            'penyesuaianbeban' => $penyesuaianbeban,
            'pendapatan' => $pendapatan,
            'penyesuaianpendapatan' => $penyesuaianpendapatan,

            // 'psaprealisasipendapatan' => $psaprealisasipendapatan,
            // 'psaprealisasipendapatanx' => $psaprealisasipendapatanx,
            // 'psappendpatanhibah' => $psappendpatanhibah,
            // 'psappenyesuaianpendp' => $psappenyesuaianpendp,
            // 'psapbebanpegawai' => $psapbebanpegawai,
            // 'psapbebanlain' => $psapbebanlain,
            // 'psappenjualanaset' => $psappenjualanaset,
            // 'psapkerugian' => $psapkerugian,
            // 'psapnonoperasional' => $psapnonoperasional,
            // 'psappendapatanluarbiasa' => $psappendapatanluarbiasa,
            // 'psapbebanluarbiasa' => $psapbebanluarbiasa
        ];
        return new JsonResponse ($data);
    }
}
