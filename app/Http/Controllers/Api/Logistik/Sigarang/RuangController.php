<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\sigarang\RuangResource;
use App\Models\Sigarang\Ruang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RuangController extends Controller
{
    public function index()
    {
        // $data = Ruang::paginate();
        $data = Ruang::oldest('id')
            ->filter(request(['q']))
            ->with('namagedung')
            ->paginate(request('per_page'));
        // return RuangResource::collection($data);
        $collect = collect($data);
        $balik = $collect->only('data');
        $balik['meta'] = $collect->except('data');

        return new JsonResponse($balik);
    }
    public function ruang()
    {
        // $data = Ruang::paginate();
        $data = Ruang::latest()
            ->filter(request(['q']))
            ->get();
        return RuangResource::collection($data);
    }
    public function cariRuang()
    {
        // $data = Ruang::paginate();
        $data = Ruang::latest()
            ->filter(request(['q']))
            ->limit(10)
            ->get();
        return new JsonResponse($data);
    }
    public function allRuang()
    {
        // $data = Ruang::paginate();
        $data = Ruang::latest()->where('ruang', '>', 0)
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
                    'gedung' => 'required',
                    'uraian' => 'required'
                ]);
                if ($validatedData->fails()) {
                    return response()->json($validatedData->errors(), 422);
                }

                // Ruang::create($request->only('nama'));
                Ruang::firstOrCreate($request->only([
                    'gedung',
                    'lantai',
                    'ruang',
                    'kode',
                    'uraian'
                ]));

                // $auth->log("Memasukkan data Ruang {$user->name}");
            } else {
                $gedung = Ruang::find($request->id);
                $gedung->update($request->only([
                    'gedung',
                    'lantai',
                    'ruang',
                    'kode',
                    'uraian'
                ]));

                // $auth->log("Merubah data Ruang {$user->name}");
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

        $data = Ruang::find($id);
        $del = $data->delete();

        if (!$del) {
            return response()->json([
                'message' => 'Error on Delete'
            ], 500);
        }

        // $user->log("Menghapus Data Ruang {$data->nama}");
        return response()->json([
            'message' => 'Data sukses terhapus'
        ], 200);
    }
}
