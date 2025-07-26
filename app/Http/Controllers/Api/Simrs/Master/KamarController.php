<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Diagnosa_m;
use App\Models\Simrs\Master\Mkamar;
use App\Models\Simrs\Master\MkamarRanap;
use App\Models\Simrs\Ranap\Kunjunganranap;
use App\Models\Simrs\Ranap\Views\Kunjunganview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class KamarController extends Controller
{
    public function listkamar()
    {
      // $listkamar = Mkamar::query()
      // ->selectRaw('rs1,rs2,rs3,rs4,rs6,groups')
      // ->where(function ($q) {
      //   $q->where('rs6', '<>', '1')
      //   ->where('status', '<>', '1');
      // })->distinct('rs1')
      // ->orderBy('rs2', 'DESC')->get();

      $listkamar = Cache::remember('kamar', now()->addDays(7), function () {
        return Mkamar::query()
        ->selectRaw('rs1,rs2,rs3,rs4,rs6,groups')
        ->where(function ($q) {
          $q->where('rs6', '<>', '1')
          ->where('status', '<>', '1');
        })->distinct('rs1')
        ->orderBy('rs2', 'DESC')->get();
      });

      return new JsonResponse($listkamar);
    }


    public function showKamar()
    {
      $data = Mkamar::query()
      ->select('groups','rs5','rs4')
      ->where('status','<>','1')
      ->with(['kamars'=>function($q){
        $q->where('rs7','<>','1')
            ->addSelect([
            'kunjungan'=> Kunjunganview::query()
                ->join('rs24', 'v_15_23.kamar', '=', 'rs24.rs2')
                ->selectRaw("GROUP_CONCAT(v_15_23.noreg order by v_15_23.tgl_masuk asc, ',')" )
                ->whereColumn('v_15_23.no_bed','=', 'rs25.rs2')
                ->whereColumn('v_15_23.kd_kmr','=', 'rs25.rs1')
                // ->whereColumn('v_15_23.kamar','=', 'rs24.rs2')
                ->where('v_15_23.status_inap','=', '')
            ])
            ->orderBy('rs5', 'asc');
          }, 
          'kamars.kamar'=>function($q){
            $q->select('rs1','rs2','rs3','rs4','rs5','groups');
          }
        ])
        ->where('status','<>','1')
        // ->where('groups','=','BG')
        ->distinct('groups')
      ->get();

      $flat = [];
      foreach ($data as $x) {
          $xy=$x->kamars;
          foreach ($xy as $y) {
              if($y->kunjungan !==null) {
                  $temp = [];
                  foreach(explode(',', $y->kunjungan) as $key => $value) {
                      $temp[$key] = $value;
                  }
                  $flat[] = $temp;
              }
          }
      }
      $flatten = collect(array_merge(...$flat))->unique()->values()->all();

      $kunjungan = Kunjunganview::select(
        'noreg','norm','status_inap',
        'tgl_masuk','group_kamar','kd_kelas','no_bed',
        'kelamin','alamat','nama',
        'kd_kmr','kamar','titipan'
      )
      ->whereIn('noreg', $flatten)
      ->where('status_inap','=','')
      ->orderBy('tgl_masuk', 'desc')
      ->groupBy('noreg')
      ->get();

      foreach ($data as $x) {
          $xy=$x->kamars;
          foreach ($xy as $y) {
              $noregs = explode(',', $y->kunjungan);
              $ee = $kunjungan
              ->whereIn('noreg', $noregs)
              ->sortBy(fn (Kunjunganview $kj) => array_flip($noregs)[$kj->noreg])
              ->values();

              // masukkan ke object harga_teringgi_kodes
              $y->setRelation('kunjungan', $ee)->toArray();
          }
      }

    
      return new JsonResponse($data);
    }
    
}
