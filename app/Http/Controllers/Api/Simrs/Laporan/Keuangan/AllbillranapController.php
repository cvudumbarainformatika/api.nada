<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Keuangan;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Ranap\Kunjunganranap;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class AllbillranapController extends Controller
{
    public function allbillranap()
    {
        if(request('filter') === '1')
        {
            $filter = 'rs3';
        }else{
            $filter = 'rs4';
        }

        $dari = request('tgldari') .' 00:00:00';
        $sampai = request('tglsampai') .' 23:59:59';

        $allbillranap = Kunjunganranap::whereBetween($filter, [$dari, $sampai])
        ->get();
        return new JsonResponse(['message' => 'Ok',$allbillranap],);
    }
}
