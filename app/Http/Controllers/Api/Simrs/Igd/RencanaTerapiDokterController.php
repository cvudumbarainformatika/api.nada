<?php

namespace App\Http\Controllers\Api\Simrs\Igd;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Rajal\Igd\RencanaTerapiDokter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RencanaTerapiDokterController extends Controller
{
    public function simpan(Request $request)
    {
        $wew = FormatingHelper::session_user();
        $kdpegsimrs = $wew['kodesimrs'];
        $kdgroupnakes = $wew['kdgroupnakes'];
        if($kdgroupnakes !== '1')
        {
            return new JsonResponse([
                'message' => 'Maaf Akun Anda Bukan Dokter...!!!'
            ], 500);
        }
        $simpan = RencanaTerapiDokter::updateOrCreate(
            [
                'id' => $request->id
            ],
            [
                'noreg' => $request->noreg,
                'norm' => $request->norm,
                'tgl' => date('Y-m-d'),
                'ruangan' => $request->ruangan,
                'targetdokter' => $request->targetdokter,
                'rencanaterapi' => $request->rencanaterapi,
                'monitoringpasien' => $request->monitoringpasien,
                'user_id' => $kdpegsimrs
            ]
        );
        if(!$simpan)
        {
            return new JsonResponse([
                'message' => 'Gagal disimpan...!!!'
            ], 500);
        }
        return new JsonResponse([
            'message' => 'Berhasil disimpan...!!!',
            'data' => $simpan
        ], 200);
    }

    public function hapus(Request $request)
    {
        $wew = FormatingHelper::session_user();
        $kdpegsimrs = $wew['kodesimrs'];
        $cari = RencanaTerapiDokter::find($request->id);
        if($cari['user_id'] !== $kdpegsimrs)
        {
            return new JsonResponse(['message' => 'Maaf Akun Anda Bukan Pengentry'],500);
        }
        $hapus=$cari->delete();

        if(!$hapus)
        {
            return new JsonResponse(['message' => 'Data Gagal Dihapus'],500);
        }

        return new JsonResponse(['message' => 'Data Berhasil Dihapus'],200);
    }
}
