<?php

namespace App\Http\Controllers\Api\Mjkn;

use App\Helpers\AuthjknHelper;
use App\Helpers\BridgingbpjsHelper;
use App\Http\Controllers\Controller;
use App\Models\Antrean\Booking;
use App\Models\Antrean\Unit;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Bpjs\Bpjsrefpoli;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class MappingController extends Controller
{
    public static function poli($kodepoli)
    {
        $caripoli = Bpjsrefpoli::getByKdSubspesialis($kodepoli)->get();

        if (count($caripoli) === 0) {
            return response()->json([
                'metadata' => [
                    'message' => 'Poli tidak ditemukan',
                    'code' => 201,
                ]
            ], 201);
        }

        $poli = $caripoli[0];

        return $poli;
    }
}
