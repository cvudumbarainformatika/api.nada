<?php

namespace App\Http\Controllers\Api\Simrs\Historypasien;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mpasien;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HistorypasienController extends Controller
{
    public function historykunjunganpasien()
    {
        $kunjungan = Mpasien::select(
            'rs1',
            DB::raw('concat(rs3," ",gelardepan," ",rs2," ",gelarbelakang) as nama'),
            DB::raw('concat(rs4," KEL ",rs5," RT ",rs7," RW ",rs8," ",rs6," ",rs11," ",rs10) as alamat'),
            'rs16 as tgllahir',
            'rs17 as kelamin',
            'rs19 as pendidikan',
            'rs22 as agama',
            'rs37 as templahir',
            'rs39 as suku',
            'rs40 as jenispasien',
            'rs46 as noka',
            'rs49 as nktp',
            'rs55 as nohp'
        )
            ->with(
                [
                    'kunjunganpoli:rs1 as noreg,rs2,rs3 as tglkunjungan,rs8,rs9,rs14',
                    'kunjunganpoli.relmpoli:rs1,rs2 as poli',
                    'kunjunganpoli.msistembayar:rs1,rs2 as sistembayar',
                    'kunjunganpoli.dokter:rs1,rs2 as namadokter',
                    'kunjunganranap:rs1 as noreg,rs2,rs3,rs5,rs10,rs19',
                    'kunjunganranap.relmasterruangranap:rs1,rs2 as namaruang',
                    'kunjunganranap.relsistembayar:rs1,rs2 as sistembayar',
                    'kunjunganranap.reldokter:rs1,rs2 as dokter'
                ]
            )
            ->where('rs1', '=', request('norm'))
            ->first();
        return new JsonResponse($kunjungan);
    }
}
