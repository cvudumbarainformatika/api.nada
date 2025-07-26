<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Transaksi\Permintaanruangan\DetailPermintaanruangan;
use App\Models\Sigarang\Transaksi\Permintaanruangan\Permintaanruangan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PermintaanruanganController extends Controller
{
    //
    public function draft()
    {
        $complete = Permintaanruangan::where('reff', '=', request()->reff)
            ->where('status', '>=', 5)->get();
        $draft = Permintaanruangan::where('reff', '=', request()->reff)
            ->where('status', '=', 1)
            ->latest('id')->with([
                'details.barangrs',
                'details.satuan',
                'details.ruang',
                'details.gudang',
                'pj',
                'pengguna',
            ])->get();
        if (count($draft)) {
            foreach ($draft as $key) {
                $kolek = collect($key->details)->groupBy('dari');
                $key->gudang = $kolek;
            }
        }
        if (count($complete)) {
            return new JsonResponse(['message' => 'completed']);
        }
        return new JsonResponse($draft);
    }


    public function store(Request $request)
    {
        $second = $request->all();
        $second['tanggal'] = $request->tanggal ? $request->tanggal : date('Y-m-d H:i:s');

        try {
            DB::beginTransaction();

            $valid = Validator::make($request->all(), ['reff' => 'required']);
            if ($valid->fails()) {
                return new JsonResponse($valid->errors(), 422);
            }

            $data = Permintaanruangan::updateOrCreate(['reff' => $request->reff, 'no_permintaan' => $request->no_permintaan], $second);

            if ($request->has('kode_rs') && $request->kode_rs !== null) {
                $data->details()->updateOrCreate(['kode_rs' => $request->kode_rs], $second);
            }

            DB::commit();

            return new JsonResponse([
                'message' => 'success',
                'data' => $data,
                // 'gudang' => $gudang,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return new JsonResponse([
                'message' => 'ada kesalahan',
                'error' => $e
            ], 500);
        }
    }
    public function selesaiInput(Request $req)
    {
        $data = Permintaanruangan::where('reff', $req->reff)->get();
        if (count($data)) {
            foreach ($data as $key) {
                $key->update(['status' => 4]);
                // if (!$data->save()) {
                //     return new JsonResponse(['message' => 'Gagal Update Status']);
                // }
            }
            return new JsonResponse(['message' => 'Input telah dinyatakan Selesai', $data]);
        }
        return new JsonResponse(['message' => 'Tidak input', $data], 410);
    }
    public function getAlokasiPermintaan()
    {
        $data = Permintaanruangan::where('status', '>=', 4)
            ->where('status', '<', 8)->get();
    }

    public function deleteDetails(Request $request)
    {
        $data = DetailPermintaanruangan::find($request->id);
        $count = DetailPermintaanruangan::where('permintaanruangan_id', $data->permintaanruangan_id)->get();
        $del = $data->delete();
        if (!$del) {
            return new JsonResponse(['message' => 'Data gagal dihapus'], 410);
        }
        if (count($count) === 1) {
            $permintaan = Permintaanruangan::find($data->permintaanruangan_id);
            $hapus = $permintaan->delete();

            return new JsonResponse(['message' => 'Data header dan detail telah dihapus'], 200);
        }
        return new JsonResponse(['message' => 'Data telah dihapus'], 200);
    }
}
