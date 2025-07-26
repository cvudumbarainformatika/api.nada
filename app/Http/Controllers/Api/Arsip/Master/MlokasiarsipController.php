<?php

namespace App\Http\Controllers\Api\Arsip\Master;

use App\Http\Controllers\Controller;
use App\Models\Arsip\Master\Mlokasiarsip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MlokasiarsipController extends Controller
{
    public function index()
    {
        $data = Mlokasiarsip::all();
        return new JsonResponse($data);
    }
}
