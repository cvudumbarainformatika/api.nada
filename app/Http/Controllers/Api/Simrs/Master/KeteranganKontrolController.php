<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\KeteranganKontrol;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KeteranganKontrolController extends Controller
{
    public function getketerangankontrol()
    {
        $data = KeteranganKontrol::where('flag','')->orWhereNull('flag')->get();
        return new JsonResponse($data);
    }
}
