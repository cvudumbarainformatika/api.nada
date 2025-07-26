<?php

namespace App\Http\Controllers\Api\Mjkn;

use App\Helpers\AuthjknHelper;
use App\Helpers\BridgingbpjsHelper;
use App\Helpers\DateHelper;
use App\Http\Controllers\Controller;
use App\Models\Antrean\Booking;
use App\Models\Antrean\Unit;
use App\Models\KunjunganPoli;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Bpjs\BpjsCheckin;
use App\Models\Simrs\Bpjs\BpjsPasienBaru;
use App\Models\Simrs\Bpjs\Bpjsrefpoli;
use App\Models\User;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class PasienBaruController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nomorkartu' => 'required|numeric',
            'nik' => 'required|numeric|digits:16',
            'nomorkk' => 'required',
            'nama' => 'required',
            'jeniskelamin' => 'required',
            'tanggallahir' => 'required|date',
            'nohp' => 'required',
            'alamat' => 'required',
            'kodeprop' => 'required',
            'namaprop' => 'required',
            'kodedati2' => 'required',
            'namadati2' => 'required',
            'kodekec' => 'required',
            'namakec' => 'required',
            'kodekel' => 'required',
            'namakel' => 'required',
            'rw' => 'required',
            'rt' => 'required'
        ]);
        if ($validator->fails()) {
            $response = [
                'metadata' => [
                    'message' => $validator->errors()->first(),
                    'code' => 201,
                ]
            ];
            return response()->json($response, $response['metadata']['code']);
        }


        if (count($getPasienBaru = BpjsPasienBaru::getByNoBpjs($request->input('nomorkartu'))->get()) > 0)
            return response()->json([
                'metadata' => [
                    'message' => 'Data Peserta Sudah Pernah Dientrikan',
                    'code' => 201,
                ]
            ], 201);

        BpjsPasienBaru::firstOrCreate($request->all());
        $norm = '-';
        $response = [
            'response' => [
                'norm' => $norm
            ],
            'metadata' => [
                'message' => 'Harap datang ke Pendaftaran untuk melengkapi data rekam medis',
                'code' => 200,
            ]
        ];
        return response()->json($response, $response['metadata']['code']);
    }
}
