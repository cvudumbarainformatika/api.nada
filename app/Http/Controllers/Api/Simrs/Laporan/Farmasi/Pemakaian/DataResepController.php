<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Farmasi\Pemakaian;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DataResepController extends Controller
{
    public function getDataResep()
    {

        $obat = Mobatnew::select(
            'kd_obat',
            'nama_obat',
            'status_forkid as forkit',
            'status_fornas as fornas',
            'status_generik as generik',
        )
            ->with([
                'resepkeluar' => function ($kel) {
                    $kel->select(
                        'resep_keluar_r.kdobat',
                        'resep_keluar_h.noresep',
                        'resep_keluar_h.norm',
                        'resep_keluar_h.tiperesep',
                        'resep_keluar_h.tgl_selesai',
                        'resep_keluar_h.dokter',
                        DB::raw('sum(resep_keluar_r.jumlah) as jumlah'),
                    )
                        ->with('dokter:nama,kdpegsimrs')
                        ->leftJoin('resep_keluar_h', 'resep_keluar_h.noresep', '=', 'resep_keluar_r.noresep')
                        // ->where('resep_keluar_h.tgl_selesai', 'LIKE', '%' . request('tahun') . '-' . request('bulan') . '%')
                        ->whereBetween('resep_keluar_h.tgl_selesai', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
                        ->when(request('tipe') != 'all', function ($q) {
                            $q->where('resep_keluar_h.tiperesep', request('tipe'));
                        })
                        ->when(request('kode_ruang') != 'all', function ($q) {
                            $q->where('resep_keluar_h.depo', request('kode_ruang'));
                        })
                        ->when(
                            request('jenis') == 'detail',
                            function ($q) {
                                $q->groupBy('resep_keluar_h.noresep', 'resep_keluar_r.kdobat');
                            },
                            function ($q) {
                                $q->groupBy('resep_keluar_r.kdobat');
                            }
                        )
                        ->orderBy('resep_keluar_h.tgl_selesai', 'asc');
                },

                'resepkeluarracikan' => function ($kel) {
                    $kel->select(
                        'resep_keluar_racikan_r.kdobat',
                        'resep_keluar_h.noresep',
                        'resep_keluar_h.norm',
                        'resep_keluar_h.tiperesep',
                        'resep_keluar_h.tgl_selesai',
                        'resep_keluar_h.dokter',
                        DB::raw('sum(resep_keluar_racikan_r.jumlah) as jumlah'),
                    )
                        ->with('dokter:nama,kdpegsimrs')
                        ->leftJoin('resep_keluar_h', 'resep_keluar_h.noresep', '=', 'resep_keluar_racikan_r.noresep')
                        // ->where('resep_keluar_h.tgl_selesai', 'LIKE', '%' . request('tahun') . '-' . request('bulan') . '%')
                        ->whereBetween('resep_keluar_h.tgl_selesai', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
                        ->when(request('tipe') != 'all', function ($q) {
                            $q->where('resep_keluar_h.tiperesep', request('tipe'));
                        })
                        ->when(request('kode_ruang') != 'all', function ($q) {
                            $q->where('resep_keluar_h.depo', request('kode_ruang'));
                        })
                        ->when(
                            request('jenis') == 'detail',
                            function ($q) {
                                $q->groupBy('resep_keluar_h.noresep', 'resep_keluar_racikan_r.kdobat');
                            },
                            function ($q) {
                                $q->groupBy('resep_keluar_racikan_r.kdobat');
                            }
                        )
                        ->orderBy('resep_keluar_h.tgl_selesai', 'asc');
                    // ->groupBy('resep_keluar_h.noresep', 'resep_keluar_racikan_r.kdobat');
                },
            ])
            ->where(function ($query) {
                $query->where('nama_obat', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('kd_obat', 'LIKE', '%' . request('q') . '%');
            })
            ->paginate(request('per_page'));

        $data = collect($obat)['data'];
        $meta = collect($obat)->except('data');
        return new JsonResponse([
            'data' => $data,
            'meta' => $meta,
            'req' => request()->all(),
        ]);
    }
}
