<?php

namespace App\Http\Controllers\Api\Simrs\Master\Pegawai;

use App\Helpers\FormatingHelper;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Pegawai\Jabatan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MjabatanController extends Controller
{
    public function index()
    {
        $req = [
            'order_by' => request('order_by', 'created_at'),
            'sort' => request('sort', 'asc'),
            'page' => request('page', 1),
            'per_page' => request('per_page', 10),
        ];

        $query = Jabatan::query()
            ->when(request('q'), function ($q) {
                $q->where(function ($query) {
                    $query->where('jabatan', 'like', '%' . request('q') . '%');
                });
            })
            ->where('aktif','<>', '1')
            ->orderBy($req['order_by'], $req['sort']);
        $totalCount = (clone $query)->count();
        $data = $query->simplePaginate($req['per_page']);

        $resp = ResponseHelper::responseGetSimplePaginate($data, $req, $totalCount);
        return new JsonResponse($resp);
    }

    public function store(Request $request)
    {
        $kode = $request->kode_jabatan;
        $validated = $request->validate([
            'jabatan' => 'required',
            'kode_jabatan' => 'nullable',
        ], [
            'jabatan.required' => 'Jabatan wajib diisi.'
        ]);

        if (!$kode) {
            DB::connection('kepex')->select('call mjabatan(@nomor)');
            $nomor = DB::connection('kepex')->table('counter')->select('mjabatan')->first();
            $validated['kode_jabatan'] = FormatingHelper::allmaster($nomor->mjabatan, 'JBT');
        }

         $jabatan = Jabatan::updateOrCreate(
            ['kode_jabatan' => $validated['kode_jabatan']],
            ['jabatan' => $validated['jabatan']],
        );
        return new JsonResponse([
            'data' => $jabatan,
            'message' => 'Data Jabatan berhasil disimpan'
        ]);

    }

    public function hapus(Request $request)
    {
        $id = $request->id;
        $jabatan = Jabatan::where('id', $id)->first();
        if (!$jabatan) {
            return new JsonResponse([
                'message' => 'Data Jabatan tidak ditemukan'
            ], 404);
        }
        $jabatan->aktif = '1';
        $jabatan->save();
        return new JsonResponse([
            'data' => $jabatan,
            'message' => 'Data Jabatan berhasil disimpan'
        ]);

    }
}
