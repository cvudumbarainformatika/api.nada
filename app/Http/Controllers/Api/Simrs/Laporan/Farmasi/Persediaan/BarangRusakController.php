<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Farmasi\Persediaan;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Obat\BarangRusak;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BarangRusakController extends Controller
{
    public function getData()
    {
        $kdobat = [];
        if (request('q')) {
            $kdobat = Mobatnew::select('kd_obat')
                ->where('nama_obat', 'LIKE', '%' . request('q') . '%')
                ->where('kd_obat', 'LIKE', '%' . request('q') . '%')
                ->pluck('kd_obat');
        }
        $rusak = BarangRusak::with('masterobat:kd_obat,nama_obat,satuan_k')
            ->where('kunci', '1')
            ->when(request('kode_ruang') !== 'all', function ($q) {
                $q->where('gudang', request('kode_ruang'));
            })
            ->when(request('q'), function ($q) use ($kdobat) {
                $q->whereIn('kd_obat', $kdobat);
            })
            ->where('tgl_kunci', 'LIKE', '%' . request('tahun') . '-' . request('bulan') . '%')
            ->paginate(request('per_page'));
        $data['data'] = collect($rusak)['data'];
        $data['meta'] = collect($rusak)->except('data');
        $data['req'] = request()->all();

        return new JsonResponse($data);
    }
}
