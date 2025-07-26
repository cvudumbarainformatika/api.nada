<?php

namespace App\Http\Controllers\Api\Siasik\Akuntansi\Laporan;

use App\Http\Controllers\Controller;
use App\Models\Siasik\Akuntansi\Jurnal\Create_JurnalPosting;
use App\Models\Siasik\Akuntansi\SaldoAwal;
use App\Models\Siasik\Master\Akun50_2024;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NeracaController extends Controller
{
    public function getNeraca (){
        $thn=Carbon::createFromFormat('Y-m-d', request('tgl'))->format('Y');
        $awal=request('tgl', 'Y-m-d');
        $akhir=request('tglx', 'Y-m-d');
        $sebelum = date( 'Y-m-d', strtotime( $awal . ' -1 day' ) );
        $thnakhir=Carbon::createFromFormat('Y-m-d', request('tglx'))->format('Y');
        if($thn !== $thnakhir){
         return response()->json(['message' => 'Tahun Tidak Sama'], 500);
        }

        $setarakas = Akun50_2024::select(
            'akun50_2024.kodeall3',
            'akun50_2024.uraian',
        )
        ->with(['saldoawal'=>function($sa) use ($awal,$akhir){
            $sa->select(
                'saldoawal.tglentry as tanggal',
                'saldoawal.kodepsap13',
                DB::raw('ifnull(sum(saldoawal.debit-saldoawal.kredit),0) as saldo')
            ) ->whereBetween('saldoawal.tglentry', [$awal. ' 00:00:00', $akhir. ' 23:59:59'])
            ->groupBy( 'saldoawal.kodepsap13');
        },'jurnalotom' => function($x) use ($awal,$akhir){
            $x->select(
                'jurnal_postingotom.kode',
                DB::raw('ifnull(sum(jurnal_postingotom.debit-jurnal_postingotom.kredit),0) as totaljurnal'),
            )->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
            ->where('jurnal_postingotom.verif', '=', '1')
            ->groupBy( 'jurnal_postingotom.kode');
        },
        'penyesuaianx' =>  function($sel) use ($awal,$akhir){
            $sel->join('jurnalumum_heder', 'jurnalumum_heder.nobukti', 'jurnalumum_rinci.nobukti')
            ->select('jurnalumum_rinci.kodepsap13',
                    'jurnalumum_heder.tanggal',
                    DB::raw('sum(jurnalumum_rinci.debet-jurnalumum_rinci.kredit) as totalpenyesuaian')
                    )
            ->where('jurnalumum_heder.verif', '=', '1')
            ->whereBetween('jurnalumum_heder.tanggal', [$awal, $akhir])
            ->where('jurnalumum_heder.keterangan', 'NOT LIKE', 'Reklas Pendapatan' . '%')
            ->groupBy( 'jurnalumum_rinci.kodepsap13');
        }])

        ->where('akun50_2024.kodeall3', 'LIKE', '1.1.01.02.01.0001' . '%')
        ->orWhere('akun50_2024.kodeall3', 'LIKE', '1.1.01.03.01.0001' . '%')
        ->orWhere('akun50_2024.kodeall3', 'LIKE', '1.1.01.04.01.0001' . '%')
        ->orWhere('akun50_2024.kodeall3', 'LIKE', '1.1.01.07.01.0001' . '%')
        ->orWhere('akun50_2024.kodeall3', 'LIKE', '1.1.01.08.01.0001' . '%')
        // ->groupBy('kode6')
        ->orderBy('akun50_2024.kodeall3', 'asc')
        ->get();

        $retribusi = Akun50_2024::select(
            'akun50_2024.kodeall3',
            'akun50_2024.uraian',
        )
        ->with(['saldoawal'=>function($sa) use ($awal,$akhir){
            $sa->select(
                'saldoawal.tglentry as tanggal',
                'saldoawal.kodepsap13',
                DB::raw('ifnull(sum(saldoawal.debit-saldoawal.kredit),0) as saldo')
            ) ->whereBetween('saldoawal.tglentry', [$awal. ' 00:00:00', $akhir. ' 23:59:59'])
            ->groupBy( 'saldoawal.kodepsap13');
        },'jurnalotom' => function($x) use ($awal,$akhir){
            $x->select(
                'jurnal_postingotom.kode',
                DB::raw('ifnull(sum(jurnal_postingotom.debit-jurnal_postingotom.kredit),0) as totaljurnal'),
            )->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
            ->where('jurnal_postingotom.verif', '=', '1')
            ->groupBy( 'jurnal_postingotom.kode');
        },
        'penyesuaianx' =>  function($sel) use ($awal,$akhir){
            $sel->join('jurnalumum_heder', 'jurnalumum_heder.nobukti', 'jurnalumum_rinci.nobukti')
            ->select('jurnalumum_rinci.kodepsap13',
                    'jurnalumum_heder.tanggal',
                    DB::raw('sum(jurnalumum_rinci.debet-jurnalumum_rinci.kredit) as totalpenyesuaian')
                    )
            ->where('jurnalumum_heder.verif', '=', '1')
            ->whereBetween('jurnalumum_heder.tanggal', [$awal, $akhir])
            ->where('jurnalumum_heder.keterangan', 'NOT LIKE', 'Reklas Pendapatan' . '%')
            ->groupBy( 'jurnalumum_rinci.kodepsap13');
        }])

        ->where('akun50_2024.kodeall3', 'LIKE', '1.1.04.' . '%')
        // ->orWhere('akun50_2024.kodeall3', 'LIKE', '1.1.04.02.' . '%')
        ->orderBy('akun50_2024.kodeall3', 'asc')
        ->get();

        $piutang = Akun50_2024::select(
            'akun50_2024.kodeall3',
            'akun50_2024.uraian',
        )
        ->with(['saldoawal'=>function($sa) use ($awal,$akhir){
            $sa->select(
                'saldoawal.tglentry as tanggal',
                'saldoawal.kodepsap13',
                DB::raw('ifnull(sum(saldoawal.debit-saldoawal.kredit),0) as saldo')
            ) ->whereBetween('saldoawal.tglentry', [$awal. ' 00:00:00', $akhir. ' 23:59:59'])
            ->groupBy( 'saldoawal.kodepsap13');
        },'jurnalotom' => function($x) use ($awal,$akhir){
            $x->select(
                'jurnal_postingotom.kode',
                DB::raw('ifnull(sum(jurnal_postingotom.debit-jurnal_postingotom.kredit),0) as totaljurnal'),
            )->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
            ->where('jurnal_postingotom.verif', '=', '1')
            ->groupBy( 'jurnal_postingotom.kode');
        },
        'penyesuaianx' =>  function($sel) use ($awal,$akhir){
            $sel->join('jurnalumum_heder', 'jurnalumum_heder.nobukti', 'jurnalumum_rinci.nobukti')
            ->select('jurnalumum_rinci.kodepsap13',
                    'jurnalumum_heder.tanggal',
                    DB::raw('sum(jurnalumum_rinci.debet-jurnalumum_rinci.kredit) as totalpenyesuaian')
                    )
            ->where('jurnalumum_heder.verif', '=', '1')
            ->whereBetween('jurnalumum_heder.tanggal', [$awal, $akhir])
            ->where('jurnalumum_heder.keterangan', 'NOT LIKE', 'Reklas Pendapatan' . '%')
            ->groupBy( 'jurnalumum_rinci.kodepsap13');
        }])
        ->where('akun50_2024.kodeall3', 'LIKE', '1.1.06.16' . '%')
        ->orderBy('akun50_2024.kodeall3', 'asc')
        ->get();

        $piutanglain = Akun50_2024::select(
            'akun50_2024.kodeall3',
            'akun50_2024.uraian',
        )
        ->with(['saldoawal'=>function($sa) use ($awal,$akhir){
            $sa->select(
                'saldoawal.tglentry as tanggal',
                'saldoawal.kodepsap13',
                DB::raw('ifnull(sum(saldoawal.debit-saldoawal.kredit),0) as saldo')
            ) ->whereBetween('saldoawal.tglentry', [$awal. ' 00:00:00', $akhir. ' 23:59:59'])
            ->groupBy( 'saldoawal.kodepsap13');
        },'jurnalotom' => function($x) use ($awal,$akhir){
            $x->select(
                'jurnal_postingotom.kode',
                DB::raw('ifnull(sum(jurnal_postingotom.debit-jurnal_postingotom.kredit),0) as totaljurnal'),
            )->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
            ->where('jurnal_postingotom.verif', '=', '1')
            ->groupBy( 'jurnal_postingotom.kode');
        },
        'penyesuaianx' =>  function($sel) use ($awal,$akhir){
            $sel->join('jurnalumum_heder', 'jurnalumum_heder.nobukti', 'jurnalumum_rinci.nobukti')
            ->select('jurnalumum_rinci.kodepsap13',
                    'jurnalumum_heder.tanggal',
                    DB::raw('sum(jurnalumum_rinci.debet-jurnalumum_rinci.kredit) as totalpenyesuaian')
                    )
            ->where('jurnalumum_heder.verif', '=', '1')
            ->whereBetween('jurnalumum_heder.tanggal', [$awal, $akhir])
            ->where('jurnalumum_heder.keterangan', 'NOT LIKE', 'Reklas Pendapatan' . '%')
            ->groupBy( 'jurnalumum_rinci.kodepsap13');
        }])
        ->where('akun50_2024.kodeall3', 'LIKE', '1.1.06.' . '%')
        ->where('akun50_2024.kodeall3', 'NOT LIKE', '1.1.06.16' . '%')
        ->orderBy('akun50_2024.kodeall3', 'asc')
        ->get();

        // $penyisihanpiutang = Akun50_2024::select(
        //     'akun50_2024.kodeall3',
        //     'akun50_2024.uraian',
        // )
        // ->with(['saldoawal'=>function($sa) use ($awal,$akhir){
        //     $sa->select(
        //         'saldoawal.tglentry as tanggal',
        //         'saldoawal.kodepsap13',
        //         DB::raw('ifnull(sum(saldoawal.debit-saldoawal.kredit),0) as saldo')
        //     ) ->whereBetween('saldoawal.tglentry', [$awal. ' 00:00:00', $akhir. ' 23:59:59'])
        //     ->groupBy( 'saldoawal.kodepsap13');
        // },'jurnalotom' => function($x) use ($awal,$akhir){
        //     $x->select(
        //         'jurnal_postingotom.kode',
        //         DB::raw('ifnull(sum(jurnal_postingotom.debit-jurnal_postingotom.kredit),0) as totaljurnal'),
        //     )->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
        //     ->where('jurnal_postingotom.verif', '=', '1')
        //     ->groupBy( 'jurnal_postingotom.kode');
        // },
        // 'penyesuaianx' =>  function($sel) use ($awal,$akhir){
        //     $sel->join('jurnalumum_heder', 'jurnalumum_heder.nobukti', 'jurnalumum_rinci.nobukti')
        //     ->select('jurnalumum_rinci.kodepsap13',
        //             'jurnalumum_heder.tanggal',
        //             DB::raw('sum(jurnalumum_rinci.debet-jurnalumum_rinci.kredit) as totalpenyesuaian')
        //             )
        //     ->where('jurnalumum_heder.verif', '=', '1')
        //     ->whereBetween('jurnalumum_heder.tanggal', [$awal, $akhir])
        //     ->where('jurnalumum_heder.keterangan', 'NOT LIKE', 'Reklas Pendapatan' . '%')
        //     ->groupBy( 'jurnalumum_rinci.kodepsap13');
        // }])
        // ->where('akun50_2024.kodeall3', 'LIKE', '1.1.10.' . '%')
        // ->orderBy('akun50_2024.kodeall3', 'asc')
        // ->get();

        // $persediaan = Akun50_2024::select(
        //     'akun50_2024.kodeall3',
        //     'akun50_2024.uraian',
        // )
        // ->with(['saldoawal'=>function($sa) use ($awal,$akhir){
        //     $sa->select(
        //         'saldoawal.tglentry as tanggal',
        //         'saldoawal.kodepsap13',
        //         DB::raw('ifnull(sum(saldoawal.debit-saldoawal.kredit),0) as saldo')
        //     ) ->whereBetween('saldoawal.tglentry', [$awal. ' 00:00:00', $akhir. ' 23:59:59'])
        //     ->groupBy( 'saldoawal.kodepsap13');
        // },'jurnalotom' => function($x) use ($awal,$akhir){
        //     $x->select(
        //         'jurnal_postingotom.kode',
        //         DB::raw('ifnull(sum(jurnal_postingotom.debit-jurnal_postingotom.kredit),0) as totaljurnal'),
        //     )->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
        //     ->where('jurnal_postingotom.verif', '=', '1')
        //     ->groupBy( 'jurnal_postingotom.kode');
        // },
        // 'penyesuaianx' =>  function($sel) use ($awal,$akhir){
        //     $sel->join('jurnalumum_heder', 'jurnalumum_heder.nobukti', 'jurnalumum_rinci.nobukti')
        //     ->select('jurnalumum_rinci.kodepsap13',
        //             'jurnalumum_heder.tanggal',
        //             DB::raw('sum(jurnalumum_rinci.debet-jurnalumum_rinci.kredit) as totalpenyesuaian')
        //             )
        //     ->where('jurnalumum_heder.verif', '=', '1')
        //     ->whereBetween('jurnalumum_heder.tanggal', [$awal, $akhir])
        //     ->where('jurnalumum_heder.keterangan', 'NOT LIKE', 'Reklas Pendapatan' . '%')
        //     ->groupBy( 'jurnalumum_rinci.kodepsap13');
        // }])
        // ->where('akun50_2024.kodeall3', 'LIKE', '1.1.12.' . '%')
        // ->orderBy('akun50_2024.kodeall3', 'asc')
        // ->get();

        // $investasi = Akun50_2024::select(
        //     'akun50_2024.kodeall3',
        //     'akun50_2024.uraian',
        // )
        // ->with(['saldoawal'=>function($sa) use ($awal,$akhir){
        //     $sa->select(
        //         'saldoawal.tglentry as tanggal',
        //         'saldoawal.kodepsap13',
        //         DB::raw('ifnull(sum(saldoawal.debit-saldoawal.kredit),0) as saldo')
        //     ) ->whereBetween('saldoawal.tglentry', [$awal. ' 00:00:00', $akhir. ' 23:59:59'])
        //     ->groupBy( 'saldoawal.kodepsap13');
        // },'jurnalotom' => function($x) use ($awal,$akhir){
        //     $x->select(
        //         'jurnal_postingotom.kode',
        //         DB::raw('ifnull(sum(jurnal_postingotom.debit-jurnal_postingotom.kredit),0) as totaljurnal'),
        //     )->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
        //     ->where('jurnal_postingotom.verif', '=', '1')
        //     ->groupBy( 'jurnal_postingotom.kode');
        // },
        // 'penyesuaianx' =>  function($sel) use ($awal,$akhir){
        //     $sel->join('jurnalumum_heder', 'jurnalumum_heder.nobukti', 'jurnalumum_rinci.nobukti')
        //     ->select('jurnalumum_rinci.kodepsap13',
        //             'jurnalumum_heder.tanggal',
        //             DB::raw('sum(jurnalumum_rinci.debet-jurnalumum_rinci.kredit) as totalpenyesuaian')
        //             )
        //     ->where('jurnalumum_heder.verif', '=', '1')
        //     ->whereBetween('jurnalumum_heder.tanggal', [$awal, $akhir])
        //     ->where('jurnalumum_heder.keterangan', 'NOT LIKE', 'Reklas Pendapatan' . '%')
        //     ->groupBy( 'jurnalumum_rinci.kodepsap13');
        // }])
        // ->where('akun50_2024.kodeall3', 'LIKE', '1.2.' . '%')
        // ->orderBy('akun50_2024.kodeall3', 'asc')
        // ->get();


        $aset = Akun50_2024::select(
            'akun50_2024.kodeall3',
            'akun50_2024.uraian',
            DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 3) as kode3'),
            DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 3) as kodex'),
        )
        ->with(['kode3' => function($sel){
            $sel->select('akun50_2024.kodeall2','akun50_2024.uraian');
        },'saldoawal'=>function($sa) use ($awal,$akhir){
            $sa->select(
                'saldoawal.tglentry as tanggal',
                'saldoawal.kodepsap13',
                DB::raw('ifnull(sum(saldoawal.debit-saldoawal.kredit),0) as saldo')
            ) ->whereBetween('saldoawal.tglentry', [$awal. ' 00:00:00', $akhir. ' 23:59:59'])
            ->groupBy( 'saldoawal.kodepsap13');
        },'jurnalotom' => function($x) use ($awal,$akhir){
            $x->select(
                'jurnal_postingotom.kode',
                DB::raw('ifnull(sum(jurnal_postingotom.debit-jurnal_postingotom.kredit),0) as totaljurnal'),
            )->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
            ->where('jurnal_postingotom.verif', '=', '1')
            ->groupBy( 'jurnal_postingotom.kode');
        },
        'penyesuaianx' =>  function($sel) use ($awal,$akhir){
            $sel->join('jurnalumum_heder', 'jurnalumum_heder.nobukti', 'jurnalumum_rinci.nobukti')
            ->select('jurnalumum_rinci.kodepsap13',
                    'jurnalumum_heder.tanggal',
                    DB::raw('sum(jurnalumum_rinci.debet-jurnalumum_rinci.kredit) as totalpenyesuaian')
                    )
            ->where('jurnalumum_heder.verif', '=', '1')
            ->whereBetween('jurnalumum_heder.tanggal', [$awal, $akhir])
            ->where('jurnalumum_heder.keterangan', 'NOT LIKE', 'Reklas Pendapatan' . '%')
            ->groupBy( 'jurnalumum_rinci.kodepsap13');
        }])
        ->where('akun50_2024.kodeall3', 'LIKE', '1.1.10.' . '%')
        ->orWhere('akun50_2024.kodeall3', 'LIKE', '1.1.12.' . '%')
        ->orWhere('akun50_2024.kodeall3', 'LIKE', '1.2.' . '%')
        ->orWhere('akun50_2024.kodeall3', 'LIKE', '1.3.' . '%')
        ->orWhere('akun50_2024.kodeall3', 'LIKE', '1.5.03' . '%')
        ->orWhere('akun50_2024.kodeall3', 'LIKE', '1.5.04' . '%')
        ->orWhere('akun50_2024.kodeall3', 'LIKE', '1.5.05' . '%')
        ->orWhere('akun50_2024.kodeall3', 'LIKE', '1.5.06' . '%')
        ->orderBy('akun50_2024.kodeall3', 'asc')
        ->get();

        // $asetlainnya = Akun50_2024::select(
        //     'akun50_2024.kodeall3',
        //     'akun50_2024.uraian',
        //     DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 3) as kode3'),
        //     DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 3) as kodex'),
        // )
        // ->with(['kode3' => function($sel){
        //     $sel->select('akun50_2024.kodeall2','akun50_2024.uraian');
        // },'saldoawal'=>function($sa) use ($awal,$akhir){
        //     $sa->select(
        //         'saldoawal.tglentry as tanggal',
        //         'saldoawal.kodepsap13',
        //         DB::raw('ifnull(sum(saldoawal.debit-saldoawal.kredit),0) as saldo')
        //     ) ->whereBetween('saldoawal.tglentry', [$awal. ' 00:00:00', $akhir. ' 23:59:59'])
        //     ->groupBy( 'saldoawal.kodepsap13');
        // },'jurnalotom' => function($x) use ($awal,$akhir){
        //     $x->select(
        //         'jurnal_postingotom.kode',
        //         DB::raw('ifnull(sum(jurnal_postingotom.debit-jurnal_postingotom.kredit),0) as totaljurnal'),
        //     )->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
        //     ->where('jurnal_postingotom.verif', '=', '1')
        //     ->groupBy( 'jurnal_postingotom.kode');
        // },
        // 'penyesuaianx' =>  function($sel) use ($awal,$akhir){
        //     $sel->join('jurnalumum_heder', 'jurnalumum_heder.nobukti', 'jurnalumum_rinci.nobukti')
        //     ->select('jurnalumum_rinci.kodepsap13',
        //             'jurnalumum_heder.tanggal',
        //             DB::raw('sum(jurnalumum_rinci.debet-jurnalumum_rinci.kredit) as totalpenyesuaian')
        //             )
        //     ->where('jurnalumum_heder.verif', '=', '1')
        //     ->whereBetween('jurnalumum_heder.tanggal', [$awal, $akhir])
        //     ->where('jurnalumum_heder.keterangan', 'NOT LIKE', 'Reklas Pendapatan' . '%')
        //     ->groupBy( 'jurnalumum_rinci.kodepsap13');
        // }])
        // ->where('akun50_2024.kodeall3', 'LIKE', '1.5.03' . '%')
        // ->orWhere('akun50_2024.kodeall3', 'LIKE', '1.5.04' . '%')
        // ->orWhere('akun50_2024.kodeall3', 'LIKE', '1.5.05' . '%')
        // ->orWhere('akun50_2024.kodeall3', 'LIKE', '1.5.06' . '%')
        // ->orderBy('akun50_2024.kodeall3', 'asc')
        // ->get();

        $utang = Akun50_2024::select(
            'akun50_2024.kodeall3',
            'akun50_2024.uraian',
            DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 3) as kode3'),
            DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 4) as kode4'),
            DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 3) as kodex'),
        )
        ->with(['kode3' => function($sel){
            $sel->select('akun50_2024.kodeall2','akun50_2024.uraian');
        },'saldoawal'=>function($sa) use ($awal,$akhir){
            $sa->select(
                'saldoawal.tglentry as tanggal',
                'saldoawal.kodepsap13',
                DB::raw('ifnull(sum(saldoawal.kredit-saldoawal.debit),0) as saldo')
            ) ->whereBetween('saldoawal.tglentry', [$awal. ' 00:00:00', $akhir. ' 23:59:59'])
            ->groupBy( 'saldoawal.kodepsap13');
        },'jurnalotom' => function($x) use ($awal,$akhir){
            $x->select(
                'jurnal_postingotom.kode',
                DB::raw('ifnull(sum(jurnal_postingotom.kredit-jurnal_postingotom.debit),0) as totaljurnal'),
            )->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
            ->where('jurnal_postingotom.verif', '=', '1')
            ->groupBy( 'jurnal_postingotom.kode');
        },
        'penyesuaianx' =>  function($sel) use ($awal,$akhir){
            $sel->join('jurnalumum_heder', 'jurnalumum_heder.nobukti', 'jurnalumum_rinci.nobukti')
            ->select('jurnalumum_rinci.kodepsap13',
                    'jurnalumum_heder.tanggal',
                    DB::raw('sum(jurnalumum_rinci.kredit-jurnalumum_rinci.debet) as totalpenyesuaian')
                    )
            ->where('jurnalumum_heder.verif', '=', '1')
            ->whereBetween('jurnalumum_heder.tanggal', [$awal, $akhir])
            ->where('jurnalumum_heder.keterangan', 'NOT LIKE', 'Reklas Pendapatan' . '%')
            ->groupBy( 'jurnalumum_rinci.kodepsap13');
        }])
        ->where('akun50_2024.kodeall3', 'LIKE', '2.2.' . '%')
        ->orWhere('akun50_2024.kodeall3', 'LIKE', '2.1.06' . '%')
        ->orWhere('akun50_2024.kodeall3', 'LIKE', '2.1.07' . '%')
        ->orWhere('akun50_2024.kodeall3', 'LIKE', '2.1.05' . '%')
        ->orWhere('akun50_2024.kodeall3', 'LIKE', '3.1.01' . '%')
        ->orderBy('akun50_2024.kodeall3', 'asc')
        ->get();


        // $utangjkpanjang = Akun50_2024::select(
        //     'akun50_2024.kodeall3',
        //     'akun50_2024.uraian',
        //     DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 3) as kode3'),
        //     DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 3) as kodex'),
        // )
        // ->with(['kode3' => function($sel){
        //     $sel->select('akun50_2024.kodeall2','akun50_2024.uraian');
        // },'saldoawal'=>function($sa) use ($awal,$akhir){
        //     $sa->select(
        //         'saldoawal.tglentry as tanggal',
        //         'saldoawal.kodepsap13',
        //         DB::raw('ifnull(sum(saldoawal.kredit-saldoawal.debit),0) as saldo')
        //     ) ->whereBetween('saldoawal.tglentry', [$awal. ' 00:00:00', $akhir. ' 23:59:59'])
        //     ->groupBy( 'saldoawal.kodepsap13');
        // },'jurnalotom' => function($x) use ($awal,$akhir){
        //     $x->select(
        //         'jurnal_postingotom.kode',
        //         DB::raw('ifnull(sum(jurnal_postingotom.kredit-jurnal_postingotom.debit),0) as totaljurnal'),
        //     )->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
        //     ->where('jurnal_postingotom.verif', '=', '1')
        //     ->groupBy( 'jurnal_postingotom.kode');
        // },
        // 'penyesuaianx' =>  function($sel) use ($awal,$akhir){
        //     $sel->join('jurnalumum_heder', 'jurnalumum_heder.nobukti', 'jurnalumum_rinci.nobukti')
        //     ->select('jurnalumum_rinci.kodepsap13',
        //             'jurnalumum_heder.tanggal',
        //             DB::raw('sum(jurnalumum_rinci.kredit-jurnalumum_rinci.debet) as totalpenyesuaian')
        //             )
        //     ->where('jurnalumum_heder.verif', '=', '1')
        //     ->whereBetween('jurnalumum_heder.tanggal', [$awal, $akhir])
        //     ->where('jurnalumum_heder.keterangan', 'NOT LIKE', 'Reklas Pendapatan' . '%')
        //     ->groupBy( 'jurnalumum_rinci.kodepsap13');
        // }])
        // ->where('akun50_2024.kodeall3', 'LIKE', '2.2.' . '%')
        // ->orderBy('akun50_2024.kodeall3', 'asc')
        // ->get();

        // $ekuitas = Akun50_2024::select(
        //     'akun50_2024.kodeall3',
        //     'akun50_2024.uraian',
        //     DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 3) as kode3'),
        //     DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 3) as kodex'),
        // )
        // ->with(['kode3' => function($sel){
        //     $sel->select('akun50_2024.kodeall2','akun50_2024.uraian');
        // },'saldoawal'=>function($sa) use ($awal,$akhir){
        //     $sa->select(
        //         'saldoawal.tglentry as tanggal',
        //         'saldoawal.kodepsap13',
        //         DB::raw('ifnull(sum(saldoawal.kredit-saldoawal.debit),0) as saldo')
        //     ) ->whereBetween('saldoawal.tglentry', [$awal. ' 00:00:00', $akhir. ' 23:59:59'])
        //     ->groupBy( 'saldoawal.kodepsap13');
        // },'jurnalotom' => function($x) use ($awal,$akhir){
        //     $x->select(
        //         'jurnal_postingotom.kode',
        //         DB::raw('ifnull(sum(jurnal_postingotom.kredit-jurnal_postingotom.debit),0) as totaljurnal'),
        //     )->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
        //     ->where('jurnal_postingotom.verif', '=', '1')
        //     ->where('jurnal_postingotom.uraian', 'LIKE', 'Ekuitas' . '%')
        //     ->groupBy( 'jurnal_postingotom.kode');
        // },
        // 'penyesuaianx' =>  function($sel) use ($awal,$akhir){
        //     $sel->join('jurnalumum_heder', 'jurnalumum_heder.nobukti', 'jurnalumum_rinci.nobukti')
        //     ->select('jurnalumum_rinci.kodepsap13',
        //             'jurnalumum_heder.tanggal',
        //             DB::raw('sum(jurnalumum_rinci.kredit-jurnalumum_rinci.debet) as totalpenyesuaian')
        //             )
        //     ->where('jurnalumum_heder.verif', '=', '1')
        //     ->whereBetween('jurnalumum_heder.tanggal', [$awal, $akhir])
        //     ->where('jurnalumum_heder.keterangan', 'NOT LIKE', 'Reklas Pendapatan' . '%')
        //     ->where('jurnalumum_rinci.uraianpsap13', 'LIKE', 'Ekuitas' . '%')
        //     ->groupBy( 'jurnalumum_rinci.kodepsap13');
        // }])
        // ->where('akun50_2024.kodeall3', 'LIKE', '3.1.01' . '%')
        // // ->orWhere('akun50_2024.kodeall3', 'LIKE', 'Ekuitas' . '%')
        // // ->orWhere('akun50_2024.kodeall3', 'LIKE', '3.1.03' . '%')
        // ->orderBy('akun50_2024.kodeall3', 'asc')
        // ->get();



        // $Semua = Akun50_2024::select(
        //     'akun50_2024.kodeall3',
        //     'akun50_2024.uraian',
        //     DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 3) as kode3'),
        //     DB::raw('SUBSTRING_INDEX(akun50_2024.kodeall3, ".", 3) as kodex'),
        // )
        // ->with(['kode3' => function($sel){
        //     $sel->select('akun50_2024.kodeall2','akun50_2024.uraian');
        // },'saldoawal'=>function($sa) use ($awal,$akhir){
        //     $sa->select(
        //         'saldoawal.tglentry as tanggal',
        //         'saldoawal.kodepsap13',
        //         DB::raw('ifnull(sum(saldoawal.kredit-saldoawal.debit),0) as saldo')
        //     ) ->whereBetween('saldoawal.tglentry', [$awal. ' 00:00:00', $akhir. ' 23:59:59'])
        //     ->groupBy( 'saldoawal.kodepsap13');
        // },'jurnalotom' => function($x) use ($awal,$akhir){
        //     $x->select(
        //         'jurnal_postingotom.kode',
        //         DB::raw('ifnull(sum(jurnal_postingotom.kredit-jurnal_postingotom.debit),0) as totaljurnal'),
        //     )->whereBetween('jurnal_postingotom.tanggal', [$awal, $akhir])
        //     ->where('jurnal_postingotom.verif', '=', '1')
        //     ->where('jurnal_postingotom.uraian', 'LIKE', 'Ekuitas' . '%')
        //     ->groupBy( 'jurnal_postingotom.kode');
        // },
        // 'penyesuaianx' =>  function($sel) use ($awal,$akhir){
        //     $sel->join('jurnalumum_heder', 'jurnalumum_heder.nobukti', 'jurnalumum_rinci.nobukti')
        //     ->select('jurnalumum_rinci.kodepsap13',
        //             'jurnalumum_heder.tanggal',
        //             DB::raw('sum(jurnalumum_rinci.kredit-jurnalumum_rinci.debet) as totalpenyesuaian')
        //             )
        //     ->where('jurnalumum_heder.verif', '=', '1')
        //     ->whereBetween('jurnalumum_heder.tanggal', [$awal, $akhir])
        //     ->where('jurnalumum_heder.keterangan', 'NOT LIKE', 'Reklas Pendapatan' . '%')
        //     ->where('jurnalumum_rinci.uraianpsap13', 'LIKE', 'Ekuitas' . '%')
        //     ->groupBy( 'jurnalumum_rinci.kodepsap13');
        // }])
        // ->where('akun50_2024.kodeall3', 'LIKE', '1.1.12.' . '%')
        // ->orWhere('akun50_2024.kodeall3', 'LIKE', '1.1.10.' . '%')
        // ->orWhere('akun50_2024.kodeall3', 'LIKE', '1.1.04.' . '%')
        // ->orWhere('akun50_2024.kodeall3', 'LIKE', '1.5.03' . '%')
        // ->orWhere('akun50_2024.kodeall3', 'LIKE', '1.5.04' . '%')
        // ->orWhere('akun50_2024.kodeall3', 'LIKE', '1.5.05' . '%')
        // ->orWhere('akun50_2024.kodeall3', 'LIKE', '1.5.06' . '%')
        // ->orWhere('akun50_2024.kodeall3', 'LIKE', '2.1.06' . '%')
        // ->orWhere('akun50_2024.kodeall3', 'LIKE', '2.1.07' . '%')
        // ->orWhere('akun50_2024.kodeall3', 'LIKE', '2.1.05' . '%')
        // ->orWhere('akun50_2024.kodeall3', 'LIKE', '3.1.01' . '%')
        // ->orderBy('akun50_2024.kodeall3', 'asc')
        // ->get();

        $data = [
            'setarakas' => $setarakas,
            'retribusi' => $retribusi,
            'piutang' => $piutang,
            'piutanglain' => $piutanglain,
            // 'penyisihanpiutang' => $penyisihanpiutang,
            // 'persediaan' => $persediaan,
            // 'investasi' => $investasi,
            'aset' => $aset,
            // 'asetlainnya' => $asetlainnya,
            'utang' => $utang,
            // 'utangjkpanjang' => $utangjkpanjang,
            // 'ekuitas' => $ekuitas
        ];
        return new JsonResponse ($data);
    }
}
