<?php

namespace App\Http\Controllers\Api\Anjungan;

use App\Helpers\BridgingbpjsHelper;
use App\Http\Controllers\Controller;
use App\Models\KunjunganPoli;
use App\Models\Pasien;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AnjunganController extends Controller
{

    public function cari_rujukan()
    {
        // $rujukan = false;
        $rujukanPcare = BridgingbpjsHelper::get_url('vclaim', 'Rujukan/' . request('search'));
        return $rujukanPcare;
    }
    public function cari_rujukan_rs()
    {
        // $rujukan = false;
        $rujukanPcare = BridgingbpjsHelper::get_url('vclaim', 'Rujukan/RS/' . request('search'));
        return $rujukanPcare;
    }
    public function cek_jumlah_sep()
    {
        // $rujukan = false;
        $data = BridgingbpjsHelper::get_url('vclaim', 'Rujukan/JumlahSEP/' . request('jenisrujukan') . '/' . request('norujukan'));
        return $data;
    }

    public function cariRencanaKontrol()
    {
        return BridgingbpjsHelper::get_url('vclaim', 'RencanaKontrol/noSuratKontrol/' . request('search'));
    }

    public function cari_dokter()
    {
        return BridgingbpjsHelper::get_url('antrean', 'jadwaldokter/kodepoli/' . request('kodepoli') . "/tanggal/" . request('tanggal'));
    }
    public function cari_noka()
    {
        $cari = Pasien::where('rs46', request('noka'))->first();
        if (!$cari) {
            return response()->json(['result' => 'Tidak ditemukan']);
        }
        return response()->json(['result' => $cari]); // ditemukan
    }

    public function cari_norm()
    {
        $cari = Pasien::where('rs1', request('search'))->first();
        if (!$cari) {
            return response()->json(['result' => 'Tidak ditemukan']);
        }
        return response()->json(['result' => $cari]); // ditemukan
    }
}
