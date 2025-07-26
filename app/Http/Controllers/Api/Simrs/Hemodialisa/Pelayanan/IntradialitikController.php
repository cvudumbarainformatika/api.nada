<?php

namespace App\Http\Controllers\Api\Simrs\Hemodialisa\Pelayanan;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Hemodialisa\Intradialitik;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IntradialitikController extends Controller
{
    public function list() {}
    public function simpan(Request $request)
    {
        $user = FormatingHelper::session_user()['kodesimrs'];
        if ($request->has('id')) {
            $data = Intradialitik::find($request->id);
            $data->update([
                'rs4' => $request->jamKe,
                'rs3' => $request->tgl,
                'rs5' => $request->keluhan,
                'rs6' => $request->bb,
                'rs7' => $request->kesadaran,
                'rs8' => $request->tkDarah,
                'rs9' => $request->napas,
                'rs10' => $request->suhu,
                'rs11' => $request->qb,
                'rs12' => $request->qd,
                'rs13' => $request->tkVena,
                'rs14' => $request->tmp,
                'rs15' => $request->uf,
                'rs16' => $request->assasement,
                'rs17' => $user,
            ]);
        } else {

            $data = Intradialitik::updateOrCreate(
                [
                    'rs1' => $request->noreg,
                    'rs2' => $request->norm,
                    'rs4' => $request->jamKe,
                    'rs3' => $request->tgl,
                ],
                [
                    'rs5' => $request->keluhan,
                    'rs6' => $request->bb,
                    'rs7' => $request->kesadaran,
                    'rs8' => $request->tkDarah,
                    'rs9' => $request->napas,
                    'rs10' => $request->suhu,
                    'rs11' => $request->qb,
                    'rs12' => $request->qd,
                    'rs13' => $request->tkVena,
                    'rs14' => $request->tmp,
                    'rs15' => $request->uf,
                    'rs16' => $request->assasement,
                    'rs17' => $user,

                ]
            );
        }
        $data->load('user:nama,kdpegsimrs');

        return new JsonResponse([
            'message' => 'Data berhasil disimpan',
            'req' => $request->all(),
            'user' => $user,
            'data' => $data,
        ]);
    }
    public function hapus(Request $request)
    {
        $data = Intradialitik::find($request->id);
        if (!$data) {
            return new JsonResponse([
                'message' => 'Data tidak ditemukan',
            ], 410);
        }
        $data->delete();

        return new JsonResponse([
            'message' => 'Data berhasil dihapus',
            // 'data' => $data,
        ]);
    }
}
