<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Master\Dokter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class NakesController extends Controller
{
    public function selaindokter()
    {
        $selaindokter = Dokter::where('rs13','!=', '1')->where('rs1','!=','')
        ->get();
        return new JsonResponse($selaindokter);
    }

    public function dokter()
    {
    //    $dokter = Pegawai::select('nama','kdpegsimrs', 'kdgroupnakes','kddpjp')
    //         ->where('kdgroupnakes', '1')->where('aktif', 'AKTIF')
    //         ->get();
        $dokter = Cache::remember('dokter', now()->addDays(7), function () {
            return Pegawai::select('nama','kdpegsimrs', 'kdgroupnakes','kddpjp')
            ->where('kdgroupnakes', '1')->where('aktif', 'AKTIF')
            ->get();
        });
        return new JsonResponse($dokter);
    }
}
