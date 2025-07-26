<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Rstigapuluhtarif as MasterRstigapuluhtarif;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RsTigaPuluhTarif extends Controller
{
    public function gettigapuluhtarif()
    {
        $data = MasterRstigapuluhtarif::get();
        return new JsonResponse($data);
    }
}
