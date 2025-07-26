<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Helpers\BridgingbpjsHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BridgingbpjsController extends Controller
{
    public function cekpsertabpjsbynoka(Request $request)
    {
        $cekpsereta = BridgingbpjsHelper::get_url(
            'vclaim',
            'Peserta/nokartu/' . $request->noka . '/tglSEP/' . $request->tglsep
        );
        // $wew = $cekpsereta['result']->peserta->provUmum;
        return ($cekpsereta);
    }

    public function listpoli(Request $request)
    {
        $cekpsereta = BridgingbpjsHelper::get_url(
            'vclaim',
            'referensi/poli/' . $request->poli
        );
        // $wew = $cekpsereta['result']->peserta->provUmum;
        return ($cekpsereta);
    }

    public function cekpsertabpjsbynik(Request $request)
    {
        $cekpseretax = BridgingbpjsHelper::get_url(
            'vclaim',
            'Peserta/nik/' . $request->nik . '/tglSEP/' . $request->tglsep
        );
        // $wew = $cekpsereta['result']->peserta->provUmum;
        return ($cekpseretax);
    }

    public function listrujukanpcare(Request $request)
    {
        $listrujukanpcare = BridgingbpjsHelper::get_url(
            'vclaim',
            'Rujukan/List/Peserta/' . $request->noka
        );
        return ($listrujukanpcare);
    }

    public function listrujukanrs(Request $request)
    {
        $listrujukanrs = BridgingbpjsHelper::get_url(
            'vclaim',
            '/Rujukan/RS/List/Peserta/' . $request->noka
        );
        return ($listrujukanrs);
    }

    public function diagnosabybpjs(Request $request)
    {
        if ($request->kodediagnosa != '') {
            $diagnosa = BridgingbpjsHelper::get_url(
                'vclaim',
                'referensi/diagnosa/' . $request->kodediagnosa
            );
            return ($diagnosa);
        }
        $diagnosa = BridgingbpjsHelper::get_url(
            'vclaim',
            'referensi/diagnosa/' . $request->diagnosa
        );
        return ($diagnosa);
    }

    public function faskesasalbpjs(Request $request)
    {
        $faskesbpjs = BridgingbpjsHelper::get_url(
            'vclaim',
            'referensi/faskes/' . $request->faskesasal . '/1'
        );
        return ($faskesbpjs);
    }

    public function dpjpbpjs(Request $request)
    {
        $dpjpbpjs = BridgingbpjsHelper::get_url(
            'vclaim',
            'referensi/dokter/pelayanan/' . $request->jenis_pelayanan . '/tglPelayanan/' . $request->tglsep . '/Spesialis/' . $request->kdmappolbpjs
        );
        return ($dpjpbpjs);
    }

    public function cekfingerprint(Request $request)
    {
        $cekfingerprint = BridgingbpjsHelper::get_url(
            'vclaim',
            'SEP/FingerPrint/Peserta/' . $request->noka . '/TglPelayanan/' . $request->tglsep
        );
        return ($cekfingerprint);
    }

    public function provinsibpjs(Request $request)
    {
        $provinsibpjs = BridgingbpjsHelper::get_url(
            'vclaim',
            'referensi/propinsi'
        );
        return ($provinsibpjs);
    }

    public function kabupatenbpjs(Request $request)
    {
        $kabupatenbpjs = BridgingbpjsHelper::get_url(
            'vclaim',
            'referensi/kabupaten/propinsi/' . $request->kodepropinsi
        );
        return ($kabupatenbpjs);
    }

    public function kecamatanbpjs(Request $request)
    {
        $kecamatanbpjs = BridgingbpjsHelper::get_url(
            'vclaim',
            'referensi/kecamatan/kabupaten/' . $request->kodekabupaten
        );
        return ($kecamatanbpjs);
    }

    public function ceksuplesibpjs(Request $request)
    {
        $ceksuplesibpjs = BridgingbpjsHelper::get_url(
            'vclaim',
            'sep/JasaRaharja/Suplesi/' . $request->noka . '/tglPelayanan/' . $request->tglsep
        );
        return ($ceksuplesibpjs);
    }

    public function rencanakontrolbpjs(Request $request)
    {
        $rencanakontrolbpjs = BridgingbpjsHelper::get_url(
            'vclaim',
            'RencanaKontrol/ListRencanaKontrol/Bulan/' . $request->bulan . '/Tahun/' . $request->tahun . '/Nokartu/' . $request->noka . '/filter/2'
        );
        return ($rencanakontrolbpjs);
    }

    public function carirujukanpcarebynorujukan(Request $request)
    {
        $carirujukanpcarebynorujukan = BridgingbpjsHelper::get_url(
            'vclaim',
            'Rujukan/' . $request->noka
        );
        return ($carirujukanpcarebynorujukan);
    }

    public function jadwaldokter(Request $request)
    {
        $tanggal = date('Y-m-d');
        $jadwaldokter = BridgingbpjsHelper::get_url(
            'antrean',
            'jadwaldokter/kodepoli/' . $request->kodepoli . '/tanggal/' . $tanggal
        );
        return ($jadwaldokter);
    }
}
