<?php

namespace App\Http\Controllers;

use App\Helpers\BridgingbpjsHelper;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use \LZCompressor\LZString;

class DvlpController extends Controller
{
    public function index()
    {
        // $no_rujukan = '132701010323P000003';
        // $no_rujukan = '0213B0060523P000015';
        $noka = '0000112366822';
        return BridgingbpjsHelper::get_url('vclaim', 'Rujukan/List/Peserta/' . $noka);
        // jadwaldokt05-22er/kodepoli/{Parameter1}/tanggal/{Parameter2}


        $post = [
            "request" => [
                "t_sep" => [
                    "noKartu" => "0001112230666",
                    "tglSep" => "2023-06-05",
                    "ppkPelayanan" => "1327R001",
                    "jnsPelayanan" => "2",
                    "klsRawat" => [
                        "klsRawatHak" => "2",
                        "klsRawatNaik" => "1",
                        "pembiayaan" => "1",
                        "penanggungJawab" => "Pribadi"
                    ],
                    "noMR" => "123456",
                    "rujukan" => [
                        "asalRujukan" => "1",
                        "tglRujukan" => "2021-07-23",
                        "noRujukan" => "RJKMR9835001",
                        "ppkRujukan" => "0301R011"
                    ],
                    "catatan" => "testinsert RI",
                    "diagAwal" => "E10",
                    "poli" => [
                        "tujuan" => "",
                        "eksekutif" => "0"
                    ],
                    "cob" => [
                        "cob" => "0"
                    ],
                    "katarak" => [
                        "katarak" => "0"
                    ],
                    "jaminan" => [
                        "lakaLantas" => "0",
                        "noLP" => "12345",
                        "penjamin" => [
                            "tglKejadian" => "",
                            "keterangan" => "",
                            "suplesi" => [
                                "suplesi" => "0",
                                "noSepSuplesi" => "",
                                "lokasiLaka" => [
                                    "kdPropinsi" => "",
                                    "kdKabupaten" => "",
                                    "kdKecamatan" => ""
                                ]
                            ]
                        ]
                    ],
                    "tujuanKunj" => "0",
                    "flagProcedure" => "",
                    "kdPenunjang" => "",
                    "assesmentPel" => "",
                    "skdp" => [
                        "noSurat" => "000002",
                        "kodeDPJP" => "31574"
                    ],
                    "dpjpLayan" => "",
                    "noTelp" => "081111111101",
                    "user" => "Coba Ws"
                ]
            ]
        ];

        return BridgingbpjsHelper::post_url('vclaim', 'SEP/2.0/insert', $post);

        // return response()->json($coba);

        // return response()->json($coba);
        //     $tgl = '2023-05-22';
        //     $data = BridgingbpjsHelper::get_url('antrean', "antrean/pendaftaran/tanggal/$tgl");
        //     return $data;
    }

    public function antrian()
    {
        // $reqLog = (new Client())->post('http://192.168.160.100:2000/api/api' . '/get_list_antrian_tanggal', [
        //     'form_params' => [
        //         'tanggal' => date('Y-m-d')
        //     ],
        //     'http_errors' => false
        // ]);
        // $resLog = json_decode($reqLog->getBody()->getContents(), false);

        // // return response()->json($resLog);
        // return $resLog;
        $myReq["layanan"] = '1';
        $myReq["loket"] = '1';
        $myReq["id_ruang"] = '1';
        $myReq["user_id"] = "a1";
        $myReq["nomor"] = 'B147';
        $reqLog = (new Client())->post('http://192.168.160.100:2000/api/api' . '/tombolrecall_layanan_ruang', [
            'form_params' => $myReq,
            'http_errors' => false
        ]);
        $resLog = json_decode($reqLog->getBody()->getContents(), false);

        // return response()->json($resLog);
        return $resLog;
    }

    // public function coba()
    // {
    //     $sign = BpjsConfigBridging::getSignature();

    //     // return BridgingbpjsHelper::get_url('vclaim');

    //     $service_name = 'vclaim-rest-dev';
    //     $base_url = 'https://apijkn-dev.bpjs-kesehatan.go.id/';
    //     // {BASE URL}/{Service Name}/Rujukan/RS/{parameter}
    //     $no_rujukan = '132701010323P000001';
    //     // $no_rujukan = '1327R0010423K001408';

    //     // $url = 'https://apijkn-dev.bpjs-kesehatan.go.id/vclaim-rest-dev/Rujukan/' . $no_rujukan;
    //     // $url = $base_url . $service_name .  "/" . $no_rujukan;

    //     // $headers = [
    //     //     'X-cons-id' => $sign['xconsid'],
    //     //     'X-timestamp' => $sign['xtimestamp'],

    //     //     'X-signature' => $sign['xsignature'],
    //     //     'user_key' => $sign['user_key']
    //     // ];

    //     $url = BridgingbpjsHelper::get_url('vclaim') . 'Rujukan/' . $no_rujukan;
    //     // $url = BridgingbpjsHelper::get_url('vclaim') . 'referensi/poli/geriatri';


    //     // $url =  'https://apijkn-dev.bpjs-kesehatan.go.id/antreanrs_dev/' . 'ref/poli';
    //     // $url =  'https://apijkn-dev.bpjs-kesehatan.go.id/antreanrs_dev/' . 'antrean/getlisttask';
    //     // $url =  'https://apijkn-dev.bpjs-kesehatan.go.id/antreanrs_dev/' . 'ref/dokter';

    //     // return $headers;
    //     $response = Http::withHeaders(BridgingbpjsHelper::getHeader())->get($url);
    //     // if (!$response) {
    //     //     return response()->json([
    //     //         'message' => 'ERRROR'
    //     //     ], 500);
    //     // }

    //     // $statusCode = $response->status();
    //     // // $responseBody = json_decode(
    //     // //     $response->getBody(),
    //     // //     true
    //     // // );
    //     $data = json_decode($response, true);

    //     $kunci = $sign['xconsid'] . $sign['secret_key'] . $sign['xtimestamp'];

    //     if (!$data) {
    //         return response()->json([
    //             'code' => 500,
    //             'message' => 'ERRROR SIGNATURE'
    //         ], 500);
    //     }


    //     $nilairespon = $data["response"];
    //     $hasilakhir = BridgingbpjsHelper::decompress(BridgingbpjsHelper::stringDecrypt($kunci, $nilairespon));

    //     // $res['metadata'] = $data['metadata'];
    //     $res['result'] = json_decode($hasilakhir);

    //     if (!$hasilakhir) {
    //         return response()->json([
    //             'code' => 500,
    //             'message' => 'ERRROR METADATA'
    //         ], 500);
    //     }
    //     return $res;
    // }
}
