<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MorganisasiAdministrasi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MorganisasiAdministrasiController extends Controller
{
    public function listorganisasi()
    {
        $data = MorganisasiAdministrasi::where(function ($query) {
                $query->wherenull('hiddenx')
                    ->orWhere('hiddenx','');
            })->get();
        return new JsonResponse($data);
    }
}
