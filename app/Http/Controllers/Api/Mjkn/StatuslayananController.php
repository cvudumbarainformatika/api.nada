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

class StatuslayananController extends Controller
{
    public function byLayanan(Request $request)
    {

        // $user = AuthjknHelper::user();
        $validator = Validator::make($request->all(), [
            'kodepoli' => 'required',
            'kodedokter' => 'required',
            'tanggalperiksa' => 'required|date',
            'jampraktek' => 'required',
        ]);
        if ($validator->fails()) {
            $response = [
                'metadata' => [
                    'message' => $validator->errors()->first(),
                    'code' => 201,
                ]
            ];
            return new JsonResponse($response, $response['metadata']['code']);
        }

        $tanggalperiksa = $request->input('tanggalperiksa');
        $kodepoli = $request->input('kodepoli');
        $kodedokter = $request->input('kodedokter');

        // Cari POLI
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

        if (strtotime($tanggalperiksa) < strtotime(date('Y-m-d')))
            return response()->json([
                'metadata' => [
                    'message' => 'Tanggal periksa tidak berlaku',
                    'code' => 201,
                ]
            ], 201);

        // CARI UNIT Antrian
        $unitAntrian = Unit::where('layanan_id', $poli->kdpolirs)->first();

        if (!$unitAntrian) {
            return response()->json([
                'metadata' => [
                    'message' => 'Unit Belum Ada',
                    'code' => 201,
                ]
            ], 201);
        }

        $jadwalPoli = self::cari_dokter($kodepoli, $tanggalperiksa); // json_decode($jadwalPoli) for back to jSon
        // return new JsonResponse($jadwalPoli);
        $code = $jadwalPoli['metadata']['code'];
        if ($code != 200)
            return response()->json([
                'metadata' => [
                    'message' => 'Maaf, jadwal poli tujuan tidak ditemukan pada tanggal tersebut.',
                    'code' => 201,
                ]
            ], 201);

        $cekDokter = collect($jadwalPoli['result'])->firstWhere('kodedokter', $kodedokter);

        if (!$cekDokter)
            return response()->json([
                'metadata' => [
                    'message' => 'Maaf, jadwal dokter tujuan tidak ditemukan pada tanggal tersebut.',
                    'code' => 201,
                ]
            ], 201);


        $logAntrean = Booking::whereBetween('tanggalperiksa', [$tanggalperiksa . ' 00:00:00', $tanggalperiksa . ' 23:59:59'])
            ->where('layanan_id', $unitAntrian->layanan_id)
            ->where('statuscetak', 1)
            // ->where('statuspanggil', 1)
            ->orderBy('id', 'DESC')
            ->get();
        $collectLog = collect($logAntrean);

        $totalantrean = $collectLog->count();
        $logPanggil = $collectLog->filter(function ($value, $key) {
            return $value['statuspanggil'] === 1;
        });

        $logPanggilan = $collectLog->first();
        $antreanpanggil = '-';
        if ($logPanggilan) {
            $antreanpanggil = $logPanggilan->nomorantrean;
        }

        $jumlahAntreanPanggil = $logPanggil->count();

        $sisaantrean = $totalantrean - $jumlahAntreanPanggil;

        $kuotaJkn = $unitAntrian->kuotajkn;
        $kuotanonJkn = $unitAntrian->kuotanonjkn;

        $logJkn = $collectLog->filter(function ($value, $key) {
            return $value['jenispasien'] === 'JKN';
        })->count();
        // $logJknPanggil = $collectLog->filter(function ($value, $key) {
        //     return $value['jenispasien'] === 'JKN' && $value['statuspanggil'] === 1;
        // })->count();

        $logNonJkn = $collectLog->filter(function ($value, $key) {
            return $value['jenispasien'] !== 'JKN';
        })->count();
        // $logPanggilNonJkn = $collectLog->filter(function ($value, $key) {
        //     return $value['jenispasien'] !== 'JKN' && $value['statuspanggil'] === 1;
        // });

        $sisakuotajkn = $kuotaJkn - $logJkn;
        $sisakuotanonjkn = $kuotanonJkn - $logNonJkn;


        // JIKA SUCCESS
        $response = [
            'response' => [
                'namapoli' => $unitAntrian->unit_group,
                'namadokter' => $cekDokter->namadokter,
                'totalantrean' => $totalantrean,
                'sisaantrean' => $sisaantrean,
                'antreanpanggil' => $antreanpanggil,
                'sisakuotajkn' => $sisakuotajkn,
                'kuotajkn' => $kuotaJkn,
                'sisakuotanonjkn' => $sisakuotanonjkn,
                'kuotanonjkn' => $kuotanonJkn,
                'keterangan' => '',
            ],
            'metadata' => [
                'message' => 'Ok',
                'code' => 200,
            ]
        ];

        return new JsonResponse($response);
    }


    public function cari_dokter($kodepoli, $tanggal)
    {
        return BridgingbpjsHelper::get_url('antrean', 'jadwaldokter/kodepoli/' . $kodepoli . "/tanggal/" . $tanggal);
    }
}
