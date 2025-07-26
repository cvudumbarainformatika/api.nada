<?php

namespace App\Http\Controllers\Api\Pegawai\Absensi;

use App\Http\Controllers\Controller;
use App\Models\Pegawai\Kategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class KategoryController extends Controller
{
    public function index()
    {
        $data = Kategory::oldest('id')
            ->filter(request(['q']))
            ->paginate(request('per_page'));
        return new JsonResponse($data);
    }
    public function all()
    {
        $data = Kategory::oldest('id')
            ->get();
        return new JsonResponse($data);
    }
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            $valid = Validator::make($request->all(), [
                'nama' => 'required',
                'masuk' => 'required',
                'pulang' => 'required',
                'jam' => 'required',
                'menit' => 'required',
            ]);
            if ($valid->fails()) {
                return new JsonResponse(['message' => 'silahkan isi yang belum di isi'], 422);
            }
            $data = Kategory::updateOrCreate(
                [
                    'id' => $request->id
                ],
                $request->all()
            );

            DB::commit();
            if (!$data->wasRecentlyCreated) {
                $status = 200;
                $pesan = 'Data telah di perbarui';
            } else {
                $status = 201;
                $pesan = 'Data telah di tambakan';
            }
            return new JsonResponse(['message' => $pesan], $status);
        } catch (\Exception $e) {
            DB::rollBack();
            return new JsonResponse([
                'message' => 'ada kesalahan',
                'error' => $e
            ], 500);
        }
    }

    public function destroy(Request $request)
    {
        // $auth = auth()->user()->id;
        $data = Kategory::find($request->id);
        $del = $data->delete();

        if (!$del) {
            return response()->json([
                'message' => 'Error on Delete'
            ], 500);
        }

        // $user->log("Menghapus Data Prota {$data->nama}");
        return new JsonResponse([
            'message' => 'Data sukses terhapus'
        ], 200);
    }
}
