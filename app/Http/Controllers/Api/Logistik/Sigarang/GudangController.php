<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\sigarang\GudangResource;
use App\Models\Sigarang\Gudang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class GudangController extends Controller
{
    public function index()
    {
        // $data = Gudang::paginate();
        $data = Gudang::latest()
            ->filter(request(['q']))
            ->paginate(request('per_page'));
        // return GudangResource::collection($data);
        $collect = collect($data);
        $balik = $collect->only('data');
        $balik['meta'] = $collect->except('data');

        return new JsonResponse($balik);
    }

    public function gudang()
    {
        $data = Gudang::latest('id')->filter(request(['q']))->get(); //paginate(request('per_page'));
        return GudangResource::collection($data);
        // return new JsonResponse($data);
    }


    public function gudangHabisPakai()
    {
        $data = Gudang::where('gudang', '=', 1)
            ->where('gedung', '=', 2)
            ->get();
        return new JsonResponse($data);
    }
    public function depo()
    {
        $data = Gudang::where('depo', '<>', null)
            ->where('depo', '<>', '')
            ->where('gedung', '=', 2)
            ->get();
        return new JsonResponse($data);
    }
    public function store(Request $request)
    {
        // $auth = $request->user();
        try {

            DB::beginTransaction();

            if (!$request->has('id')) {

                $validatedData = Validator::make($request->all(), [
                    'nama' => 'required'
                ]);
                if ($validatedData->fails()) {
                    return response()->json($validatedData->errors(), 422);
                }

                Gudang::firstOrCreate($request->only(['gedung', 'depo', 'lantai', 'gudang', 'kode', 'nama']));
                // Gudang::firstOrCreate([
                //     'nama' => $request->nama,
                //     'nomor' => $request->nomor
                // ]);

                // $auth->log("Memasukkan data Gudang {$user->name}");
            } else {
                $gedung = Gudang::find($request->id);
                $gedung->update($request->only(['gedung', 'depo', 'lantai', 'gudang', 'kode', 'nama']));
                // $gedung->update([
                //     'nomor' => $request->nomor,
                //     'nama' => $request->nama
                // ]);

                // $auth->log("Merubah data Gudang {$user->name}");
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

        $data = Gudang::find($id);
        $del = $data->delete();

        if (!$del) {
            return response()->json([
                'message' => 'Error on Delete'
            ], 500);
        }

        // $user->log("Menghapus Data Gudang {$data->nama}");
        return response()->json([
            'message' => 'Data sukses terhapus'
        ], 200);
    }
}
