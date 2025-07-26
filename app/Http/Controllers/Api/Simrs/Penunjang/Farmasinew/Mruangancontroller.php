<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Mgudangs;
use App\Models\Simrs\Penunjang\Farmasinew\Mruangans;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Mruangancontroller extends Controller
{
    public function listruangan()
    {
        $gudang = Mgudangs::gudangs()
        ->filter(request(['q']));
        $ruangan = Mruangans::ruangans()->filter(request(['q']))
        ->union($gudang)
        ->get();
        return new JsonResponse($ruangan);
    }
}
