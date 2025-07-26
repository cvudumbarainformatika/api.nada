<?php

namespace App\Http\Controllers\Api\Satusehat;

use App\Helpers\BridgingSatsetHelper;
use App\Http\Controllers\Controller;
use App\Models\Pegawai\Extra;
use App\Models\Sigarang\Pegawai;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PractitionerController extends Controller
{
    public function listPractitioner()
    {
        $data = Pegawai::when(
            request('q'),
            function ($q) {
                $q->where('nama', 'LIKE', '%' . request('q') . '%');
            }
        )->where(
            function ($q) {
                if (request('status') === 'Terkoneksi') {
                    $q->where('satset_uuid', '<>', '')->orWhereNotNull('satset_uuid');
                } else if (request('status') === 'Belum Terkoneksi') {
                    $q->where('satset_uuid', '')->orWhereNull('satset_uuid');
                }
            }
        )
            ->where([
                ['aktif', '=', 'AKTIF'],
                ['kdgroupnakes', '<>', ''],
            ])->paginate(request('per_page'));


        return new JsonResponse($data);
    }

    public function getPractitionerSatset()
    {
        $nakes_id = request('id');
        $token = request('token');

        $data = Pegawai::find($nakes_id);

        if (!$data) {
            return response()->json([
                'message' => 'Maaf ... Data Tidak ditemukan'
            ], 500);
        }

        $nik = $data->nik;
        if (!$nik) {
            return response()->json([
                'message' => 'Maaf ... Data Nik Tidak Ada'
            ], 500);
        }

        $params = "/Practitioner?identifier=https://fhir.kemkes.go.id/id/nik|$data->nik";
        // return $params dasdas;

        $send = BridgingSatsetHelper::get_data($token, $params);

        if ($send['message'] === 'success') {
            $data->satset_uuid = $send['data']['uuid'];
            $data->save();
        }
        return $send;
    }
}
