<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Rekammedik;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Rajal\KunjunganPoli;
use Illuminate\Http\JsonResponse;

class LapcarakeluarpasienIgdController extends Controller
{
    public function laporancarakeluarpasienigd()
    {
        $from=request('tgldari');
        $to=request('tglsampai');
        $data = KunjunganPoli::select('rs17.rs1','rs17.rs1 as noreg','rs17.rs2 as norm','rs17.rs3 as tglmasuk','rs141.rs4 as flaging',
        'plann_igd_pulang.atas_dasar as flagingx','rs15.rs2 as nama','rs15.rs46 as noka',
        'rs15.rs49 as ktp')
        ->with(
            [
                'triage' => function($triage) {
                    $triage->select('rs1','doa')->whereNotNull('doa')->Orwhere('doa','!=','');
                }
            ])
        ->whereBetween('rs17.rs3', [$from, $to])
        ->where('rs17.rs8','POL014')
        ->where('rs17.rs19','1')
        ->leftjoin('rs141', 'rs141.rs1','rs17.rs1')
        ->leftjoin('rs15','rs15.rs1','rs17.rs2')
        ->leftjoin('plann_igd_pulang', 'plann_igd_pulang.noreg','rs17.rs1')
        ->get();
        return new JsonResponse($data);
    }
}
