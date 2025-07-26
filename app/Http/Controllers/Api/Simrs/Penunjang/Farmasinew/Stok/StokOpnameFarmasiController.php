<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Stok;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Counter;
use App\Models\Simrs\Penunjang\Farmasinew\Stokreal;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokopname;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StokOpnameFarmasiController extends Controller
{
    // stok opname otomatis
    public function storeMonthly()
    {
        $tanggal = request('tahun') . '-' . request('bulan') . '-' . date('d');
        // $today = date('2024-05-01');
        // $yesterday = date('2024-05-01', strtotime('-1 days'));
        // $lastDay = date('2024-05-01', strtotime($today));
        $today = request('tahun') ? $tanggal : date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 days'));
        // $lastDay = date('Y-m-t', strtotime($today)); ga dipake
        $lastDay = date('Y-m-01', strtotime($today));
        $dToday = date_create($today);
        $dLastDay = date_create($lastDay);
        $diff = date_diff($dToday, $dLastDay);

        // return new JsonResponse([
        //     'today' => $today,
        //     'last day' => $lastDay,
        //     'diff' => $diff,
        //     'request' => request()->all(),
        //     // 'recent' => $recent,
        //     // 'awal' => $dataAwal,
        // ], 410);

        if ($diff->d === 0 && $diff->m === 0) {
            // ambil data barang yang ada stoknya di tabel sekarang
            $get = date('Y-m-d H:i:s');
            $recent = Stokreal::where('jumlah', '>', 0)
                // ->with('obat')
                ->get();

            if (count($recent) <= 0) {
                return new JsonResponse([
                    'message' => 'Tidak ada Stok untuk opname',
                    'stok' => $recent
                ], 410);
            }
            $tanggal = $yesterday . ' 23:59:58';
            $newOpname = [];
            foreach ($recent as $key) {
                $item = [
                    'nopenerimaan' => $key->nopenerimaan,
                    'tglpenerimaan' => $key->tglpenerimaan,
                    'kdobat' => $key->kdobat,
                    'jumlah' => $key->jumlah,
                    'kdruang' => $key->kdruang,
                    'harga' => $key->harga,
                    'flag' => $key->flag,
                    'tglexp' => $key->tglexp,
                    'nobatch' => $key->nobatch,
                    'nodistribusi' => $key->nodistribusi,
                    'tglopname' => $tanggal,
                    'created_at' => $get,
                    'updated_at' => date('Y-m-d H:i:s'),

                    // 'tanggal' => $tanggal,
                    // 'kode_rs' => $key->kode_rs,
                    // 'kode_ruang' => $key->kode_ruang,
                    // 'no_penerimaan' => $key->no_penerimaan,
                    // 'sisa_stok' => $key->sisa_stok,
                    // 'harga' => $key->harga !== '' ? $key->harga : 0,
                    // 'satuan' => $key->satuan !== '' ? $key->satuan : 'Belum ada satuan',
                    // 'kode_satuan' => $key->kode_satuan !== '' ? ($key->barang ? $key->barang->kode_satuan : '71') : '71',
                ];
                $newOpname[] = $item;
            }


            if (count($newOpname) > 0) {
                $stoktgl = Stokopname::where('tglopname', $tanggal)->delete();
                // if (count($stoktgl) > 0) {
                //     $stoktgl->delete();
                // }
                foreach (array_chunk($newOpname, 100) as $t) {
                    $data = Stokopname::insert($t);
                }
            }
            
            $rstlai='tidak ada reset';
            $counter=Counter::first();
            if($counter){
                $counter->update([
                    'deporajal'=>0,
                    'deporanap'=>0,
                    'depook'=>0,
                    'depoigd'=>0,
                    'regbebas'=>0,
                    'respbebas'=>0,
                ]);
                $rstlai='reset bulanan';
            }
            $dat=date('d-m');
            if($dat==='01-01'){
                if($counter){
                    $counter->update([
                        'rencblobat'=>0,
                        'pemesanan'=>0,
                        'penerimaanko'=>0,
                        'penerimaanfs'=>0,
                        'permintaandepo'=>0,
                        'returpbf'=>0,
                        'bast'=>0,
                        'persiapanok'=>0,
                        'returpenjualan'=>0,
                        'pemakaianruangan'=>0,
                        'konsinyasi'=>0,
                    ]);
                    $bfr=$rstlai . ' ditambah reset tahunan';
                    $rstlai=$bfr;
                }
            }
            return new JsonResponse([
                'message' => 'data opname farmasi berhasil disimpan',
                'recent' => count($recent),
                'cnewOpname' => count($newOpname),
                // 'newOpname' => $newOpname,
                'stoktgl' => $stoktgl,
                'data' => $data,
                'reset counter' => $rstlai,

            ], 201);

            //end if
        }

        return new JsonResponse([
            'message' => 'Stok opname farmasi dapat dilakukan di hari terakhir tiap bulan',
            'hari ini' => $yesterday
        ], 410);
    }
}
