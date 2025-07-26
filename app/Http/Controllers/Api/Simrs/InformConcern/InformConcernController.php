<?php

namespace App\Http\Controllers\Api\Simrs\InformConcern;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\InformConcern\InformConcern;
use App\Models\Simrs\Konsultasi\Konsultasi;
use App\Models\Simrs\Master\Mhais;
use App\Models\Simrs\Master\Rstigapuluhtarif;
use App\Models\Simrs\Visite\Visite;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Polyfill\Intl\Idn\Info;

class InformConcernController extends Controller
{


    public function simpandata(Request $request)
    {


        $data = null;
        if ($request->has('id')) {
            $data = InformConcern::find($request->id);
        } else {
            $data = new InformConcern();
        }
        $data->noreg = $request->noreg;
        $data->norm = $request->norm;
        $data->tgl = date('Y-m-d H:i:s');
        $data->tanggal = $request->tanggal . ' ' . date('H:i:s');
        $data->pelaksana = $request->pelaksana;
        $data->pengedukasi = $request->pengedukasi;
        $data->penerimaEdukasi = $request->penerimaEdukasi;
        $data->diagnosis = $request->diagnosis;
        $data->dasarDiagnosis = $request->dasarDiagnosis;
        $data->tindakanMedis = $request->tindakanMedis;
        $data->indikasi = $request->indikasi;
        $data->tujuan = $request->tujuan;
        $data->tujuanLain = $request->tujuanLain;
        $data->tatacara = $request->tatacara;
        $data->resiko = $request->resiko;
        $data->resikoLain = $request->resikoLain;
        $data->komplikasi = $request->komplikasi;
        $data->prognosis = $request->prognosis;
        $data->alternatif = $request->alternatif;
        $data->ttdPetugas = $request->ttdPetugas;
        $data->ttdPasien = $request->ttdPasien;
        $data->hubunganDgPasien = $request->hubunganDgPasien;
        $data->keluarga = $request->keluarga;
        $data->nama = $request->nama;
        $data->lp = $request->lp;
        $data->tglLahir = $request->tglLahir;
        $data->noKtp = $request->noKtp;
        $data->alamat = $request->alamat;
        $data->telepon = $request->telepon;

        $data->kdDokter = $request->kdDokter;
        $data->kdPetugas = $request->kdPetugas;
        $data->saksiPasien = $request->saksiPasien;
        $data->setuju = $request->setuju;
        $data->kdRuang = $request->kdRuang;
        $data->jenis = $request->jenis;
        $data->user = $user['kodesimrs'] ?? '';
        $data->save();


        // save image
        $saved = InformConcern::find($data->id);

        $ttdDokter = self::saveImage($request, $request->ttdDokter, $data->id . '_dokter');
        $ttdPetugas = self::saveImage($request, $request->ttdPetugas, $data->id . '_petugas');
        $ttdSaksiPasien = self::saveImage($request, $request->ttdSaksiPasien, $data->id . '_saksi');
        $ttdygMenyatakan = self::saveImage($request, $request->ttdYgMenyatakan, $data->id . '_ygMenyatakan');

        $saved->ttdDokter = $ttdDokter;
        $saved->ttdPetugas = $ttdPetugas;
        $saved->ttdSaksiPasien = $ttdSaksiPasien;
        $saved->ttdYgMenyatakan = $ttdygMenyatakan;

        $saved->save();

        // return new JsonResponse(['message' => 'Data Berhasil Disimpan', 'result' => $data], 200);
        return new JsonResponse(['message' => 'Data Berhasil Disimpan', 'result' => $saved], 200);
    }

    static function saveImage($request, $image, $id)
    {

        $file = null;

        if ($image && $id) {
            $name = $id;
            $noreg = str_replace('/', '-', $request->noreg);
            $folderPath = "inform_concern/" . $noreg . '_' . $request->jenis . '/';

            $image_parts = explode(";base64,", $image);
            $image_type_aux = explode("image/", $image_parts[0]);
            $image_type = $image_type_aux[1];
            $image_base64 = base64_decode($image_parts[1], true);
            $file = $folderPath . $name . '.' . $image_type;

            $imageName = $name . '.' . $image_type;
            // Storage::delete('public/' . $folderPath . $imageName);
            //   Storage::disk('remote')->delete('public/' . $folderPath . $imageName);
            // Storage::disk('public')->put($folderPath . $imageName, $image_base64);
            Storage::disk('remote')->put('public/' . $folderPath . $imageName, $image_base64);
        }

        return $file;
    }

    public function hapusdata(Request $request)
    {
        $cek = InformConcern::find($request->id);
        if (!$cek) {
            return new JsonResponse(['message' => 'data tidak ditemukan'], 500);
        }


        // $noreg = str_replace('/', '-', $request->noreg);
        // $folderPath = "inform_concern/" . $noreg .'_'.$cek->jenis. '/';
        // Storage::delete('public/' . $cek->ttdPetugas);
        // Storage::delete('public/' . $cek->ttdDokter);
        // Storage::delete('public/' . $cek->ttdSaksiPasien);
        // Storage::delete('public/' . $cek->ttdYgMenyatakan);
        // Storage::disk('remote')->delete('public/' . $cek->ttdPetugas);
        // Storage::disk('remote')->delete('public/' . $cek->ttdDokter);
        // Storage::disk('remote')->delete('public/' . $cek->ttdSaksiPasien);
        // Storage::disk('remote')->delete('public/' . $cek->ttdYgMenyatakan);
        $hapus = $cek->delete();
        if (!$hapus) {
            return new JsonResponse(['message' => 'gagal dihapus'], 500);
        }
        return new JsonResponse(['message' => 'berhasil dihapus'], 200);
    }
}
