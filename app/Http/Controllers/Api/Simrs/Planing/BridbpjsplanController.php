<?php

namespace App\Http\Controllers\Api\Simrs\Planing;

use App\Helpers\BridgingbpjsHelper;
use App\Helpers\DateHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Pendaftaran\Rajalumum\Bpjs_http_respon;
use App\Models\Simrs\Planing\Simpanspri;
use App\Models\Simrs\Planing\Transrujukan;
use Illuminate\Http\Request;

class BridbpjsplanController extends Controller
{
    public static function bridcretaerujukan($request)
    {
        // menghindari lengt user bpjs yang minta minimal 3 karakter
        $data = auth()->user()->pegawai_id;
        $len = strlen($data);
        $use = $len === 1 ? '000' . $data : ($len === 2 ? '00' . $data : ($len === 3 ? '0' . $data : $data));

        $data = [
            "request" => [
                "t_rujukan" => [
                    "noSep" => $request->nosep,
                    "tglRujukan" => $request->tglrujukan,
                    'tglRencanaKunjungan' => $request->tglrencanakunjungan,
                    "ppkDirujuk" => $request->ppkdirujuk,
                    "jnsPelayanan" => $request->jenispelayanan,
                    "catatan" => $request->catatan,
                    "diagRujukan" => $request->diagnosarujukan,
                    "tipeRujukan" => $request->tiperujukan,
                    "poliRujukan" => $request->polirujukan,
                    'user' => $use

                    // "noSep" => '1327R0010923V008341',
                    // "tglRujukan" => '2023-09-29',
                    // 'tglRencanaKunjungan' => '2023-10-10',
                    // "ppkDirujuk" => '1323R001',
                    // "jnsPelayanan" => '2',
                    // "catatan" => 'coba ws',
                    // "diagRujukan" => 'A15',
                    // "tipeRujukan" => '0',
                    // "poliRujukan" => 'BSY',
                    // 'user' => auth()->user()->pegawai_id
                ]
            ]
        ];
        $tgltobpjshttpres = DateHelper::getDateTime();

        $bridcretaerujukan = BridgingbpjsHelper::post_url(
            'vclaim',
            'Rujukan/2.0/insert',
            $data
        );

        Bpjs_http_respon::create(
            [
                'method' => 'POST',
                'noreg' => $request->noreg,
                'request' => $data,
                'respon' => $bridcretaerujukan,
                'url' => '/Rujukan/2.0/insert',
                'tgl' => $tgltobpjshttpres
            ]
        );

        $xxx = $bridcretaerujukan['metadata']['code'];

        if ($xxx === 200 || $xxx === '200') {
            $norujukan = $bridcretaerujukan['response']->rujukan->noRujukan;
            $simpanrujukan = Transrujukan::create(
                [
                    'rs1' => $request->noreg,
                    'rs2' => $request->norm,
                    'rs3' => $norujukan,
                    'rs4' => $request->nosep,
                    'rs5' => $request->tglrujukan,
                    'rs6' => $request->ppkdirujuk,
                    'rs7' => $request->namappkdirujuk,
                    'rs8' => $request->jenispelayanan,
                    'rs9' => $request->catatan,
                    'rs10' => $request->diagnosarujukan,
                    'rs11' => $request->tiperujukan,
                    'rs12' => $request->kodepoli,
                    'rs13' => date('Y-m-d H:i:s'),
                    'rs14' => auth()->user()->pegawai_id,
                    'rs15' => $request->noka,
                    'rs16' => $request->nama,
                    'rs17' => $request->kelamin,
                    'tglRencanaKunjungan' => $request->tglrencanakunjungan,
                    'diagnosa' => $request->diagnosa,
                    'poli' => $request->namapolirujukan,
                    'tipefaskes' => $request->tipefaskes,
                    'polix' => $request->polirujukan
                ]
            );

            if (!$simpanrujukan) {
                return 500;
            }
            return 200;
        }

        return $bridcretaerujukan;
    }
    public static function bridUpdateRujukan($request)
    {
        // menghindari lengt user bpjs yang minta minimal 3 karakter
        $data = auth()->user()->pegawai_id;
        $len = strlen($data);
        $use = $len === 1 ? '000' . $data : ($len === 2 ? '00' . $data : ($len === 3 ? '0' . $data : $data));

        $data = [
            "request" => [
                "t_rujukan" => [
                    "noRujukan" => $request->norujukan,
                    "tglRujukan" => $request->tglrujukan,
                    'tglRencanaKunjungan' => $request->tglrencanakunjungan,
                    "ppkDirujuk" => $request->ppkdirujuk,
                    "jnsPelayanan" => $request->jenispelayanan,
                    "catatan" => $request->catatan,
                    "diagRujukan" => $request->diagnosarujukan,
                    "tipeRujukan" => $request->tiperujukan,
                    "poliRujukan" => $request->polirujukan,
                    'user' => $use


                    // "noSep" => '1327R0010923V008341',
                    // "tglRujukan" => '2023-09-29',
                    // 'tglRencanaKunjungan' => '2023-10-10',
                    // "ppkDirujuk" => '1323R001',
                    // "jnsPelayanan" => '2',
                    // "catatan" => 'coba ws',
                    // "diagRujukan" => 'A15',
                    // "tipeRujukan" => '0',
                    // "poliRujukan" => 'BSY',
                    // 'user' => auth()->user()->pegawai_id
                ]
            ]
        ];
        $tgltobpjshttpres = DateHelper::getDateTime();

        $bridcretaerujukan = BridgingbpjsHelper::put_url(
            'vclaim',
            'Rujukan/2.0/update',
            $data
        );

        Bpjs_http_respon::create(
            [
                'method' => 'PUT',
                'noreg' => $request->noreg,
                'request' => $data,
                'respon' => $bridcretaerujukan,
                'url' => '/Rujukan/2.0/update',
                'tgl' => $tgltobpjshttpres
            ]
        );

        $xxx = $bridcretaerujukan['metadata']['code'];

        if ($xxx === 200 || $xxx === '200') {
            $norujukan = $bridcretaerujukan['result'];
            $simpanrujukan = Transrujukan::updateOrCreate(
                [
                    'rs1' => $request->noreg,
                ],
                [
                    'rs2' => $request->norm,
                    'rs3' => $norujukan,
                    'rs4' => $request->nosep,
                    'rs5' => $request->tglrujukan,
                    'rs6' => $request->ppkdirujuk,
                    'rs7' => $request->namappkdirujuk,
                    'rs8' => $request->jenispelayanan,
                    'rs9' => $request->catatan,
                    'rs10' => $request->diagnosarujukan,
                    'rs11' => $request->tiperujukan,
                    'rs12' => $request->kodepoli,
                    'rs13' => date('Y-m-d H:i:s'),
                    'rs14' => auth()->user()->pegawai_id,
                    'rs15' => $request->noka,
                    'rs16' => $request->nama,
                    'rs17' => $request->kelamin,
                    'tglRencanaKunjungan' => $request->tglrencanakunjungan,
                    'diagnosa' => $request->diagnosa,
                    'poli' => $request->namapolirujukan,
                    'tipefaskes' => $request->tipefaskes,
                    'polix' => $request->polirujukan
                ]
            );

            if (!$simpanrujukan) {
                return 500;
            }
            return 200;
        }

        return $bridcretaerujukan;
    }

