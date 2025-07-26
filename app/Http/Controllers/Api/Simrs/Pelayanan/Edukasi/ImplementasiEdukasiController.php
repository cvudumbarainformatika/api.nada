<?php

namespace App\Http\Controllers\Api\Simrs\Pelayanan\Edukasi;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Edukasi\ImplementasiEdukasi;
use App\Models\Simrs\Edukasi\Mkebutuhanedukasi;
use App\Models\Simrs\Edukasi\Mpenerimaedukasi;
use App\Models\Simrs\Edukasi\Transedukasi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImplementasiEdukasiController extends Controller
{
    public function list()
    {
        $data = ImplementasiEdukasi::select(
            'id',
            'noreg',
            'norm',
            'tgl',
            'metode',
            'materi',
            'materiLain',
            'media',
            'evaluasi',
            'penerima',
            'namaPenerima',
            'ttdPenerima',
            'nakes',
            'estimasi',
            'user',
            'kdruang'
        )
            ->with(['petugas:kdpegsimrs,nik,nama,kdgroupnakes'])
            ->where('noreg', request('noreg'))
            ->where('kdruang', '!=', 'POL014')
            ->orderBy('tgl', 'desc')
            ->get();

        return new JsonResponse($data);
    }

    public function saveData(Request $request)
    {
        $user = Pegawai::find(auth()->user()->pegawai_id);
        $kdpegsimrs = $user->kdpegsimrs;

        // return $request->all();
        $data = null;
        $ttdPasien = $request->ttdPenerima;
        if ($request->has('id')) {
            $data = ImplementasiEdukasi::find($request->id);
            if (!$data) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Data tidak ditemukan'
                ]);
            }
        } else {
            $data = new ImplementasiEdukasi();
        }


        $data->noreg = $request->noreg;
        $data->norm = $request->norm;
        $data->tgl = date('Y-m-d H:i:s');
        $data->metode = $request->metode;
        $data->materi = $request->materi;
        $data->materiLain = $request->materiLain;
        $data->media = $request->media;
        $data->evaluasi = $request->evaluasi;
        $data->penerima = $request->penerima;
        $data->namaPenerima = $request->namaPenerima;
        // $data->ttdPenerima = $request->ttdPenerima;
        $data->kdruang = $request->kdruang;
        $data->estimasi = $request->estimasi;
        $data->nakes = $request->nakes;
        $data->user = $kdpegsimrs;
        $data->save();

        if (!$data) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Gagal menyimpan data'
            ]);
        }


        $cek = ImplementasiEdukasi::find($data->id);



        if ($ttdPasien !== null || $ttdPasien !== "") {
            $isBase64 = self::is_base64_image($ttdPasien);
            if ($isBase64) {
                $ttdPasienx = $this->saveImage($request, $ttdPasien, $data->id);
                $cek->update([
                    'ttdPenerima' => $ttdPasienx
                ]);
            }
        }

        return new JsonResponse([
            'success' => true,
            'message' => 'success',
            'result' => $cek->load('petugas')
        ]);
    }

    static function saveImage($request, $image, $id)
    {

        $file = null;

        if ($image && $id) {
            $name = $id;
            $noreg = str_replace('/', '-', $request->noreg);
            $folderPath = "implementasi_edukasi/" . $noreg . '/';

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

    public static function is_base64_image($image)
    {
        $pattern = '/^data:image\/(jpeg|jpg|png|gif|bmp);base64,/';
        return preg_match($pattern, $image) === 1;
    }

    public function hapusData(Request $request)
    {
        $hapus = ImplementasiEdukasi::where('id', $request->id)->delete();

        if (!$hapus) {
            return new JsonResponse(['message' => 'Data Gagal Dihapus...!!!'], 500);
        }
        // $listedukasi = Transedukasi::where('noreg', $request->noreg);
        return new JsonResponse(
            [
                'message' => 'Data Berhasil Dihapus...!!!',
                // 'result' => $listedukasi
            ],
            200
        );
    }
}
