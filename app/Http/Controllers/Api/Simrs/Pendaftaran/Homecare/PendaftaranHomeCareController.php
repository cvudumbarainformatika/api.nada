<?php

namespace App\Http\Controllers\Api\Simrs\Pendaftaran\Homecare;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PendaftaranHomeCareController extends Controller
{
    public function listKunjungan()
    {

        $data = request()->all();
        $meta = request()->all();
        return new JsonResponse([
            'meta' => $meta,
            'data' => $data
        ]);
    }
    public function simpanKunjungan(Request $request)
    {

        $data = $request->all();

        return new JsonResponse($data);
    }
}
