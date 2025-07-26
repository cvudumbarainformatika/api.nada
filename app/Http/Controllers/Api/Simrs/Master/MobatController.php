<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mobat;
use Illuminate\Http\JsonResponse;

class MobatController extends Controller
{
    public function index()
    {
        $query = Mobat::mobat()->paginate(10);
        return new JsonResponse($query);
    }

    public function cariobat()
    {
        $query = Mobat::mobat()->filter(request(['q']))
            ->limit(50)
            ->get();
        return new JsonResponse($query);
    }
}
