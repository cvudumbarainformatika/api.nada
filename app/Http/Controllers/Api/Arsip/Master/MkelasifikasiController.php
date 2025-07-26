<?php

namespace App\Http\Controllers\Api\Arsip\Master;

use App\Http\Controllers\Controller;
use App\Models\Arsip\Master\MkelasifikasiArsip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MkelasifikasiController extends Controller
{
    public function simpan(Request $request)
    {
        $simpan = MkelasifikasiArsip::updateOrCreate(
            [
                'id' => $request->id
            ],
            [
                'kode' => $request->kode,
                'nama' => $request->kelasifikasi,
                'retensi' => $request->retensi
            ]
        );
        if(!$simpan)
        {
            return new JsonResponse(['message' => 'Data Gagal Disimpan'], 500);
        }

        $result = self::listmkelasifikasi();

        return new JsonResponse(['message' => 'Data Berhasil Disimpan','result' => $result], 200);
    }

    public static function listmkelasifikasi()
    {
        $list = MkelasifikasiArsip::where('hide','')
        ->where(function ($query) {
            $query->where('kode', 'LIKE', '%' . request('q') . '%')
                ->orWhere('nama', 'LIKE', '%' . request('q') . '%');
        })
        ->get();
        return ($list);
    }

    public function hapuskelasifikasi(Request $request)
    {
        $update = MkelasifikasiArsip::where('id',$request->id)->first();
        $update->hide = '1';
        $update->save();

        $result = self::listmkelasifikasi();

        return new JsonResponse(['message' => 'Data Berhasil Dihapus...!!!', 'result' => $result], 200);
    }
}
