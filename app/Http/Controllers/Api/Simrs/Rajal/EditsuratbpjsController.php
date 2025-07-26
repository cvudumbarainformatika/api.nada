<?php

namespace App\Http\Controllers\Api\Simrs\Rajal;

use App\Helpers\BridgingbpjsHelper;
use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Pendaftaran\Rajalumum\Bpjs_http_respon;
use App\Models\Simrs\Planing\Simpansuratkontrol;
use App\Models\Simrs\Planing\Transrujukan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EditsuratbpjsController extends Controller
{
    public function suratKOntrolByNoka()
    {
        $bulan = request('bulan');
        $tahun = request('tahun');
        $noka = request('noka');
        $filter = request('filter');
        // $bulan = '04';
        // $tahun = '2025';
        // $noka = '0000113144837';
        // $filter = '2';
        $suratkontrol = BridgingbpjsHelper::get_url('vclaim', '/RencanaKontrol/ListRencanaKontrol/Bulan/' . $bulan . '/Tahun/' . $tahun . '/Nokartu/' . $noka . '/filter/' . $filter);
        return new JsonResponse($suratkontrol);
    }
    public function listsuratkontrol()
    {
        $tglawal = request('tglawal');
        $tglakhir = request('tglakhir');
        $filter = request('filter');
        $listsuratkontrol = BridgingbpjsHelper::get_url('vclaim', '/RencanaKontrol/ListRencanaKontrol/tglAwal/' . $tglawal . '/tglAkhir/' . $tglakhir . '/filter/' . $filter);
        return new JsonResponse($listsuratkontrol);
    }

    public function editsuratkontrol(Request $request)
    {
        $user = FormatingHelper::session_user();
        $data = [
            "request" => [
                "noSuratKontrol" => $request->noSuratKontrol,
                "noSEP" => $request->noSepAsalKontrol,
                "kodeDokter" => $request->kodeDokter,
                "poliKontrol" => $request->poliTujuan,
                "tglRencanaKontrol" => $request->tglrencanakontrol,
                "user" => '000' . $user['kodesimrs']
            ]
        ];
        $editsuratkontrol = BridgingbpjsHelper::put_url(
            'vclaim',
            'RencanaKontrol/Update',
            $data
        );
        $cari = Simpansuratkontrol::where('noSuratKontrol', $request->noSuratKontrol)->first();
        $noreg = '';
        if ($cari) {
            $noreg = $cari->noreg;
        }
        Bpjs_http_respon::create(
            [
                'noreg' => $noreg,
                'method' => 'PUT',
                'request' => $data,
                'respon' => $editsuratkontrol,
                'url' => 'RencanaKontrol/Update',
                'tgl' => date('Y-m-d H:i:s')
            ]
        );
        $xxx = $editsuratkontrol['metadata']['code'];
        if ($xxx === 200 || $xxx === '200') {
            $cari = Simpansuratkontrol::where('noSuratKontrol', $request->noSuratKontrol)->first();
            if ($cari) {
                $cari->tglRencanaKontrol = $request->tglrencanakontrol;
                $cari->save();
            }
            return new JsonResponse(
                [
                    'result' => $editsuratkontrol
                ]
            );
        } else {
            return new JsonResponse(
                [
                    'result' => $editsuratkontrol
                ]
            );
        }
    }

    public function jadwaldokter(Request $request)
    {
        // $tanggal = date('Y-m-d');
        // $jadwaldokter = BridgingbpjsHelper::get_url(
        //     'antrean',
        //     'jadwaldokter/kodepoli/' . $request->poliTujuan . '/tanggal/' . $request->tglrencanakontrol
        // );
        $jadwaldokter = BridgingbpjsHelper::get_url(
            'vclaim',
            'RencanaKontrol/JadwalPraktekDokter/JnsKontrol/2/KdPoli/' . $request->poliTujuan . '/TglRencanaKontrol/' . $request->tglrencanakontrol
        );

        // return $jadwaldokter['result']->list;
        if ($jadwaldokter['metadata']['code'] === 200 || $jadwaldokter['metadata']['code'] === '200') {
            $list = $jadwaldokter['result']->list ?? [];
            $xx = [];
            if (count($list) > 0) {
                $xx = collect($list)->map(function ($x) {
                    return [
                        'kodedokter' => $x->kodeDokter,
                        'namadokter' => $x->namaDokter,
                        'kapasitas' => $x->kapasitas,
                        'jadwalpraktek' => $x->jadwalPraktek,
                    ];
                });
            }
            $jadwaldokter['result'] = $xx;
        }
        return ($jadwaldokter);
    }

    public function listrujukankeluarrs()
    {
        $listrujukankeluarrs = BridgingbpjsHelper::get_url(
            'vclaim',
            'Rujukan/Keluar/List/tglMulai/' . request('tglawal') . '/tglAkhir/' . request('tglakhir')
        );
        return new JsonResponse($listrujukankeluarrs);
    }

    public function updaterujukan(Request $request)
    {
        $user = FormatingHelper::session_user();
        $data = [
            "request" => [
                "t_rujukan" => [
                    "noRujukan" => $request->norujukan,
                    "tglRujukan" => $request->tglrujukan,
                    "tglRencanaKunjungan" => $request->tglrencanarujukan,
                    "ppkDirujuk" => $request->kdfaskestujuan,
                    "jnsPelayanan" => $request->jenispelayanan,
                    "catatan" => $request->catatan,
                    "diagRujukan" => $request->norujukan,
                    "tipeRujukan" => $request->typerujukan,
                    "poliRujukan" => $request->typerujukan ? '' : $request->typerujukan,
                    "user" => $user['kodesimrs']
                ]
            ]
        ];
        $editrujukan = BridgingbpjsHelper::put_url(
            'vclaim',
            'RencanaKontrol/Update',
            $data
        );
        $cari = Transrujukan::where('rs3', $request->norujukan)->first();
        $noreg = '';
        if ($cari) {
            $noreg = $cari->noreg;
        }
        Bpjs_http_respon::create(
            [
                'noreg' => $noreg,
                'method' => 'PUT',
                'request' => $data,
                'respon' => $editrujukan,
                'url' => 'Rujukan/2.0/Update',
                'tgl' => date('Y-m-d H:i:s')
            ]
        );
        $xxx = $editrujukan['metadata']['code'];
        if ($xxx === 200 || $xxx === '200') {
            $cari = Transrujukan::where('rs3', $request->norujukan)->first();
            if ($cari) {
                $cari->rs5 = $request->tglrencanakunjungan;
                $cari->save();
            }
            return new JsonResponse(
                [
                    'result' => $editrujukan
                ]
            );
        } else {
            return new JsonResponse(
                [
                    'result' => $editrujukan
                ]
            );
        }
    }
}
