<?php

namespace App\Http\Controllers\Api\Pegawai\Absensi;

use App\Http\Controllers\Controller;
use App\Models\Pegawai\Extra;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExtraController extends Controller
{
    public function index()
    {
        // yang berhak mengajukan extra adalah karyawan shift, jadi cari yang shift aja
    }
    public function store(Request $request)
    {
    }
    public function destroy(Request $request)
    {
        // $auth = auth()->user()->id;
        $data = Extra::find($request->id);
        $del = $data->delete();

        if (!$del) {
            return response()->json([
                'message' => 'Error on Delete'
            ], 500);
        }

        // $user->log("Menghapus Data Jadwal Absen {$data->nama}");
        return new JsonResponse([
            'message' => 'Data sukses terhapus'
        ], 200);
    }
}
