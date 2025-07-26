<?php

namespace App\Http\Controllers\Api\Simrs\Ranap;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Rajal\Igd\TriageA;
use App\Models\Simrs\Rajal\KunjunganPoli;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HistoryController extends Controller
{
    

    public function layananigd(Request $request)
    {
        $cekx = KunjunganPoli::select('rs1', 'rs2', 'rs3','rs4','rs8', 'rs9','rs14', 'rs19')->where('rs1', $request->noreg)->where('rs8','POL014')
        ->with([
            'anamnesis' => function($anamnesis){
                $anamnesis->with(['anamnesetambahan','anamnesebps','anamnesenips'])->where('kdruang', 'POL014');
            },
            'datasimpeg:id,nip,nik,nama,kelamin,foto,kdpegsimrs,kddpjp,ttdpegawai',
            'permintaanperawatanjenazah',
            'triage' => function($triage) {
                $triage->select(
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
                'rs250.hasilsecondsurve',
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
                )->leftjoin('rs251','rs250.rs1','rs251.rs1')->groupBy('id');
            },
            'penilaiananamnesis' => function($penilaiananamnesis){
                $penilaiananamnesis->select([
                    'id','rs1','rs1 as noreg',
                    'rs2 as norm','rs3 as tgl',
                    'barthel','norton','humpty_dumpty','morse_fall','ontario','user','kdruang','awal','group_nakes'
                   ])
                   ->with(['petugas:kdpegsimrs,nik,nama,kdgroupnakes'])->where('kdruang','POL014');
            },
            'historyperkawinan',
            'historykehamilan',
            'anamnesekebidanan',
            'bankdarah',
            'msistembayar',
            'planheder' => function($planheder){
                $planheder->with([
                    'planranap' => function($planranap){
                        $planranap->with(
                            [
                                'ruangranap'
                            ]
                        );
                    },
                    'planrujukan',
                    'planpulang'
                ]);
            },
            'ambulan' => function($ambulan) {
                $ambulan->with(
                    [
                        'tujuan',
                        'perawat',
                        'perawat2'
                    ]
                );
            },
            'laborats' => function ($t) {
                $t->with('details.pemeriksaanlab')->where('unit_pengirim', 'POL014')
                    ->orderBy('id', 'DESC');
            },
            'laboratold'=> function ($t) {
                $t->with('pemeriksaanlab')
                    ->orderBy('id', 'DESC')->where('rs23','POL014');
            },
            'radiologi' => function ($t) {
                $t->orderBy('id', 'DESC');
            },
            'penunjanglain' => function ($t) {
                $t->with('masterpenunjang')->orderBy('id', 'DESC');
            },
            'tindakan' => function ($t) {
                $t->with('mastertindakan:rs1,rs2', 'pegawai:nama,kdpegsimrs', 'pelaksanalamasimrs:nama,kdpegsimrs', 'gambardokumens:id,rs73_id,nama,original,url','mpoli:rs1,rs2')
                    ->where('rs4','<>','T00075')
                    ->orderBy('id', 'DESC')->where('rs22','POL014');
            },
            'diagnosa' => function ($d) {
                $d->with('masterdiagnosa')->where('rs13','POL014');
            },
            'pemeriksaanfisik' => function ($a) {
                $a->with(['detailgambars', 'pemeriksaankhususmata', 'pemeriksaankhususparu'])
                    ->orderBy('id', 'DESC');
            },
            'ok' => function ($q) {
                $q->orderBy('id', 'DESC');
            },
            'diagnosakeperawatan'=> function ($d) {
                $d->with('petugas:id,nama,satset_uuid','intervensi.masterintervensi');
            },
            'diagnosakebidanan' => function ($diag) {
                    $diag->with('intervensi.masterintervensi');
            },
            'pemeriksaanfisikpsikologidll' => function($pemeriksaanfisikpsikologidll){
                $pemeriksaanfisikpsikologidll->with('pemerisaanpsikologidll')->where('kdruang','POL014');
            },
            // 'taskid' => function ($q) {
            //     $q->orderBy('taskid', 'DESC');
            // },
            // 'planning' => function ($p) {
            //     $p->with(
            //         'masterpoli',
            //         'rekomdpjp',
            //         'transrujukan',
            //         'listkonsul',
            //         'spri',
            //         'ranap',
            //         'kontrol',
            //         'operasi',
            //     )->orderBy('id', 'DESC');
            // },
            // 'edukasi' => function ($x) {
            //     $x->orderBy('id', 'DESC');
            // },
            // 'diet' => function ($diet) {
            //     $diet->orderBy('id', 'DESC');
            // },
            // 'sharing' => function ($sharing) {
            //     $sharing->orderBy('id', 'DESC');
            // },
            'newapotekrajal' => function ($newapotekrajal) {
                $newapotekrajal->with([
                    'permintaanresep.mobat:kd_obat,nama_obat',
                    'permintaanracikan.mobat:kd_obat,nama_obat',
                ])
                    ->orderBy('id', 'DESC');
            },
            'tinjauanulang' => function($tinjauanulang){
                $tinjauanulang->with([
                    'tinjauanulangnips',
                    'tinjauanulangbps'
                ]);
            },
            'konsuldokterspesialis' => function ($konsuldokterspesialis){ 
                $konsuldokterspesialis->with(
                    [
                        'tindakan' => function($tindakans){
                            $tindakans->with(
                                [
                                    'mastertindakan'
                                ]
                            );
                        }
                    ]
                )->where('kdruang', 'POL014');
            },
            'rencanaterapidokter',
            'skalatransfer',
        ])
        ->first();

        
        return new JsonResponse($cekx, 200);
    }

   
}
