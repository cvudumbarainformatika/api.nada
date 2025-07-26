<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Icd9prosedure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class Icd9Controller extends Controller
{
    public function mastericd9()
    {
        $list = Icd9prosedure::select('kd_prosedur', 'prosedur')
            ->where('kd_prosedur', 'like', '%' . request('q') . '%')
            ->orWhere('prosedur', 'like', '%' . request('q') . '%')
            ->get();
        return new JsonResponse($list);
    }
}
