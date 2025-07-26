<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mhambatan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MhambatanController extends Controller
{
    public function listmhambatan()
    {
        $listhambatan = Mhambatan::where('flag', '')->get();
        return new JsonResponse($listhambatan);
    }
}
