<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mpihakketiga;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PihakketigaController extends Controller
{
    public function pihakketiga()
    {
        $pihakletiga = Mpihakketiga::where('nama', 'LIKE', '%' . request('nama') . '%')->where('hidden', '')->limit(20)->get();
        return new JsonResponse($pihakletiga);
    }
}
