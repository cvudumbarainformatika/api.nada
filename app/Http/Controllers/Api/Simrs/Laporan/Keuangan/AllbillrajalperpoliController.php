<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Keuangan;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Billing\Rajal\Allbillrajal;
use App\Models\Simrs\Master\Mpoli;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AllbillrajalperpoliController extends Controller
{
    public function allbillperlopi()
    {
        //  $sampai = request('tglsampai') .' 23:59:59';
        $dari = request('tgldari') . ' 00:00:00';
        $sampai = request('tglsampai') . ' 23:59:59';
        // $query = Allbillrajal::select('rs1','rs2','rs3','rs8','rs9')->with([
        //     'relmpoli'
        // ])
        // ->whereBetween('rs17.rs3', [$dari,$sampai])
        // ->get();
        // $query = Mpoli::select('rs1','rs2')->withCount(
        //   [
        //     'jumlahkunjunganpoli'
        //      => function($x) use ($dari,$sampai){
        //         $x->whereBetween('rs3', [$dari,$sampai]
        //         );
        //     }
        //   ]
        // )
        // ->where('rs4','=','Poliklinik')->where('rs5','=','1')
        // ->get();

        $allbillrajal = Allbillrajal::select('rs1', 'rs2', 'rs3', 'rs8', 'rs9', 'rs14')->with([
            'dokter:rs1,rs2',
            'relmpoli:rs1,rs2',
            'msistembayar:rs1,rs2,rs9',
            'apotekrajalpolilaluumum:rs1,rs2,rs3,rs4,rs6,rs8,rs10',
            'apotekracikanrajalumum.relasihederracikan:rs1,rs2,rs8',
            'laborat:id,rs1,rs2,rs3,rs4,rs5,rs6,rs13',
            'laborat.pemeriksaanlab:rs1,rs2,rs21',
            'radiologi',
            'radiologi.reltransrinci:rs1,rs2,rs3,rs4,rs5,rs6,rs7,rs8,rs24',
            'rekammdedikumum:rs1,rs2,rs3,rs5,rs6,rs7,rs8,rs11',
            'tindakanpoliumum:rs1,rs2,rs3,rs4,rs5,rs7,rs13',
            'visiteumum:rs1,rs4,rs5',
            'psikologtransumum:rs1,rs2,rs3,rs5,rs6,rs7,rs13',
            'pendapatanumum:noreg,norm,tgl,total,batal',
            'pendapatanallbpjs:noreg,konsultasi,tenaga_ahli,keperawatan,penunjang,radiologi,Pelayanan_darah,rehabilitasi,kamar,rawat_intensif,obat,alkes,bmhp,sewa_alat,tarif_poli_eks,delete_status,status_klaim'
        ])
            ->whereBetween('rs3', [$dari, $sampai])
            ->where('rs8', '!=', 'POL014')->where('rs8', '!=', 'PEN004')->where('rs8', '!=', 'PEN005')
            ->where('rs19', '=', '1')
            ->get();

        return new JsonResponse($allbillrajal);
    }

    public function billpoli()
    {
        $dari = request('tgldari') . ' 00:00:00';
        $sampai = request('tglsampai') . ' 23:59:59';
        // $dari = '2020-01-01 00:00:00';
        // $sampai = '2020-01-02 23:59:59';
        $data = Mpoli::selectRaw('rs1,rs2,rs4')->where('rs4', 'Poliklinik')->with([
            'kunjungan' => function ($kun) use ($dari, $sampai) {
                $kun->select('rs1', 'rs2', 'rs3', 'rs8', 'rs9', 'rs14')->with(
                    'dokter:rs1,rs2',
                    'relmpoli:rs1,rs2',
                    'msistembayar:rs1,rs2,rs9',
                    'apotekrajalpolilaluumum:rs1,rs2,rs3,rs4,rs6,rs8,rs10',
                    'apotekracikanrajalumum.relasihederracikan:rs1,rs2,rs8',
                    'laborat:id,rs1,rs2,rs3,rs4,rs5,rs6,rs13',
                    'laborat.pemeriksaanlab:rs1,rs2,rs21',
                    'radiologi',
                    'radiologi.reltransrinci:rs1,rs2,rs3,rs4,rs5,rs6,rs7,rs8,rs24',
                    'rekammdedikumum:rs1,rs2,rs3,rs5,rs6,rs7,rs8,rs11',
                    'tindakanpoliumum:rs1,rs2,rs3,rs4,rs5,rs7,rs13',
                    'visiteumum:rs1,rs4,rs5',
                    'psikologtransumum:rs1,rs2,rs3,rs5,rs6,rs7,rs13',
                    'pendapatanumum:noreg,norm,tgl,total,batal'
                )
                    ->whereBetween('rs3', [$dari, $sampai])
                    ->where('rs8', '!=', 'POL014')->where('rs8', '!=', 'PEN004')->where('rs8', '!=', 'PEN005')
                    ->where('rs19', '=', '1');
            }
        ])->get();
        return new JsonResponse($data);
    }
}
