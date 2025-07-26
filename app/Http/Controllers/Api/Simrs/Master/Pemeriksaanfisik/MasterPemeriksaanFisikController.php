<?php

namespace App\Http\Controllers\Api\Simrs\Master\Pemeriksaanfisik;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mpemeriksaanfisik;
use App\Models\Simrs\Master\Mtemplategambar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MasterPemeriksaanFisikController extends Controller
{
    public function index()
    {

        $data = Mpemeriksaanfisik::all();
        return new JsonResponse($data->load('gambars'), 200);
    }

    public function simpanmasterpemeriksaan(Request $request)
    {
        $data = null;
        if ($request->has('id')) {
            $data = Mpemeriksaanfisik::find($request->id);
            $data->nama = $request->nama;
            $data->icon = $request->icon;
            $data->lokalis = $request->lokalis;
            $data->save();
        } else {
            $data = Mpemeriksaanfisik::create(
                [
                    'nama' => $request->nama,
                    'icon' => $request->icon,
                    'lokalis' => $request->lokalis
                ]
            );
        }

        return new JsonResponse(['message' => 'Berhasil disimpan', 'result' => $data->load('gambars')], 200);
    }

    public function uploads(Request $request)
    {
        if ($request->hasFile('images')) {
            $files = $request->file('images');
            if (!empty($files)) {

                for ($i = 0; $i < count($files); $i++) {
                    $file = $files[$i];
                    $originalname = $file->getClientOriginalName();
                    $penamaan = date('YmdHis') . '-' . $i . '-' . $request->mpemeriksaanfisik_id . '.' . $file->getClientOriginalExtension();
                    $data = Mtemplategambar::where('original', $originalname)->first();
                    // Storage::delete('public/templategambarpemeriksaanfisik/' . $originalname);
                    // Storage::disk('remote')->delete('public/templategambarpemeriksaanfisik/' . $originalname);

                    $gallery = null;
                    if ($data) {
                        $gallery = $data;
                    } else {
                        $gallery = new Mtemplategambar();
                    }
                    $path = $file->storeAs('public/templategambarpemeriksaanfisik', $penamaan, 'remote');
                    // $target = storage_path() . "/app/public/templategambarpemeriksaanfisik/" . $penamaan;
                    $target = config('filesystems.disks.remote.root') . '/' . $path;
                    $type = pathinfo($target, PATHINFO_EXTENSION);
                    $data = file_get_contents($target);
                    $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                    // $base64 = 'data:' . mime_content_type($target) . ';base64,' . base64_encode($target); //ini baru



                    $gallery->nama = $path;
                    $gallery->url = 'templategambarpemeriksaanfisik/' . $penamaan;
                    $gallery->original = $originalname;
                    $gallery->mpemeriksaanfisik_id = $request->mpemeriksaanfisik_id;
                    $gallery->image = $base64;
                    $gallery->save();
                }
                $res = Mpemeriksaanfisik::find($request->mpemeriksaanfisik_id);
                return new JsonResponse(['message' => 'success', 'result' => $res->load('gambars')], 200);
            }
        }
    }

    public function deletetemplate(Request $request)
    {
        $template = Mtemplategambar::find($request->id);
        // Storage::delete('public/templategambarpemeriksaanfisik/' . $template->original);
        // Storage::disk('remote')->delete('public/templategambarpemeriksaanfisik/' . $template->original);
        $template->delete();

        $res = Mpemeriksaanfisik::find($request->mpemeriksaanfisik_id);

        return new JsonResponse(['message' => 'success', 'result' => $res->load('gambars')], 200);
    }
}
