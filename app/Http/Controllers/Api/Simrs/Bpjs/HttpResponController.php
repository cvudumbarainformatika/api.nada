<?php

namespace App\Http\Controllers\Api\Simrs\Bpjs;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Bpjs\BpjsHttpRespon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HttpResponController extends Controller
{
    public function index()
    {
        $data = BpjsHttpRespon::latest('tgl')->paginate(request('per_page'));
        return new JsonResponse($data);
    }
}
