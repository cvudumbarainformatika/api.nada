<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Farmasi\Hutang;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mpihakketiga;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MutasiHutangObat extends Controller
{
    public function reportMutasiHutangObat()
    {
        $dari = request('tgldari');

        $tgldari = request('tgldari') ;
        $tglsampai = request('tglsampai');

    $pbf=PenerimaanHeder::select('kdpbf')->distinct()->pluck('kdpbf');
    $data = Mpihakketiga::with([
        'penerimaanobat' => function($penerimaanobat) use($dari){
            $penerimaanobat->select('farmasi.penerimaan_h.nopenerimaan','farmasi.penerimaan_h.tglpenerimaan',
            'farmasi.penerimaan_h.nomorsurat','farmasi.penerimaan_h.kdpbf','farmasi.penerimaan_h.gudang',
            'farmasi.penerimaan_h.jenis_penerimaan')
            ->with([
                'penerimaanrinci'
            ])
            ->whereDate('farmasi.penerimaan_h.tglpenerimaan','<', $dari)
            ->where('farmasi.penerimaan_h.jenis_penerimaan','Pesanan');
        },
        'penerimaanobatkonsinyasi' => function($penerimaanobatkonsinyasi) use($dari){
            $penerimaanobatkonsinyasi->select('farmasi.bast_konsinyasis.notranskonsi','farmasi.bast_konsinyasis.nobast',
            'farmasi.bast_konsinyasis.kdpbf','farmasi.bast_konsinyasis.tgl_bast')
            ->with([
                'rinci'
            ])
            ->whereDate('farmasi.bast_konsinyasis.tgl_bast','<', $dari)
            ->where('farmasi.bast_konsinyasis.nobast','!=','')
            ->whereNull('farmasi.bast_konsinyasis.flag_bayar');
        },
        'penerimaanobatperiodeskrng' => function($penerimaanobatperiodeskrng) use($tgldari, $tglsampai){
            $penerimaanobatperiodeskrng->select('farmasi.penerimaan_h.nopenerimaan','farmasi.penerimaan_h.tglpenerimaan',
            'farmasi.penerimaan_h.nomorsurat','farmasi.penerimaan_h.kdpbf','farmasi.penerimaan_h.gudang','farmasi.penerimaan_h.jenis_penerimaan')
            ->with([
                'penerimaanrinci'
            ])
            ->whereDate('farmasi.penerimaan_h.tglpenerimaan','>=', $tgldari)
            ->whereDate('farmasi.penerimaan_h.tglpenerimaan','<=', $tglsampai)
            ->where('farmasi.penerimaan_h.jenis_penerimaan','Pesanan');
        },
        'penerimaanobatkonsinyasiperiodeskrng' => function($penerimaanobatkonsinyasiperiodeskrng) use($tgldari, $tglsampai){
            $penerimaanobatkonsinyasiperiodeskrng->select('farmasi.bast_konsinyasis.notranskonsi','farmasi.bast_konsinyasis.nobast',
            'farmasi.bast_konsinyasis.kdpbf','farmasi.bast_konsinyasis.tgl_bast')
            ->with([
                'rinci'
            ])
            ->whereDate('farmasi.bast_konsinyasis.tgl_bast','>=', $tgldari)
            ->whereDate('farmasi.bast_konsinyasis.tgl_bast','<=', $tglsampai)
            ->where('farmasi.bast_konsinyasis.nobast','!=','')
            ->whereNull('farmasi.bast_konsinyasis.flag_bayar');
        }
    ])
    ->whereIn('kode', $pbf)
    ->get();
        return new JsonResponse($data);
    }
}
