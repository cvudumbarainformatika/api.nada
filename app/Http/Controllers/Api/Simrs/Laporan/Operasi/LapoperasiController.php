<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Operasi;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Laporan\Operasi\LaporanOperasi;
use Illuminate\Http\JsonResponse;

class LapoperasiController extends Controller
{
    public function lapoperasirr()
    {
        $from=request('from');
        $to=request('to');
        $query = LaporanOperasi::with([
            'permintaanoperasi:rs1',
            'pasien_kunjungan_poli:rs15.rs1 as norm,rs15.rs2 as nama',
            'pasien_kunjungan_rawat_inap:rs15.rs1 as norm,rs15.rs2 as nama'])
            //->whereMonth('rs217.rs3','='.$bln)
            //->whereYear('rs3','='. $thn)
            ->whereBetween('rs217.rs3', [$from, $to])
            ->get();

        // $query = LaporanOperasi::with(['permintaanoperasi:rs1'])
        // ->limit(100)->get();
        return new JsonResponse($query, 200);
    }
}
