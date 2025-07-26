<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Farmasi\Billing;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mpihakketiga;
use App\Models\Simrs\Penunjang\Farmasi\Apotekrajal;
use App\Models\Simrs\Penunjang\Farmasi\Apotekrajallalu;
use App\Models\Simrs\Penunjang\Farmasi\Apotekrajalracikanheder;
use App\Models\Simrs\Penunjang\Farmasi\Apotekrajalracikanhedlalu;
use App\Models\Simrs\Penunjang\Farmasi\Apotekrajalretur;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class BillingFarmasiController extends Controller
{
    public static function farmasi($noreg)
    {
        $nonracikan = Apotekrajal::where('rs1', $noreg)->get();
        $nonracikanlalu = Apotekrajallalu::where('rs1', $noreg)->get();

        $racikan = Apotekrajalracikanheder::select(DB::raw('((rs92.rs7*rs92.rs5)+rs91.rs8) as subtotal'))
            ->join('rs92', 'rs91.rs1', 'rs92.rs1')
            ->where('rs91.rs1', $noreg)
            ->get();
        $racikanlalu = Apotekrajalracikanhedlalu::select(DB::raw('((rs164.rs7*rs164.rs5)+rs163.rs8) as subtotal'))
            ->join('rs164', 'rs163.rs1', 'rs164.rs1')
            ->where('rs164.rs1', $noreg)
            ->get();
        $retur = Apotekrajalretur::select(DB::raw('(rs88.rs3*rs88.rs4) as subtotal'))
            ->where('rs88.rs1', $noreg)
            ->get();

        $obat = $nonracikan->sum('subtotal') + $nonracikanlalu->sum('subtotal') + $racikan->sum('subtotal') + $racikanlalu->sum('subtotal') - $retur->sum('subtotal');
        return $obat;
    }

    public static function farmasinew($noreg)
    {
        $nonracikan = Resepkeluarheder::select(
        DB::raw('round((resep_keluar_r.jumlah*resep_keluar_r.harga_jual+resep_keluar_r.nilai_r)) as subtotal'))
        ->join('resep_keluar_r', 'resep_keluar_r.noresep', 'resep_keluar_h.noresep')
        ->where('resep_keluar_h.noreg', $noreg)
        ->get();

        $racikan = Resepkeluarheder::select(
        DB::raw('round((resep_keluar_racikan_r.jumlah*resep_keluar_racikan_r.harga_jual)) as subtotal'))
        ->join('resep_keluar_racikan_r', 'resep_keluar_racikan_r.noresep', 'resep_keluar_h.noresep')
        ->where('resep_keluar_h.noreg', $noreg)
        ->get();

        $racikan_R = Resepkeluarheder::select(
            DB::raw('resep_keluar_racikan_r.nilai_r as subtotal'))
            ->join('resep_keluar_racikan_r', 'resep_keluar_racikan_r.noresep', 'resep_keluar_h.noresep')
            ->where('resep_keluar_h.noreg', $noreg)
            ->groupBy('resep_keluar_h.noresep')
            ->get();

        $farmasi = $nonracikan->sum('subtotal')+$racikan->sum('subtotal')+$racikan_R->sum('subtotal');
        return $farmasi;
    }

    public function gettagihanpasien()
    {
        // // $idpegawai = auth()->user()->pegawai_id;
        // // $kodegudang = Pegawai::find($idpegawai);
        // $kodegudang = request('gudang');
        // $supl = [];
        // if (request('cari')) {
        //     $supl = Mpihakketiga::select('kode')->where('nama', 'Like', '%' . request('cari') . '%')->pluck('kode');

        //     // $supl = collect($temp)->map(function ($item, $key) {
        //     //     return $item->kode;
        //     // });
        // }
        // $listpenerimaan = PenerimaanHeder::select(
        //     'nopenerimaan',
        //     'nopemesanan',
        //     'tglpenerimaan',
        //     'kdpbf',
        //     'gudang',
        //     'pengirim',
        //     'jenissurat',
        //     'nomorsurat',
        //     'tglsurat',
        //     'batasbayar',
        //     'jenis_penerimaan',
        //     'kunci',
        //     'total_faktur_pbf as total',
        // )
        //     // ->leftJoin('siasik.pihak_ketiga', 'siasik.pihak_ketiga.kode', 'penerimaan_h.kdpbf')
        //     ->when(request('gudang'),function($q){
        //         $q->where('gudang', '=', request('gudang'));
        //     })
        //     ->when(count($supl) > 0, function ($e) use ($supl) {
        //         $e->whereIn('kdpbf', $supl);
        //     })
        //     ->when(count($supl) <= 0, function ($e) use ($supl) {
        //         $e->where(function ($qu) {
        //             $qu->where('nopemesanan', 'Like', '%' . request('cari') . '%')
        //                 ->orWhere('nopenerimaan', 'Like', '%' . request('cari') . '%')
        //                 // ->orWhere('tglpenerimaan', 'Like', '%' . request('cari') . '%')
        //                 ->orWhere('pengirim', 'Like', '%' . request('cari') . '%')
        //                 ->orWhere('jenissurat', 'Like', '%' . request('cari') . '%')
        //                 ->orWhere('nomorsurat', 'Like', '%' . request('cari') . '%');
        //         });
        //     })
        //     ->when(request('jenispenerimaan'),function($q){
        //         $q->where('jenis_penerimaan',request('jenispenerimaan'));
        //     })
        //     ->when(request('from'), function($q){
        //         $q->whereBetween('tglpenerimaan',[request('from').' 00:00:00', request('to').' 23:59:59']);
        //     })
        //     ->with([
        //         'penerimaanrinci',
        //         'penerimaanrinci.masterobat',
        //         'pihakketiga:kode,nama',
        //         'faktur'
        //     ])
        //     ->orderBy('tglpenerimaan', 'desc')
        //     ->paginate(request('per_page'));
        // return new JsonResponse([
        //     'data' => $listpenerimaan,
        //     'req' => request()->all(),
        //     'kode'=>$supl
        // ]);
        $cekresep = Resepkeluarheder::select('*')
            // ->where('noreg', 'like', '%%51740/07/2024/J%')
            ->where('sistembayar', 'not like', '%BPJS%')
            ->where('sistembayar', '!=', 'UMUM')
            ->when(request('from'), function($q){
                $q->whereBetween('tgl_permintaan',[request('from').' 00:00:00', request('to').' 23:59:59']);
            })
            ->with([
                'rincian.mobat:kd_obat,nama_obat,satuan_k,status_kronis',
                'rincianracik.mobat:kd_obat,nama_obat,satuan_k,status_kronis',
                'datapasien',
                'poli',
                'ruanganranap',
                'sistembayar'
            ])
            ->groupBy('noresep')
            ->paginate(request('per_page'));

            return new JsonResponse([
            'data' => $cekresep,
            'req' => request()->all()
        ]);
    }
}
