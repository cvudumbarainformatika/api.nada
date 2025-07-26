<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Msigna;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SignaController extends Controller
{
    //

    public function getSigna()
    {
        $data = Msigna::paginate(request('per_page'));
        return new JsonResponse($data);
    }
    public function getAutocompleteSigna()
    {
        $q = Msigna::query();
        $data = $q->select('signa', 'jumlah')
            ->when(request('q'), function ($query) {
                $query->where('signa', 'like', '%' . request('q') . '%');
            })->limit(10)->get();
        return new JsonResponse($data);
    }

    public function store(Request $request)
    {
        $jumlah = (float) $request->jumlah;
        if (!$jumlah) {
            return new JsonResponse([
                'message' => 'jumlah konsumsi / hari harus diisi',

            ], 410);
        }
        if ($jumlah <= 0) {
            return new JsonResponse([
                'message' => 'jumlah konsumsi / hari tidak boleh minus',
            ], 410);
        }
        $data = Msigna::updateOrCreate(
            ['signa' => $request->signa],
            $request->all()
        );
        if ($data->wasRecentlyCreated) {
            return new JsonResponse([
                'message' => 'Signa Berhasil di simpan',
                'data' => $data
            ]);
        } else if ($data->wasChanged()) {
            return new JsonResponse([
                'message' => 'jumlah konsumsi / hari di Update',
                'data' => $data
            ]);
        } else {
            return new JsonResponse([
                'message' => 'Tidak Ada perubahan data',
                'data' => $data
            ]);
        }
    }
}
