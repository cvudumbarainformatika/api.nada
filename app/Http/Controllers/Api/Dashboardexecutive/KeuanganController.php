<?php

namespace App\Http\Controllers\Api\Dashboardexecutive;

use App\Http\Controllers\Controller;
use App\Models\Agama;
use App\Models\Executive\AnggaranPendapatan;
use App\Models\Executive\DetailPenerimaan;
use App\Models\Executive\HeaderPenerimaan;
use App\Models\Executive\KeuTransPendapatan;
use App\Models\Siasik\Akuntansi\Jurnal\Create_JurnalPosting;
use App\Models\Siasik\Akuntansi\Jurnal\JurnalUmum_Header;
use App\Models\Siasik\TransaksiLS\Contrapost;
use App\Models\Siasik\TransaksiLS\NpkLS_heder;
use App\Models\Siasik\TransaksiPjr\Nihil;
use App\Models\Siasik\TransaksiPjr\SpjPanjar_Header;
use App\Models\Siasik\TransaksiPjr\SPM_GU;
use App\Models\Siasik\TransaksiPjr\SpmUP;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KeuanganController extends Controller
{
    public function pendapatan()
    {
        // $transaksiPendapatan = KeuTransPendapatan::where('noTrans', 'not like', "%TBP-UJ%")
        //     ->whereMonth('tgl', request('month'))
        //     ->whereYear('tgl', request('year'))->sum('nilai');

        // $penerimaan = DetailPenerimaan::hasByNonDependentSubquery('header_penerimaan', function ($q) {
        //     $q->whereYear('rs2', request('year'))
        //         ->where('setor', '=', 'Setor')
        //         ->where(function ($query) {
        //             $query->whereNull('tglBatal')
        //                 ->orWhere('tglBatal', '=', '0000-00-00 00:00:00');
        //         });
        // })->with('header_penerimaan')
        //     ->sum('rs4');

        // $penerimaan2 = DetailPenerimaan::hasByNonDependentSubquery('header_penerimaan', function ($q) {
        //     $q->whereYear('rs2', request('year'))
        //         ->where('setor', '<>', 'Setor')
        //         ->where(function ($query) {
        //             $query->whereNull('tglBatal')
        //                 ->orWhere('tglBatal', '=', '0000-00-00 00:00:00');
        //         })
        //         ->whereHas('keu_trans_setor');
        // })->with('header_penerimaan')
        //     ->sum('rs4');

        // $data = array(
        //     'transaksi_pendapatan' => $transaksiPendapatan,
        //     'penerimaan' => $penerimaan,
        //     'penerimaan2' => $penerimaan2
        // );

        $tgl = request('year') . "-" . "01-01";
        $tglx = request('year') . "-" . request('month') . "-31";

        // PENDAPATAN dari JURNAL
        $datareklas = JurnalUmum_Header::where('jurnalumum_heder.verif', '=', '1')
        ->whereBetween('jurnalumum_heder.tanggal', [$tgl, $tglx])
        ->join('jurnalumum_rinci', 'jurnalumum_rinci.nobukti', 'jurnalumum_heder.nobukti')
        ->select(
                DB::raw('sum(jurnalumum_rinci.kredit-jurnalumum_rinci.debet) as totalpenyesuaian'),
                )
        ->join('akun50_2024', 'akun50_2024.kodeall3', 'jurnalumum_rinci.kodepsap13')
        ->where('jurnalumum_rinci.kodepsap13', 'LIKE', '4.' . '%')
        ->get();
        $penerimaan = Create_JurnalPosting::select(
            DB::raw('sum(jurnal_postingotom.kredit-jurnal_postingotom.debit) as penerimaan'))
        ->whereBetween('jurnal_postingotom.tanggal', [$tgl, $tglx])
        ->where('jurnal_postingotom.kode', 'LIKE', '4.' . '%')
        ->where('jurnal_postingotom.verif', '=', '1')
        ->get();

        $totalpenyesuaian = $datareklas->first()->totalpenyesuaian ?? 0;
        $penerimaan = $penerimaan->first()->penerimaan ?? 0;
        $pendapatan = [
            'penerimaan' => $penerimaan + $totalpenyesuaian
        ];

        // $penerimaan = DB::select("select sum(penerimaan) as penerimaan from (
		// 							select
		// 								tgl,
		// 								noRek,
		// 								concat(ket,' (',noTrans,')') uraian,
		// 								nilai penerimaan,
		// 								0 pengeluaran,
		// 								0 saldo,
		// 								1 urut
		// 							from
		// 								keu_trans_pendapatan
		// 							where
		// 								tgl>='" . $tgl . "'
		// 								and tgl<='" . $tglx . "'
		// 								and noTrans not like '%TBP-UJ%'
		// 							union all

		// 							select
		// 								rs258.rs2 tgl,
		// 								rs258.noRek,
		// 								concat(rs260.ket,' (',rs258.rs1,')') uraian,
		// 								rs260.rs4 penerimaan,
		// 								0 pengeluaran,
		// 								0 saldo,
		// 								1 urut
		// 							from
		// 								rs258,
		// 								rs260
		// 							where
		// 								rs258.rs1=rs260.rs1
		// 								and rs258.rs2>='" . $tgl . "'
		// 								and rs258.rs2<='" . $tglx . "'
		// 								and setor='Setor'
		// 								and (rs258.tglBatal is null or rs258.tglBatal='0000-00-00 00:00:00')
		// 							union all


		// 							select tgl,noRek,uraian,sum(penerimaan) penerimaan,pengeluaran,saldo,urut from (
		// 							select
		// 								keu_trans_setor.noSetor,
		// 								keu_trans_setor.tgl,
		// 								rs258.noRek,
		// 								concat(keu_trans_setor.ket,' (',keu_trans_setor.noSetor,')') uraian,
		// 								rs260.rs4 penerimaan,
		// 								0 pengeluaran,
		// 								0 saldo,
		// 								1 urut
		// 							from
		// 								rs258,
		// 								rs260,
		// 								keu_trans_setor
		// 							where
		// 								rs258.rs1=rs260.rs1
		// 								and keu_trans_setor.noSetor = rs258.noSetor
		// 								and keu_trans_setor.tgl>='" . $tgl . "'
		// 								and keu_trans_setor.tgl<='" . $tglx . "'
		// 								and setor<>'Setor'
		// 								and (rs258.tglBatal is null or rs258.tglBatal='0000-00-00 00:00:00')
		// 							union all



		// 							select
		// 								keu_trans_setor.noSetor,
		// 								keu_trans_setor.tgl,
		// 								keu_trans_setor.noRek,
		// 								concat(ket,' (',keu_trans_setor.noSetor,')') uraian,
		// 								tbp.nilai penerimaan,
		// 								0 pengeluaran,
		// 								0 saldo,
		// 								1 urut
		// 							from
		// 								keu_trans_setor,
		// 								tbp
		// 							where
		// 								tbp.noSetor=keu_trans_setor.noSetor
		// 								and keu_trans_setor.tgl>='" . $tgl . "'
		// 								and keu_trans_setor.tgl<='" . $tglx . "'
		// 								and tbp.setor<>'Setor'
		// 							) as vTunai group by noSetor
		// 							union all
		// 							select
		// 								keu_trans_setor.tgl,
		// 								keu_trans_setor.noRek,
		// 								concat(ket,' (',keu_trans_setor.noSetor,')') uraian,
		// 								tbpuj.nilai penerimaan,
		// 								0 pengeluaran,
		// 								0 saldo,
		// 								1 urut
		// 							from
		// 								keu_trans_setor,
		// 								tbpuj
		// 							where
		// 								tbpuj.noSetor=keu_trans_setor.noSetor
		// 								and keu_trans_setor.tgl>='" . $tgl . "'
		// 								and keu_trans_setor.tgl<='" . $tglx . "'
		// 								and tbpuj.setor<>'Setor'
		// 							union all
		// 							select
		// 								tglTrans tgl,
		// 								noRekPengirim noRek,
		// 								concat(ket,' (',idTrans,')') uraian,
		// 								0 penerimaan,
		// 								nilai pengeluaran,
		// 								0 saldo,
		// 								2 urut
		// 							from
		// 								keu_trans_bk
		// 							where
		// 								tglTrans>='" . $tgl . "'
		// 								and tglTrans<='" . $tglx . "'
		// 								and (batal is null or batal='')
		// 							union all
		// 							select
		// 								tglTrans tgl,
		// 								noRek,
		// 								concat(ket,' (',id,')') uraian,
		// 								0 penerimaan,
		// 								nominal pengeluaran,
		// 								0 saldo,
		// 								2 urut
		// 							from
		// 								keu_bp_pph
		// 							where
		// 								tglTrans>='" . $tgl . "'
		// 								and tglTrans<='" . $tglx . "'
		// 								and (batal is null or batal='')
		// 							union all
		// 							select
		// 								date(tanggalpenerimaan) tgl,
		// 								noRek,
		// 								concat(keterangan,' (',nomorpenerimaan,')') uraian,
		// 								0 penerimaan,
		// 								nominal pengeluaran,
		// 								0 saldo,
		// 								2 urut
		// 							from
		// 								penerimaandaribank
		// 							where
		// 								tanggalpenerimaan>='" . $tgl . "'
		// 								and tanggalpenerimaan<='" . $tglx . "'
		// 						) as vBku order by tgl,urut");

        $targetPendapatan = AnggaranPendapatan::where('tahun', '=', request('year'))->sum('nilai');
        // $realisasiBelanja = DB::connection('siasik')->select(
        //     "select sum(realisasi)-sum(kurangi) as realisasix from(
		// 		select '' as kode,'' as uraian,'' as anggaran,sum(npkls_rinci.total) as realisasi,'' as kurangi
        //         from npkls_rinci,npkls_heder
        //         where npkls_heder.nopencairan=npkls_rinci.nopencairan
        //         and npkls_heder.tglpencairan >= '" . $tgl . "' and npkls_heder.tglpencairan <= '" . $tglx . "'
		// 													   union all
		// 													   select '' as kode,'' as uraian,'' as anggaran,sum(spjpanjar_rinci.jumlahbelanjapanjar) as realisasi,'' as kurangi
		// 													   from spjpanjar_heder,spjpanjar_rinci
		// 													   where spjpanjar_heder.nospjpanjar=spjpanjar_rinci.nospjpanjar and spjpanjar_heder.verif=1
		// 													   and spjpanjar_heder.tglspjpanjar >= '" . $tgl . "' and spjpanjar_heder.tglspjpanjar <= '" . $tglx . "') as total;"
        // );

        // BELANJA
        $anggaranBelanja = DB::connection('siasik')->select(
            "select sum(pagu) as anggaran from t_tampung where tgl= '" . request('year') . "'"
        );

        $pencairanls = NpkLS_heder::select(
            DB::raw('sum(npkls_rinci.total) as pencairanls'))
        ->whereBetween('npkls_heder.tglpindahbuku', [$tgl, $tglx])
        ->join('npkls_rinci', 'npkls_rinci.nonpk', 'npkls_heder.nonpk')
        ->where('npkls_heder.nopencairan', '!=', '')
        ->get();

        $pencairanpanjar = SpjPanjar_Header::select(
            DB::raw('sum(spjpanjar_rinci.jumlahbelanjapanjar) as pencairanpanjar'))
        ->where('spjpanjar_heder.verif', '=', '1')
        ->whereBetween('spjpanjar_heder.tglspjpanjar', [$tgl, $tglx])
        ->join('spjpanjar_rinci', 'spjpanjar_rinci.nospjpanjar', 'spjpanjar_heder.nospjpanjar')
        ->get();

        $contrapost = Contrapost::select(
            DB::raw('sum(nominalcontrapost) as cp'))
        ->whereBetween('tglcontrapost',  [$tgl. ' 00:00:00', $tglx. ' 23:59:59'])
        ->get();

        $ls = $pencairanls->first()->pencairanls ?? 0;
        $panjar = $pencairanpanjar->first()->pencairanpanjar ?? 0;
        $cp = $contrapost->first()->cp ?? 0;
        $realisasiBelanja = [
            'realisasix' => ($ls + $panjar) - $cp
        ];


        // UNTUK KAS BLUD
        $spmUp = SpmUP::select(
            DB::raw('sum(jumlahspp) as spmup'))
        ->whereBetween('tglSpm', [$tgl, $tglx])
        ->get();

        $spmGu = SPM_GU::select(
            DB::raw('sum(jumlahspp) as spmgu'))
        ->whereBetween('tglSpm', [$tgl, $tglx])
        ->get();

        $nihil = Nihil::select(
            DB::raw('sum(jmlpengembalianreal) as nihil'))
        ->whereBetween('tgltrans', [$tgl, $tglx])
        ->get();

        $up = $spmUp->first()->spmup ?? 0;
        $gu = $spmGu->first()->spmgu ?? 0;
        $pengembaliannihil = $nihil->first()->nihil ?? 0;

        $KasBlud = ( $penerimaan + $totalpenyesuaian + $pengembaliannihil) - (($ls + $panjar + $up + $gu) - $cp);


        // UNTUK KAS BENDAHARA PENGELUARAN
        $KasPengeluaran = $up + $gu + $cp - $pengembaliannihil - $panjar;


        // SELECT * FROM table WHERE DATE_FORMAT(column_name,'%Y-%m') = '2021-06'

        $data = array(
            'penerimaan' => $pendapatan,
            'targetPendapatan' => $targetPendapatan,
            'realisasiBelanja' => $realisasiBelanja,
            'anggaranBelanja' => $anggaranBelanja,
            'kasblud' =>round($KasBlud, 2),
            'kaspengeluaran' => round($KasPengeluaran, 2),
        );
        return response()->json($data);
    }
}
