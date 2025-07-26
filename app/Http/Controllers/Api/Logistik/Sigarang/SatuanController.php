<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\sigarang\SatuanResource;
use App\Models\Sigarang\Satuan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SatuanController extends Controller
{
    public function index()
    {
        $data = Satuan::latest('id')
            ->filter(request(['q']))
            ->paginate(request('per_page'));

        // return SatuanResource::collection($data);
        $collect = collect($data);
        $balik = $collect->only('data');
        $balik['meta'] = $collect->except('data');

        return new JsonResponse($balik);
    }
    public function satuan()
    {
        $data = Satuan::latest('id')->get();

        return SatuanResource::collection($data);
    }
    public function satuanCount()
    {
        $data = Satuan::count();

        return new JsonResponse($data);
    }

    public function store(Request $request)
    {
        // $auth = $request->user();
        try {

            DB::beginTransaction();


            $validatedData = Validator::make($request->all(), [
                'kode' => 'required'
            ]);
            if ($validatedData->fails()) {
                return response()->json($validatedData->errors(), 422);
            }

            $data = Satuan::updateOrCreate(['kode' => $request->kode], $request->all());

            // $auth->log("Memasukkan data Satuan {$user->name}");
            // if (!$request->has('id')) {
            // } else {
            //     $toUpdate = $request->all();
            //     unset($toUpdate['id']);
            //     $barang = Satuan::find($request->id);
            //     $barang->update($toUpdate);

            //     // $auth->log("Merubah data Satuan {$user->name}");
            // }

            DB::commit();
            if ($data->wasRecentlyCreated) {
                return response()->json(['message' => 'dibuat', 'data' => $data], 201);
            } else if ($data->wasChanged()) {
                return response()->json(['message' => 'diupdate', 'data' => $data], 200);
            } else {
                return response()->json(['message' => 'data tidak berubah'], 410);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'ada kesalahan', 'error' => $e], 500);
        }
    }
    public function destroy(Request $request)
    {

        // $auth = auth()->user()->id;
        $id = $request->id;

        $data = Satuan::find($id);
        $del = $data->delete();

        if (!$del) {
            return response()->json([
                'message' => 'Error on Delete'
            ], 500);
        }

        // $user->log("Menghapus Data Satuan {$data->nama}");
        return response()->json([
            'message' => 'Data sukses terhapus'
        ], 200);
    }
}
