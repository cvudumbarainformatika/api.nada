<?php

namespace App\Http\Controllers\Api\Simrs\Ranap\Pelayanan;

use App\Http\Controllers\Controller;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Planing\Planningdokter;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TargetController extends Controller
{
    
    public function simpan(Request $request)
    {
        $pegawai = Petugas::find(auth()->user()->pegawai_id);

        $data = null;
        if ($request->id === null) {
          $data = new Planningdokter();
        } else {
          $data = Planningdokter::find($request->id);
        }

        $data->noreg = $request->noreg;
        $data->norm = $request->norm;
        $data->target = $request->target;
        $data->terapi = $request->terapi;
        $data->monitor = $request->monitor;
        $data->kdruang = $request->kdruang;
        $data->user = $pegawai->kdpegsimrs;
        $data->save();

        
        return new JsonResponse($data);
    }


    
}
