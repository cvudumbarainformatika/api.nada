<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\sigarang\MappingBarangResource;
use App\Models\Sigarang\MapingBarang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MappingBarangController extends Controller
{
    public function index()
    {
        // $data = MapingBarang::paginate();
        $data = MapingBarang::orderBy(request('order_by'), request('sort'))
            ->filter(request(['q']))
            ->with('barang108', 'barangrs', 'satuan')
            ->paginate(request('per_page'));
        // return MappingBarangResource::collection($data);
        $collect = collect($data);
        $balik = $collect->only('data');
        $balik['meta'] = $collect->except('data');

        return new JsonResponse($balik);
    }
    public function maping()
    {
        $data = MapingBarang::latest('id')->filter(request(['q']))->get(); //paginate(request('per_page'));
        return MappingBarangResource::collection($data);
    }
    public function mapingwith()
    {
        $data = MapingBarang::latest('id')
            ->filter(request(['q']))
            ->with('barangrs', 'barang108', 'satuan')
            ->get(); //paginate(request('per_page'));
        return MappingBarangResource::collection($data);
    }
    public function store(Request $request)
    {
        // $auth = $request->user();
        try {

            DB::beginTransaction();

            if (!$request->has('id')) {

                $validatedData = Validator::make($request->all(), [
                    'kode_108' => 'required',
                    'kode_rs' => 'required',
                    'kode_108' => 'required'
                ]);
                if ($validatedData->fails()) {
                    return response()->json($validatedData->errors(), 422);
                }

                // MapingBarang::create($request->only('nama'));
                // MapingBarang::firstOrCreate([
                //     'level_1' => $request->level_1,
                //     'level_2' => $request->level_2,
                //     'level_3' => $request->level_3,
                //     'level_4' => $request->level_4,
                //     'jabatan' => $request->jabatan
                // ]);
                MapingBarang::firstOrCreate($request->all());

                // $auth->log("Memasukkan data MapingBarang {$user->name}");
            } else {
                $toUpdate = $request->all();
                unset($toUpdate['id']);
                $barang = MapingBarang::find($request->id);
                $barang->update($toUpdate);

                // $auth->log("Merubah data MapingBarang {$user->name}");
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

        $data = MapingBarang::find($id);
        $del = $data->delete();

        if (!$del) {
            return response()->json([
                'message' => 'Error on Delete'
            ], 500);
        }

        // $user->log("Menghapus Data MapingBarang {$data->nama}");
        return response()->json([
            'message' => 'Data sukses terhapus'
        ], 200);
    }
}
