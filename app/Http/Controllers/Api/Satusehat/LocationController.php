<?php

namespace App\Http\Controllers\Api\Satusehat;

use App\Helpers\AuthSatsetHelper;
use App\Helpers\BridgingSatsetHelper;
use App\Http\Controllers\Controller;
use App\Models\Pegawai\Extra;
use App\Models\Pegawai\Ruangan;
use App\Models\Satset\SatsetToken;
use App\Models\Sigarang\Ruang;
use App\Models\Simrs\Organisasi\Organisasi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

use function PHPUnit\Framework\isEmpty;

class LocationController extends Controller
{
    public function listRuanganRajal()
    {
        $data = Ruang::where('groupper', '=', 'rajal')
            ->with(['namagedung', 'organisasi', 'satset'])
            ->get();

        return response()->json($data);
    }
    public function listRuanganRanap()
    {
        $data = Ruang::where('groupper', '=', 'ranap')
            ->with(['namagedung', 'organisasi', 'satset'])
            ->get();

        return response()->json($data);
    }

    public function listRuanganByGroup()
    {
        $data = Ruang::where('groupper', '=', request('group'))
        ->whereNotNull('groupper')
        ->with(['namagedung', 'organisasi', 'satset'])
        ->get();

        return response()->json($data);
    }

    public function updateLocation(Request $request)
    {


        $data = Ruang::find($request->id);
        // return $request->type;

        $form = [
            'gedung' => $request->gedung,
            'lantai' => $request->lantai,
            'ruang' => $request->ruang,
            'uraian' => $request->uraian,
            // 'group'=>$request->group,
            'groupper' => $request->groupper,
            'phone' => $request->phone,
            'fax' => $request->fax,
            'email' => $request->email,
            'web' => $request->web,
            'alamat' => $request->alamat,
            'rt' => $request->rt,
            'rw' => $request->rw,
            'longitude' => $request->longitude,
            'latitude' => $request->latitude,
            'altitude' => $request->altitude,
            'departement_uuid' => $request->departement_uuid,
        ];

        $update = $data->update($form);

        if (!$update) {
            return response()->json([
                'message' => 'Gaga Update Lokasi'
            ], 500);
        }

        // ambil token satset dr database
        $token = AuthSatsetHelper::accessToken();

        $send = $this->sendToSatset($token, $data);
        // if ($send['data']['message'] === 'failed') {
        //     $expired = $send['data']['response']['issue'][0]['code']==='invalid-access-token';
        //     if ($expired) {
        //         $token = AuthSatsetHelper::accessToken();
        //         $send = $this->sendToSatset($token, $data);
        //     }
        // }
        return $send;
        // $expired = $send['data']['response']['issue'][0]['code']==='invalid-access-token';

        

        // return $send;
    }

    public function sendToSatset($token, $data)
    {
        $organization_id = '100026342';
        $satset_uuid = $data->satset_uuid;
        $uuid = '';
        if (!empty($satset_uuid)) {
            $uuid = '"id": "' . $satset_uuid . '",';
        }
        $body = '{
                    "resourceType": "Location",
                    ' . $uuid . '
                    "identifier": [
                        {
                        "system": "http://sys-ids.kemkes.go.id/location/' . $organization_id . '",
                        "value": "' . $data->kode . '"
                        }
                    ],
                    "status": "active",
                    "name": "' . $data->uraian . '",
                    "description": "Ruang ' . $data->ruang . ', RSUD Mohamad Saleh, Lantai ' . $data->lantai . ', Gedung ' . $data->gedung . '",
                    "mode": "instance",
                    "telecom": [
                        {
                        "system": "phone",
                        "value": "' . $data->phone . '",
                        "use": "work"
                        },
                        {
                        "system": "fax",
                        "value": "' . $data->fax . '",
                        "use": "work"
                        },
                        {
                        "system": "email",
                        "value": "' . $data->email . '"
                        },
                        {
                        "system": "url",
                        "value": "' . $data->web . '",
                        "use": "work"
                        }
                    ],
                    "address": {
                        "use": "work",
                        "line": [
                        "' . $data->alamat . '"
                        ],
                        "city": "Probolinggo",
                        "postalCode": "67219",
                        "country": "ID",
                        "extension": [
                        {
                            "url": "https://fhir.kemkes.go.id/r4/StructureDefinition/administrativeCode",
                            "extension": [
                            {
                                "url": "province",
                                "valueCode": "35"
                            },
                            {
                                "url": "city",
                                "valueCode": "3574"
                            },
                            {
                                "url": "district",
                                "valueCode": "357403"
                            },
                            {
                                "url": "village",
                                "valueCode": "3574031007"
                            },
                            {
                                "url": "rt",
                                "valueCode": "' . $data->rt . '"
                            },
                            {
                                "url": "rw",
                                "valueCode": "' . $data->rw . '"
                            }
                            ]
                        }
                        ]
                    },
                    "physicalType": {
                        "coding": [
                        {
                            "system": "http://terminology.hl7.org/CodeSystem/location-physical-type",
                            "code": "ro",
                            "display": "Room"
                        }
                        ]
                    },
                    "position": {
                        "longitude": ' . $data->longitude . ',
                        "latitude": ' . $data->latitude . ',
                        "altitude": ' . $data->altitude . '
                    },
                    "managingOrganization": {
                        "reference": "Organization/' . $data->departement_uuid . '"
                    }
                }';
        $form = json_decode($body, true);



        // put
        if ($satset_uuid) {
            $send = BridgingSatsetHelper::put_data($token, '/Location' . '/' . $satset_uuid, $form);
            if ($send['message'] === 'success') {
                $data->satset_uuid = $send['data']->uuid;
                $data->save();
            }
            return $send;
        }

        // post
        $send = BridgingSatsetHelper::post_data($token, '/Location', $form);

        if ($send['message'] === 'success') {
            $data->satset_uuid = $send['data']->uuid;
            $data->save();
        }
        return $send;
    }
}
