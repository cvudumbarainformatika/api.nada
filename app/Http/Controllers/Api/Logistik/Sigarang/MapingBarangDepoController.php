<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\MapingBarangDepo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MapingBarangDepoController extends Controller
{
    public function allMapingDepo()
    {
        $mentah = MapingBarangDepo::with('barangrs.satuan', 'barangrs.barang108', 'gudang')->get();
        $data = collect($mentah)->groupBy('kode_gudang');
        return new JsonResponse($data);
    }
    public function allMapingBarangDepo()
    {
        $mentah = MapingBarangDepo::with('barangrs.satuan', 'barangrs.barang108', 'gudang')->get();
        // $data = collect($mentah)->groupBy('kode_gudang');
        return new JsonResponse($mentah);
    }
}
