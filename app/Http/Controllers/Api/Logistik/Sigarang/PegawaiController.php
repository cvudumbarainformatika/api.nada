<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\sigarang\PegawaiResource;
use App\Models\Sigarang\Pegawai;
use App\Models\Simpeg\Petugas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PegawaiController extends Controller
{
    public function index()
    {
        $data = Pegawai::latest('id')->filter(request(['q']))->paginate(request('per_page'));

        return PegawaiResource::collection($data);
        // return response()->json([
        //     'data' => $data
        // ]);
    }
    public function find()
    {
        $data = Pegawai::latest('id')->filter(request(['q']))->limit(request('limit'))->get();

        return PegawaiResource::collection($data);
        // return response()->json([
        //     'data' => $data
        // ]);
    }
    //----route public start -----
    public function cariPegawai(Request $request)
    {
        $data = Pegawai::where('nik',  $request->nik)
            ->whereIn('aktif',  ['AKTIF', 'Aktif'])
            ->orWhere('tgllahir', '=', $request->tgllahir)
            ->with('jabatanTambahan', 'user')
            ->first();
        if (!$data) {
            return new JsonResponse(['message' => 'NIK Anda tidak ditemukan, Silahkan hubungi SDM untuk melengkapi Data'], 200);
        }

        return new JsonResponse($data, 200);
    }

    public function cari()
    {
        $data = Pegawai::latest('id')->filter(request(['q']))->with('jabatan', 'jabatanTambahan')->limit(request('limit'))->get();

        return new JsonResponse($data);
        // return response()->json([
        //     'data' => $data
        // ]);
    }


    public function allNakes()
    {
        
        $data = Cache::remember('allnakes', now()->addMinutes(30), function () {
            $nakes = ['1','2','3'];
            return Petugas::select('id', 'nik', 'nip', 'nama', 'jabatan', 'kdpegsimrs', 'aktif','kddpjp','kdgroupnakes')
            ->where('aktif', 'AKTIF')
            ->whereIn('kdgroupnakes', $nakes)
            ->get();
        });

    //    $data = Petugas::select('id', 'nik', 'nip', 'nama', 'jabatan', 'kdpegsimrs', 'aktif')
    //         ->where('aktif', 'AKTIF')->get();
        return new JsonResponse($data);
    }
    //------route public end -----
}
