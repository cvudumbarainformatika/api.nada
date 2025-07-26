<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\sigarang\Barang108Resource;
use App\Models\Sigarang\Barang108;
use App\Models\Sigarang\Maping108To50;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class Barang108Controller extends Controller
{
    public function index()
    {
        // $data = Barang108::paginate();
        $data = Barang108::latest('id')
            ->filter(request(['q']))
            ->with('maping')
            ->paginate(request('per_page'));
        // return Barang108Resource::collection($data);
        $collect = collect($data);
        $balik = $collect->only('data');
        $balik['meta'] = $collect->except('data');

        return new JsonResponse($balik);
    }
    public function barang108()
    {
        $data = Barang108::latest('id')->filter(request(['q']))->get(); //paginate(request('per_page'));
        return Barang108Resource::collection($data);
    }
    public function maping108to50()
    {
        $data = Maping108To50::latest('id')
            ->filter(request(['q']))
            ->paginate(request('per_page'));
        // return Barang108Resource::collection($data);
        $collect = collect($data);
        $balik = $collect->only('data');
        $balik['meta'] = $collect->except('data');

        return new JsonResponse($balik);
    }

    public function store(Request $request)
    {
        // $auth = $request->user();
        try {

            DB::beginTransaction();

            if (!$request->has('id')) {

                $validatedData = Validator::make($request->all(), [
                    'kode' => 'required'
                ]);
                if ($validatedData->fails()) {
                    return response()->json($validatedData->errors(), 422);
                }

                Barang108::firstOrCreate($request->all());

                // $auth->log("Memasukkan data Barang108 {$user->name}");
            } else {
                $toUpdate = $request->all();
                unset($toUpdate['id']);
                $barang = Barang108::find($request->id);
                $barang->update($toUpdate);

                // $auth->log("Merubah data Barang108 {$user->name}");
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

        $data = Barang108::find($id);
        $del = $data->delete();

        if (!$del) {
            return response()->json([
                'message' => 'Error on Delete'
            ], 500);
        }

        // $user->log("Menghapus Data Barang108 {$data->nama}");
        return response()->json([
            'message' => 'Data sukses terhapus'
        ], 200);
    }
}
