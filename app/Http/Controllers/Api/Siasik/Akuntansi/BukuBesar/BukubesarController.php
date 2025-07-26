<?php

namespace App\Http\Controllers\Api\Siasik\Akuntansi\BukuBesar;

use App\Http\Controllers\Controller;
use App\Models\Pegawai\Mpegawaisimpeg;
use App\Models\Siasik\Akuntansi\Jurnal\Create_JurnalPosting;
use App\Models\Siasik\Akuntansi\Jurnal\JurnalUmum_Header;
use App\Models\Siasik\Akuntansi\SaldoAwal;
use App\Models\Siasik\Master\Akun50_2024;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class BukubesarController extends Controller
{
    public function getTtd(){
        $ttd= Mpegawaisimpeg::whereIn('jabatan', ['J00001'])
        ->where('aktif', 'AKTIF')
        ->select('pegawai.nip',
                'pegawai.nama')
        ->get();
        return new JsonResponse($ttd);
    }
    public function akunkepmend()
    {
        $perPage = request('per_page', 100); // Default ke 100 per halaman, 0 untuk semua data

        // Mapping levelrinci
        $levelRinci = [
            '1' => 1,   // Akun
            '3' => 3,   // Kelompok
            '6' => 6,   // Jenis
            '9' => 9,   // Objek
            '12' => 12, // Rincian Objek
            '17' => 17  // SubRincian Objek
        ];

        $query = Akun50_2024::select('uraian', 'kodeall3')
            ->where('akun', '!=', '');

        // Filter berdasarkan levelberapa
        if (request('levelberapa')) {
            $level = request('levelberapa');
            if (isset($levelRinci[$level])) {
                $length = $levelRinci[$level];
                $query->whereRaw('LENGTH(TRIM(kodeall3)) = ?', [$length]);
            } else {
                Log::warning('Levelberapa tidak valid: ' . $level);
            }
        }

        // Pencarian
        if (request('q')) {
            $cari = request('q');
            $query->where(function ($q) use ($cari) {
                $q->where('uraian', 'like', '%' . $cari . '%')
                  ->orWhere('kodeall3', 'like', '%' . $cari . '%');
            });
        }

        if ($perPage <= 0) {
            $akun = $query->get();
            return new JsonResponse(['data' => $akun]);
        }

        $akun = $query->simplePaginate($perPage);

        return new JsonResponse($akun);
    }
    public function getBukubesar(){
        $thn=Carbon::createFromFormat('Y-m-d', request('tgl'))->format('Y');
        // $awal=request('tgl', 'Y'.'-'.'01-01');
        $awal=request('tgl', 'Y-m-d');
        $akhir=request('tglx', 'Y-m-d');
        $sebelum = Carbon::createFromFormat('Y-m-d', $awal)->subDay()->format('Y-m-d');
        $thnakhir=Carbon::createFromFormat('Y-m-d', request('tglx'))->format('Y');
        if($thn !== $thnakhir){
         return response()->json(['message' => 'Tahun Tidak Sama'], 500);
        }
        // $awal=request('tahun').'-'. request('bulan').'-01';
        // $akhir=request('tahun').'-'. request('bulan').'-31';
        $jurnalotom = Create_JurnalPosting::select(
            'jurnal_postingotom.notrans',
            'jurnal_postingotom.tanggal',
            'jurnal_postingotom.kegiatan',
            'jurnal_postingotom.keterangan',
            'jurnal_postingotom.kode as kode6',
            'jurnal_postingotom.uraian',
            'jurnal_postingotom.debit',
            'jurnal_postingotom.kredit',
            'akun50_2024.uraian',
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 1) as kode1'),
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 2) as kode2'),
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 3) as kode3'),
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 4) as kode4'),
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 5) as kode5'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 1) LIMIT 1) as uraian1'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 2) LIMIT 1) as uraian2'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 3) LIMIT 1) as uraian3'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 4) LIMIT 1) as uraian4'),
            DB::raw('(SELECT uraian FROM akun50_2024 WHERE kodeall3 = SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 5) LIMIT 1) as uraian5'),

            )
        ->when(request('rekenings'), function ($query) {
            $query->where(function ($q)  {
                $q->where(DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 1)'), '=', request('rekenings'))
                ->orWhere(DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 2)'), '=', request('rekenings'))
                ->orWhere(DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 3)'), '=', request('rekenings'))
                ->orWhere(DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 4)'), '=', request('rekenings'))
                ->orWhere(DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 5)'), '=', request('rekenings'))
                ->orWhere('jurnal_postingotom.kode', '=', request('rekenings')); // Untuk kode6
            });
        })
        ->join('akun50_2024', 'akun50_2024.kodeall3', 'jurnal_postingotom.kode')
        // ->with(['lvl1' => function($sel){
        //         $sel->select('akun50_2024.kodeall3','akun50_2024.uraian');
        //     }, 'lvl2' => function($sel){
        //         $sel->select('akun50_2024.kodeall3','akun50_2024.uraian');
        //     },'lvl3' => function($sel){
        //         $sel->select('akun50_2024.kodeall3','akun50_2024.uraian');
        //     },'lvl4' => function($sel){
        //         $sel->select('akun50_2024.kodeall3','akun50_2024.uraian');
        //     },'lvl5' => function($sel){
        //         $sel->select('akun50_2024.kodeall3','akun50_2024.uraian');
        // }])
        ->where('jurnal_postingotom.verif', '=', '1')
        ->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
        ->where(function($query){
            $query->when(request('q'), function($q){
                $q->where('notrans', 'like', '%'.request('q').'%')
                ->orWhere('tanggal', 'like', '%'.request('q').'%')
                ->orWhere('kegiatan', 'like', '%'.request('q').'%')
                ->orWhere('keterangan', 'like', '%'.request('q').'%')
                ->orWhere('debit', 'like', '%'.request('q').'%')
                ->orWhere('kredit', 'like', '%'.request('q').'%');
            });
        })
        // ->groupBy('kode6')
        ->get();


        $jurnalmanual=JurnalUmum_Header::select(
            'jurnalumum_heder.tanggal',
            'jurnalumum_heder.keterangan',
            'jurnalumum_heder.nobukti as notrans',
            'jurnalumum_rinci.kodepsap13 as kode6',
            'jurnalumum_rinci.uraianpsap13 as uraian',
            'jurnalumum_rinci.debet as debit',
            'jurnalumum_rinci.kredit',
            'jurnalumum_rinci.jumlah',
            'akun50_2024.uraian',
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
        ->when(request('rekenings'), function ($query) {
            $query->where(function ($q)  {
                $q->where(DB::raw('SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 1)'), '=', request('rekenings'))
                ->orWhere(DB::raw('SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 2)'), '=', request('rekenings'))
                ->orWhere(DB::raw('SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 3)'), '=', request('rekenings'))
                ->orWhere(DB::raw('SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 4)'), '=', request('rekenings'))
                ->orWhere(DB::raw('SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 5)'), '=', request('rekenings'))
                ->orWhere('jurnalumum_rinci.kodepsap13', '=', request('rekenings')); // Untuk kode6
            });
        })
        ->join('jurnalumum_rinci', 'jurnalumum_rinci.nobukti', 'jurnalumum_heder.nobukti')
        ->join('akun50_2024', 'akun50_2024.kodeall3', 'jurnalumum_rinci.kodepsap13')
        ->where('jurnalumum_heder.verif', '=', '1')
        // ->where('jurnalumum_rinci.kodepsap13', request('level'))

        ->whereBetween('jurnalumum_heder.tanggal', [$awal, $akhir])
        ->get();

        $saldoawal = SaldoAwal::select(
            'saldoawal.tanggal',
            'saldoawal.kodepsap13 as kode6',
            'saldoawal.uraianpsap13 as uraian',
            'saldoawal.debit',
            'saldoawal.kredit',
            DB::raw('SUBSTRING_INDEX(saldoawal.kodepsap13, ".", 1) as kode1'),
            DB::raw('SUBSTRING_INDEX(saldoawal.kodepsap13, ".", 2) as kode2'),
            DB::raw('SUBSTRING_INDEX(saldoawal.kodepsap13, ".", 3) as kode3'),
            DB::raw('SUBSTRING_INDEX(saldoawal.kodepsap13, ".", 4) as kode4'),
            DB::raw('SUBSTRING_INDEX(saldoawal.kodepsap13, ".", 5) as kode5'))
        ->whereBetween('saldoawal.tanggal', [$awal. ' 00:00:00', $akhir. ' 23:59:59'])
        ->orderBy('kode6', 'ASC')
        ->get();


        $sajurnalotom = Create_JurnalPosting::select(
            'jurnal_postingotom.notrans',
            'jurnal_postingotom.tanggal',
            'jurnal_postingotom.kegiatan',
            'jurnal_postingotom.keterangan',
            'jurnal_postingotom.kode as kode6',
            'akun50_2024.uraian',
            'jurnal_postingotom.debit',
            'jurnal_postingotom.kredit',
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 1) as kode1'),
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 2) as kode2'),
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 3) as kode3'),
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 4) as kode4'),
            DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 5) as kode5'))
        ->when(request('rekenings'), function ($query) {
                $query->where(function ($q)  {
                    $q->where(DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 1)'), '=', request('rekenings'))
                    ->orWhere(DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 2)'), '=', request('rekenings'))
                    ->orWhere(DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 3)'), '=', request('rekenings'))
                    ->orWhere(DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 4)'), '=', request('rekenings'))
                    ->orWhere(DB::raw('SUBSTRING_INDEX(jurnal_postingotom.kode, ".", 5)'), '=', request('rekenings'))
                    ->orWhere('jurnal_postingotom.kode', '=', request('rekenings')); // Untuk kode6
                });
            })
        ->join('akun50_2024', 'akun50_2024.kodeall3', 'jurnal_postingotom.kode')
        ->where('jurnal_postingotom.verif', '=', '1')
        ->whereBetween('jurnal_postingotom.tanggal', [$thn.'-01-01', $sebelum])
        ->orderBy('kode6', 'ASC')
        ->get();

        $sajurnalmanual = JurnalUmum_Header::select(
            'jurnalumum_heder.tanggal',
            'jurnalumum_heder.keterangan',
            'jurnalumum_heder.nobukti as notrans',
            'jurnalumum_rinci.kodepsap13 as kode6',
            'jurnalumum_rinci.uraianpsap13 as uraian',
            'jurnalumum_rinci.debet as debit',
            'jurnalumum_rinci.kredit',
            'jurnalumum_rinci.jumlah',
            'akun50_2024.uraian',
            DB::raw('SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 1) as kode1'),
            DB::raw('SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 2) as kode2'),
            DB::raw('SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 3) as kode3'),
            DB::raw('SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 4) as kode4'),
            DB::raw('SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 5) as kode5'))
        ->when(request('rekenings'), function ($query) {
            $query->where(function ($q)  {
                $q->where(DB::raw('SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 1)'), '=', request('rekenings'))
                ->orWhere(DB::raw('SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 2)'), '=', request('rekenings'))
                ->orWhere(DB::raw('SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 3)'), '=', request('rekenings'))
                ->orWhere(DB::raw('SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 4)'), '=', request('rekenings'))
                ->orWhere(DB::raw('SUBSTRING_INDEX(jurnalumum_rinci.kodepsap13, ".", 5)'), '=', request('rekenings'))
                ->orWhere('jurnalumum_rinci.kodepsap13', '=', request('rekenings')); // Untuk kode6
            });
        })
        ->join('jurnalumum_rinci', 'jurnalumum_rinci.nobukti', 'jurnalumum_heder.nobukti')
        ->join('akun50_2024', 'akun50_2024.kodeall3', 'jurnalumum_rinci.kodepsap13')

        ->where('jurnalumum_heder.verif', '=', '1')
        ->whereBetween('jurnalumum_heder.tanggal', [$thn.'-01-01', $sebelum])
        ->orderBy('kode6', 'ASC')
        ->get();


        $saldoawalsebelum = SaldoAwal::select(
            'saldoawal.id as notrans',
            'saldoawal.tglentry as tanggal',
            'saldoawal.kodepsap13 as kode6',
            'saldoawal.uraianpsap13 as uraian',
            'saldoawal.debit',
            'saldoawal.kredit',
            DB::raw('SUBSTRING_INDEX(saldoawal.kodepsap13, ".", 1) as kode1'),
            DB::raw('SUBSTRING_INDEX(saldoawal.kodepsap13, ".", 2) as kode2'),
            DB::raw('SUBSTRING_INDEX(saldoawal.kodepsap13, ".", 3) as kode3'),
            DB::raw('SUBSTRING_INDEX(saldoawal.kodepsap13, ".", 4) as kode4'),
            DB::raw('SUBSTRING_INDEX(saldoawal.kodepsap13, ".", 5) as kode5'),
        )
        ->when(request('rekenings'), function ($query) {
                $query->where(function ($q)  {
                    $q->where(DB::raw('SUBSTRING_INDEX(saldoawal.kodepsap13, ".", 1)'), '=', request('rekenings'))
                    ->orWhere(DB::raw('SUBSTRING_INDEX(saldoawal.kodepsap13, ".", 2)'), '=', request('rekenings'))
                    ->orWhere(DB::raw('SUBSTRING_INDEX(saldoawal.kodepsap13, ".", 3)'), '=', request('rekenings'))
                    ->orWhere(DB::raw('SUBSTRING_INDEX(saldoawal.kodepsap13, ".", 4)'), '=', request('rekenings'))
                    ->orWhere(DB::raw('SUBSTRING_INDEX(saldoawal.kodepsap13, ".", 5)'), '=', request('rekenings'))
                    ->orWhere('saldoawal.kodepsap13', '=', request('rekenings')); // Untuk kode6
                });
            })
        ->join('akun50_2024', 'akun50_2024.kodeall3', 'saldoawal.kodepsap13')
        ->whereBetween('saldoawal.tglentry', [$thn.'-01-01'. ' 00:00:00', $sebelum. ' 23:59:59'])
        ->get();

        $data = [
            'jurnalotom' => $jurnalotom,
            'jurnalmanual' => $jurnalmanual,
            'saldoawal' => $saldoawal,
            'sajurnalotom' => $sajurnalotom,
            'sajurnalmanual' => $sajurnalmanual,
            'saldosebelum' => $saldoawalsebelum
        ];
        return new JsonResponse ($data);
    }
}
