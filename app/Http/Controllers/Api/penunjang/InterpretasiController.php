<?php

namespace App\Http\Controllers\Api\penunjang;

use App\Http\Controllers\Controller;
use App\Models\Interpretasi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InterpretasiController extends Controller
{
    public function store(Request $request)
    {
        $saved = Interpretasi::updateOrCreate(['rs5'=>$request->nota],
            [
                'rs1'=> $request->nota,
                'ket'=> $request->catatan
            ]
    );

        if (!$saved) {
            return new JsonResponse(['message'=>'Ada Kesalahan'], 500);
        }

        return new JsonResponse(['message'=>'success'], 201);
    }
}
