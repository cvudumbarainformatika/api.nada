<?php

namespace App\Http\Controllers\Api\Arsip\Master;

use App\Http\Controllers\Controller;
use App\Models\Arsip\Master\Munitpengelolah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MunitpengelolahController extends Controller
{
    public function unitpengelolah()
    {
        $data = Munitpengelolah::where('hiddenx','')->get();
        return new JsonResponse($data);
    }
}
