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
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class SisaAntreanController extends Controller
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
                    'message' => 'Antrian tidak ditemukan',
                    'code' => 201,
                ]
            ], 201);
        }

        $tgl = Carbon::parse($booking->tanggalperiksa);
        $tanggalperiksa = $tgl->toDateString();
        $layanan_id = $booking->layanan_id;
        $angkaantrean = $booking->angkaantrean;
        $antreanpanggil = '-';
        $sisaantrean = 0;

        $keterangan = "Harap Datang 30 Menit Lebih Awal !";

        $logpanggilan = Booking::with('dokter')->whereBetween('tanggalperiksa', [$tanggalperiksa . ' 00:00:00', $tanggalperiksa . ' 23:59:59'])
            ->where('layanan_id', $layanan_id)
            ->where('statuscetak', 1)
            // ->where('statuspanggil', 1)
            ->orderBy('angkaantrean', 'DESC')
            ->get();
        $collection = collect($logpanggilan);
        $totalantrean = $collection->count();

        $totaldipanggil = 0;
        $sisaantrean = $totalantrean;
        $angkadipanggil = 0;
        if ($totalantrean > 0) {

            $log = $collection->filter(function ($value, $key) {
                return $value->statuspanggil === 1;
            });

            $totaldipanggil = $log->count();


            if ($totaldipanggil > 0) {
                $logpanggilterakhir = $log->first();
                $antreanpanggil = $logpanggilterakhir->nomorantrean;
                $angkadipanggil = $logpanggilterakhir->angkaantrean;

                $sisaantrean = $angkaantrean - $angkadipanggil;
            }
        }

        $unit = Unit::where('layanan_id', '=', $layanan_id)->get();
        $waktutunggu = 0;
        $namapoli = '';
        if (count($unit) > 0) {
            $waktutunggu = ($unit[0]->waktu_layanan_perpasien * ($sisaantrean - 1)) * 60;
            $namapoli = $unit[0]->unit_group;
        }


        $response = [
            'response' => [
                'nomorantrean' => $booking->nomorantrean,
                'namapoli' => $namapoli,
                'namadokter' => $booking->dokter ? $booking->dokter->namadokter : '-',
                'sisaantrean' => $sisaantrean,
                'antreanpanggil' => $antreanpanggil,
                'waktutunggu' => $waktutunggu,
                'keterangan' => $keterangan,
            ],
            'metadata' => [
                'message' => 'Ok',
                'code' => 200,
            ]
        ];
        return response()->json($response, $response['metadata']['code']);
    }
}
