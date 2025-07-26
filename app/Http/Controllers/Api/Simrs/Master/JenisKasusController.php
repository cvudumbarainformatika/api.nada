<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mjeniskartukarcis;
use App\Models\Simrs\Master\MjenisKasus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JenisKasusController extends Controller
{
    public function jeniskasus()
    {
        $data = MjenisKasus::where('flag','1')->get();
        return new JsonResponse($data);
    }
}
