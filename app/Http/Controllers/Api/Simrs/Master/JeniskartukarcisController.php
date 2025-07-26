<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mjeniskartukarcis;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JeniskartukarcisController extends Controller
{
    public function jeniskartukarcis()
    {
        $jeniskartukarcis = Mjeniskartukarcis::all();
        return new JsonResponse($jeniskartukarcis);
    }
}
