<?php

namespace App\Http\Controllers\Api\Mobile\Simrs\Pelayanan\Poli;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\MdokumenUpload;
use App\Models\Simrs\Pelayanan\DokumenUpload;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{

    public function master()
    {
        $data = MdokumenUpload::pluck('nama');
        return new JsonResponse($data);
    }

    public function store(Request $request)
    {
        // return response()->json($request->all());
        if ($request->hasFile('dokumen')) {

            $files = $request->file('dokumen');

            $user = auth()->user()->pegawai_id;

            if (!empty($files)) {

                // for ($i = 0; $i < count($files); $i++) {
                $file = $files;
                $originalname = $file->getClientOriginalName();
                $penamaan = date('YmdHis') . '-xenter-' . $request->norm . '.' . $file->getClientOriginalExtension();
                $gallery = new DokumenUpload();
                $path = $file->storeAs('public/dokumen_luar_poli', $penamaan, 'remote');

                $gallery->noreg = $request->noreg;
                $gallery->norm = $request->norm;
                $gallery->nama = $request->nama;
                $gallery->path = $path;
                $gallery->url = 'dokumen_luar_poli/' . $penamaan;
                $gallery->original = $originalname;
                $gallery->user_input = $user;
                $gallery->save();
                // }

                $kirim = DokumenUpload::where([['noreg', '=', $request->noreg]])->get();
                return new JsonResponse(['message' => 'success', 'result' => $kirim->load('pegawai:id,nama')], 200);
            }

            return new JsonResponse(['message' => 'invalid dokumen'], 500);
        }
        return new JsonResponse(['message' => 'invalid dokumen'], 500);
    }

    public function dokumenBy()
    {
        $kirim = DokumenUpload::where([['noreg', '=', request('noreg')]])->get();
        return new JsonResponse(['message' => 'success', 'result' => $kirim->load('pegawai:id,nama')], 200);
    }


    public function deletedata(Request $request)
    {

        $data = DokumenUpload::find($request->id);

        if (!$data) {
            return new JsonResponse(['message' => 'Data tidak ditemukan'], 500);
        }
        // Storage::delete($data->path);
        //   Storage::disk('remote')->delete($data->path);
        $del = $data->delete();

        if (!$del) {
            return new JsonResponse(['message' => 'Failed'], 500);
        }

        return new JsonResponse(['message' => 'Data Berhasil dihapus'], 200);
    }
}
