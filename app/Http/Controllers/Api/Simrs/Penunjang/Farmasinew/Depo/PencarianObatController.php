<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Depo;

use App\Helpers\QueryHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PencarianObatController extends Controller
{
    public function pencarianObatResep()
    // {
    //     try {
    //         $kdruang = request('kdruang');
    //         $q = request('q');
    //         $groupsistembayar = request('groups');
    //         $tiperesep = request('tiperesep');
            
    //         // Prepare sistembayar array once
    //         $sistembayar = ((int)$groupsistembayar === 1) ? ['SEMUA', 'BPJS'] : ['SEMUA', 'UMUM'];
            
    //         // Build efficient base query
    //         $query = Mobatnew::from('new_masterobat as mo')
    //             ->select([
    //                 'mo.kd_obat',
    //                 'mo.nama_obat as namaobat',
    //                 'mo.kandungan',
    //                 'mo.bentuk_sediaan',
    //                 'mo.satuan_k as satuankecil',
    //                 'mo.status_fornas as fornas',
    //                 'mo.status_forkid as forkit',
    //                 'mo.status_generik as generik',
    //                 'mo.status_kronis as kronis',
    //                 'mo.status_prb as prb',
    //                 'mo.kode108',
    //                 'mo.uraian108',
    //                 'mo.kode50',
    //                 'mo.uraian50',
    //                 'mo.kekuatan_dosis as kekuatandosis',
    //                 'mo.volumesediaan',
    //                 'mo.kelompok_psikotropika as psikotropika',
    //                 'mo.jenis_perbekalan',
    //                 DB::raw('COALESCE(SUM(stokreal.jumlah), 0) as total')
    //             ])
    //             ->whereIn('mo.sistembayar', $sistembayar)
    //             ->where(function($query) use ($q) {
    //                 $query->where('mo.nama_obat', 'LIKE', "%{$q}%");
    //                 // $query->where('new_masterobat.nama_obat', 'LIKE', "{$q}%")
    //                 //       ->orWhere('new_masterobat.kandungan', 'LIKE', "{$q}%");
    //             });

    //         // Add type-specific filters
    //         // if ($tiperesep === 'prb') {
    //         //     $query->where('new_masterobat.status_prb', '!=', '');
    //         // } elseif ($tiperesep === 'iter') {
    //         //     $query->where('new_masterobat.status_kronis', '!=', '');
    //         // }

    //         $query->when($tiperesep === 'prb', function ($q) {
    //             $q->where('mo.status_prb', '!=', '');
    //         })->when($tiperesep === 'iter', function ($q) {
    //             $q->where('mo.status_kronis', '!=', '');
    //         });

    //         // Optimize joins
    //         $query->leftJoin('stokreal', function($join) use ($kdruang) {
    //             $join->on('mo.kd_obat', '=', 'stokreal.kdobat')
    //                  ->where('stokreal.kdruang', '=', $kdruang);
    //         });

    //         // Add efficient relationships
    //         $result = $query->with([
    //             'onepermintaandeporinci' => function($q) use ($kdruang) {
    //                 $q->select(
    //                     'permintaan_r.kdobat',
    //                     DB::raw('COALESCE(SUM(permintaan_r.jumlah_minta), 0) as jumlah_minta')
    //                 )
    //                 ->join('permintaan_h', 'permintaan_h.no_permintaan', '=', 'permintaan_r.no_permintaan')
    //                 ->where('permintaan_h.tujuan', $kdruang)
    //                 ->whereIn('permintaan_h.flag', ['', '1', '2'])
    //                 ->groupBy('permintaan_r.kdobat');
    //             },
    //             'oneperracikan' => function($q) use ($kdruang) {
    //                 $q->select(
    //                     'resep_permintaan_keluar_racikan.kdobat',
    //                     DB::raw('COALESCE(SUM(CASE WHEN resep_keluar_racikan_r.jumlah is null THEN resep_permintaan_keluar_racikan.jumlah ELSE 0 END), 0) as jumlah')
    //                 )
    //                 ->join('resep_keluar_h', 'resep_keluar_h.noresep', '=', 'resep_permintaan_keluar_racikan.noresep')
    //                 ->leftJoin('resep_keluar_racikan_r', function($join) {
    //                     $join->on('resep_keluar_racikan_r.noresep', '=', 'resep_permintaan_keluar_racikan.noresep')
    //                         ->on('resep_keluar_racikan_r.kdobat', '=', 'resep_permintaan_keluar_racikan.kdobat');
    //                 })
    //                 ->where('resep_keluar_h.depo', $kdruang)
    //                 ->whereIn('resep_keluar_h.flag', ['', '1', '2'])
    //                 ->groupBy('resep_permintaan_keluar_racikan.kdobat');
    //             },
    //             'onepermintaan' => function($q) use ($kdruang) {
    //                 $q->select(
    //                     'resep_permintaan_keluar.kdobat',
    //                     DB::raw('COALESCE(SUM(CASE WHEN resep_keluar_r.jumlah is null THEN resep_permintaan_keluar.jumlah ELSE 0 END), 0) as jumlah')
    //                 )
    //                 ->join('resep_keluar_h', 'resep_keluar_h.noresep', '=', 'resep_permintaan_keluar.noresep')
    //                 ->leftJoin('resep_keluar_r', function($join) {
    //                     $join->on('resep_keluar_r.noresep', '=', 'resep_permintaan_keluar.noresep')
    //                         ->on('resep_keluar_r.kdobat', '=', 'resep_permintaan_keluar.kdobat');
    //                 })
    //                 ->where('resep_keluar_h.depo', $kdruang)
    //                 ->whereIn('resep_keluar_h.flag', ['', '1', '2'])
    //                 ->groupBy('resep_permintaan_keluar.kdobat');
    //             }
    //         ])
    //         ->groupBy('mo.kd_obat')
    //         ->orderByDesc('total')
    //         ->limit(20)
    //         ->get();

    //         // Log::info($query->toSql());
    //         // Log::info($query->getBindings());

    //         // Efficient transformation with null checks
    //         $wew = $result->map(function($item) {
    //             $total = $item->total ?? 0;
    //             $jumlahtransx = $item->oneperracikan ? collect($item->oneperracikan)->sum('jumlah') : 0;
    //             $jumlahtrans = $item->onepermintaan ? collect($item->onepermintaan)->sum('jumlah') : 0;
    //             $permintaanobatrinci = $item->onepermintaandeporinci ? collect($item->onepermintaandeporinci)->sum('jumlah_minta') : 0;
                
    //             $alokasi = max(0, $total - $jumlahtransx - $jumlahtrans - $permintaanobatrinci);
                
    //             $item->alokasi = $alokasi;
    //             return $item;
    //         });

    //         return new JsonResponse(['dataobat' => $wew]);
            
    //     } catch (\Exception $e) {
    //         Log::error('Pencarian Obat Error: ' . $e->getMessage(), [
    //             'kdruang' => request('kdruang'),
    //             'query' => request('q'),
    //             'trace' => $e->getTraceAsString()
    //         ]);
            
    //         return response()->json([
    //             'error' => 'Terjadi kesalahan dalam pencarian obat',
    //             'message' => $e->getMessage()
    //         ], 500);
    //     }
    // }
    // {

    //     $kdruang = request('kdruang');
    //     $q = request('q');
    //     $groupsistembayar = request('groups');
    //     $tiperesep = request('tiperesep');
        
    //     // Prepare sistembayar array once
    //     $sistembayar = ((int)$groupsistembayar === 1) ? ['SEMUA', 'BPJS'] : ['SEMUA', 'UMUM'];

    //     $results = DB::connection('farmasi')->table('new_masterobat as mo')
    //     ->selectRaw('
    //         mo.kd_obat,
    //         mo.nama_obat AS namaobat,
    //         mo.kandungan,
    //         mo.bentuk_sediaan,
    //         mo.satuan_k AS satuankecil,
    //         mo.status_fornas AS fornas,
    //         mo.status_forkid AS forkit,
    //         mo.status_generik AS generik,
    //         mo.status_kronis AS kronis,
    //         mo.status_prb AS prb,
    //         mo.kode108,
    //         mo.uraian108,
    //         mo.kode50,
    //         mo.uraian50,
    //         mo.kekuatan_dosis AS kekuatandosis,
    //         mo.volumesediaan,
    //         mo.kelompok_psikotropika AS psikotropika,
    //         mo.jenis_perbekalan,

    //         COALESCE(SUM(sr.jumlah), 0) AS total,
    //         COALESCE(SUM(pr.jumlah_minta), 0) AS jumlah_minta_depo,
    //         COALESCE(SUM(CASE WHEN rkr.jumlah IS NULL THEN rpkr.jumlah ELSE 0 END), 0) AS jumlah_terpakai_racikan,
    //         COALESCE(SUM(CASE WHEN rkrx.jumlah IS NULL THEN rpk.jumlah ELSE 0 END), 0) AS jumlah_terpakai_permintaan,

    //         GREATEST(
    //             COALESCE(SUM(sr.jumlah), 0)
    //             - COALESCE(SUM(CASE WHEN rkr.jumlah IS NULL THEN rpkr.jumlah ELSE 0 END), 0)
    //             - COALESCE(SUM(CASE WHEN rkrx.jumlah IS NULL THEN rpk.jumlah ELSE 0 END), 0)
    //             - COALESCE(SUM(pr.jumlah_minta), 0),
    //             0
    //         ) AS alokasi
    //     ')
    //     ->leftJoin('stokreal as sr', function ($join) use ($kdruang) {
    //         $join->on('mo.kd_obat', '=', 'sr.kdobat')
    //             ->where('sr.kdruang', '=', $kdruang);
    //     })
    //     ->leftJoin('permintaan_r as pr', 'mo.kd_obat', '=', 'pr.kdobat')
    //     ->leftJoin('permintaan_h as ph', function ($join) use ($kdruang) {
    //         $join->on('pr.no_permintaan', '=', 'ph.no_permintaan')
    //             ->where('ph.tujuan', '=', $kdruang)
    //             ->whereIn('ph.flag', ['', '1', '2']);
    //     })
    //     ->leftJoin('resep_permintaan_keluar_racikan as rpkr', 'mo.kd_obat', '=', 'rpkr.kdobat')
    //     ->leftJoin('resep_keluar_h as rkh', function ($join) use ($kdruang) {
    //         $join->on('rpkr.noresep', '=', 'rkh.noresep')
    //             ->where('rkh.depo', '=', $kdruang)
    //             ->whereIn('rkh.flag', ['', '1', '2']);
    //     })
    //     ->leftJoin('resep_keluar_racikan_r as rkr', function ($join) use ($kdruang) {
    //         $join->on('rkr.noresep', '=', 'rpkr.noresep')
    //             ->on('rkr.kdobat', '=', 'rpkr.kdobat');
    //     })
    //     ->leftJoin('resep_permintaan_keluar as rpk', 'mo.kd_obat', '=', 'rpk.kdobat')
    //     ->leftJoin('resep_keluar_r as rkrx', function ($join) use ($kdruang) {
    //         $join->on('rkrx.noresep', '=', 'rpk.noresep')
    //             ->on('rkrx.kdobat', '=', 'rpk.kdobat');
    //     })
    //     ->leftJoin('resep_keluar_h as rkhx', function ($join) use ($kdruang) {
    //         $join->on('rkhx.noresep', '=', 'rpk.noresep')
    //             ->where('rkhx.depo', '=', $kdruang)
    //             ->whereIn('rkhx.flag', ['', '1', '2']);
    //     })
    //     ->whereIn('mo.sistembayar', ['SEMUA', 'BPJS'])
    //     ->where(function ($query) use ($q) {
    //         $query->where('mo.nama_obat', 'like', "%{$q}%");
    //             // ->orWhere('mo.kandungan', 'like', 'para%');
    //     })
    //     ->groupBy('mo.kd_obat')
    //     ->orderByDesc('alokasi')
    //     ->limit(20)
    //     ->get();
    //     return new JsonResponse(['dataobat' => $results]);
    // }

    {
        $kdruang = request('kdruang');
        $q = request('q');
        $groupsistembayar = request('groups');
        $tiperesep = request('tiperesep');
        
        $sistembayar = ((int)$groupsistembayar === 1) ? ['SEMUA', 'BPJS'] : ['SEMUA', 'UMUM'];
        
        

        if (!preg_match('/^[A-Za-z0-9\-]+$/', $kdruang)) {
            // Validasi gagal, tangani dengan cara yang sesuai
            // throw new Exception('Invalid kdruangan format');
            return response()->json([
                'error' => 'Terjadi kesalahan dalam pencarian obat',
                'message' => 'Invalid kdruangan format'
            ], 500);
        }
        $result = DB::table('farmasi.new_masterobat as mo')
            ->select(DB::raw('
                mo.kd_obat,
                mo.kd_obat AS kdobat,
                mo.nama_obat AS namaobat,
                mo.kandungan,
                mo.bentuk_sediaan,
                mo.satuan_k AS satuankecil,
                mo.status_fornas AS fornas,
                mo.status_forkid AS forkit,
                mo.status_generik AS generik,
                mo.status_kronis AS kronis,
                mo.status_prb AS prb,
                mo.kode108,
                mo.uraian108,
                mo.kode50,
                mo.uraian50,
                mo.kekuatan_dosis AS kekuatandosis,
                mo.volumesediaan,
                mo.kelompok_psikotropika AS psikotropika,
                mo.jenis_perbekalan,
                COALESCE(SUM(stokreal.jumlah), 0) AS total,
                COALESCE(SUM(stokreal.jumlah), 0)
                - COALESCE(racikan.jumlah_racikan, 0)
                - COALESCE(permintaan.jumlah_permintaan, 0)
                - COALESCE(permintaan_depo.jumlah_permintaan_depo, 0) AS alokasi
            '))
            ->leftJoin('farmasi.stokreal', function($join) use ($kdruang) {
                $join->on('mo.kd_obat', '=', 'stokreal.kdobat')
                     ->where('stokreal.kdruang', '=', $kdruang);
            })
            // ini urutan where jangan dirubah karena sudah terindex yaaaa
            ->leftJoin(DB::raw("
                (SELECT rpr.kdobat, SUM(rpr.jumlah) AS jumlah_racikan
                FROM farmasi.resep_permintaan_keluar_racikan rpr
                JOIN farmasi.resep_keluar_h rh ON rh.noresep = rpr.noresep
                WHERE rh.depo = '{$kdruang}'
                AND rh.flag IN ('', '1', '2')
                GROUP BY rpr.kdobat) racikan
            "), 'racikan.kdobat', '=', 'mo.kd_obat')
            ->leftJoin(DB::raw("
                (SELECT rp.kdobat, SUM(rp.jumlah) AS jumlah_permintaan
                FROM farmasi.resep_permintaan_keluar rp
                JOIN farmasi.resep_keluar_h rh ON rh.noresep = rp.noresep
                WHERE rh.depo = '{$kdruang}'
                AND rh.flag IN ('', '1', '2')
                GROUP BY rp.kdobat) permintaan
            "), 'permintaan.kdobat', '=', 'mo.kd_obat')
            ->leftJoin(DB::raw("
                (SELECT pr.kdobat, SUM(pr.jumlah_minta) AS jumlah_permintaan_depo
                FROM farmasi.permintaan_r pr
                JOIN farmasi.permintaan_h ph ON ph.no_permintaan = pr.no_permintaan
                WHERE ph.tujuan = '{$kdruang}'
                AND ph.flag IN ('', '1', '2')
                GROUP BY pr.kdobat) permintaan_depo
            "), 'permintaan_depo.kdobat', '=', 'mo.kd_obat')
            ->whereIn('mo.sistembayar', $sistembayar)
            ->where(function ($query) use ($q) {
                $query->where('mo.nama_obat', 'like', "%{$q}%")
                    ->orWhere('mo.kandungan', 'like', "%{$q}%");
            })
            ->when($tiperesep === 'prb', fn($q) => $q->where('mo.status_prb', '!=', ''))
            ->when($tiperesep === 'iter', fn($q) => $q->where('mo.status_kronis', '!=', ''))
            ->groupBy(
                'mo.kd_obat'
            )
            ->orderByDesc('total')
            ->limit(20)
            ->get();



        




        
        // $sql = QueryHelper::getSqlWithBindings($query);
        // Log::info('Query: ' . $sql);


        return new JsonResponse(['dataobat' => $result]);
    }
}
