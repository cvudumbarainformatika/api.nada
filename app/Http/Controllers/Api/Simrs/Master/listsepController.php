<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Pendaftaran\Ranap\Sepranap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class listsepController extends Controller
{
    public function listsepmrs(Request $request)
    {
        $listsepmrs = Sepranap::where('rs13','=', $request->noka)->get();
        return new JsonResponse($listsepmrs);
    }
}
