<?php

namespace App\Http\Controllers\Api\Simrs\Ranap\Pelayanan;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Anamnesis\Anamnesis;
use App\Models\Simrs\Anamnesis\KeluhanNyeri;
use App\Models\Simrs\Master\Rstigapuluhtarif;
use App\Models\Simrs\Ranap\Pelayanan\Cppt;
use App\Models\Simrs\Ranap\Pelayanan\Pemeriksaan\PemeriksaanKebidanan;
use App\Models\Simrs\Ranap\Pelayanan\Pemeriksaan\PemeriksaanNeonatal;
use App\Models\Simrs\Ranap\Pelayanan\Pemeriksaan\PemeriksaanPediatrik;
use App\Models\Simrs\Ranap\Pelayanan\Pemeriksaan\PemeriksaanSambung;
use App\Models\Simrs\Ranap\Pelayanan\Pemeriksaan\PemeriksaanUmum;
use App\Models\Simrs\Ranap\Pelayanan\Pemeriksaan\Penilaian;
use App\Models\Simrs\Visite\Visite;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CpptController extends Controller
{
    public function list()
    {
        $data = self::getdata(request('noreg'), null);
        return new JsonResponse($data);
    }

    public static function getdata($noreg, $id){
      $data = Cppt::query()
      ->where(function($query) use ($noreg, $id){
        if ($id !==null) {
          $query->where('id', $id);
        } else {
          $query->where('noreg', $noreg);
        }
      })
      ->with([
        'petugas:kdpegsimrs,nik,nama,kdgroupnakes',
        'anamnesis'=> function($query){
          $query->select(
            'rs209.id','rs209.rs1','rs209.rs1 as noreg',
            'rs209.rs2 as norm',
            'rs209.rs3 as tgl',
            'rs209.rs4 as keluhanUtama',
            'rs209.riwayatpenyakit',
            'rs209.riwayatalergi',
            'rs209.keteranganalergi',
            'rs209.riwayatpengobatan',
            'rs209.riwayatpenyakitsekarang',
            'rs209.riwayatpenyakitkeluarga',
            'rs209.riwayat_pekerjaan_yang_berhubungan_dengan_zat_berbahaya',
            'rs209.kdruang',
            'rs209.awal',
            'rs209.user',
          )->with(['petugas:kdpegsimrs,nik,nama,kdgroupnakes',
          'keluhannyeri',
          'skreeninggizi',
          'neonatal',
          'pediatrik',
          'kebidanan'
          ]);
          // ->where('awal','!=', '1');
        },
        'pemeriksaan'=> function($query){
          $query->select([
            'rs253.id','rs253.rs1','rs253.rs1 as noreg',
          'rs253.rs2 as norm',
          'rs253.rs3 as tgl',
          'rs253.rs4 as ruang',
          'rs253.pernapasan as pernapasanigd',
          'rs253.nadi as nadiigd',
          'rs253.tensi as tensiigd',
          'rs253.beratbadan',
          'rs253.tinggibadan',
          'rs253.kdruang',
          'rs253.user',
          'rs253.awal',
          
          'sambung.keadaanUmum',
          'sambung.bb' ,
          'sambung.tb' ,
          'sambung.nadi' ,
          'sambung.suhu' ,
          'sambung.sistole' ,
          'sambung.diastole' ,
          'sambung.pernapasan' ,
          'sambung.spo' ,
          'sambung.tkKesadaran' ,
          'sambung.tkKesadaranKet' ,
          'sambung.sosial' ,
          'sambung.spiritual' ,
          'sambung.statusPsikologis' ,
          'sambung.ansuransi' ,
          'sambung.edukasi',
          'sambung.ketEdukasi',
          'sambung.penyebabSakit' ,
          'sambung.komunikasi' ,
          'sambung.makananPokok' ,
          'sambung.makananPokokLain' ,
          'sambung.pantanganMkanan' ,
          
          'pegawai.nama as petugas',
          'pegawai.kdgroupnakes as nakes',
          ])
          ->leftJoin('rs253_sambung as sambung', 'rs253.id', '=', 'sambung.rs253_id')
          ->leftJoin('kepegx.pegawai as pegawai', 'rs253.user', '=', 'pegawai.kdpegsimrs')
          // ->with(['petugas:kdpegsimrs,nik,nama,kdgroupnakes',
          //   'neonatal',
          //   'pediatrik',
          //   'kebidanan',
          //   //  'penilaian'
          //   ])
            ->groupBy('rs253.id')
          ;
          // ->where('awal','!=', '1');
        },
        'penilaian'=> function($query){
          $query->select([
            'id','rs1','rs1 as noreg',
            'rs2 as norm','rs3 as tgl',
            'barthel','norton','humpty_dumpty','morse_fall','ontario','user','kdruang','awal','group_nakes'
          ]);
          // ->where('awal','!=', '1');
        },
        'cpptlama',

        ])
      ->orderBy('tgl', 'DESC')
      ->get();
      return $data;
    }

    
    public function saveCppt(Request $request)
    {

      $cekKasir = DB::table('rs23')->select('rs42')->where('rs1', $request->noreg)->where('rs41', '=','1')->get();

      if (count($cekKasir) > 0) {
        return response()->json(['status' => 'failed', 'message' => 'Maaf, data pasien telah dikunci oleh kasir pada tanggal '.$cekKasir[0]->rs42], 500);
      }

      $user = Pegawai::find(auth()->user()->pegawai_id);
      $kdpegsimrs = $user->kdpegsimrs;
      $nakes = $user->kdgroupnakes;

       $anamnesis = AnamnesisController::storeAnamnesis((object) $request->anamnesis);
      //  return $anamnesis;
       $anamnesisId = null;
       if ($anamnesis['success']===true) {
        $anamnesisId = $anamnesis['idAnamnesis'];
       }

       $pemeriksaanUmum = PemeriksaanUmumController::store((object) $request->pemeriksaan);
      //  return $pemeriksaanUmum;
       $pemeriksaanUmumId = null;
       if ($pemeriksaanUmum['success']===true) {
        $pemeriksaanUmumId = $pemeriksaanUmum['idPemeriksaan'];
       }

       $penilaian = PemeriksaanPenilaianController::store((object) $request->penilaian);
       $penilaianId = null;
       if ($penilaian['success']===true) {
        $penilaianId = $penilaian['idPenilaian'];
       }

       $cppt = Cppt::create([
        'noreg' => $request->noreg,
        'norm' => $request->norm,
        'tgl' => date('Y-m-d H:i:s'),
        'rs209_id' => $anamnesisId,
        'rs253_id' => $pemeriksaanUmumId,
        'penilaian_id' => $penilaianId,
        'asessment'=> $request->form['asessment'],
        'plann'=> $request->form['plann'],
        'instruksi'=> $request->form['instruksi'],
        'kdruang' => $request->kdruang,
        'user' => $kdpegsimrs,
        'nakes'=> $nakes,
        // tambahan baru
        's_sambung' => $request->form['s_sambung'] ?? null,
        'o_sambung' => $request->form['o_sambung'] ?? null,

       ]);

       if (!$cppt) {
        return new JsonResponse([
          'success' => false,
          'message' => 'Gagal menyimpan data'
        ]);
       }

       // insert tarif jika akun dokter
       $dokter = $nakes === '1';
       if ($dokter) {
          self::insertTarif($request,$user, $kdpegsimrs);
       }
       

       $result = self::getdata($request->noreg, null);

       return new JsonResponse([
        'success' => true,
        'message' => 'success',
        'result' => $result
       ]);
    }

    public static function insertTarif($request,$user, $kdpegsimrs)
    {


        $spesialis = strtoupper($user->statusspesialis) === 'SPESIALIS';

        // cek tarif
        $tarifVisite = self::cekTarip($spesialis, $request);
        if (!$tarifVisite) {
          return new JsonResponse(['message' => 'Maaf Ada error Server .... harap menghubungi IT'], 500);
        }

        // cek apakah sudah ada billing
        $cekTarif = Visite::select('rs1')
          ->where('rs1', $request->noreg)
          ->where('rs3', $kdpegsimrs)
          ->where('rs2', 'LIKE', '%'.date('Y-m-d').'%')
          ->where('rs6', $tarifVisite['flag_biaya'])
          ->get();

          $hari_ini = date('Y-m-d H:i:s');

          // jika billing belum masuk
          if (count($cekTarif) === 0) {

            Visite::create([
              'rs1' => $request->noreg,
              'rs2' => $hari_ini,
              'rs3' => $kdpegsimrs,
              'rs4' => $tarifVisite['sarana'],
              'rs5' => $tarifVisite['pelayanan'],
              'rs6' => $tarifVisite['flag_biaya'],
              'rs8' => $request->kdgroup_ruangan,
              'rs9' => $request->kodesistembayar
            ]);
          }
    }


    public static function cekTarip($spesialis, $request)
    {
        
      $sarana=0;
      $pelayanan=0;
      $flag_biaya=null;

        if ($spesialis) {
          // "select * from rs30tarif where (rs3='V2#' or rs3='V3#'
          $rsx=null;
          if ($request->kelas_ruangan==="IC" || $request->kelas_ruangan==="ICC" || $request->kelas_ruangan==="NICU" ){
            $rsx = Rstigapuluhtarif::where('rs3', 'V3#')
            ->where('rs4', 'like', '%|'.$request->kdgroup_ruangan.'|%')
            ->where('rs5', 'like', '%|'.$request->kelas_ruangan.'|%')
            ->first();
          } else {
            $rsx = Rstigapuluhtarif::where('rs3', 'V2#')
            ->where('rs4', 'like', '%|'.$request->kdgroup_ruangan.'|%')
            ->where('rs5', 'like', '%|'.$request->kelas_ruangan.'|%')
            ->first();
          }
         
        
          //   return $rsx;
          if (!$rsx) {
            $sarana=0;
            $pelayanan=0;
            $flag_biaya=null;
          }
          
          $flag_biaya=$rsx->rs3;

          if($request->kelas_ruangan==="3" || $request->kelas_ruangan==="IC" || $request->kelas_ruangan==="ICC" || $request->kelas_ruangan==="NICU" || $request->kelas_ruangan==="IN")
          {
            $sarana=$rsx->rs6;
						$pelayanan=$rsx->rs7;
          }else if($request->kelas_ruangan=="2"){
						$sarana=$rsx->rs8;
						$pelayanan=$rsx->rs9;
					}else if($request->kelas_ruangan=="1"){
						$sarana=$rsx->rs10;
						$pelayanan=$rsx->rs11;
					}else if($request->kelas_ruangan=="Utama"){
						$sarana=$rsx->rs12;
						$pelayanan=$rsx->rs13;
					}else if($request->kelas_ruangan=="VIP"){
						$sarana=$rsx->rs14;
						$pelayanan=$rsx->rs15;
					}else if($request->kelas_ruangan=="VVIP"){
						$sarana=$rsx->rs16;
						$pelayanan=$rsx->rs17;
					}	else if ($request->kelas_ruangan == "HCU") {


            $hakKelas = $request->hak_kelas;
            if ($hakKelas === '1') {
                $sarana = $rsx->rs10;
                $pelayanan = $rsx->rs11;
            } else if($hakKelas === '2'){
                $sarana = $rsx->rs8;
                $pelayanan = $rsx->rs9;
            } else if($hakKelas === '3'){
                $sarana = $rsx->rs6;
                $pelayanan = $rsx->rs7;
            }
          } else if ($request->kelas_ruangan == "PS") {

              $sarana = $rsx->pss;
              $pelayanan = $rsx->psp;
              
          }
        } else {

          //select * from rs30tarif where (rs3='V1#

          $rsx=null;
          if ($request->kelas_ruangan==="IC" || $request->kelas_ruangan==="ICC" || $request->kelas_ruangan==="NICU" ){
            $rsx = Rstigapuluhtarif::where('rs3', 'V5#')
            ->where('rs4', 'like', '%|'.$request->kdgroup_ruangan.'|%')
            ->where('rs5', 'like', '%|'.$request->kelas_ruangan.'|%')
            ->first();
          } else {
            $rsx = Rstigapuluhtarif::where('rs3', 'V1#')
            ->where('rs4', 'like', '%|'.$request->kdgroup_ruangan.'|%')
            ->where('rs5', 'like', '%|'.$request->kelas_ruangan.'|%')
            ->first();
          }

          if (!$rsx) {
            return null;
          }

          
          $flag_biaya=$rsx->rs3;

          if($request->kelas_ruangan==="3" || $request->kelas_ruangan==="IC" || $request->kelas_ruangan==="ICC" || $request->kelas_ruangan==="NICU" || $request->kelas_ruangan==="IN")
          {
            $sarana=$rsx->rs6;
						$pelayanan=$rsx->rs7;
					}else if($request->kelas_ruangan==="2"){
						$sarana=$rsx->rs8;
						$pelayanan=$rsx->rs9;
					}else if($request->kelas_ruangan==="1"){
						$sarana=$rsx->rs10;
						$pelayanan=$rsx->rs11;
					}else if($request->kelas_ruangan==="Utama"){
						$sarana=$rsx->rs12;
						$pelayanan=$rsx->rs13;
					}else if($request->kelas_ruangan==="VIP"){
						$sarana=$rsx->rs14;
						$pelayanan=$rsx->rs15;
					}else if($request->kelas_ruangan==="VVIP"){
						$sarana=$rsx->rs16;
						$pelayanan=$rsx->rs17;
					}	else if ($request->kelas_ruangan == "HCU") {


            $hakKelas = $request->hak_kelas;
            if ($hakKelas === '1') {
                $sarana = $rsx->rs10;
                $pelayanan = $rsx->rs11;
            } else if($hakKelas === '2'){
                $sarana = $rsx->rs8;
                $pelayanan = $rsx->rs9;
            } else if($hakKelas === '3'){
                $sarana = $rsx->rs6;
                $pelayanan = $rsx->rs7;
            }
          } else if ($request->kelas_ruangan == "PS") {

              $sarana = $rsx->pss;
              $pelayanan = $rsx->psp;
              
          }
        }

        $tarif = (int) $sarana + (int) $pelayanan;

        return [
          'flag_biaya' => $flag_biaya,
          'tarif' => $tarif,
          'sarana' => $sarana,
          'pelayanan' => $pelayanan
        ];
    }


    public function editCpptAnamnesis(Request $request)
    {
      $user = Pegawai::find(auth()->user()->pegawai_id);
      $kdpegsimrs = $user->kdpegsimrs;

      DB::beginTransaction();
      try {
        
        $data = null;
        if ($request->id !== null) {
          $data = Anamnesis::find($request->id);
        } else {
          $data = new Anamnesis();
        }

        $data->rs1 = $request->noreg;
        $data->rs2 = $request->norm;
        $data->rs3 = date('Y-m-d H:i:s');
        $data->rs4 = $request->form['keluhanUtama'] ?? '';
        $data->kdruang  = $request->kdruang;
        $data->user  = $kdpegsimrs;
        $data->save();

        $skorNyeri = 0;
        $ketNyeri = null;
        $formNyeri = null;
        if ($request->formKebidanan ===null && $request->formNeoNatal=== null && $request->formPediatrik=== null) {
          $skorNyeri = $request->form['keluhannyeri']['skorNyeri'] ?? 0;
          $ketNyeri = $request->form['keluhannyeri']['ket'] ?? null;
          $formNyeri = $request->form['keluhannyeri'];
        }
        else if ($request->formKebidanan !==null) {
          $skorNyeri = $request->formKebidanan['keluhannyeri']['skorNyeri'] ?? 0;
          $ketNyeri = $request->formKebidanan['keluhannyeri']['ket'] ?? null;
          $formNyeri = null;
        }
        else if ($request->formNeoNatal !==null) {
          $skorNyeri = $request->formNeoNatal['keluhannyeri']['skorNyeri'] ?? 0;
          $ketNyeri = $request->formNeoNatal['keluhannyeri']['ket'] ?? null;
          $formNyeri = null;
        }
        else if ($request->formPediatrik !==null) {
          $skorNyeri = $request->formPediatrik['keluhannyeri']['skorNyeri'] ?? 0;
          $ketNyeri = $request->formPediatrik['keluhannyeri']['ket'] ?? null;
          $formNyeri = null;
        }

        $klNyeri = KeluhanNyeri::where('rs209_id', $data->id)->first();
        if (!$klNyeri) {
          $klNyeri = new KeluhanNyeri();
        }

        $klNyeri->rs209_id = $data->id;
        $klNyeri->noreg = $data->rs1;
        $klNyeri->norm = $data->rs2;
        $klNyeri->dewasa= $formNyeri; // array
        $klNyeri->kebidanan= $request->formKebidanan['keluhannyeri'] ?? null; // array
        $klNyeri->neonatal= $request->formNeoNatal['keluhannyeri'] ?? null; // array
        $klNyeri->pediatrik= $request->formPediatrik['keluhannyeri'] ?? null; // array
        $klNyeri->skor= $skorNyeri;
        $klNyeri->keluhan= $ketNyeri;
        $klNyeri->user_input= $kdpegsimrs;
        $klNyeri->group_nakes = $user->kdgroupnakes;
        $klNyeri->save();


        // KeluhanNyeri::where('rs209_id', $data->id)->update(
        //   [
        //     'dewasa'=> $request->form['keluhannyeri'] ?? null, // array
        //     'kebidanan'=> $request->formKebidanan['keluhannyeri'] ?? null, // array
        //     'neonatal'=> $request->formNeoNatal['keluhannyeri'] ?? null, // array
        //     'pediatrik'=> $request->formPediatrik['keluhannyeri'] ?? null, // array
        //     'skor'=> $skorNyeri,
        //     'keluhan'=> $ketNyeri,
        //     'user_input'=> $kdpegsimrs,
        //     'group_nakes' => $user->kdgroupnakes
        //   ]
        // );

        if ($request->id === null) {
          Cppt::find($request->id_cppt)->update([
            'rs209_id' => $data->id
          ]);
        }

        DB::commit();
        return new JsonResponse([
          'success' => true,
          'message' => 'success',
          'result' => self::getdata(null, $request->id_cppt)
        ]);
      } catch (\Exception $th) {
        DB::rollBack();
        $data = [
          'success' => false,
          'message' => 'GAGAL DISIMPAN',
          'result' => $th->getMessage(),
        ];
        return new JsonResponse($data, 500);
      }
    }


    public function editCpptPemeriksaan(Request $request)
    {
      $user = Pegawai::find(auth()->user()->pegawai_id);
      $kdpegsimrs = $user->kdpegsimrs;

      // return $request->all();

      DB::beginTransaction();
      try {
        
        $data = null;
        if ($request->id !== null) {
          $cek = PemeriksaanUmum::find($request->id);
          if (!$cek) {
            $data = new PemeriksaanUmum();
          } else{
            $data = $cek;
          }
        } else {
          $data = new PemeriksaanUmum();
        }

        // return $cek = PemeriksaanUmum::find($request->id);

        $data->rs1 = $request->noreg;
        $data->rs2 = $request->norm;
        $data->rs3 = date('Y-m-d H:i:s');
        $data->pernapasan = $request->form['pernapasan'] ?? '';
        $data->nadi  = $request->form['nadi'] ?? '';
        $data->tensi  = $request->form['sistole']. '/' . $request->form['diastole'];
        $data->beratbadan  = $request->form['bb'];
        $data->tinggibadan  = $request->form['tb'];
        $data->kdruang  = $request->kdruang;
        $data->save();

        PemeriksaanSambung::updateOrCreate(
          ['rs253_id' => $data->id],
          [
            'noreg' => $request->noreg,
            'norm' => $request->norm,
            'keadaanUmum' => $request->form['keadaanUmum'] ?? '',
            'bb' => $request->form['bb'],
            'tb' => $request->form['tb'],
            'nadi' => $request->form['nadi'],
            'suhu' => $request->form['suhu'],
            'sistole' => $request->form['sistole'],
            'diastole' => $request->form['diastole'],
            'pernapasan' => $request->form['pernapasan'],
            'spo' => $request->form['spo'],
            'tkKesadaran' => $request->form['tkKesadaran'],
            'tkKesadaranKet' => $request->form['tkKesadaranKet'],
            'sosial' => $request->form['sosial'],
            'spiritual' => $request->form['spiritual'],
            'statusPsikologis' => $request->form['statusPsikologis'],
            'ansuransi' => $request->form['ansuransi'],
            'edukasi' => $request->form['edukasi'],
            'ketEdukasi' => $request->form['ketEdukasi'],
            'penyebabSakit' => $request->form['penyebabSakit'],
            'komunikasi' => $request->form['komunikasi'],
            'makananPokok' => $request->form['makananPokok'],
            'makananPokokLain' => $request->form['makananPokokLain'],
            'pantanganMkanan' => $request->form['pantanganMkanan'],
          ]
        );

        // save kebidanan
        if ($request->formKebidanan !==null) {
          PemeriksaanKebidanan::updateOrCreate(
            ['rs253_id'=> $data->id],
            [
              'noreg'=> $request->noreg,
              'norm'=> $request->norm,
              'nyeri'  => $request->formKebidanan['nyeri'],
              'lochea'  => $request->formKebidanan['lochea'],
              'proteinUrin'  => $request->formKebidanan['proteinUrin'],
              'mata'  => $request->formKebidanan['mata'],
              'leher'  => $request->formKebidanan['leher'],
              'dada'  => $request->formKebidanan['dada'],
              'putingMenonjol'  => $request->formKebidanan['putingMenonjol'],
              'hiperpigmentasi'  => $request->formKebidanan['hiperpigmentasi'],
              'kolostrum'  => $request->formKebidanan['kolostrum'],
              'konsistensiPayudara'  => $request->formKebidanan['konsistensiPayudara'],
              'nyeriTekan'  => $request->formKebidanan['nyeriTekan'],
              'benjolan'  => $request->formKebidanan['benjolan'],
              'abdomen'  => $request->formKebidanan['abdomen'],
              'anoGenital'  => $request->formKebidanan['anoGenital'],
              'ekstremitasTungkai'  => $request->formKebidanan['ekstremitasTungkai'],
              'hmlInspeksi'  => $request->formKebidanan['hmlInspeksi'],
              'hmlTfuPuka'  => $request->formKebidanan['hmlTfuPuka'],
              'hmlTfuPuki'  => $request->formKebidanan['hmlTfuPuki'],
              'hmlTfuPresentasi'  => $request->formKebidanan['hmlTfuPresentasi'],
              'hmlNyeri'  => $request->formKebidanan['hmlNyeri'],
              'hmlOsborn'  => $request->formKebidanan['hmlOsborn'],
              'hmlCekung'  => $request->formKebidanan['hmlCekung'],
              'hmlAusDenyut'  => $request->formKebidanan['hmlAusDenyut'],
              'hmlAusTeratur'  => $request->formKebidanan['hmlAusTeratur'],
              'hmlHisFrekuensi'  => $request->formKebidanan['hmlHisFrekuensi'],
              'hmlHisIntensitas'  => $request->formKebidanan['hmlHisIntensitas'],
              'hmlVgnBentuk'  => $request->formKebidanan['hmlVgnBentuk'],
              'hmlVgnJml'  => $request->formKebidanan['hmlVgnJml'],
              'hmlVgnKtuban'  => $request->formKebidanan['hmlVgnKtuban'],
              'hmlVgnToucher'  => $request->formKebidanan['hmlVgnToucher'],
              'nfsTfu'  => $request->formKebidanan['nfsTfu'],
              'nfsUterus'  => $request->formKebidanan['nfsUterus'],
              'nfsVgnBentuk'  => $request->formKebidanan['nfsVgnBentuk'],
              'nfsVgnJml'  => $request->formKebidanan['nfsVgnJml'],
              'nfsVgnLochea'  => $request->formKebidanan['nfsVgnLochea'],
              'nfsVgnLuka'  => $request->formKebidanan['nfsVgnLuka'],
              'nfsVgnDrjLuka'  => $request->formKebidanan['nfsVgnDrjLuka'],
              'nfsVgnLukaPost'  => $request->formKebidanan['nfsVgnLukaPost'],
              'gynecologiPalpasi'  => $request->formKebidanan['gynecologiPalpasi'],
              'gynecologiInsVgn'  => $request->formKebidanan['gynecologiInsVgn'],
              'gynecologiInsPortio'  => $request->formKebidanan['gynecologiInsPortio'],
              'gynecologiInsVgnToucher'  => $request->formKebidanan['gynecologiInsVgnToucher'],
              'user_input'=> $kdpegsimrs,
              'group_nakes' => $user->kdgroupnakes
    
            ]
          );
        } else {
          PemeriksaanKebidanan::where('rs253_id', $data->id)->delete();
        }

        // save neonatal
        if ($request->formNeonatal !==null) {
          PemeriksaanNeonatal::updateOrCreate(
            ['rs253_id'=> $data->id],
            [
              'noreg'=> $request->noreg,
              'norm'=> $request->norm,
              
              'lila'  => $request->formNeonatal['lila'],
              'lida'  => $request->formNeonatal['lida'],
              'lirut'  => $request->formNeonatal['lirut'],
              'grkBayi'  => $request->formNeonatal['grkBayi'],
              'uub'  => $request->formNeonatal['uub'],
              'kejang'  => $request->formNeonatal['kejang'],
              'refleks'  => $request->formNeonatal['refleks'],
              'tngsBayi'  => $request->formNeonatal['tngsBayi'],
              'pssMata'  => $request->formNeonatal['pssMata'],
              'bsrPupil'  => $request->formNeonatal['bsrPupil'],
              'klpkMata'  => $request->formNeonatal['klpkMata'],
              'konjungtiva'  => $request->formNeonatal['konjungtiva'],
              'sklera'  => $request->formNeonatal['sklera'],
              'pendengaran'  => $request->formNeonatal['pendengaran'],
              'penciuman'  => $request->formNeonatal['penciuman'],
              'warnaKlt'  => $request->formNeonatal['warnaKlt'],
              'denyutNadi'  => $request->formNeonatal['denyutNadi'],
              'sirkulasi'  => $request->formNeonatal['sirkulasi'],
              'pulsasi'  => $request->formNeonatal['pulsasi'],
              'polaNafas'  => $request->formNeonatal['polaNafas'],
              'jnsPernafasan'  => $request->formNeonatal['jnsPernafasan'],
              'irmNapas'  => $request->formNeonatal['irmNapas'],
              'retraksi'  => $request->formNeonatal['retraksi'],
              'airEntri'  => $request->formNeonatal['airEntri'],
              'merintih'  => $request->formNeonatal['merintih'],
              'suaraNapas'  => $request->formNeonatal['suaraNapas'],
              'mulut'  => $request->formNeonatal['mulut'],
              'lidah'  => $request->formNeonatal['lidah'],
              'oesofagus'  => $request->formNeonatal['oesofagus'],
              'abdomen'  => $request->formNeonatal['abdomen'],
              'bab'  => $request->formNeonatal['bab'],
              'warnaBab'  => $request->formNeonatal['warnaBab'],
              'warnaUrine'  => $request->formNeonatal['warnaUrine'],
              'bak'  => $request->formNeonatal['bak'],
              'laki'  => $request->formNeonatal['laki'],
              'perempuan'  => $request->formNeonatal['perempuan'],
              'vernicKasesosa'  => $request->formNeonatal['vernicKasesosa'],
              'lanugo'  => $request->formNeonatal['lanugo'],
              'warnaIntegument'  => $request->formNeonatal['warnaIntegument'],
              'turgor'  => $request->formNeonatal['turgor'],
              'kulit'  => $request->formNeonatal['kulit'],
              'lengan'  => $request->formNeonatal['lengan'],
              'tungkai'  => $request->formNeonatal['tungkai'],
              'rekoilTelinga'  => $request->formNeonatal['rekoilTelinga'],
              'grsTlpkKaki'  => $request->formNeonatal['grsTlpkKaki'],
              'apgarScores'  => $request->formNeonatal['apgarScores'],
              'apgarScore'  => $request->formNeonatal['apgarScore'],
              'apgarKet'  => $request->formNeonatal['apgarKet'],

              'user_input'=> $kdpegsimrs,
              'group_nakes' => $user->kdgroupnakes
    
            ]
          );
        } else {
          PemeriksaanNeonatal::where('rs253_id', $data->id)->delete();
        }

        // save pediatri
        if ($request->formPediatrik !==null) {
          PemeriksaanPediatrik::updateOrCreate(
            ['rs253_id'=> $data->id],
            [
              'noreg'=> $request->noreg,
              'norm'=> $request->norm,
              
              'lila'  => $request->formPediatrik['lila'],
              'lida'  => $request->formPediatrik['lida'],
              'lirut'  => $request->formPediatrik['lirut'],
              'lilengtas'  => $request->formPediatrik['lilengtas'],
              'glasgow'  => $request->formPediatrik['glasgow'],
              'glasgowSkor'  => $request->formPediatrik['glasgowSkor'],
              'glasgowKet'  => $request->formPediatrik['glasgowKet'],
              
              'user_input'=> $kdpegsimrs,
              'group_nakes' => $user->kdgroupnakes
            ]
          );
        }else {
          PemeriksaanPediatrik::where('rs253_id', $data->id)->delete();
        }


        //penilaian

        Penilaian::where('id', $request->penilaian['id'])->update(
          [
            'barthel' => $request->penilaian['barthel'],
            'norton' => $request->penilaian['norton'],
            'humpty_dumpty' => $request->penilaian['humpty_dumpty'],
            'morse_fall' => $request->penilaian['morse_fall'],
            'ontario' => $request->penilaian['ontario'],
            
            
            'user'  => $kdpegsimrs,
            'group_nakes'  => $user->kdgroupnakes,
          ]
        );

        if ($data->id) {
          Cppt::find($request->id_cppt)->update([
            'rs253_id' => $data->id,
          ]);
        }

        DB::commit();
        return new JsonResponse([
          'success' => true,
          'message' => 'success',
          'result' => self::getdata(null, $request->id_cppt)
        ]);
      } catch (\Exception $th) {
        DB::rollBack();
        $data = [
          'success' => false,
          'message' => 'GAGAL DISIMPAN',
          'result' => $th->getMessage(),
        ];
        return new JsonResponse($data, 500);
      }

    }

    // updateAsPlanInst
    public function updateAsPlanInst(Request $request)
    {
      $user = Pegawai::find(auth()->user()->pegawai_id);
      $kdpegsimrs = $user->kdpegsimrs;

      $cppt = Cppt::find($request->id)->update([
        
        'asessment'=> $request->asessment,
        'plann'=> $request->plann,
        'instruksi'=> $request->instruksi,
        'user' => $kdpegsimrs,
        'nakes'=> $user->kdgroupnakes,
      ]);
      

      return new JsonResponse([
        'success' => true,
        'message' => 'success',
        'result' => $cppt
      ]);

    }
    public function updateosambung(Request $request)
    {
      // $user = Pegawai::find(auth()->user()->pegawai_id);
      // $kdpegsimrs = $user->kdpegsimrs;

      $cppt = Cppt::find($request->id)->update([
        
        'o_sambung'=> $request->o_sambung,
      ]);
      

      return new JsonResponse([
        'success' => true,
        'message' => 'success',
        'result' => $cppt
      ]);

    }
    public function updatessambung(Request $request)
    {

      $cppt = Cppt::find($request->id)->update([
        
        's_sambung'=> $request->s_sambung,
      ]);
      

      return new JsonResponse([
        'success' => true,
        'message' => 'success',
        'result' => $cppt
      ]);

    }


    public function deleteCppt(Request $request)
    {
      $cari = Cppt::find($request->id);
      if (!$cari) {
          return new JsonResponse(['message' => 'MAAF DATA TIDAK DITEMUKAN'], 500);
      }
      $hapus = $cari->delete();
      if (!$hapus) {
          return new JsonResponse(['message' => 'gagal dihapus'], 501);
      }
      return new JsonResponse(
          [
              'message' => 'data berhasil dihapus'
          ], 
      200);
    }

   


    
}
