<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Perusahaan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PerusahaanController extends Controller
{
    public function index()
    {
        $data = Perusahaan::all();
        return new JsonResponse($data);
    }
}
