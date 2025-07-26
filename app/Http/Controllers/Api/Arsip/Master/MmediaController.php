<?php

namespace App\Http\Controllers\Api\Arsip\Master;

use App\Http\Controllers\Controller;
use App\Models\Arsip\Master\MmediaArsip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MmediaController extends Controller
{
    public function simpan(Request $request)
    {
        $simpan = MmediaArsip::updateOrCreate(
            [
                'id' => $request->id
            ],
            [
                'nama_media' => $request->media,
            ]
        );
        if(!$simpan)
        {
            return new JsonResponse(['message' => 'Data Gagal Disimpan'], 500);
        }

        $result = self::listmastermedia();

        return new JsonResponse(['message' => 'Data Berhasil Disimpan','result' => $result], 200);
    }

    public static function listmastermedia()
    {
        $list = MmediaArsip::whereNull('hide')
        ->where(function ($query) {
            $query->where('nama_media', 'LIKE', '%' . request('q') . '%');
        })
        ->get();
        return ($list);
    }

    public function hapusmastermedia(Request $request)
    {
        $update = MmediaArsip::where('id',$request->id)->first();
        $update->hide = '1';
        $update->save();

        $result = self::listmastermedia();

        return new JsonResponse(['message' => 'Data Berhasil Dihapus...!!!', 'result' => $result], 200);
    }
}
