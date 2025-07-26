<?php

namespace App\Http\Controllers\Api\Pegawai\Absensi;

use App\Http\Controllers\Controller;
use App\Models\Pegawai\Prota;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProtaController extends Controller
{
    //
    public function index()
    {
        $tahun = request('tahun') ? request('tahun') : date('Y');
        $from = $tahun . '-01-01';
        $to = $tahun . '-12-31';
        // return new JsonResponse(['to' => $to, 'from' => $from]);
        $data = Prota::where('tgl_libur', '>=', $from)
            ->where('tgl_libur', '<=', $to)
            ->orderBy(request('order_by'), request('sort'))
            ->filter(request(['q']))
            ->paginate(request('per_page'));

        return new JsonResponse($data);
    }
    public static function getAll()
    {
        return ProtaController::class;
    }
    public function all()
    {
        $tahun = request('tahun') ? request('tahun') : date('Y');
        $bulan = request('bulan') ? request('bulan') : date('m');
        $from = $tahun . '-' . $bulan . '-01';
        $to = $tahun . '-' . $bulan . '-31';
        // return new JsonResponse(['to' => $to, 'from' => $from]);
        $data = Prota::where('tgl_libur', '>=', $from)
            ->where('tgl_libur', '<=', $to)
            // ->orderBy(request('order_by'), request('sort'))
            // ->filter(request(['q']))
            ->get();
        foreach ($data as $key) {
            $temp = explode('-', $key['tgl_libur']);
            $day = $temp[2];
            $key['day'] = $day;
        }

        return new JsonResponse($data);
    }

    public function tahunProta()
    {
        $data = Prota::get();
        $tahun = [];
        foreach ($data as $key) {
            $temp = date('Y', strtotime($key->tgl_libur));
            array_push($tahun, $temp);
        }
        $temp = array_unique($tahun);
        $collect = collect($temp)->sort()->values()->all();
        return new JsonResponse($collect, 200);
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            $valid = Validator::make($request->all(), ['tgl_libur' => 'required']);
            if ($valid->fails()) {
                return new JsonResponse([$valid->errors(), 422]);
            }

            $data = Prota::updateOrCreate(
                ['id' => $request->id, 'tgl_libur' => $request->tgl_libur],
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

        $data = Prota::find($id);
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
