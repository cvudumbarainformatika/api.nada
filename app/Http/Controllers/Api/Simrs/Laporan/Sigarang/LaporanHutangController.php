<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Sigarang;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Supplier;
use App\Models\Sigarang\Transaksi\Penerimaan\Penerimaan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LaporanHutangController extends Controller
{
    public function lapHutang()
    {
        $kodePer = Penerimaan::select('kode_perusahaan')->where('nilai_pembayaran', '<=', 0)->distinct('kode_perusahaan')->get();

        $data = Supplier::select('kode', 'nama')
            ->with([
                'penerimaan' => function ($p) {
                    $p->select('kode_perusahaan', 'no_penerimaan', 'faktur', 'tanggal', 'tanggal_bast', 'tempo', 'total', 'nilai_tagihan')
                        // ->selectRaw('sum(total) as sumTotal')
                        // ->selectRaw('sum(nilai_tagihan) as sumTagihan')
                        ->where('nilai_pembayaran', '<=', 0);
                }
            ])
            ->whereIn('kode', $kodePer)->get();
        return new JsonResponse($data);
    }
}
