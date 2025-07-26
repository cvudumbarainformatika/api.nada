<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Http\Controllers\Api\Simrs\Pendaftaran\Rajal\DaftarrajalController;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mpasien;
use App\Models\Simrs\Master\Mpasienx;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PasienController extends Controller
{
    // public function index()
    // {
    //     $query = Mpasien::query()
    //     ->selectRaw('rs1 as norm,rs2 as nama,rs3 as sapaan,rs4 as alamat,rs5 as kelurahan,rs6 as kota')
    //     ->limit(100);

    //     $queryx = Mpasienx::query()
    //     ->selectRaw('rs1 as norm,rs2 as nama,rs3 as sapaan,rs4 as alamat,rs5 as kelurahan,rs6 as kota')
    //     ->limit(100)
    //     ->unionAll($query)
    //     ->get();

    //     return new JsonResponse($queryx);

    // }

    // public function getpasiennorm()
    // {
    //     $norm = request('norm');

    //     $query = Mpasien::query()
    //     ->selectRaw('rs1 as norm,rs2 as nama,rs3 as sapaan,rs4 as alamat,rs5 as kelurahan,rs6 as kota')
    //     ->where('rs1',$norm);

    //     $queryx = Mpasienx::query()
    //     ->selectRaw('rs1 as norm,rs2 as nama,rs3 as sapaan,rs4 as alamat,rs5 as kelurahan,rs6 as kota')
    //     ->where('rs1',$norm)
    //     ->unionAll($query)
    //     ->limit(1)
    //     ->get();

    //     //dd($query);
    //     return new JsonResponse($queryx);


    // }

    public function simpanMaster(Request $request)
    {
        if ($request->barulama === 'baru') {
            $data = Mpasien::where('rs1', $request->norm)->first();
            if ($data) {
                return new JsonResponse([
                    'message' => 'Nomor RM Sudah ada',
                    'data' => $data
                ], 410);
            }
            $data2 = Mpasien::where('rs49', $request->nik)->first();
            if ($data2) {
                return new JsonResponse([
                    'message' => 'NIK Sudah ada',
                    'data' => $data
                ], 410);
            }
        }
        $data = DaftarrajalController::simpanMpasien($request);

        return new JsonResponse($data);
    }

    public function cekDataPasien()
    {
        // 'rs1' => $request->norm
        // 'rs49' => $nik,
        // 'rs46' => $nokabpjs,
        $cek = request('cek');
        $cari = request('q');
        $data = Mpasien::where('rs1', $cari)
            ->orWhere('rs49', $cari)
            ->orWhere('rs46', $cari)
            ->first();
        if ($data) {
            return new JsonResponse([
                'data' => $data,
                'message' => 'Data Found'
            ], 410);
        }
    }

    public function Pasien()
    {
        $query = Mpasien::pasien()->get();
        return new JsonResponse($query);
    }

    public function index()
    {
        $query = Mpasien::pasien()->filter(request(['q']))
            ->limit(50);

        $queryx = Mpasienx::pasienx()->filter(request(['q']))
            // ->limit(50)
            ->union($query)
            // ->get();
            ->paginate(request('per_page'));

        return new JsonResponse($queryx);
    }

    public function listpasien()
    {
        $query = Mpasien::pasien()->filter(request(['q']))
            ->orderBy(request('order_by'), request('sort'))
            ->paginate(request('per_page'));
        return new JsonResponse($query);
    }

    public function caripasien()
    {
        // $query = Mpasien::pasien()->filter(request(['q']))
        //     ->orderBy('rs2')
        //     ->limit(20)
        //     ->get();
        //   ->paginate(request('per_page'));

        $query = Mpasien::pasien()->filter(request(['q']))
            ->limit(20)
            ->get();

        if (count($query) > 0) {
            return new JsonResponse($query);
        }

        $queryx = Mpasienx::pasienx()->filter(request(['q']))
            ->limit(20)
            // ->union($query)
            ->get();
        //->paginate(request('per_page'));
        return new JsonResponse($queryx);
    }
    public function caripasienbyrm()
    {
        // $query = Mpasien::pasien()->filter(request(['q']))
        //     ->orderBy('rs2')
        //     ->limit(20)
        //     ->get();
        //   ->paginate(request('per_page'));

        $query = Mpasien::pasien()->where('rs1', request(['q']))
            ->limit(20)
            ->get();

        if (count($query) > 0) {
            return new JsonResponse($query);
        }

        $queryx = Mpasienx::pasienx()->where('rs1', request(['q']))
            ->limit(20)
            // ->union($query)
            ->get();
        //->paginate(request('per_page'));
        return new JsonResponse($queryx);
    }
}
