<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mpenunjangbpjs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PenunjangbpjsController extends Controller
{
    public function penunjangbpjs()
    {
        $mpenunjangBpjs = Mpenunjangbpjs::all();
        return new JsonResponse($mpenunjangBpjs);
    }
}
