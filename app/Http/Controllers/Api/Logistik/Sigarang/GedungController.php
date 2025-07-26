<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\sigarang\GedungResource;
use App\Models\Sigarang\Gedung;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class GedungController extends Controller
{
    public function index()
    {
        // $data = Gedung::paginate();
        $data = Gedung::oldest()
            ->filter(request(['q']))
            ->paginate(request('per_page'));
        // return GedungResource::collection($data);
        $collect = collect($data);
        $balik = $collect->only('data');
        $balik['meta'] = $collect->except('data');

        return new JsonResponse($balik);
    }
    public function gedung()
    {
        $data = Gedung::latest('id')->filter(request(['q']))->get(); //paginate(request('per_page'));
        return GedungResource::collection($data);
        // return new JsonResponse($data);
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

                Gedung::firstOrCreate($request->only(['nama', 'nomor']));
                // Gedung::firstOrCreate([
                //     'nama' => $request->nama,
                //     'nomor' => $request->nomor
                // ]);

                // $auth->log("Memasukkan data Gedung {$user->name}");
            } else {
                $gedung = Gedung::find($request->id);
                $gedung->update($request->only(['nomor', 'nama']));
                // $gedung->update([
                //     'nomor' => $request->nomor,
                //     'nama' => $request->nama
                // ]);

                // $auth->log("Merubah data Gedung {$user->name}");
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

        $data = Gedung::find($id);
        $del = $data->delete();

        if (!$del) {
            return response()->json([
                'message' => 'Error on Delete'
            ], 500);
        }

        // $user->log("Menghapus Data Gedung {$data->nama}");
        return response()->json([
            'message' => 'Data sukses terhapus'
        ], 200);
    }
}
