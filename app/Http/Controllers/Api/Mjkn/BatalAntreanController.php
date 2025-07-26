<?php

namespace App\Http\Controllers\Api\Mjkn;

use App\Helpers\AuthjknHelper;
use App\Helpers\BridgingbpjsHelper;
use App\Http\Controllers\Controller;
use App\Models\Antrean\Booking;
use App\Models\Antrean\Unit;
use App\Models\KunjunganPoli;
use App\Models\Sigarang\Pegawai;
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

class BatalAntreanController extends Controller
{
    public function byKodebooking(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kodebooking' => 'required'
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

        $kodebooking = $request->input('kodebooking');

        $booking = Booking::where(['kodebooking' => $kodebooking, 'statuscetak' => 1])->first();


        if (!$booking) {
            return response()->json([
                'metadata' => [
                    'message' => 'Antrian tidak ditemukan atau sudah dibatalkan',
                    'code' => 201,
                ]
            ], 201);
        }

        $noreg = $booking->noreg;
        if (is_null($noreg))
            $noreg = '-----------';

        $kunjungan = KunjunganPoli::where(['rs1' => $noreg, 'rs19' => '1'])->get();
        if (count($kunjungan) > 0)
            return response()->json([
                'metadata' => [
                    'message' => 'Pasien Sudah Dilayani, Antrean Tidak Dapat Dibatalkan',
                    'code' => 201,
                ]
            ], 201);

        $batalkan = Booking::where('kodebooking', '=', $kodebooking)->update(['statuscetak' => 0]);

        if (!$batalkan) {
            return response()->json([
                'metadata' => [
                    'message' => 'Maaf, Ada kesalahan disisi server ... Ulangi Lagi!',
                    'code' => 201,
                ]
            ], 201);
        }

        $response = [
            'metadata' => [
                'message' => 'Ok',
                'code' => 200,
            ]
        ];

        return response()->json($response, $response['metadata']['code']);
    }
}
