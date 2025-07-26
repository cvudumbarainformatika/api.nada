<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Farmasi\Persediaan;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeadStokController extends Controller
{
    //
    public function deadStok()
    {

        $data = request()->all();

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Data berhasil diambil',
            'data' => $data
        ], 200);
    }
}
