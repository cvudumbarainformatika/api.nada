<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mjeniskunjunganbpjs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JeniskunjunganbpjsController extends Controller
{
    public function jeniskunjunganbpjs()
    {
        $jeniskunjunganbpjs = Mjeniskunjunganbpjs::get();
        return new JsonResponse($jeniskunjunganbpjs);
    }
}
