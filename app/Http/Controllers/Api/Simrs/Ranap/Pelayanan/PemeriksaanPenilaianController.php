<?php

namespace App\Http\Controllers\Api\Simrs\Ranap\Pelayanan;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Ranap\Pelayanan\Pemeriksaan\Penilaian;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PemeriksaanPenilaianController extends Controller
{
    public function list()
    {

      $data = self::getdata(request('noreg'));
       return new JsonResponse($data);
    }

    public static function getdata($noreg){
       $data = Penilaian::select([
        'id','rs1','rs1 as noreg',
        'rs2 as norm','rs3 as tgl',
        'barthel','norton','humpty_dumpty','morse_fall','ontario','user','kdruang','awal','group_nakes'
       ])
       ->with(['petugas:kdpegsimrs,nik,nama,kdgroupnakes'])
       ->where('rs1', $noreg)
      ->get();

      return $data;
    }

    public function simpan(Request $request)
    {
      $data = self::store($request);
      return new JsonResponse($data);
    }

    public static function store($request)
    {

      // return $request->all();

      $user = Pegawai::find(auth()->user()->pegawai_id);
      $kdpegsimrs = $user->kdpegsimrs;


      DB::beginTransaction();
      try {
        if ($request->id !== null) {
            $hasil = Penilaian::where('id', $request->id)->update(
                [
                  'rs1' => $request->noreg,
                  'rs2' => $request->norm,
                  'rs3' => date('Y-m-d H:i:s'),

                  'barthel' => $request->barthel,
                  'norton' => $request->norton,
                  'humpty_dumpty' => $request->humpty_dumpty,
                  'morse_fall' => $request->morse_fall,
                  'ontario' => $request->ontario,
                  'downscore' => $request->downscore,

                  'kdruang'=> $request->kdruang,
                  'awal'=> $request->awal ?? null,
                  'user'  => $kdpegsimrs,
                  'group_nakes'  => $user->kdgroupnakes,
                ]
            );
            if ($hasil === 1) {
                $simpan = Penilaian::where('id', $request->id)->first();
            } else {
                $simpan = null;
            }
        } else {
          $simpan = Penilaian::create(
            [
                'rs1' => $request->noreg,
                'rs2' => $request->norm,
                'rs3' => date('Y-m-d H:i:s'),

                'barthel' => $request->barthel,
                'norton' => $request->norton,
                'humpty_dumpty' => $request->humpty_dumpty,
                'morse_fall' => $request->morse_fall,
                'ontario' => $request->ontario,
                'downscore' => $request->downscore,

                'kdruang'=> $request->kdruang,
                'awal'=> $request->awal ?? null,
                'user'  => $kdpegsimrs,
                'group_nakes'  => $user->kdgroupnakes,
            ]
          );
        }


        DB::commit();
        // return response()->json([
        //     'message' => 'BERHASIL DISIMPAN',
        //     'result' => self::getdata($request->noreg),
        // ], 200);

        $data = [
          'success' => true,
          'message' => 'BERHASIL DISIMPAN',
          'idPenilaian' => $simpan->id,
          'result' => self::getdata($request->noreg),
        ];

        return $data;
      } catch (\Throwable $th) {
        DB::rollBack();
        // return new JsonResponse(['message' => 'GAGAL DISIMPAN','err'=>$th], 500);
        $data = [
          'success' => false,
          'message' => 'GAGAL DISIMPAN',
          'result' => $th->getMessage(),
        ];

        return $data;
      }


    }

    public static function delete(Request $request)
    {
        $datapenilaian =  Penilaian::where('id', $request->id);
        $hapuspenilaian = $datapenilaian->delete();

        $data = [
            'success' => true,
            'message' => 'BERHASIL DIHAPUS',
            'result' => self::getdata($request->noreg)
        ];

        return $data;
    }

}
