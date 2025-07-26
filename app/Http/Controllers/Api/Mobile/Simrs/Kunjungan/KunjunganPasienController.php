<?php

namespace App\Http\Controllers\Api\Mobile\Simrs\Kunjungan;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Rajal\KunjunganPoli;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class KunjunganPasienController extends Controller
{
    public function pasienpoli(Request $request)
    {
      // return $request->all();
      $data=$this->poli($request);
      return new JsonResponse($data);
    }

    public function poli($request)
    {
      if ($request->tglAwal === '' || $request->tglAkhir === null) {
        $tgl = Carbon::now()->format('Y-m-d 00:00:00');
        $tglx = Carbon::now()->format('Y-m-d 23:59:59');
      } else {
          $tgl = $request->tglAwal . ' 00:00:00';
          $tglx = $request->tglAkhir . ' 23:59:59';
      }

      $ruangan = $request->kodepoli;
      $data = KunjunganPoli::select(
          'rs17.rs1', 'rs17.rs2','rs17.rs3','rs17.rs4','rs17.rs8', 'rs17.rs9', 'rs17.rs19',
          'rs17.rs1 as noreg',
          'rs17.rs2 as norm',
          'rs17.rs3 as tgl_kunjungan',
          'rs17.rs8 as kodepoli',
          'rs17.rs19 as status',
          'rs19.rs2 as poli',
          'rs21.rs2 as dokter',
          'rs9.rs2 as sistembayar',
          'rs15.rs2 as nama',
          'rs15.rs17 as kelamin',
          'rs15.rs22 as agama',
          'rs15.rs46 as noka',
          'rs15.rs49 as nktp',
          'rs15.rs55 as nohp',
          'rs222.rs8 as sep',
          'antrian_ambil.nomor as noantrian',
          DB::raw('concat(TIMESTAMPDIFF(YEAR, rs15.rs16, CURDATE())," Tahun ",
                        TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()) % 12," Bulan ",
                        TIMESTAMPDIFF(DAY, TIMESTAMPADD(MONTH, TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()), rs15.rs16), CURDATE()), " Hari") AS usia')
          )
            ->leftjoin('rs15', 'rs15.rs1', '=', 'rs17.rs2') //pasien
            ->leftjoin('rs19', 'rs19.rs1', '=', 'rs17.rs8') //poli
            ->leftjoin('rs21', 'rs21.rs1', '=', 'rs17.rs9') //dokter
            ->leftjoin('rs9', 'rs9.rs1', '=', 'rs17.rs14') //sistembayar
            ->leftjoin('rs222', 'rs222.rs1', '=', 'rs17.rs1') //sep
            ->leftjoin('antrian_ambil', 'antrian_ambil.noreg', 'rs17.rs1')

              ->whereBetween('rs17.rs3', [$tgl, $tglx])
              // ->where('rs19.rs4', '=', 'Poliklinik')
              ->whereIn('rs17.rs8', $ruangan)
              // ->where('rs17.rs8', '!=', 'POL014')
              ->where(function ($query) use($request) {
                $query->where('rs15.rs2', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('rs15.rs46', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('rs17.rs2', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('rs17.rs1', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('rs19.rs2', 'LIKE', '%' . $request->q . '%')
                    ->orWhere('rs9.rs2', 'LIKE', '%' . $request->q . '%');
              })
          ->with([
            'dokumenluar'=> function($neo){
              $neo->with(['pegawai:id,nama']);
            }
          ])
          ->orderby('antrian_ambil.nomor', 'Asc')
          ->groupby('rs17.rs1')
        ->paginate(50);

      return $data;
    }
}
