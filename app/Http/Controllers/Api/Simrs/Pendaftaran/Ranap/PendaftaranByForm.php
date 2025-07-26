<?php

namespace App\Http\Controllers\Api\Simrs\Pendaftaran\Ranap;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mpasien;
use App\Models\Simrs\Master\Mwna;
use App\Models\Simrs\Pendaftaran\Ranap\Sepranap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PendaftaranByForm extends Controller
{
    public static function store(Request $request)
    {
        $request->validate([
          'norm' => 'required|string|max:6|min:6',
          'nama'=>  'required|string',
          'tanggallahir' => 'required|date_format:Y-m-d'
        ]);

        $masterpasien = Mpasien::updateOrCreate(
          ['rs1' => $request->norm],
          [
              'rs2' => $request->nama,
              'rs3' => $request->sapaan ?? '',
              'rs4' => $request->alamat ?? '',
              'alamatdomisili' => $request->alamatDomisili ?? '',
              'rs5' => $request->kelurahan ?? '',
              'kd_kel' => $request->kd_kelurahan ?? '',
              'rs6' => $request->kecamatan ?? '',
              'kd_kec' => $request->kd_kecamatan ?? '',
              'rs7' => $request->rt ?? '',
              'rs8' => $request->rw ?? '',
              'rs10' => $request->propinsi ?? '',
              'kd_propinsi' => $request->kd_propinsi ?? '',
              'rs11' => $request->kota ?? '',
              'kd_kota' => $request->kd_kotakabupaten ?? '',
              'rs49' => $request->noktp ?? '',
              'rs37' => $request->tempatlahir ?? '',
              'rs16' => $request->tanggallahir ?? '',
              'rs17' => $request->kelamin ?? '',
              'rs19' => $request->pendidikan ?? '',
              'kd_kelamin' => $request->kd_kelamin ?? '',
              'rs22' => $request->agama ?? '',
              'kd_agama' => $request->kd_agama ?? '',
              'rs39' => $request->suku ?? '',
              'rs55' => $request->nohp ?? '',
              'bahasa' => $request->bahasa ?? '',
              // 'noidentitaslain' => $nomoridentitaslain,
              'namaibu' => $request->ibukandung ?? '',
              'kodepos' => $request->kodepos ?? '',
              'kd_negara' => $request->kd_negara ?? '',
              'kd_rt_dom' => $request->rtDomisili ?? '',
              'kd_rw_dom' => $request->rwDomisili ?? '',
              'kd_kel_dom' => $request->kd_kelurahan_dom ?? '',
              'kd_kec_dom' => $request->kd_kecamatan_dom ?? '',
              'kd_kota_dom' => $request->kd_kotakabupaten_dom ?? '',
              'kodeposdom' => $request->kodeposDomisili ?? '',
              'kd_prov_dom' => $request->kd_propinsi_dom ?? '',
              'kd_negara_dom' => $request->kd_negara_dom ?? '',
              'noteleponrumah' => $request->notelp ?? '',
              'kd_pendidikan' => $request->kd_pendidikan ?? '',
              'kd_pekerjaan' => $request->pekerjaan ?? '',
              'flag_pernikahan' => $request->statuspernikahan ?? '',
              'rs46' => $request->nokabpjs ?? '',
              'rs40' => $request->barulama ?? '',
              // 'gelardepan' => $gelardepan,
              // 'gelarbelakang' => $gelarbelakang,
              'bacatulis' => $request->bisabacatulis ?? ''
              // 'kdhambatan' => $request->kdhambatan
          ]
        );

        if ($request->kewarganegaraan === 'WNA') {
            Mwna::updateOrCreate(
                ['norm' => $request->norm],
                [
                    'kewarganegaraan' => $request->kewarganegaraan,
                    'paspor' => $request->paspor,
                    'country' => $request->country,
                    'city' => $request->city,
                    'region' => $request->region
                ]
                );
        }

        return $masterpasien;
     
    }



}
