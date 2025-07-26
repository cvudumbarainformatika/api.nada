<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\sigarang\BarangRSResource;
use App\Models\Sigarang\BarangRS;
use App\Models\Sigarang\MapingBarangDepo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;

class BarangRSController extends Controller
{
    public function index()
    {
        $data = BarangRS::filter(request(['q']))
            ->latest('id')
            ->with('barang108.maping', 'rekening50', 'satuan', 'satuankecil', 'depo')
            ->paginate(request('per_page'));
        $collect = collect($data);
        $balik = $collect->only('data');
        $balik['meta'] = $collect->except('data');

        return new JsonResponse($balik);
    }

    public function countIndex()
    {
        $data = BarangRS::withTrashed()->count();
        return new JsonResponse($data + 1, 200);
    }

    public function indexForPemesanan()
    {
        $data = BarangRS::latest('id')
            ->filter(request(['q']))
            ->with('barang108', 'satuan', 'satuankecil', 'depo')
            ->paginate(request('per_page'));
        $collect = collect($data);
        $balik = $collect->only('data');
        $balik['meta'] = $collect->except('data');

        return new JsonResponse($balik);
    }
    public function barangrs()
    {
        $data = BarangRS::oldest('id')->with('barang108', 'satuan', 'satuankecil')->get(); //paginate(request('per_page'));
        // return BarangRSResource::collection($data);
        return new JsonResponse($data);
    }
    public function storeByKode(Request $request)
    {
        $validatedData = Validator::make($request->all(), [
            'kode' => 'required'
        ]);
        if ($validatedData->fails()) {
            return response()->json($validatedData->errors(), 422);
        }
        try {
            DB::beginTransaction();
            // update or crete
            $data = BarangRS::updateOrCreate(['kode' => $request->kode], $request->all());

            if ($data->wasRecentlyCreated) {
                $depo = MapingBarangDepo::updateOrCreate(['kode_rs' => $request->kode], ['kode_gudang' => $request->kode_gudang]);
                return new JsonResponse(['message' => 'data berhasil ditambahkan', 'depo' => $depo], 201);
            } else if ($data->wasChanged()) {
                $depo = MapingBarangDepo::updateOrCreate(['kode_rs' => $request->kode], ['kode_gudang' => $request->kode_gudang]);
                return new JsonResponse(['message' => 'data berhasil di Update', 'depo' => $depo], 200);
            } else {

                return new JsonResponse(['message' => 'Tidak ada perubahan data'], 410);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'ada kesalahan', 'error' => $e, 'request' => $request->all()], 500);
        }
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

                // insert or take barang
                BarangRS::firstOrCreate(['kode' => $request->kode], $request->all());

                // $auth->log("Memasukkan data BarangRS {$user->name}");
            } else {
                $toUpdate = $request->all();
                unset($toUpdate['id']);
                $barang = BarangRS::find($request->id);
                $barang->update($toUpdate);

                // $auth->log("Merubah data BarangRS {$user->name}");
            }

            DB::commit();
            return response()->json(['message' => 'success'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'ada kesalahan', 'error' => $e, 'request' => $request->all()], 500);
        }
    }
    public function destroy(Request $request)
    {

        $auth = auth()->user()->id;
        $id = $request->id;

        $data = BarangRS::find($id);
        $maping = MapingBarangDepo::where('kode_rs', $data->kode)->first();
        $del = null;
        $hapus = null;
        if ($data) {
            $data->update(['deleted_by' => $auth]);
            $del = $data->delete();
        }
        if ($maping) {
            $hapus = $maping->delete();
        }

        if (!$del && !$hapus) {
            return response()->json([
                'message' => 'Error on Delete'
            ], 500);
        } else if ($del && !$hapus) {
            return response()->json([
                'message' => 'Maping data Depo Not Found'
            ], 200);
        } else if (!$del && $hapus) {
            return response()->json([
                'message' => 'Error on Delete Data barang'
            ], 500);
        }

        // $user->log("Menghapus Data BarangRS {$data->nama}");
        return response()->json([
            'message' => 'Data sukses terhapus'
        ], 200);
    }
}
