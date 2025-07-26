<?php

namespace App\Http\Controllers\Api\Satusehat;

use App\Helpers\AuthSatsetHelper;
use App\Http\Controllers\Controller;
use App\Models\Pegawai\Extra;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    public function index()
    {
        $token = AuthSatsetHelper::getToken();
        return $token;
    }
}
