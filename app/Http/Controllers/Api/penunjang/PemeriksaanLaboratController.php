<?php

namespace App\Http\Controllers\Api\penunjang;

use App\Http\Controllers\Controller;
use App\Models\PemeriksaanLaborat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PemeriksaanLaboratController extends Controller
{
    public function groupped()
    {

        $data = PemeriksaanLaborat::query()
            // ->selectRaw('rs1,rs2,rs3,rs4,rs5,rs6,rs21,DISTINCT(rs21) as group,rs22,rs23,rs24,rs25,hidden')
            // ->where('rs2', 'LIKE', '%' . request('q') . '%')
            //             ->orWhere('rs21', 'LIKE', '%' . request('q') . '%')
            //     ->when(request()->q, function ($search){
            //         // if (request('p') == 'non') {
            //         //     return $search->orWhere('rs21','=','')
            //         //     ->orWhere('rs2', 'LIKE', '%' . $q . '%');
            //         // }
            //             return $search->where('rs2', 'LIKE', '%' . 'DARA' . '%')
            //             ->orWhere('rs21', 'LIKE', '%' . 'DARA' . '%');

            // })
            // ->groupBy('rs21')
            ->where('hidden', '=', '')->get();
        return new JsonResponse($data);
    }
}
