<?php

namespace App\Http\Controllers;

use App\Models\Simrs\Master\Mruanganranap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MruanganRanapController extends Controller
{
    public function mruanganranap()
    {
        $ruangranap = Mruanganranap::groupBy('rs4')->get();
        return new JsonResponse($ruangranap);
    }
}