    public function deleterujukan()
    {
        $data = [
            "request" => [
                "t_rujukan" => [
                    "noRujukan" => "0301R0011117B000015",
                    "user" => "Coba Ws"
                ]
            ]
        ];

        $deleterujukan = BridgingbpjsHelper::post_url(
            'vclaim',
            'Rujukan/2.0/delete',
            $data
        );
        return $deleterujukan;
    }

    public function faskes()
    {
        $namafaskes = request('namafaskes');
        $jnsfaskes = request('jnsfaskes');
        $faskes = BridgingbpjsHelper::get_url('vclaim', '/referensi/faskes/' . $namafaskes . '/' . $jnsfaskes);
        return $faskes;
    }
    public function diagPrb()
    {
        $data = BridgingbpjsHelper::get_url('vclaim', '/referensi/diagnosaprb');
        return $data;
    }
    public static function createPrb($request)
    {
        $user = auth()->user()->pegawai_id;
        $len = strlen($user);
        $use = $len === 1 ? '000' . $user : ($len === 2 ? '00' . $user : ($len === 3 ? '0' . $user : $user));
        // $data = BridgingbpjsHelper::get_url('vclaim', '/referensi/diagnosaprb');
        $data = [
            'request' =>
            [
                't_prb' => [
                    'noSep' => $request->nosep,
                    'noKartu' => $request->noka,
                    'alamat' => $request->alamat,
                    'email' => $request->email,
                    'programPRB' => $request->diagnosa,
                    'kodeDPJP' => $request->kodedokterdpjp,
                    'keterangan' => $request->keterangan,
                    'saran' => $request->saran,
                    'obat' => $request->obat,
                    'user' => $use
                ]
            ]
        ];

        // $tgltobpjshttpres = DateHelper::getDateTime();
        // $sendToBpjs = BridgingbpjsHelper::post_url(
        //     'vclaim',
        //     '/PRB/insert',
        //     $data
        // );

        // Bpjs_http_respon::create(
        //     [
        //         'method' => 'POST',
        //         'noreg' => $request->noreg,
        //         'request' => $data,
        //         'respon' => $sendToBpjs,
        //         'url' => '/PRB/insert',
        //         'tgl' => $tgltobpjshttpres
        //     ]
        // );
        // $code = $sendToBpjs['metadata']['code'];
        // if ($code == 200 || $code == '200') {
        //     $response = $sendToBpjs['response'];
        //     $request->nosrb = ($response['noSRB'] ?? $response->noSRB) ?? null;
        // $request->bpjs_response = $sendToBpjs??null;
        // } else {
        //     return [
        //         'code' => 500,
        //         'sendToBpjs' => $sendToBpjs,
        //         'form' => $data
        //     ];
        // }

        $request->form = $data;
        $simpanLocal = PlaningController::simpanprb($request);
        if ($simpanLocal == 500) {
            return [
                'code' => 410,
                'sendToBpjs' => $sendToBpjs ?? null,
                'form' => $data,
                'simpanLocal' => $simpanLocal,


            ];
        }
        return [
            'code' => 200,
            'sendToBpjs' => $sendToBpjs ?? null,
            'form' => $data,
            'simpanLocal' => $simpanLocal,
        ];
    }

