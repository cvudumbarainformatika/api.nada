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

class CheckInController extends Controller
{
    public function byKodebooking(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kodebooking' => 'required',
            'waktu' => 'required',
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
                    'message' => 'Maaf .. Antrian tidak ditemukan atau sudah dibatalkan',
                    'code' => 201,
                ]
            ], 201);
        }

        $updateCheckin = $booking->update(['checkin' => DateHelper::getDateTime()]);

        if (!$updateCheckin) {
            return response()->json([
                'metadata' => [
                    'message' => 'Maaf .. Ada kesalahan.. Harap ulangi',
                    'code' => 201,
                ]
            ], 201);
        }

        BpjsCheckin::firstOrCreate($request->all());
        // $datang = DateHelper::convertToDateTimeString($request->input('waktu'));
        $response = [
            'metadata' => [
                'message' => "Ok",
                'code' => 200,
            ]
        ];
        return response()->json($response, 200);
    }
}
