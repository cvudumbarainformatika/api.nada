<?php

namespace App\Http\Controllers\Api\Satusehat;

use App\Helpers\BridgingSatsetHelper;
use App\Http\Controllers\Controller;
use App\Models\Pegawai\Extra;
use App\Models\Simrs\Organisasi\Organisasi;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

use function PHPUnit\Framework\isEmpty;

class OrganizationController extends Controller
{
    public function listOrganisasiRs()
    {
        $data = Organisasi::with('satset')->get();
        return new JsonResponse($data);
    }
    public function cariorganisasidisatset()
    {
        $token = request('token');
        $params = '/Organization/63312221-e8d8-4ecd-bf4f-d2d329e41e59';
        $data = BridgingSatsetHelper::get_data($token, $params);

        return $data;
    }

    public function postOrganisasiRs(Request $request)
    {
        $form = [
            'nama' => $request->nama,
            'phone' => $request->phone,
            'email' => $request->email,
            'website' => $request->website,
            'level' => $request->level
        ];

        $post = null;

        if ($request->has('id')) {
            $data = Organisasi::find($request->id);
            $post = $data->update($form);
        } else {
            $post = Organisasi::create($form);
        }



        if (!$post) {
            return response()->json([
                'message' => 'Maaf Ada Kesalahan'
            ], 500);
        }

        return response()->json([
            'message' => 'Data telah tersimpan'
        ], 200);
    }

    public function sendToSatset()
    {
        $id = request('id');
        $data = Organisasi::find($id);
        if (!$data) {
            return response()->json([
                'message' => 'Maaf ... data organisasi tidak ditemukan!'
            ], 500);
        }

        // jika ditemukan kirim ke satu sehat
        $organization_id_dev = '63312221-e8d8-4ecd-bf4f-d2d329e41e59';
        $organization_id = '100026342';

        $satset_uuid = $data->satset_uuid;
        $uuid = '';
        if ($satset_uuid) {
            $uuid = '"id": "' . $satset_uuid . '",';
        }

        // return $uuid;
        $body = '{
            "resourceType": "Organization",
            "active": true,
            ' . $uuid . '
            "identifier": [
                {
                "use": "official",
                "system": "http://sys-ids.kemkes.go.id/organization/' . $organization_id . '",
                "value": "' . $data->nama . '"
                }
            ],
            "type": [
                {
                "coding": [
                    {
                    "system": "http://terminology.hl7.org/CodeSystem/organization-type",
                    "code": "dept",
                    "display": "Hospital Department"
                    }
                ]
                }
            ],
            "name": "' . $data->nama . '",
            "telecom": [
                {
                "system": "phone",
                "value": "' . $data->phone . '",
                "use": "work"
                },
                {
                "system": "email",
                "value": "' . $data->email . '",
                "use": "work"
                },
                {
                "system": "url",
                "value": "' . $data->website . '",
                "use": "work"
                }
            ],
            "address": [
                {
                "use": "work",
                "type": "both",
                "line": [
                    "Jl. Mayjen Panjaitan No.65"
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
                        }
                    ]
                    }
                ]
                }
            ],
            "partOf": {
                "reference": "Organization/' . $organization_id . '"
            }
        }';

        $form = json_decode($body, true);
        // return $form;

        // put
        if ($satset_uuid) {
            $send = BridgingSatsetHelper::put_data(request('token'), '/Organization', $form);
            if ($send['message'] === 'success') {
                $data->satset_uuid = $send['data']['uuid'];
                $data->save();
            }
            return $send;
        }

        // post
        $send = BridgingSatsetHelper::post_data(request('token'), '/Organization', $form);

        if ($send['message'] === 'success') {
            $data->satset_uuid = $send['data']['uuid'];
            $data->save();
        }
        return $send;
    }
}
