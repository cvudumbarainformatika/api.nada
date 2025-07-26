<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Transaksi\Stock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MinMaxStokController extends Controller
{
    public function index()
    {
        // $data = Stock::paginate();
        $data = Stock::latest('id')
            ->filter(request(['q']))
            ->with('barang', 'gudang', 'pengguna')
            ->paginate(request('per_page'));
        // return Barang108Resource::collection($data);
        $collect = collect($data);
        $balik = $collect->only('data');
        $balik['meta'] = $collect->except('data');

        return new JsonResponse($balik);
    }
    public function minmaxstok()
    {
        $data = Stock::latest('id')->filter(request(['q']))->get(); //paginate(request('per_page'));
        return new JsonResponse($data);
    }

    public function store(Request $request)
    {
        // $auth = $request->user();
        try {

            DB::beginTransaction();

            if (!$request->has('id')) {

                $validatedData = Validator::make($request->all(), [
                    'kode_rs' => 'required'
                ]);
                if ($validatedData->fails()) {
                    return response()->json($validatedData->errors(), 422);
                }

                $data = Stock::firstOrCreate($request->all());

                // $auth->log("Memasukkan data Stock {$user->name}");
            } else {
                $toUpdate = $request->all();
                unset($toUpdate['id']);
                $data = Stock::find($request->id);
                $data->update($toUpdate);

                // $auth->log("Merubah data Stock {$user->name}");
            }

            DB::commit();
            if ($data->wasRecentlyCreated) {
                $status = 201;
                $pesan = 'Data telah di perbarui';
            } else if ($data->wasChanged()) {
                $status = 200;
                $pesan = 'Data telah diupdate';
            } else {
                $status = 500;
                $pesan = 'Tidak ada perubahan data';
            }
            return new JsonResponse(['message' => $pesan], $status);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'ada kesalahan', 'error' => $e], 500);
        }
    }
    public function destroy(Request $request)
    {

        // $auth = auth()->user()->id;
        $id = $request->id;

        $data = Stock::find($id);
        $del = $data->delete();

        if (!$del) {
            return response()->json([
                'message' => 'Error on Delete'
            ], 500);
        }

        // $user->log("Menghapus Data Stock {$data->nama}");
        return response()->json([
            'message' => 'Data sukses terhapus'
        ], 200);
    }
}
