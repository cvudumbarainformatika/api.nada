<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Farmasi\Hutang;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mpihakketiga;
use App\Models\Simrs\Penunjang\Farmasinew\Bast\BastKonsinyasi;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Obatoperasi\PersiapanOperasiDistribusi;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanRinci;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HutangKonsinyasiController extends Controller
{
    //
    public function getHutangKonsinyasix(){
        // hutang konsinyasi adalah barang konsinyasi yang sudah dipakai
        // ambil pihak 3 yang konsinyasi
        $pbf=PenerimaanHeder::select('kdpbf')->where('jenis_penerimaan','Konsinyasi')->distinct()->pluck('kdpbf');
        // ambil pihak 3
        $data=Mpihakketiga::whereIn('kode',$pbf)
        ->paginate(request('per_page'));
        // ambil master barang konsinyasi
        $master=Mobatnew::select('kd_obat')->where('status_konsinyasi','1')->pluck('kd_obat');
        // ambil jumlah distribusi dan kembali, kalo bisa cari yang belum dibayar saja
        // hubungannya ada di rinci, dengan rincian bast yang headernya belum ada tanggal bayar ini dipisah
        $dist=PersiapanOperasiDistribusi::select(
            'persiapan_operasi_distribusis.nopermintaan',
            'persiapan_operasi_distribusis.nopenerimaan',
            'persiapan_operasi_distribusis.kd_obat',
            'penerimaan_r.harga_netto_kecil as harga_net',
            'penerimaan_h.kdpbf',
            'new_masterobat.nama_obat',
            DB::raw('sum(persiapan_operasi_distribusis.jumlah) as jumlah'),
            DB::raw('sum(persiapan_operasi_distribusis.jumlah_retur) as jumlah_retur'),
            DB::raw('sum(persiapan_operasi_distribusis.jumlah - persiapan_operasi_distribusis.jumlah_retur) as dipakai'),
            DB::raw('sum((persiapan_operasi_distribusis.jumlah - persiapan_operasi_distribusis.jumlah_retur) * penerimaan_r.harga_netto_kecil) as sub'),
        )
        ->leftJoin('penerimaan_r',function($jo){
            $jo->on('penerimaan_r.nopenerimaan','=','persiapan_operasi_distribusis.nopenerimaan')
            ->on('penerimaan_r.kdobat','=','persiapan_operasi_distribusis.kd_obat');
        })
        ->leftJoin('penerimaan_h',function($jo){
            $jo->on('penerimaan_h.nopenerimaan','=','penerimaan_r.nopenerimaan');
        })
        ->leftJoin('new_masterobat',function($jo){
            $jo->on('new_masterobat.kd_obat','=','persiapan_operasi_distribusis.kd_obat');
        })
        ->leftJoin('persiapan_operasis',function($jo){
            $jo->on('persiapan_operasis.nopermintaan','=','persiapan_operasi_distribusis.nopermintaan');
        })
        ->leftJoin('persiapan_operasi_rincis',function($jo){
            $jo->on('persiapan_operasi_rincis.nopermintaan','=','persiapan_operasi_distribusis.nopermintaan')
            ->on('persiapan_operasi_rincis.kd_obat','=','persiapan_operasi_distribusis.kd_obat');
        })
        ->whereIn('persiapan_operasi_distribusis.kd_obat',$master)
        ->where('persiapan_operasis.flag','4')
        ->whereNull('persiapan_operasi_rincis.dibayar')
        ->havingRaw('dipakai > 0')
        ->with([
            // 'master:kd_obat,nama_obat',
            'persiapan:nopermintaan,norm',
            'persiapan.pasien:rs1,rs2',

        ])
        ->groupBy('persiapan_operasi_distribusis.nopenerimaan', 'persiapan_operasi_distribusis.kd_obat','persiapan_operasi_distribusis.nopermintaan')
        ->get();
        $list=PersiapanOperasiDistribusi::select(
            'persiapan_operasi_distribusis.nopermintaan',
            'persiapan_operasi_distribusis.nopenerimaan',
            'persiapan_operasi_distribusis.kd_obat',
            // 'penerimaan_r.harga_netto_kecil',
            'detail_bast_konsinyasis.harga_net',
            'penerimaan_h.kdpbf',
            'new_masterobat.nama_obat',
            DB::raw('sum(persiapan_operasi_distribusis.jumlah) as jumlah'),
            DB::raw('sum(persiapan_operasi_distribusis.jumlah_retur) as jumlah_retur'),
            DB::raw('sum(persiapan_operasi_distribusis.jumlah - persiapan_operasi_distribusis.jumlah_retur) as dipakai'),
            DB::raw('sum((persiapan_operasi_distribusis.jumlah - persiapan_operasi_distribusis.jumlah_retur) * detail_bast_konsinyasis.harga_net) as sub'),
        )
        ->leftJoin('penerimaan_r',function($jo){
            $jo->on('penerimaan_r.nopenerimaan','=','persiapan_operasi_distribusis.nopenerimaan')
            ->on('penerimaan_r.kdobat','=','persiapan_operasi_distribusis.kd_obat');
        })
        ->leftJoin('penerimaan_h',function($jo){
            $jo->on('penerimaan_h.nopenerimaan','=','penerimaan_r.nopenerimaan');
        })
        ->leftJoin('new_masterobat',function($jo){
            $jo->on('new_masterobat.kd_obat','=','persiapan_operasi_distribusis.kd_obat');
        })
        ->leftJoin('persiapan_operasis',function($jo){
            $jo->on('persiapan_operasis.nopermintaan','=','persiapan_operasi_distribusis.nopermintaan');
        })
        ->leftJoin('persiapan_operasi_rincis',function($jo){
            $jo->on('persiapan_operasi_rincis.nopermintaan','=','persiapan_operasi_distribusis.nopermintaan')
            ->on('persiapan_operasi_rincis.kd_obat','=','persiapan_operasi_distribusis.kd_obat');
        })
        ->leftJoin('detail_bast_konsinyasis',function($jo){
            $jo->on('detail_bast_konsinyasis.nopermintaan','=','persiapan_operasi_rincis.nopermintaan')
            ->on('detail_bast_konsinyasis.kdobat','=','persiapan_operasi_rincis.kd_obat')
            ->on('detail_bast_konsinyasis.noresep','=','persiapan_operasi_rincis.noresep');
        })
        ->leftJoin('bast_konsinyasis',function($jo){
            $jo->on('bast_konsinyasis.notranskonsi','=','detail_bast_konsinyasis.notranskonsi');
        })
        ->whereIn('persiapan_operasi_distribusis.kd_obat',$master)
        ->where('persiapan_operasis.flag','4')
        ->whereNotNull('persiapan_operasi_rincis.dibayar')
        ->whereNull('bast_konsinyasis.tgl_pembayaran')
        ->havingRaw('dipakai > 0')
        ->with([
            'persiapan:nopermintaan,norm',
            'persiapan.pasien:rs1,rs2',

        ])
        ->groupBy('nopenerimaan', 'kd_obat','nopermintaan')
        ->get();

        // // cari bast untuk cari jumlah dimintakan faktur dan bast
        // $kdp = collect($data->items())->pluck('kode');
        // $bastkonsi=BastKonsinyasi::with('rinci')->whereIn('kdpbf',$kdp)->get();

        // // get jumlah penerimaan
        // $nopen=$dist->pluck('nopenerimaan');
        // $trm=PenerimaanRinci::select(
        //     'penerimaan_h.kdpbf',
        //     'penerimaan_r.nopenerimaan',
        //     DB::raw('sum(penerimaan_r.subtotal) as total')
        // )
        // ->leftJoin('penerimaan_h','penerimaan_h.nopenerimaan','=','penerimaan_r.nopenerimaan')
        // ->whereIn('penerimaan_r.nopenerimaan',$nopen)
        // ->groupBy('penerimaan_r.nopenerimaan')
        // ->get();

        $datanya=collect($data)['data'];
        $meta=collect($data)->except('data');
        return new JsonResponse([
            'raw'=>$data,
            'data'=>$datanya,
            'meta'=>$meta,
            // 'bastkonsi'=>$bastkonsi,
            'dist'=>$dist,
            'list'=>$list,
            // 'trm'=>$trm,
            'req'=>request()->all(),
        ]);
    }

    public function getHutangKonsinyasi()
    {
        $dari = request('tgldari') ;
        $sampai = request('tglsampai') ;
        $data = BastKonsinyasi::with(
            [
                'rinci' => function($penerimaanrinci){
                    $penerimaanrinci->with(['obat']);
                },
                'penyedia'
            ]
        )
        ->whereBetween('tgl_bast',[$dari,$sampai])
        ->get();

        return new JsonResponse($data);
    }
}
