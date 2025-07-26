<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\sigarang\PenggunaResource;
use App\Models\Sigarang\Pengguna;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PenggunaController extends Controller
{
    public function index()
    {
        // $data = Pengguna::paginate();
        $data = Pengguna::latest()
            ->filter(request(['q']))
            ->paginate(request('per_page'));
        // return PenggunaResource::collection($data);
        $collect = collect($data);
        $balik = $collect->only('data');
        $balik['meta'] = $collect->except('data');

        return new JsonResponse($balik);
    }
    public function cariPengguna()
    {
        $data = Pengguna::latest('id')->filter(request(['q']))->get(); //paginate(request('per_page'));
        return PenggunaResource::collection($data);
    }
    public function pengguna()
    {
        $data = Pengguna::latest('id')->where('level_4', '<>', null)
            ->with('pj')
            ->get();
        // return PenggunaResource::collection($data);
        return new JsonResponse($data);
    }
    public function penanggungjawab()
    {
        $data = Pengguna::where('penanggungjawab', '<>', null)
            ->latest('id')
            ->filter(request(['q']))
            ->get(); //paginate(request('per_page'));
        return PenggunaResource::collection($data);
    }
    public function store(Request $request)
    {
        // $auth = $request->user();
        try {

            DB::beginTransaction();

            if (!$request->has('id')) {

                $validatedData = Validator::make($request->all(), [
                    'jabatan' => 'required'
                ]);
                if ($validatedData->fails()) {
                    return response()->json($validatedData->errors(), 422);
                }

                // Pengguna::create($request->only('nama'));
                Pengguna::firstOrCreate($request->only([
                    'level_1',
                    'level_2',
                    'level_3',
                    'level_4',
                    'kode',
                    'jabatan'
                ]));

                // $auth->log("Memasukkan data Pengguna {$user->name}");
            } else {
                $gedung = Pengguna::find($request->id);
                $gedung->update($request->only([
                    'level_1',
                    'level_2',
                    'level_3',
                    'level_4',
                    'kode',
                    'jabatan'
                ]));

                // $auth->log("Merubah data Pengguna {$user->name}");
            }

            DB::commit();
            return response()->json(['message' => 'success'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'ada kesalahan', 'error' => $e], 500);
        }
    }
    public function destroy(Request $request)
    {

        // $auth = auth()->user()->id;
        $id = $request->id;

        $data = Pengguna::find($id);
        $del = $data->delete();

        if (!$del) {
            return response()->json([
                'message' => 'Error on Delete'
            ], 500);
        }

        // $user->log("Menghapus Data Pengguna {$data->nama}");
        return response()->json([
            'message' => 'Data sukses terhapus'
        ], 200);
    }
}
