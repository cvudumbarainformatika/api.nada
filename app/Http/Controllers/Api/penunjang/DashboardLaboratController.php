<?php

namespace App\Http\Controllers\Api\penunjang;

use App\Http\Controllers\Controller;
use App\Models\LaboratLuar;
use App\Models\TransaksiLaborat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardLaboratController extends Controller
{
    public function index()
    {
        $from = now();
        $to = date('Y') . '-' . date('m') . '-01';
        $lab = TransaksiLaborat::selectRaw('COUNT(rs2) as y, COUNT(DISTINCT(rs2)) as z, DATE(rs3) as x')
            ->groupBy('x')
            // ->whereMonth('rs3', '=', date('m'))
            // ->whereYear('rs3', '=', date('Y'))
            ->whereBetween('rs3', [$to, $from])
            ->orderBy('rs3', 'desc')->get();

        $lab_luar = LaboratLuar::selectRaw('COUNT(nota) as y, COUNT(DISTINCT(nota)) as z, DATE(tgl) as x')
            ->groupBy('x')
            // ->whereMonth('tgl', '=', date('m'))
            // ->whereYear('tgl', '=', date('Y'))
            ->whereBetween('tgl', [$to, $from])
            ->orderBy('tgl', 'desc')->get();

        $data = array(
            'lab' => $lab,
            'lab_luar' => $lab_luar,
        );

        return new JsonResponse($data);
    }
}
