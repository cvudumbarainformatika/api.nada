<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Penerimaan;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokrel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListstokgudangController extends Controller
{
    public function stokgudang()
    {
        $idpegawai = auth()->user()->pegawai_id;
        $kodegudang = Pegawai::find($idpegawai);

        if ($kodegudang->kode_ruang !== '') {
            $stokgudang = Stokrel::with(['masterobat'])
                ->where('flag', '')->where('kdruang', $kodegudang->kode_ruang)
                ->get();
        } else {
            $stokgudang = Stokrel::with(['masterobat'])
                ->where('flag', '')
                ->get();
        }
        return new JsonResponse($stokgudang);
    }
}
