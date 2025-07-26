<?php

namespace App\Http\Controllers\Api\Simrs\Pelayanan\Kandungan;

use App\Helpers\DateHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\MskriningKehamilan;
use App\Models\Simrs\Pelayanan\Kandungan;
use App\Models\Simrs\Pelayanan\RiwayatObsetri;
use App\Models\Simrs\Pelayanan\SkriningKehamilan;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KandunganController extends Controller
{

  public function index()
  {
    $data = Kandungan::where('norm', request('norm'))->with('pegawai:id,nama')->orderBy('created_at', 'desc')->get();
    return new JsonResponse($data, 200);
  }

    public function store(Request $request)
    {
      $user = auth()->user()->pegawai_id;
      $request->request->add(['user_input' => $user]);


      // $saved = Kandungan::updateOrCreate(['noreg' => $request->noreg],$request->all());
      $saved = Kandungan::create($request->all());

      if (!$saved) {
        return new JsonResponse(['message'=> 'failed'], 500);
      }

      // $data = Kandungan::where('noreg', $request->noreg)->with('pegawai:id,nama')->first();

      return new JsonResponse($saved->load('pegawai:id,nama'), 200);   
    }
    public function deletedata(Request $request)
    {
      
      $data = Kandungan::find($request->id);

      if (!$data) {
        return new JsonResponse(['message'=> 'Data tidak ditemukan'], 500);
      }

      $del = $data->delete();

      if (!$del) {
        return new JsonResponse(['message'=> 'Failed'], 500);
      }

      return new JsonResponse(['message'=> 'Data Berhasil dihapus'], 200); 
    }

    public function masterskrining()
    {
        $data= MskriningKehamilan::all();
        $data->makeHidden(['created_at','updated_at']);

        return new JsonResponse($data);
    }
    public function skrining()
    {
        $data= SkriningKehamilan::where('norm','=',request('norm'))->get();
        $data->makeHidden(['created_at','updated_at']);

        return new JsonResponse($data);
    }
    public function storeSkrining(Request $request)
    {
        $user = auth()->user()->pegawai_id;
        $now = DateHelper::getDateTime();
        $req=[];
        if (count($request->skriningKehamilan)>0) {
        foreach ($request->skriningKehamilan as $key => $value) {
          $split = explode("--",$value);
          $master_kode = $split[0];
          $tribulan = $split[1];
          $kehamilanNo = (int)$split[2];
          $skor=(int)$split[3];
          $data=[
            'noreg'=> $request->noreg,
            'norm'=> $request->norm,
            'kehamilanNo'=> $kehamilanNo,
            'master_kode'=> $master_kode,
            'tribulan'=> $tribulan,
            'skor'=> $skor,
            'valueSingkat'=>$value,
            'user_input'=>$user,
            'created_at'=>$now,
            'updated_at'=>$now,
          ];
          $req[] = $data;
        }

        $norm = $request->norm;
        DB::transaction(function () use ($norm, $req) {
          SkriningKehamilan::where('norm', $norm)->delete();
          SkriningKehamilan::insert($req);
       });
       return new JsonResponse(['message'=> 'Success'],200);
      } else {
        $norm = $request->norm;
        $kehamilanNo = $request->kehamilanNo;
        SkriningKehamilan::where([['norm', $norm],['kehamilanNo',$kehamilanNo]])->delete();
        return new JsonResponse(['message'=> 'data sdh dihapus'],200);
      }
      
  }



  // OBSETRI

  public function riwayatObsetri()
  {
     $data = RiwayatObsetri::where('norm', request('norm'))->with('pegawai:id,nama')->get();
     return response()->json($data, 200);
  }

  public function storeObsetri(Request $request)
  {
    $user = auth()->user()->pegawai_id;
    $request->request->add(['user_input' => $user]);


    // $saved = Kandungan::updateOrCreate(['noreg' => $request->noreg],$request->all());
    $saved = RiwayatObsetri::create($request->all());

    if (!$saved) {
      return new JsonResponse(['message'=> 'failed'], 500);
    }

    // $data = Kandungan::where('noreg', $request->noreg)->with('pegawai:id,nama')->first();

    return new JsonResponse($saved->load('pegawai:id,nama'), 200);  
  }

  public function deleteObsetri(Request $request)
  {
    $data = RiwayatObsetri::find($request->id);

    if (!$data) {
      return new JsonResponse(['message'=> 'Data tidak ditemukan'], 500);
    }

    $del = $data->delete();

    if (!$del) {
      return new JsonResponse(['message'=> 'Failed'], 500);
    }

    return new JsonResponse(['message'=> 'Data Berhasil dihapus'], 200); 
  }
}