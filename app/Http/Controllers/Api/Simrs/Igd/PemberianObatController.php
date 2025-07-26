<?php

namespace App\Http\Controllers\Api\Simrs\Igd;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use App\Models\Simrs\Rajal\Igd\PemberianObatIgd;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class PemberianObatController extends Controller
{
    public function obatdariresep()
    {
        $resep = Resepkeluarheder::select(
            'resep_keluar_r.id as idrinci',
            'resep_keluar_r.*',
            'new_masterobat.*'
        )
        ->leftjoin('resep_keluar_r','resep_keluar_h.noresep','resep_keluar_r.noresep')
        ->leftjoin('new_masterobat', 'new_masterobat.kd_obat','resep_keluar_r.kdobat')
        ->where('resep_keluar_r.noreg', request('noreg'))
        ->whereIn('resep_keluar_h.flag', ['3','4'])
        ->get();

        return new JsonResource(['data' => $resep],200);
    }

    public function simpanpemberianobat(Request $request)
    {
        $tgl= $request->tglpemberianobat.' '.$request->jampemberianobat;

        $wew = FormatingHelper::session_user();
        $kdpegsimrs = $wew['kodesimrs'];
        $simpan = PemberianObatIgd::create(
            [
                'noreg' => $request->noreg,
                'norm' => $request->norm,
                'noresep' => $request->noresep,
                'tgl' => $tgl,
                'kdobat' => $request->kdobat,
                'dosis' => $request->dosis,
                'satuan' => $request->satuan,
                'pump' => $request->pump,
                'lamajam' => $request->jampump,
                'lamamenit' => $request->menitpump,
                'routepemberianobat' => $request->routepemberianobat,
                'ruangan' => 'POL014',
                'userinput' => $kdpegsimrs
            ]
        );
        $data = self::pemberianobatgetbynoreg($request->noreg);

        return new JsonResponse([
            'success' => true,
            'message' => 'success',
            'data' => $data
        ],200);
    }

    public static function pemberianobatgetbynoreg($noreg)
    {
        $getpemberianobat = PemberianObatIgd::with(
            [
                'mobat',
                'datasimpeg'
            ]
        )->where('noreg',$noreg)->orderBy('id','Desc')->get();
        return $getpemberianobat;
    }

    public function hapuspemberianobat(Request $request)
    {
          try {
            $cari = PemberianObatIgd::find($request->id);

            $hapus = $cari->delete();
            return new JsonResponse([
                'message' => 'BERHASIL DIHAPUS...!!!'
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();
            return new JsonResponse([
                'message' => 'GAGAL DIHAPUS...!!!'
            ], 500);
        }
    }
}
