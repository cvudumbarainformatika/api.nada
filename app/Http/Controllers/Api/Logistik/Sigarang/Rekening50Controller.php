<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Rekening50;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class Rekening50Controller extends Controller
{
    //
    public function index()
    {
        $data = Rekening50::oldest()
            ->filter(request(['q']))
            ->paginate(request('per_page'));
        // return GedungResource::collection($data);
        $collect = collect($data);
        $balik = $collect->only('data');
        $balik['meta'] = $collect->except('data');

        return new JsonResponse($balik);
    }
    public function semua()
    {
        $data = Rekening50::oldest('id')->get(); //paginate(request('per_page'));
        // return BarangRSResource::collection($data);
        return new JsonResponse($data);
    }
}
