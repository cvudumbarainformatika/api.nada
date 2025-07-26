<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Msistembayar;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class SistemBayarController extends Controller
{
    public function index()
    {
        $data = Msistembayar::query()
        ->selectRaw('rs1 as kode,rs2 as groupsistembayar')
        ->where('hidden', '=' ,'')
        ->get();
        return new JsonResponse($data);
    }

    public function sistembayar2()
    {
        $data = Msistembayar::where('groups','=', request('sistembayar1'))->get();
        return new JsonResponse($data);
    }

    public function allsistembayar()
    {
        // $data = Msistembayar::query()
        // ->selectRaw('rs1 as kode, rs2 as sistembayar, groups, hidden')
        // ->where('rs1', '<>' ,'') // menampilkan yg tidak di hide
        // ->get();
        $data = Cache::remember('allsistembayar', now()->addDays(7), function () {
            return Msistembayar::query()
            ->selectRaw('rs1 as kode, rs2 as sistembayar, groups, hidden')
            ->where('rs1', '<>' ,'') // menampilkan yg tidak di hide
            ->get();
        });
        return new JsonResponse($data);
    }

}
