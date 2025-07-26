<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\MaxRuangan;
use App\Models\Sigarang\MinMaxPengguna;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MinMaxStokPenggunaController extends Controller
{
    public function index()
    {
        // $data = MinMaxPengguna::paginate();
        $apem = MaxRuangan::query();
        $barang = (request('barang') !== '' && request('barang') !== null) ? request('barang') : false;
        $ruang = (request('ruang') !== '' && request('ruang') !== null) ? request('ruang') : false;
        if (request('flag_minta') !== 'all') {
            $apem->where('flag_minta', request('flag_minta'));
        }
        if ($barang) {
            $apem->where('kode_rs', $barang);
        }
        if ($ruang) {
            $apem->where('kode_ruang', $ruang);
        }
        $data = $apem->latest('id')
            // ->filter(request(['q', 'barang']))
            ->with('barang', 'ruang')
            // ->paginate(request('per_page'));
            ->simplePaginate(request('per_page'));
        // return Barang108Resource::collection($data);
        $collect = collect($data);
        $balik = $collect->only('data');
        $balik['meta'] = $collect->except('data');
        $balik['re'] = request()->all();

        return new JsonResponse($balik);
    }

    public function all()
    {
        $data = MaxRuangan::latest('id')
            // ->filter(request(['q']))
            ->with('barang', 'ruang')
            ->get(); //paginate(request('per_page'));
        return new JsonResponse($data);
    }
    public function spesifik(Request $request)
    {
        $data = MaxRuangan::where('kode_ruang', '=', $request->kode_ruang)
            ->where('kode_rs', '=', $request->kode_rs)
            ->latest('id')
            ->with('barang', 'ruang')
            ->first();
        // ->get();
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

                $data = MaxRuangan::firstOrCreate($request->all());

                // $auth->log("Memasukkan data MaxRuangan {$user->name}");
            } else {
                $toUpdate = $request->all();
                unset($toUpdate['id']);
                $data = MaxRuangan::find($request->id);
                $data->update($toUpdate);

                // $auth->log("Merubah data MaxRuangan {$user->name}");
            }

            DB::commit();
            if ($data->wasRecentlyCreated) {
                $status = 201;
                $pesan = 'Data telah dibuat';
            } else if ($data->wasChanged()) {
                $status = 200;
                $pesan = 'Data telah diupdate';
            } else {
                $status = 500;
                $pesan = 'Tidak ada perubahan data';
            }
            return new JsonResponse(['message' => $pesan, 'data' => $data], $status);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'ada kesalahan', 'error' => $e], 500);
        }
    }
    public function terimaSemua()
    {
        $data = MaxRuangan::where('flag_minta', '1')->get();
        foreach ($data as $key) {
            $ask = $key->minta;
            $key->update([
                'max_stok' => $ask,
                'minta' => 0,
                'flag_minta' => null,
            ]);
            // return new JsonResponse(['data' => $key, 'message' => 'Data Max Ruangan sudah ditambahkan'], 200);
        }
        return new JsonResponse(['data' => $data, 'message' => 'Data Max Ruangan sudah ditambahkan'], 200);
    }
    public function destroy(Request $request)
    {

        // $auth = auth()->user()->id;
        $id = $request->id;

        $data = MaxRuangan::find($id);
        $del = $data->delete();

        if (!$del) {
            return response()->json([
                'message' => 'Error on Delete'
            ], 500);
        }

        // $user->log("Menghapus Data MaxRuangan {$data->nama}");
        return response()->json([
            'message' => 'Data sukses terhapus'
        ], 200);
    }
}