    public function polibpjs()
    {
        $namapoli = request('namapoli');
        $poli = BridgingbpjsHelper::get_url('vclaim', '/referensi/poli/' . $namapoli);
        return $poli;
    }

    public static function createspri($request)
    {
        // menghindari lengt user bpjs yang minta minimal 3 karakter
        $data = auth()->user()->pegawai_id;
        $len = strlen($data);
        $use = $len === 1 ? '000' . $data : ($len === 2 ? '00' . $data : ($len === 3 ? '0' . $data : $data));

        $tgltobpjshttpres = DateHelper::getDateTime();
        $data = [
            "request" =>
            [
                "noKartu" => $request->noka,
                "kodeDokter" => $request->kodedokterdpjp,
                "poliKontrol" => $request->kodepolibpjs,
                "tglRencanaKontrol" => $request->tglrencanakontrol,
                "user" => $use
            ]
        ];

        $createspri = BridgingbpjsHelper::post_url(
            'vclaim',
            'RencanaKontrol/InsertSPRI',
            $data
        );
        Bpjs_http_respon::create(
            [
                'method' => 'POST',
                'noreg' => $request->noreg ?? '',
                'request' => $data,
                'respon' => $createspri,
                'url' => '/RencanaKontrol/InsertSPRI',
                'tgl' => $tgltobpjshttpres
            ]
        );
        return $createspri;
    }
    public static function updateSpri($request)
    {
        // menghindari lengt user bpjs yang minta minimal 3 karakter
        $data = auth()->user()->pegawai_id;
        $len = strlen($data);
        $use = $len === 1 ? '000' . $data : ($len === 2 ? '00' . $data : ($len === 3 ? '0' . $data : $data));

        $tgltobpjshttpres = DateHelper::getDateTime();
        $data = [
            "request" =>
            [
                "noSPRI" => $request->nospri,
                "kodeDokter" => $request->kodedokterdpjp,
                "poliKontrol" => $request->kodepolibpjs,
                "tglRencanaKontrol" => $request->tglrencanakontrol,
                "user" => $use
            ]
        ];

        $createspri = BridgingbpjsHelper::put_url(
            'vclaim',
            'RencanaKontrol/UpdateSPRI',
            $data
        );
        Bpjs_http_respon::create(
            [
                'method' => 'PUT',
                'noreg' => $request->noreg ?? '',
                'request' => $data,
                'respon' => $createspri,
                'url' => '/RencanaKontrol/UpdateSPRI',
                'tgl' => $tgltobpjshttpres
            ]
        );
        // $xxx = $createspri['metadata']['code'];
        // if ($xxx === 200 || $xxx === '200') {
        //     $nospri = $createspri['response']->noSPRI;
        //     $simpanspri = Simpanspri::updateOrCreate(
        //         [
        //             'noreg' => $request->noreg,
        //         ],
        //         [
        //             'noSuratKontrol' => $nospri,
        //             'norm' => $request->norm,
        //             'kodeDokter' => $request->kddokter,
        //             'poliKontrol' => $request->kodepolibpjs,
        //             'tglRencanaKontrol' => $request->tglrencanakunjungan,
        //             'namaDokter' => $request->dokter,
        //             'noKartu' => $request->noka,
        //             'nama' => $request->nama,
        //             'kelamin' => $request->kelamin,
        //             'tglLahir' => $request->tgllahir,
        //             'user_id' => auth()->user()->pegawai_id
        //         ]
        //     );
        //     return $simpanspri;
        // }
        return $createspri;
    }

