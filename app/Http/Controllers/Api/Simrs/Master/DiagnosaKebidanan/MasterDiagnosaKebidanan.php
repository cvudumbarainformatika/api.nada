<?php

namespace App\Http\Controllers\Api\Simrs\Master\DiagnosaKebidanan;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mdiagnosakebidanan;
use App\Models\Simrs\Master\Mintervensikebidanan;
use App\Models\Simrs\Master\Mpemeriksaanfisik;
use App\Models\Simrs\Master\Mtemplategambar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MasterDiagnosaKebidanan extends Controller
{

    public function index()
    {
        $thumb = collect();
        Mdiagnosakebidanan::with('intervensis')->orderBy('id')
        ->chunk(10, function($diags) use ($thumb){
            foreach ($diags as $q) {
                $thumb->push($q);
            }
        });

        return new JsonResponse([
            'message' => 'success',
            'result' => $thumb
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required',
            'kode' => [
                'required', Rule::unique('mdiagnosakebidanan', 'kode')->ignore($request->id, 'id')
            ],
        ]);

        if ($validator->fails()) {
            return new JsonResponse(['status' => false, 'message' => $validator->errors()], 201);
            // return new JsonResponse($validator->errors(), 422);
        }
        $data = Mdiagnosakebidanan::updateOrCreate(
            ['kode' => $request->kode],
            ['nama' => $request->nama]
        );

        if (!$data) {
            return new JsonResponse(['message' => 'Maaf, Data Gagal Disimpan Di RS...!!!'], 500);
        }

        return new JsonResponse([
            'message' => 'Data Berhasil Disimpan...!!!',
            'result' => $data
            // 'result' => $data->load('intervensis')
        ], 200);
    }

    public function delete(Request $request)
    {
        $data = Mdiagnosakebidanan::find($request->id);

        if (!$data) {
            return new JsonResponse(['message' => 'Maaf, Data Tidak ditemukan...!!!'], 500);
        }

        $data->delete();

        return new JsonResponse([
            'message' => 'Data Berhasil dihapus...!!!',
        ], 200);
    }

    public function storeintervensi(Request $request)
    {
        $data = null;
        if ($request->has('id')) {
            $data = Mintervensikebidanan::find($request->id);
            $data->nama = $request->nama;
            $data->save();
        } else {
          $data = Mintervensikebidanan::create(
            ['nama' => $request->nama, 'group' => $request->group, 'mdiagnosakebidanan_kode' => $request->kode]
          );
        }
        

        return new JsonResponse([
            'message' => 'Data Berhasil Disimpan...!!!',
            'result' => $data
        ], 200);
    }

    public function deleteintervensi(Request $request)
    {
        $data = Mintervensikebidanan::find($request->id);

        if (!$data) {
            return new JsonResponse(['message' => 'Maaf, Data Tidak ditemukan...!!!'], 500);
        }

        $data->delete();

        return new JsonResponse([
            'message' => 'Data Berhasil dihapus...!!!',
        ], 200);
    }
}
