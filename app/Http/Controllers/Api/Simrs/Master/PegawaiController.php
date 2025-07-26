<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Http\Controllers\Controller;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Master\Mobat;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PegawaiController extends Controller
{
    public function listnakes()
    {
       $data = Cache::remember('list_nakes', now()->addDays(1), function () {
        $kd=['1','2','3'];
        return Petugas::select('nama','nik','nip','kdpegsimrs', 'kdgroupnakes','kddpjp','foto')
        ->whereIn('kdgroupnakes', $kd)->where('aktif', 'AKTIF')
        ->get()
        ->toArray(); // <--
      });
      $jsonStart = microtime(true);
      $response =  new JsonResponse($data);
      Log::info('JSON encode time: ' . (microtime(true) - $jsonStart));

      return $response;

      
    }
    public function listNonNakes()
    {
       $data = Cache::remember('list_non_nakes', now()->addDays(1), function () {
        $kd=['1','2','3'];
        return Petugas::select('nama','nik','nip','kdpegsimrs', 'kdgroupnakes','kddpjp','foto')
        ->whereNotIn('kdgroupnakes', $kd)->where('aktif', 'AKTIF')
        ->get();
      });
      return new JsonResponse($data);
    }
    public function listAll()
    {
       $data = Cache::remember('list_all_pegawai', now()->addDays(1), function () {
        // $kd=['1','2','3'];
        return Petugas::select('nama','nik','nip','kdpegsimrs', 'kdgroupnakes','kddpjp','foto')
        ->where('aktif', 'AKTIF')
        ->get();
      });
      return new JsonResponse($data);
    }
}
