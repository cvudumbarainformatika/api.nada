<?php

namespace App\Http\Controllers\Api\Pegawai\Master;

use App\Http\Controllers\Controller;
use App\Models\Pegawai\JenisPegawai;
use App\Models\Pegawai\MasterCuti;
use App\Models\Sigarang\Pegawai;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CutiController extends Controller
{
    //

    public function index()
    {
        // $from = request('tahun') . '-01-01';
        // $to = request('tahun') . '-12-31';
        // return new JsonResponse(['to' => $to, 'from' => $from]);
        // $data = MasterCuti::where('tgl_libur', '>=', $from)
        //     ->where('tgl_libur', '<=', $to)
        // ->orderBy(request('order_by'), request('sort'))
        $data = MasterCuti::orderBy(request('order_by'), request('sort'))
            ->filter(request(['q']))
            ->with('jenispegawai')
            ->paginate(request('per_page'));

        return new JsonResponse($data);
    }


    public function jenisPegawai()
    {
        $data = JenisPegawai::get();
        return new JsonResponse($data);
    }

    public function pegawai()
    {
        $data = Pegawai::where('aktif', 'AKTIF')->get();
        return new JsonResponse($data);
    }


    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            $valid = Validator::make($request->all(), ['nama' => 'required']);
            if ($valid->fails()) {
                return new JsonResponse([$valid->errors(), 422]);
            }

            $data = MasterCuti::updateOrCreate(
                ['id' => $request->id],
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
        $id = $request->id;

        $data = MasterCuti::find($id);
        $del = $data->delete();

        if (!$del) {
            return response()->json([
                'message' => 'Error on Delete'
            ], 500);
        }

        // $user->log("Menghapus Data MasterCuti {$data->nama}");
        return new JsonResponse([
            'message' => 'Data sukses terhapus'
        ], 200);
    }
}
