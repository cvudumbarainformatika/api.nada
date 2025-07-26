<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mprocedurebpjs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProcedurebpjsController extends Controller
{
    public function procedurebpjs()
    {
        $procedurebpjs = Mprocedurebpjs::all();
        return new JsonResponse($procedurebpjs);
    }
}
