<?php

namespace App\Http\Controllers\Api\Simrs\Ranap\Pelayanan;

use App\Http\Controllers\Controller;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Planing\Planningdokter;
use App\Models\Simrs\Ranap\Pelayanan\NurseNote;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NursenoteController extends Controller
{

    public function list()
    {
       $data = NurseNote::where('noreg', request('noreg'))
       ->with('petugas:kdpegsimrs,nik,nip,nama,kdgroupnakes,foto')
       ->orderBy('created_at', 'DESC')
       ->get();
       return new JsonResponse($data);
    }
    
    public function simpan(Request $request)
    {
        $pegawai = Petugas::find(auth()->user()->pegawai_id);

        $data = null;
        if ($request->has('id')) {
          $data = NurseNote::find($request->id);
        } else {
          $data = new NurseNote();
        }

        $data->noreg = $request->noreg;
        $data->norm = $request->norm;
        $data->kdruang = $request->kdruang;
        $data->albumin = $request->albumin;
        $data->bb = $request->bb;
        $data->cvp = $request->cvp;
        $data->dia = $request->dia;
        $data->drain = $request->drain;
        $data->dx = $request->dx;
        $data->feces = $request->feces;
        $data->fraksio2 = $request->fraksio2;
        $data->frek = $request->frek;
        $data->gcs = $request->gcs;
        $data->icp = $request->icp;
        $data->implementasi = $request->implementasi;
        $data->infus = $request->infus;
        $data->iwl = $request->iwl;
        $data->kejang = $request->kejang;
        $data->ket = $request->ket;
        $data->mamin = $request->mamin;
        $data->mode = $request->mode;
        $data->muntah = $request->muntah;
        $data->nadi = $request->nadi;
        $data->nyeri = $request->nyeri;
        $data->obat = $request->obat;
        $data->peep = $request->peep;
        $data->pendarahan = $request->pendarahan;
        $data->pins = $request->pins;
        $data->produksigc = $request->produksigc;
        $data->pump = $request->pump;
        $data->ratio = $request->ratio;
        $data->flow = $request->flow;
        $data->reseps = $request->reseps;
        $data->rr = $request->rr;
        $data->sis = $request->sis;
        $data->skor = $request->skor;
        $data->spo2 = $request->spo2;
        $data->suhu = $request->suhu;
        $data->tb = $request->tb;
        $data->tindakan = $request->tindakan;
        $data->ufg = $request->ufg;
        $data->urine = $request->urine;
        $data->water = $request->water;
        $data->zonde = $request->zonde;
        $data->user = $pegawai->kdpegsimrs;

        $intake = (int)$request->infus + (int)$request->pump + (int)$request->obat + (int)$request->albumin + (int)$request->mamin + (int)$request->mamin + (int)$request->zonde + (int)$request->water ;
        $output = (int)$request->urine + (int)$request->draine + (int)$request->muntah + (int)$request->feces + (int)$request->iwl + (int)$request->pendarahan + (int)$request->ufg + (int)$request->produksigc;
        $balance = 'Balance';
        if ((int)$intake > (int)$output) {
          $balance = 'Excess';
        } elseif ((int)$intake < (int)$output) {
          $balance = 'Defisit';
        } else {
          $balance = 'Balance';
        }
        $data->balance = $balance;
        $data->flag_balance = $request->flag_balance ?? null;
        $data->flag = $request->flag ?? null;
        $data->save();


        if (!$data) {
          return new JsonResponse([
            'success' => false,
            'message' => 'Gagal menyimpan data'
          ]);
         }

         return new JsonResponse([
          'success' => true,
          'message' => 'success',
          'result' => $data->load('petugas:kdpegsimrs,nik,nip,nama,kdgroupnakes,foto') 
         ]);
        
        return new JsonResponse($data);
    }

    public function delete(Request $request)
    {
       $id = $request->id;
       $data = NurseNote::find($id);
       $del = $data->delete();
       if (!$del) {
         return new JsonResponse(['message' => 'Error on Delete'], 500);
       }
       return new JsonResponse(['message' => 'Data berhasil dihapus'], 200);
    }


    
}
