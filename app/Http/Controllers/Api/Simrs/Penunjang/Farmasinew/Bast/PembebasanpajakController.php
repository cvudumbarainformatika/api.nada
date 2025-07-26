<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Bast;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PembebasanpajakController extends Controller
{
    public function dialogsppajak()
    {
        $carisp = PenerimaanHeder::with(
            [
                'pihakketiga',
                'penerimaanrinci'
            ]
        )
            ->where('kunci', '1')
            ->where('bebaspajak', '')
            ->where('flag_bayar', '')
            ->where(function ($query) {
                $query->where('nopenerimaan', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('nopemesanan', 'LIKE', '%' . request('q') . '%');
            })
            ->get();
        return new JsonResponse($carisp);
    }
}
