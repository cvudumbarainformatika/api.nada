<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Massesmentpelbpjs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssesmentbpjsController extends Controller
{
    public function assesmentbpjs()
    {
        $assesmentbpjs = Massesmentpelbpjs::all();
        return new JsonResponse($assesmentbpjs);
    }
}
