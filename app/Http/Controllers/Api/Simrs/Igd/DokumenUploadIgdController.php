<?php

namespace App\Http\Controllers\Api\Simrs\Igd;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\MdokumenUpload;
use App\Models\Simrs\Pelayanan\DokumenUpload;
use Intervention\Image\Facades\Image;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DokumenUploadIgdController extends Controller
{
    public function master()
    {
        $data = MdokumenUpload::where('igd', '1')
            ->pluck('nama');
        return new JsonResponse($data);
    }

    public function store(Request $request)
    {
        // return response()->json($request->all());
        if ($request->hasFile('dokumen')) {

            try {
                $files = $request->file('dokumen');

                $user = auth()->user()->pegawai_id;

                if (!empty($files)) {

                    for ($i = 0; $i < count($files); $i++) {
                        $file = $files[$i];

                        $originalname = $file->getClientOriginalName();
                        $penamaan = date('YmdHis') . '-' . $i . '-' . $request->norm . '.' . $file->getClientOriginalExtension();

                        $extension = $file->getClientOriginalExtension();

                        // return new JsonResponse($extension);
                        $data = DokumenUpload::where([
                            ['noreg', $request->noreg],
                            ['original', $originalname]
                        ])->first();
                        if ($data) {
                            // Storage::delete($data->path);
                            // Storage::disk('remote')->delete($data->path);
                        }

                        $gallery = null;
                        if ($data) {
                            $gallery = $data;
                        } else {
                            $gallery = new DokumenUpload();
                        }

                        $folder = 'dokumen_luar_igd';



                        // if (!is_dir(storage_path("app/public/$folder"))) {
                        //   mkdir(storage_path("app/public/$folder"), 0775, true);
                        // }

                        if (!Storage::disk('remote')->exists("public/$folder")) {
                            Storage::disk('remote')->makeDirectory("public/$folder");
                        }

                        // // Upload Avatar (IMAGE INTERVENTION - LARAVEL)
                        // Image::make($request->file("upload_image"))->save(storage_path("app/public/post-images/".$id.".png"));

                        if ($extension !== 'pdf') {


                            $img = Image::make($file)->resize(600, null, function ($constraint) {
                                $constraint->aspectRatio();
                            });

                            // $img->save(\public_path("storage/$folder/". $penamaan), 60);
                            // Buat file temporary dengan nama random (contoh: /tmp/resize_8jf9d2)
                            $tempPath = tempnam(sys_get_temp_dir(), 'resize_');
                            $img->save($tempPath, 60);

                            // $img->save(\public_path("storage/$folder/". $penamaan), 60);
                            // Upload ke remote dengan nama file yang kita inginkan ($penamaan)
                            // Contoh $penamaan: 20240219123456-1-123456.jpg
                            Storage::disk('remote')->put(
                                "public/$folder/$penamaan",  // full path dengan nama file
                                file_get_contents($tempPath)  // isi file
                            );

                            // Hapus file temporary
                            unlink($tempPath);
                        } else {
                            // $path = $file->storeAs('public/'.$folder, $penamaan);
                            $path = $file->storeAs('public/' . $folder, $penamaan, 'remote');
                        }

                        $gallery->noreg = $request->noreg;
                        $gallery->norm = $request->norm;
                        $gallery->nama = $request->nama;
                        $gallery->path = "public/$folder/$penamaan";
                        $gallery->url = $folder . '/' . $penamaan;
                        $gallery->original = $originalname;
                        $gallery->ruangan = $request->ruangan;
                        $gallery->user_input = $user;
                        $gallery->save();
                    }

                    $kirim = DokumenUpload::where([['noreg', '=', $request->noreg]])->get();
                    return new JsonResponse(['message' => 'success', 'result' => $kirim->load('pegawai:id,nama')], 200);
                }
            } catch (\Exception $th) {
                return new JsonResponse(['message' => 'invalid dokumen', 'error' => $th->getMessage()], 500);
            }
        }
        return new JsonResponse(['message' => 'invalid dokumen'], 500);
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
