<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Diagnosa_m;
use App\Models\Simrs\Master\MtipeKhasusDiagnosa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DiagnosaController extends Controller
{
    public function listdiagnosa()
    {
        $listdiagnosa = Diagnosa_m::where('disable_status', '')->orderBy('rs3')->limit(25)->get();
        return new JsonResponse($listdiagnosa);
    }

    public function diagnosa_autocomplete()
    {
       $data = Diagnosa_m::query()
        ->select('rs1 as icd', 'rs2 as dtd', 'rs3 as ketindo', 'rs4 as keterangan')
        ->where('disable_status', '')
        ->where(function ($q) {
            $q->where('rs1', 'LIKE', '%' . request('q') . '%')
                ->orWhere('rs2', 'LIKE', '%' . request('q') . '%')
                ->orWhere('rs3', 'LIKE', '%' . request('q') . '%')
                ->orWhere('rs4', 'LIKE', '%' . request('q') . '%');
        })
        ->limit(15)->get();

        return new JsonResponse($data);
    }

    public function listtipekhasus()
    {
        $data = Cache::remember('id1', now()->addDays(7), function() {
            return MtipeKhasusDiagnosa::query()->get();
        });
        return new JsonResponse($data);
    }
}
