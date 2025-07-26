<?php

namespace App\Http\Controllers\Api\Simrs\Rekammedik;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Ews\MapingProcedure;
use App\Models\Simrs\Master\Icd9prosedure;
use App\Models\Simrs\Master\MappingSnowmed;
use App\Models\Simrs\Master\Mtindakan;
use App\Models\Simrs\Penunjang\Kamaroperasi\Masteroperasi;
use App\Models\Simrs\Penunjang\Laborat\MasterLaborat;
use App\Models\Simrs\Penunjang\Radiologi\Mpemeriksaanradiologi;
use App\Models\Simrs\Rekom\Rekomdpjp;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MappingController extends Controller
{
    public function index()
    {

      // $rad = Mpemeriksaanradiologi::select('rs1 as kode', DB::raw('CONCAT(rs2, " (",rs3,")") AS nama'), 'prosedur_mapping.icd9 as icd9')
      //   ->selectSub('SELECT "Radiologi"', 'kategory')
      //   ->selectSub('SELECT "rs47"', 'table')
      //   ->leftjoin('prosedur_mapping', 'prosedur_mapping.kdMaster', '=', 'rs47.rs1')->with('snowmed');
      // $lab = MasterLaborat::select('rs1 as kode', 'rs2 as nama', 'prosedur_mapping.icd9 as icd9')
      //   ->selectSub('SELECT "Laborat"', 'kategory')
      //   ->selectSub('SELECT "rs49"', 'table')
      //   ->leftjoin('prosedur_mapping', 'prosedur_mapping.kdMaster', '=', 'rs49.rs1')->with('snowmed');
      // $oprasi = Masteroperasi::select('rs1 as kode', 'rs2 as nama','prosedur_mapping.icd9 as icd9')
      //   ->selectSub('SELECT "Operasi"', 'kategory')
      //   ->selectSub('SELECT "rs53"', 'table')
      //   ->leftjoin('prosedur_mapping', 'prosedur_mapping.kdMaster', '=', 'rs53.rs1')->with('snowmed');

      // yang diatas jika diperlukan
      $rs30 = Mtindakan::select('rs1 as kode', 'rs2 as nama', 'prosedur_mapping.icd9 as icd9')
        ->selectSub('SELECT "Tindakan"', 'kategory')
        ->selectSub('SELECT "rs30"', 'table')
        ->leftjoin('prosedur_mapping', 'prosedur_mapping.kdMaster', '=', 'rs30.rs1')
        ->with(['snowmed','icd:kd_prosedur,prosedur'])
        ->when(request('q'), function($query) {
          $query->where('rs1', 'like', '%' . request('q') . '%')
            ->orWhere('rs2', 'like', '%' . request('q') . '%')
            ->orWhere('prosedur_mapping.icd9', 'like', '%' . request('q') . '%');
        })
        ->when(request('kodepoli'), function($query) {
          $query->where('rs4', 'like', '%' . request('kodepoli') . '%');
        })
        ->when(request('koderuangan'), function($query) {
          $query->where('rs4', 'like', '%' . request('koderuangan') . '%');
        })
      // ->union($rad)
      // ->union($lab)
      // ->union($oprasi)
          ->paginate(request('per_page'));

      return new JsonResponse($rs30, 200);
    }

    public function getIcd9(Request $request)
    {
       $data = Icd9prosedure::select('kd_prosedur', 'prosedur')
          ->where('kd_prosedur', 'LIKE', '%' . $request->icdx . '%')
          ->orWhere('prosedur', 'LIKE', '%' . $request->icdx . '%')
          ->limit(20)
          ->get();
        return response()->json($data, 200);
    }

    public function saveIcd9(Request $request)
    {
       $save = MapingProcedure::updateOrCreate(
        [
          'kdMaster' => $request->kd_master
        ],
        [
          'icd9' => $request->kode,
          'tblMaster' => $request->tbl_master,
          'tgl' => Carbon::now()->toDateTimeString(),
          'userId' => auth()->user()->pegawai_id
        ]
        );

        $cek = MapingProcedure::where('kdMaster', $request->kd_master)->with('prosedur')->first();
        return new JsonResponse($cek, 200);
    }
    public function deleteIcd9(Request $request)
    {
        // return $request->all();
        $delete = MapingProcedure::where('kdMaster', $request->kode)->where('icd9', $request->icd9)->delete();
        return new JsonResponse($delete, 200);
    }


    public function saveSnowmed(Request $request)
    {
       $save = MappingSnowmed::create(
        [
          'kdMaster' => $request->kd_master,
          'kdSnowmed' => $request->kode,
          'display' => $request->display,
          'tblMaster' => $request->tbl_master,
          'user_input'=> auth()->user()->pegawai_id
        ]
        );

        return new JsonResponse($save, 200);
    }

    public function deleteSnowmed(Request $request)
    {
        // return $request->all();
        $delete = MappingSnowmed::where('kdMaster', $request->kdMaster)->where('kdSnowmed', $request->kdSnowmed)->delete();
        return new JsonResponse($delete, 200);
    }
}
