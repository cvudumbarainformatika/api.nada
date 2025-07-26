<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mpoli;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PoliController extends Controller
{
    public function listpoli()
    {
        $listpoli = Mpoli::listpoli()->where('rs4', '=', 'Poliklinik')->where('rs5', '=', '1')
        ->get();
        return new JsonResponse($listpoli, 200);
    }
}
