<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Transaksi\Retur\Retur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReturController extends Controller
{
    //
    public function simpan(Request $request)
    {
        $request->validate([
            'reff' => 'required',
            'alasan' => 'required',
        ]);
        try {
            DB::beginTransaction();
            $data = Retur::create($request->all());

            if ($request->details) {
                foreach ($request->details as $key) {
                    $data->details()->create($key);
                }
            }
            if ($data->wasRecentlyCreated) {
                $status = 201;
                $pesan = ['message' => 'Pemakaian Ruangan telah disimpan'];
            } else if ($data->wasChanged()) {
                $status = 200;
                $pesan = ['message' => 'Pemakaian Ruangan telah diupdate'];
            } else {
                $status = 500;
                $pesan = ['message' => 'Pemakaian Ruangan gagal dibuat'];
            }

            DB::commit();
            return new JsonResponse($pesan, $status);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'ada kesalahan', 'error' => $e], 500);
        }
    }
}
