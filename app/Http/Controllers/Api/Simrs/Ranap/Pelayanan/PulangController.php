<?php

namespace App\Http\Controllers\Api\Simrs\Ranap\Pelayanan;

use App\Helpers\BridgingbpjsHelper;
use App\Helpers\DateHelper;
use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Pasien;
use App\Models\Sigarang\Pegawai;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\DischargePlanning\DischargePlanning;
use App\Models\Simrs\Pendaftaran\Rajalumum\Bpjs_http_respon;
use App\Models\Simrs\Pendaftaran\Ranap\Sepranap;
use App\Models\Simrs\Ranap\Kunjunganranap;
use App\Models\Simrs\Ranap\Rs23Sambung;
use App\Models\Simrs\SuratPasien\SuratPasien;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PulangController extends Controller
{
    
    public function getmastercarakeluar()
    {
        $data = DB::table('rs26')->select('rs1','rs2')->where('flag', '1')->get();
        return new JsonResponse($data);
    }
    public function simpandata(Request $request)
    {

      $user = Pegawai::find(auth()->user()->pegawai_id);
      $kdpegsimrs = $user->kdpegsimrs;
      //  return $anamnesis;
      $sqlz = DB::table('cppts')->select('*')->where('noreg', $request->noreg)->get();
      // $sqla = DB::table('rs242')->select('*')->where('rs1', $request->noreg)->get();
      $inStatus = ['2','3'];
      $sql_cek_kunjungan = DB::table('rs23')->select('*')->where('rs1', $request->noreg)->whereIn('rs22', $inStatus)->get();
      
      // $rencana = count($sqla);
      $cppt = count($sqlz);
      $kunjungan = count($sql_cek_kunjungan);


      // jika pasien sduah dipulangkan
      if ($kunjungan > 0) {
        $kunjunganRanapx = Kunjunganranap::where('rs1', $request->noreg)->first();
        $updateKunjunganRanap = $kunjunganRanapx->update([
          // 'rs22' => '3',
          // 'rs4' => date('Y-m-d H:i:s'),
          'rs23' => $request->caraKeluar ?? '',
          'rs24' => $request->prognosis ?? '',
          'rs26' => $request->diagnosaAkhir ?? '',
          'rs25' => $request->diagnosaPenyebabMeninggal ?? '',
          'rs27' => $request->tindakLanjut ?? ''
        ]);

        Rs23Sambung::updateOrCreate(
          ['noreg' => $request->noreg],
          [
            'ket' => $request->tindakLanjut
          ]
          );



        if ($request->noLp !== null) {
            SuratPasien::updateOrCreate(
              ['noreg' => $request->noreg],
              [
                // 'nosuratmeninggal' => $request->noSuratMeninggal,
                'nokll' => $request->noLp,
                'kddrygmenyatakan' => $request->kddrygmenyatakan
              ]
            );
        }

        return new JsonResponse([
          'success' => true,
          'message' => 'success',
          'result' => $sql_cek_kunjungan
        ]);
      }


      // jika pasien belum dipulangkan

      // if($rencana === 0 && $cppt === 0){
      //   return new JsonResponse([
      //     'message' => 'Maaf, Rencana Tindak Lanjut Pasien dan Perkembangan Pasien harus di isi.'
      //   ], 500);
      // }

      $meninggal = $request->caraKeluar === 'C003';
      $plgPaksa = $request->caraKeluar === 'C010';
      $sistemByrBpjs = ($request->kodesistembayar === 'BPJS' || $request->kodesistembayar === 'BPJS1' || $request->kodesistembayar === 'BPJS2' || $request->kodesistembayar === 'BPJS3' || $request->kodesistembayar === 'BPJS4' || $request->kodesistembayar==='BPJS5');

      if ($plgPaksa && $sistemByrBpjs) {
        Pasien::where('rs1', $request->norm)->where('rs1', $request->norm)->update([
          'rs53' => '1',
        ]);
      } 

      $noSuratMeninggal = null;

      if($meninggal){
        // $noSuratMeninggal = $request->noSuratMeninggal; // per tgl 1 ubah yg di bawah
        $noSuratMeninggal = self::buatSuratMeninggal();
      }

      if ($kunjungan === 0) {
        $kunjunganRanap = Kunjunganranap::where('rs1', $request->noreg)->first();
        $updateKunjunganRanap = $kunjunganRanap->update([
          'rs22' => '3',
          'rs4' => date('Y-m-d H:i:s'),
          'rs23' => $request->caraKeluar ?? '',
          'rs24' => $request->prognosis ?? '',
          'rs26' => $request->diagnosaAkhir ?? '',
          'rs25' => $request->diagnosaPenyebabMeninggal ?? '',
          'rs27' => $request->tindakLanjut ?? ''
        ]);

        Rs23Sambung::updateOrCreate(
          ['noreg' => $request->noreg],
          [
            'ket' => $request->tindakLanjut
          ]
          );



      }

       if (!$updateKunjunganRanap) {
        return new JsonResponse([
          'success' => false,
          'message' => 'Gagal menyimpan data'
        ]);
       }

       if ($noSuratMeninggal !== null || $request->noLp !== null) {
          SuratPasien::updateOrCreate(
            ['noreg' => $request->noreg],
            [
              'nosrtmeninggal' => $noSuratMeninggal ?? null,
              'jamMeninggal' => $request->jamMeninggal ?? null,
              'nokll' => $request->noLp ?? null,
              'norm' => $request->norm ?? null,
              'kddrygmenyatakan' => $request->kddrygmenyatakan ?? null
            ]
          );
       }

        $titipan = $kunjunganRanap->titipan;
        $kelas=$kunjunganRanap->rs5;
        $kamar=$kunjunganRanap->rs6;
        $nobed=$kunjunganRanap->rs7;

       if($titipan!=""){
          // $sql_groups_titipan=$conn->query("select distinct groups from rs24 where rs1='".$titipan."'");
          $rs_groups_titipan=DB::table('rs24')->select('groups')->distinct()->where('rs1', $titipan)->first();
          // $rs_groups_titipan=$sql_groups_titipan->fetch_object();
          // $conn->query("update rs25 set rs3='A',rs4='V' where rs5='".$titipan."' and rs1='".$kamar."' and rs2='".$nobed."'");
          DB::table('rs25')->where('rs5', $titipan)->where('rs1', $kamar)->where('rs2', $nobed)->update([
            'rs3' => 'A',
            'rs4' => 'V'  
          ]);
          // $conn->query("update rs25 set rs3='A',rs4='V' where rs6='".$rs_groups_titipan->groups."' and rs1='".$kamar."' and rs2='".$nobed."' and rs5='-'");
          DB::table('rs25')->where('rs6', $rs_groups_titipan->groups)->where('rs1', $kamar)->where('rs2', $nobed)->where('rs5', '-')->update([
            'rs3' => 'A',
            'rs4' => 'V'
          ]);
        }else{
          // $sql_groups=$conn->query("select distinct groups from rs24 where rs1='".$kelas."'");
          $rs_groups=DB::table('rs24')->select('groups')->distinct()->where('rs1', $kelas)->first();
          // $rs_groups=$sql_groups->fetch_object();
          // $conn->query("update rs25 set rs3='A',rs4='V' where rs5='".$kelas."' and rs1='".$kamar."' and rs2='".$nobed."'");
          DB::table('rs25')->where('rs5', $kelas)->where('rs1', $kamar)->where('rs2', $nobed)->update([
            'rs3' => 'A',
            'rs4' => 'V'
          ]);
          // $conn->query("update rs25 set rs3='A',rs4='V' where rs6='".$rs_groups->groups."' and rs1='".$kamar."' and rs2='".$nobed."' and rs5='-'");
          DB::table('rs25')->where('rs6', $rs_groups->groups)->where('rs1', $kamar)->where('rs2', $nobed)->where('rs5', '-')->update([
            'rs3' => 'A',
            'rs4' => 'V'
          ]);
        }

        $surat = DB::table('rs23_nosurat')->select('*')->where('noreg', $request->noreg)->get();
        $sambungan = DB::table('rs23_sambung')->select('*')->where('noreg', $request->noreg)->get();
        if ($request->noSep !== null || $request->noSep !== '') {
          self::update_pulang_bpjs_ranap($request, $user, $noSuratMeninggal);
        }


       return new JsonResponse([
        'success' => true,
        'message' => 'success',
        'result' => $sql_cek_kunjungan,
        'surat'=> $surat,
        'sambungan'=> $sambungan,
       ]);
    }


    public function buatSuratMeninggal()
    {
      $oto = 0;
      DB::select('call no_surat_kematian(@nomor)');
      $x = DB::table('rs1')->select('kematian')->get();
      $oto = $x[0]->kematian;
      // return $oto;

      $has = str_pad($oto, 4, '0', STR_PAD_LEFT);


      $bulan = (int) date('m');
      $blnRomawi = self::intToRoman($bulan);

      $no = "472.12/$has/425.102.8/KEM/$blnRomawi/" . date('Y');
        return $no;
    }

    public static function intToRoman($num) {
      $romanNumerals = [
          1000 => 'M',
          900 => 'CM',
          500 => 'D',
          400 => 'CD',
          100 => 'C',
          90 => 'XC',
          50 => 'L',
          40 => 'XL',
          10 => 'X',
          9 => 'IX',
          5 => 'V',
          4 => 'IV',
          1 => 'I'
      ];
  
      $result = '';
      foreach ($romanNumerals as $value => $numeral) {
          while ($num >= $value) {
              $result .= $numeral;
              $num -= $value;
          }
      }
      return $result;
  }

    public static function update_pulang_bpjs_ranap($request, $user, $noSuratMeninggal)
    {
        
        $request->validate([
            'noSep' => 'required',
        ]);

        // return $request->all();
        $status = 5;
        switch ($request->caraKeluar) {
          case 'C001':
              $status = 1;
              break;
          case 'C002':
              $status = 3;
              break;
          case 'C003':
              $status = 4;
              break;
          default:
              $status = 5;
              break;
        }

        // $noSuratMeninggal = $request->noSuratMeninggal;
        
        $data = [
          "request" => [
              "t_sep" => [
                  "noSep" => $request->noSep,
                  "statusPulang" =>$status,
                  "noSuratMeninggal" => $status == 4 ? $noSuratMeninggal ?? "" : "",
                  "tglMeninggal" => $status == 4 ? date('Y-m-d') : "",
                  "tglPulang" => date('Y-m-d'),
                  "noLPManual" => $request->noLp ?? "",
                  "user" => $user->nama ?? "-"
              ],
          ],
      ];

        // return $data;
        $tgltobpjshttpres = DateHelper::getDateTime();
        $updateSep = BridgingbpjsHelper::put_url(
            'vclaim',
            'SEP/2.0/updtglplg',
            $data
        );

        Bpjs_http_respon::create(
            [
                'method' => 'PUT',
                'noreg' => $request->noreg === null ? '' : $request->noreg,
                'request' => $data,
                'respon' => $updateSep,
                'url' => '/SEP/2.0/updtglplg',
                'tgl' => $tgltobpjshttpres
            ]
        );

        // update ke rs227
        $bpjs = $updateSep['metadata']['code'];
        if ($bpjs === 200 || $bpjs === '200') {
            Sepranap::where('rs1', $request->noreg)->update(
                [
                  'rs19' => '2',
                  'users' => auth()->user()->pegawai_id,
                ]
            );
        }
        return $updateSep;
        
    }


   public function hapusdata(Request $request)
   {
       $cari = DischargePlanning::find($request->id);
       if (!$cari) {
         return new JsonResponse(['message' => 'data tidak ditemukan'], 501);
       }
       $cari->delete();
       return new JsonResponse(['message' => 'berhasil dihapus'], 200);
   }


    
}
