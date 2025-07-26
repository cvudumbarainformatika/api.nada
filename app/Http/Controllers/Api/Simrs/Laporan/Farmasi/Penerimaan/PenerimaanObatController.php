<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Farmasi\Penerimaan;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Pemesanan\PemesananHeder;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PenerimaanObatController extends Controller
{
    public function caripenerimaanobat()
    {
        $dari = request('tgldari') ;
        $sampai = request('tglsampai') ;

        $cari = PenerimaanHeder::with(
            [
                'pihakketiga',
                'gudang'
            ]
        )->when(request('gudang') !== 'all', function ($q) {
            $q->where('gudang', request('gudang'));
        })->when(request('jenispenerimaan') !== 'all', function ($q) {
            $q->where('jenis_penerimaan', request('jenispenerimaan'));
        })->when(request('pihakketiga') !== 'all', function ($q) {
            $q->where('kdpbf', request('pihakketiga'));
        })
        ->whereBetween('tglpenerimaan', [$dari, $sampai])
        ->get();

        return new JsonResponse($cari);
    }

    public function caripenerimaanobatrinci()
    {
        $dari = request('tgldari') ;
        $sampai = request('tglsampai') ;

        $cari = PenerimaanHeder::leftjoin('penerimaan_r','penerimaan_r.nopenerimaan','penerimaan_h.nopenerimaan')
        ->leftjoin('new_masterobat','penerimaan_r.kdobat','new_masterobat.kd_obat')
        ->with(
            [
                'pihakketiga',
                'gudang'
            ]
        )->when(request('gudang') !== 'all', function ($q) {
            $q->where('gudang', request('gudang'));
        })->when(request('pihakketiga') !== 'all', function ($q) {
            $q->where('kdpbf', request('pihakketiga'));
        })->when(request('rekeningbelanja') !== 'all', function($q){
            $q->where('new_masterobat.kode108',request('rekeningbelanja'));
        })
        ->whereBetween('tglpenerimaan', [$dari, $sampai])
        ->get();

        return new JsonResponse($cari);
    }
}