    public function bridbpjslistrujukan()
    {
        $listrujukan = BridgingbpjsHelper::get_url('vclaim', '/Rujukan/Keluar/List/tglMulai/2023-10-10/tglAkhir/2023-10-10');
        return $listrujukan;
    }

    public static function insertsuratcontrol($request)
    {
        // menghindari lengt user bpjs yang minta minimal 3 karakter
        $data = auth()->user()->pegawai_id;
        $len = strlen($data);
        $use = $len === 1 ? '000' . $data : ($len === 2 ? '00' . $data : ($len === 3 ? '0' . $data : $data));

        $tgltobpjshttpres = DateHelper::getDateTime();
        $data = [
            "request" =>
            [
                "noSEP" => $request->nosep,
                "kodeDokter" => $request->kodedokterdpjp,
                "poliKontrol" => $request->kodepolibpjs,
                "tglRencanaKontrol" => $request->tglrencanakunjungan,
                "user" => $use
            ]
        ];

        $insernokontrol = BridgingbpjsHelper::post_url(
            'vclaim',
            'RencanaKontrol/insert',
            $data
        );

        Bpjs_http_respon::create(
            [
                'method' => 'POST',
                'noreg' => $request->noreg ?? '',
                'request' => $data,
                'respon' => $insernokontrol,
                'url' => '/RencanaKontrol/insert',
                'tgl' => $tgltobpjshttpres
            ]
        );
        return $insernokontrol;
    }
    public static function hapussuratcontrol($request, $nosurat)
    {
        // menghindari lengt user bpjs yang minta minimal 3 karakter
        $data = auth()->user()->pegawai_id;
        $len = strlen($data);
        $use = $len === 1 ? '000' . $data : ($len === 2 ? '00' . $data : ($len === 3 ? '0' . $data : $data));

        $tgltobpjshttpres = DateHelper::getDateTime();
        $data = [
            "request" =>
            [
                "t_suratkontrol" => [
                    "noSuratKontrol" => $nosurat,
                    "user" => $use
                ]
            ]
        ];

        $insernokontrol = BridgingbpjsHelper::delete_url(
            'vclaim',
            'RencanaKontrol/Delete',
            $data
        );

        Bpjs_http_respon::create(
            [
                'method' => 'POST',
                'noreg' => $request->noreg ?? '',
                'request' => $data,
                'respon' => $insernokontrol,
                'url' => '/RencanaKontrol/Delete',
                'tgl' => $tgltobpjshttpres
            ]
        );
        return $insernokontrol;
    }
}
