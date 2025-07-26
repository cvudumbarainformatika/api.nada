<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\sigarang\PenggunaRuangResource;
use App\Models\Sigarang\Pegawai;
use App\Models\Sigarang\PenggunaRuang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PenggunaRuangController extends Controller
{
    public function index()
    {
        // $data = PenggunaRuang::paginate();
        $data = PenggunaRuang::latest('id')
            ->filter(request(['q']))
            ->with('ruang.namagedung', 'pengguna', 'penanggungjawab')
            ->paginate(request('per_page'));
        // return PenggunaRuangResource::collection($data);
        $collect = collect($data);
        $balik = $collect->only('data');
        $balik['meta'] = $collect->except('data');

        return new JsonResponse($balik);
    }
    public function penggunaRuang()
    {
        $user = auth()->user();
        $pegawai = Pegawai::find($user->pegawai_id);
        $pengguna = PenggunaRuang::where('kode_ruang', $pegawai->kode_ruang)->first();
        $role = $pegawai->role_id;
        if ($pengguna && $pegawai->role_id === 5) {
            $data = PenggunaRuang::where('kode_pengguna', $pengguna->kode_pengguna)->with('ruang.namagedung', 'pengguna', 'penanggungjawab')
                ->get();
        } else {
            $data = PenggunaRuang::with('ruang.namagedung', 'pengguna', 'penanggungjawab')
                ->get();
        }

        // return new JsonResponse([$role, $pengguna, $pegawai, $data]);
        return new JsonResponse($data);
    }
    public function store(Request $request)
    {
        // $auth = $request->user();
        $secondArray = null;
        unset($secondArray['kode']);
        try {

            DB::beginTransaction();

            if (!$request->has('id')) {

                $validatedData = Validator::make($request->all(), [
                    'kode_ruang' => 'required',
                    'kode_pengguna' => 'required',
                    'kode_penanggungjawab' => 'required'
                ]);
                if ($validatedData->fails()) {
                    return response()->json($validatedData->errors(), 422);
                }
                // return new JsonResponse($validatedData);
                // PenggunaRuang::create($request->only('nama'));
                PenggunaRuang::firstOrCreate($request->only('kode_ruang'), $request->only(['kode_ruang', 'kode_penanggungjawab', 'kode_pengguna']));

                //     PenggunaRuang::firstOrCreate([
                //         'kode' => $request->kode,
                //         'pengguna_id' => $request->pengguna_id,
                //         'penanggungjawab_id' => $request->penanggungjawab_id,
                //         'ruang_id' => $request->ruang_id,
                //     ]);

                //     // $auth->log("Memasukkan data PenggunaRuang {$user->name}");
            } else {
                $gedung = PenggunaRuang::find($request->id);
                $gedung->update($request->only(['kode_ruang', 'kode_penanggungjawab', 'kode_pengguna']));

                // $auth->log("Merubah data PenggunaRuang {$user->name}");
            }

            DB::commit();
            return response()->json(['message' => 'success'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'ada kesalahan', 'error' => $e, 'input' => $secondArray], 500);
        }
    }
    public function destroy(Request $request)
    {

        // $auth = auth()->user()->id;
        $id = $request->id;

        $data = PenggunaRuang::find($id);
        $del = $data->delete();

        if (!$del) {
            return response()->json([
                'message' => 'Error on Delete'
            ], 500);
        }

        // $user->log("Menghapus Data PenggunaRuang {$data->nama}");
        return response()->json([
            'message' => 'Data sukses terhapus'
        ], 200);
    }
}
