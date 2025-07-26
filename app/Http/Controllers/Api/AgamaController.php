<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agama;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgamaController extends Controller
{
    public function index()
    {
        $data = Agama::limit(5)->get();
        return new JsonResponse($data);
    }
}
