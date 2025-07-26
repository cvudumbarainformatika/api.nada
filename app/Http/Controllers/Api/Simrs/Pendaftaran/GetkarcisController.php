<?php

namespace App\Http\Controllers\Api\Simrs\Pendaftaran;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GetkarcisController extends Controller
{
    public function getkarciscontoller()
    {
        $getkarcis = FormatingHelper::getKarcisPoli(request('kd_poli'),request('flag'));
        return new JsonResponse($getkarcis);
    }
}
