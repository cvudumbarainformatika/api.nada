<?php

namespace App\Http\Controllers\Api\Simrs\Igd;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Rajal\Igd\TriageA;
use App\Models\Simrs\Rajal\Igd\TriageB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TriageController extends Controller
{
    public function simpantriage(Request $request)
    {
        //return $request->has('id');
        if($request->id === null && $request->noreg !== null){
            $cek = TriageA::where('rs1', $request->noreg)->count();
            if($cek > 0){
                return new JsonResponse(
                    [
                        'message' => 'Triage Sudah Pernah Di Input...!!!',
                    ],500);
            }else{

                $user = Pegawai::find(auth()->user()->pegawai_id);
                $kdpegsimrs = $user->kdpegsimrs;
                try {
                    DB::beginTransaction();
                    $caritanggal = TriageA::where('rs1', $request->noreg)->first();

                    if(!$caritanggal)
                    {
                        $rs3 = date('Y-m-d H:i:s');
                        $rs6 = date('Y-m-d H:i:s');
                    }else{
                        $rs3 =  $caritanggal->rs3;
                        $rs6 =  $caritanggal->rs6 ;
                    }

                    $simpan = TriageA::updateOrCreate(
                        [
                            'rs1' => $request->noreg,
                            'rs2' => $request->norm,
                            'rs3' => $rs3,
                            'rs6' => $rs6,
                        ],
                        [
                            'rs4' => 'IRD',
                            'rs5' => 'POL014',
                            'rs8' => $request->suhu,
                            'rs9' => '-',
                            'rs10' => $request->pernapasanx,
                            'rs11' => $request->nadi,

                            'rs13' => $request->bb,
                            'nyeri_hilang' => $request->nyerihilang,
                            'rs16' => $request->kategoritriage,
                            'rs17' => $kdpegsimrs,
                            'rs20' => $kdpegsimrs,
                            'rs21' => $request->tinggibadan,

                            'sistole' => $request->sistole,
                            'diastole' => $request->diastole,
                            'kesadarans' => $request->kesadaran,
                            'spo2' => $request->spo2,
                            'doa' => $request->doa,
                            'scorediastole' => $request->scorediastole,
                            'scoresistole' => $request->scoresistole,
                            'scorekesadaran' => $request->scorekesadaran,
                            'scorelochea' => $request->scorelochea,
                            'scorenadi' => $request->scorenadi,
                            'scorenyeri' => $request->scorenyeri,
                            'scorepernapasanx' => $request->scorepernapasanx,
                            'scoreproteinurin' => $request->scoreproteinurin,
                            'scorespo2' => $request->scorespo2,
                            'scoresuhu' => $request->scoresuhu,
                            'totalscore' => $request->totalscore,
                            'hasilprimarusurve' => $request->hasilprimarysurve,
                            'hasilsecondsurve' => $request->hasilsecondsurve,
                            'gangguanperilaku' => $request->gangguanperilaku,
                            'falsetriage' => $request->falsetriage,
                            'meninggaldiluarrs' => $request->meninggaldiluarrs,
                            'barulahirmeninggal' => $request->barulahirmeninggal
                        ]
                    );

                    $caritanggal = TriageB::where('rs1', $request->noreg)->first();
                    if(!$caritanggal)
                    {
                        $rs3 = date('Y-m-d');
                    }else{
                        $rs3 =  $caritanggal->rs3 ;
                    }
                    $simpanx = TriageB::updateOrCreate(
                        [
                            'rs1' => $request->noreg,
                            'rs2' => $request->norm,
                            'rs3' => $rs3,
                        ],
                        [
                            'rs4' => 'IRD',
                            'rs5' => 'POL014',
                            'rs7' => $request->jalannafas,
                            'rs9' => $request->pernapasan,
                            'rs14' => $request->eye,
                            'rs15' => $request->verbal,
                            'rs16' => $request->motorik,
                            'rs18' => $kdpegsimrs,
                            'rs19' => $request->sirkulasi,
                            'flaghamil' => $request->pasienhamil,
                            'haidterakir' => $request->haid,
                            'gravida' => $request->gravida,
                            'partus' => $request->partus,
                            'abortus' => $request->abortus,
                            'nyeri' => $request->nyeri,
                            'lochea' => $request->lochea,
                            'proteinurin' => $request->proteinurin,
                            'rs20' => $request->disability,
                        ]
                    );

                    $result = [
                        'id' => $simpan['id'],
                        'noreg' => $simpan['rs1'],
                        'rs1' => $simpan['rs1'],
                        'suhu' => $simpan['rs8'],
                        'pernapasanx' => $simpan['rs10'],
                        'nadi' => $simpan['rs11'],
                        'bb' => $simpan['rs13'],
                        'tb' => $simpan['rs21'],
                        'sistole' => $simpan['sistole'],
                        'diastole' => $simpan['diastole'],
                        'kesadaran' => $simpan['kesadarans'],
                        'spo2' => $simpan['spo2'],
                        'doa' => $simpan['doa'],
                        'jalannafas' => $simpanx['rs7'],
                        'pernapasan' => $simpanx['rs9'],
                        'scoresistole' => $simpan['scoresistole'],
                        'scorediastole' => $simpan['scorediastole'],
                        'scorekesadaran' => $simpan['scorekesadaran'],
                        'scorelochea' => $simpan['scorelochea'],
                        'scorenadi' => $simpan['scorenadi'],
                        'scorenyeri' => $simpan['scorenyeri'],
                        'scorepernapasanx' => $simpan['scorepernapasanx'],
                        'scoreproteinurin' => $simpan['scoreproteinurin'],
                        'scorespo2' => $simpan['scorespo2'],
                        'scoresuhu' => $simpan['scoresuhu'],
                        'totalscore' => $simpan['totalscore'],
                        'kategoritriage' => $simpan['rs16'],
                        'hasilprimarusurve' => $simpan['hasilprimarusurve'],
                        'hasilsecondsurve' => $simpan['hasilsecondsurve'],

                        'gangguanperilaku' => $simpan['gangguanperilaku'],
                        'falsetriage' => $simpan['falsetriage'] === false ? '0' : '1',

                        'eye' => $simpanx['rs14'],
                        'verbal' => $simpanx['rs15'],
                        'motorik' => $simpanx['rs16'],
                        'sirkulasi' => $simpanx['rs19'],
                        'flaghamil' => $simpanx['flaghamil'],
                        'haid' => $simpanx['haidterakir'],
                        'gravida' => $simpanx['gravida'],
                        'partus' => $simpanx['partus'],
                        'abortus' => $simpanx['abortus'],
                        'nyeri' => $simpanx['nyeri'],
                        'lochea' => $simpanx['lochea'],
                        'proteinurin' => $simpanx['proteinurin'],
                        'disability' => $simpanx['rs20'],

                        'meninggaldiluarrs' => $simpan['meninggaldiluarrs'],
                        'barulahirmeninggal' => $simpan['barulahirmeninggal']
                    ];

                    DB::commit();
                    return new JsonResponse([
                        'message' => 'BERHASIL DISIMPAN',
                        'result' => $result
                    ], 200);

                } catch (\Exception $e) {
                    DB::rollback();
                    return new JsonResponse([
                        'message' => 'GAGAL DISIMPAN...!!! '. $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine()
                    ], 500);
                }
            }
        }else{

            $user = Pegawai::find(auth()->user()->pegawai_id);
            $kdpegsimrs = $user->kdpegsimrs;
            try {
                DB::beginTransaction();
                $caritanggal = TriageA::where('rs1', $request->noreg)->first();

                if(!$caritanggal)
                {
                    $rs3 = date('Y-m-d H:i:s');
                    $rs6 = date('Y-m-d H:i:s');
                }else{
                    $rs3 =  $caritanggal->rs3;
                    $rs6 =  $caritanggal->rs6 ;
                }
                $simpan = TriageA::updateOrCreate(
                    [
                        'rs1' => $request->noreg,
                        'rs2' => $request->norm,
                        'rs3' => $rs3,
                        'rs6' => $rs6,
                    ],
                    [
                        'rs4' => 'IRD',
                        'rs5' => 'POL014',
                        'rs8' => $request->suhu,
                        'rs9' => '-',
                        'rs10' => $request->pernapasanx,
                        'rs11' => $request->nadi,

                        'rs13' => $request->bb,
                        'nyeri_hilang' => $request->nyerihilang,
                        'rs16' => $request->kategoritriage,
                        'rs17' => $kdpegsimrs,
                        'rs20' => $kdpegsimrs,
                        'rs21' => $request->tinggibadan,

                        'sistole' => $request->sistole,
                        'diastole' => $request->diastole,
                        'kesadarans' => $request->kesadaran,
                        'spo2' => $request->spo2,
                        'doa' => $request->doa,
                        'scorediastole' => $request->scorediastole,
                        'scoresistole' => $request->scoresistole,
                        'scorekesadaran' => $request->scorekesadaran,
                        'scorelochea' => $request->scorelochea,
                        'scorenadi' => $request->scorenadi,
                        'scorenyeri' => $request->scorenyeri,
                        'scorepernapasanx' => $request->scorepernapasanx,
                        'scoreproteinurin' => $request->scoreproteinurin,
                        'scorespo2' => $request->scorespo2,
                        'scoresuhu' => $request->scoresuhu,
                        'totalscore' => $request->totalscore,
                        'hasilprimarusurve' => $request->hasilprimarysurve,
                        'hasilsecondsurve' => $request->hasilsecondsurve,
                        'gangguanperilaku' => $request->gangguanperilaku,
                        'falsetriage' => $request->falsetriage,
                        'meninggaldiluarrs' => $request->meninggaldiluarrs,
                        'barulahirmeninggal' => $request->barulahirmeninggal,
                    ]
                );

                $caritanggal = TriageB::where('rs1', $request->noreg)->first();
                if(!$caritanggal)
                {
                    $rs3 = date('Y-m-d');
                }else{
                    $rs3 =  $caritanggal->rs3 ;
                }
                $simpanx = TriageB::updateOrCreate(
                    [
                        'rs1' => $request->noreg,
                        'rs2' => $request->norm,
                        'rs3' => $rs3,
                    ],
                    [
                        'rs4' => 'IRD',
                        'rs5' => 'POL014',
                        'rs7' => $request->jalannafas,
                        'rs9' => $request->pernapasan,
                        'rs14' => $request->eye,
                        'rs15' => $request->verbal,
                        'rs16' => $request->motorik,
                        'rs18' => $kdpegsimrs,
                        'rs19' => $request->sirkulasi,
                        'flaghamil' => $request->pasienhamil,
                        'haidterakir' => $request->haid,
                        'gravida' => $request->gravida,
                        'partus' => $request->partus,
                        'abortus' => $request->abortus,
                        'nyeri' => $request->nyeri,
                        'lochea' => $request->lochea,
                        'proteinurin' => $request->proteinurin,
                        'rs20' => $request->disability,
                    ]
                );

                $result = [
                    'id' => $simpan['id'],
                    'noreg' => $simpan['rs1'],
                    'rs1' => $simpan['rs1'],
                    'suhu' => $simpan['rs8'],
                    'pernapasanx' => $simpan['rs10'],
                    'nadi' => $simpan['rs11'],
                    'bb' => $simpan['rs13'],
                    'tb' => $simpan['rs21'],
                    'sistole' => $simpan['sistole'],
                    'diastole' => $simpan['diastole'],
                    'kesadaran' => $simpan['kesadarans'],
                    'spo2' => $simpan['spo2'],
                    'doa' => $simpan['doa'],
                    'jalannafas' => $simpanx['rs7'],
                    'pernapasan' => $simpanx['rs9'],
                    'scoresistole' => $simpan['scoresistole'],
                    'scorediastole' => $simpan['scorediastole'],
                    'scorekesadaran' => $simpan['scorekesadaran'],
                    'scorelochea' => $simpan['scorelochea'],
                    'scorenadi' => $simpan['scorenadi'],
                    'scorenyeri' => $simpan['scorenyeri'],
                    'scorepernapasanx' => $simpan['scorepernapasanx'],
                    'scoreproteinurin' => $simpan['scoreproteinurin'],
                    'scorespo2' => $simpan['scorespo2'],
                    'scoresuhu' => $simpan['scoresuhu'],
                    'totalscore' => $simpan['totalscore'],
                    'kategoritriage' => $simpan['rs16'],
                    'hasilprimarusurve' => $simpan['hasilprimarusurve'],
                    'hasilsecondsurve' => $simpan['hasilsecondsurve'],

                    'gangguanperilaku' => $simpan['gangguanperilaku'],
                    'falsetriage' => $simpan['falsetriage'] === false ? '0' : '1',

                    'eye' => $simpanx['rs14'],
                    'verbal' => $simpanx['rs15'],
                    'motorik' => $simpanx['rs16'],
                    'sirkulasi' => $simpanx['rs19'],
                    'flaghamil' => $simpanx['flaghamil'],
                    'haid' => $simpanx['haidterakir'],
                    'gravida' => $simpanx['gravida'],
                    'partus' => $simpanx['partus'],
                    'abortus' => $simpanx['abortus'],
                    'nyeri' => $simpanx['nyeri'],
                    'lochea' => $simpanx['lochea'],
                    'proteinurin' => $simpanx['proteinurin'],
                    'disability' => $simpanx['rs20'],

                    'meninggaldiluarrs' => $simpan['meninggaldiluarrs'],
                    'barulahirmeninggal' => $simpan['barulahirmeninggal'],
                ];

                 DB::commit();
                return new JsonResponse([
                    'message' => 'BERHASIL DISIMPAN',
                    'result' => $result
                ], 200);

            } catch (\Exception $e) {
                DB::rollback();
                return new JsonResponse([
                    'message' => 'GAGAL DISIMPAN...!!! '. $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ], 500);
            }
        }
    }

    public function hapustriage(Request $request)
    {
          try {
            $carinoreg = TriageA::select('rs1')->where('id', $request->id)->get();
            $noreg = $carinoreg[0]['rs1'];

            $cariid = TriageB::select('id')->where('rs1', $noreg)->orderBy('id','DESC')->limit(1)->get();
            $id = $cariid[0]['id'];

            $triageA = TriageA::find($request->id);
            $triageB = TriageB::find($id);

            $hapusB = $triageB->delete();
            $hapusA = $triageA->delete();

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

    public function getDataTriage()
    {
        $result = TriageA::select(
            'rs250.id','rs250.rs1 as noreg','rs250.rs1',
            'rs250.doa','rs250.rs6 as tanggal',
            'rs250.rs8 as suhu',
            'rs250.rs10 as pernapasan',
            'rs250.rs11 as nadi',
            'rs250.rs12 as tensi',
            'rs250.rs13 as bb',
            'rs250.rs21 as tb',
            'rs250.rs10 as pernapasanx',
            'rs250.sistole',
            'rs250.diastole',
            'rs250.kesadarans as kesadaran','rs250.scorediastole','rs250.scoresistole','rs250.scorekesadaran','rs250.scorelochea','rs250.scorenadi','rs250.scorenyeri',
            'rs250.scorepernapasanx','rs250.scoreproteinurin','rs250.scorespo2','rs250.scoresuhu','rs250.totalscore','rs250.rs16 as kategoritriage','rs250.hasilprimarusurve',
            'rs250.hasilsecondsurve','rs250.meninggaldiluarrs','rs250.barulahirmeninggal',
            'rs251.rs14 as eye',
            'rs251.rs15 as verbal',
            'rs251.rs16 as motorik',
            'rs250.spo2','rs250.gangguanperilaku','rs250.falsetriage',
            'rs251.flaghamil',
            'rs251.haidterakir as haid',
            'rs251.gravida',
            'rs251.partus',
            'rs251.abortus','rs251.nyeri','rs251.lochea','rs251.proteinurin',
            'rs251.rs7 as jalannafas','rs251.rs9 as pernapasan','rs251.rs19 as sirkulasi','rs251.rs20 as disability'
            )->leftjoin('rs251','rs250.rs1','rs251.rs1')
            ->where('rs250.rs1', request('noreg'))
            ->get();
        return new JsonResponse($result);
    }
}
