<?php

namespace App\Http\Controllers\Api\Simrs\Ranap\Pelayanan;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Anamnesis\Anamnesis;
use App\Models\Simrs\Anamnesis\Kebidanan;
use App\Models\Simrs\Anamnesis\KeluhanNyeri;
use App\Models\Simrs\Anamnesis\Neonatal;
use App\Models\Simrs\Anamnesis\Pediatrik;
use App\Models\Simrs\Anamnesis\SkreeningGizi;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnamnesisController extends Controller
{
    public function list()
    {
      
      $data = self::getdata(request('noreg'));
       return new JsonResponse($data);
    }

    public static function getdata($noreg){
      // $akun = auth()->user()->pegawai_id;
      // $nakes = Petugas::select('kdgroupnakes')->find($akun)->kdgroupnakes;

       $data = Anamnesis::select([
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
        'pegawai.nama as petugas',
        'pegawai.kdgroupnakes as nakes',
       ])
       ->leftJoin('kepegx.pegawai as pegawai', 'rs209.user', '=', 'pegawai.kdpegsimrs')
       ->where('rs209.rs1','=', $noreg)
      //  ->where('rs209.kdruang','!=', 'POL014')
      //  ->where('pegawai.aktif', '=', 'AKTIF')
       ->with(['petugas:kdpegsimrs,nik,nama,kdgroupnakes',
       'keluhannyeri',
       'skreeninggizi',
       'neonatal',
       'pediatrik',
       'kebidanan'
       ])

       ->groupBy('rs209.id')
       ->get();

       return $data;
    }
    
    public function simpananamnesis(Request $request)
    {
      $data = self::storeAnamnesis($request);
      return new JsonResponse($data);
    }

    public static function storeAnamnesis($request)
    {
      $user = Pegawai::find(auth()->user()->pegawai_id);
      $kdpegsimrs = $user->kdpegsimrs;

      DB::beginTransaction();
      try {
        if ($request->id !== null) {
            $hasil = Anamnesis::where('id', $request->id)->update(
                [
                  'rs1' => $request->noreg,
                  'rs2' => $request->norm,
                  'rs3' => date('Y-m-d H:i:s'),
                  'rs4' => $request->form['keluhanUtama'] ?? '',
                  'riwayatpenyakit' => $request->form['rwPenyDhl'] ?? '',
                  'riwayatalergi' => $request->form['rwAlergi'] ?? '', // array
                  'keteranganalergi' => $request->form['ketRwAlergi'] ?? '',
                  'riwayatpengobatan' => $request->form['rwPengobatan'] ?? '',
                  'riwayatpenyakitsekarang' => $request->form['rwPenySkr'] ?? '',
                  'riwayatpenyakitkeluarga' => $request->form['rwPenyKlrg'] ?? '',
                  'riwayat_pekerjaan_yang_berhubungan_dengan_zat_berbahaya' => $request->form['rwPkrjDgZatBahaya'] ?? '',
                  // 'skreeninggizi' => $request->skreeninggizi ?? 0,
                  // 'asupanmakan' => $request->asupanmakan ?? 0,
                  // 'kondisikhusus' => $request->kondisikhusus ?? '',
                  // 'skor' => $request->skor ?? 0,
                  // 'scorenyeri' => $request->skorNyeri ?? 0,
                  // 'keteranganscorenyeri' => $request->keluhanNyeri ?? '',
                  'kdruang'=> $request->kdruang,
                  'awal'=> $request->awal ?? null,
                  'user'  => $kdpegsimrs,
                ]
            );
            if ($hasil === 1) { 
                $simpananamnesis = Anamnesis::where('id', $request->id)->first();
            } else {
                $simpananamnesis = null;
            }
        } else {
          $simpananamnesis = Anamnesis::create(
            [
                'rs1' => $request->noreg,
                'rs2' => $request->norm,
                'rs3' => date('Y-m-d H:i:s'),
                'rs4' => $request->form['keluhanUtama'] ?? '',
                'riwayatpenyakit' => $request->form['rwPenyDhl'] ?? '',
                'riwayatalergi' => $request->form['rwAlergi'] ?? '', // array
                'keteranganalergi' => $request->form['ketRwAlergi'] ?? '',
                'riwayatpengobatan' => $request->form['rwPengobatan'] ?? '',
                'riwayatpenyakitsekarang' => $request->form['rwPenySkr'] ?? '',
                'riwayatpenyakitkeluarga' => $request->form['rwPenyKlrg'] ?? '',
                'riwayat_pekerjaan_yang_berhubungan_dengan_zat_berbahaya' => $request->form['rwPkrjDgZatBahaya'] ?? '',
                'kdruang'=> $request->kdruang,
                'awal'=> $request->awal ?? null,
                'user'  => $kdpegsimrs,
            ]
          );
        }

        // save kebidanan
        if ($request->formKebidanan !==null) {
          Kebidanan::updateOrCreate(
            ['rs209_id'=> $simpananamnesis->id],
            [
              'noreg'=> $request->noreg,
              'norm'=> $request->norm,
              "rwObsetri" => $request->formKebidanan['rwObsetri'],
              "rwRawat" => $request->formKebidanan['rwRawat'],
              "rwOperasi" => $request->formKebidanan['rwOperasi'],
              "rwGynecology" => $request->formKebidanan['rwGynecology'],
              "rwGynecologyLain" => $request->formKebidanan['rwGynecologyLain'],
              "rwKbJns" => $request->formKebidanan['rwKbJns'],
              "rwKbLama" => $request->formKebidanan['rwKbLama'],
              "rwKbKeluhan" => $request->formKebidanan['rwKbKeluhan'],
              "menarche" => $request->formKebidanan['menarche'],
              "siklusHari" => $request->formKebidanan['siklusHari'],
              "siklus" => $request->formKebidanan['siklus'],
              "lamaMens" => $request->formKebidanan['lamaMens'],
              "kondisiMens" => $request->formKebidanan['kondisiMens'],
              "hpht" => $request->formKebidanan['hpht'],
              "tglPerkPersalinan" => $request->formKebidanan['tglPerkPersalinan'],
              "rwKawinStatus" => $request->formKebidanan['rwKawinStatus'],
              "kawinKe" => $request->formKebidanan['kawinKe'],
              "nikahUmur" => $request->formKebidanan['nikahUmur'],
              "g" => $request->formKebidanan['g'],
              "p" => $request->formKebidanan['p'],
              "ab" => $request->formKebidanan['ab'],
              "ah" => $request->formKebidanan['ah'],
              "anc" => $request->formKebidanan['anc'],
              "imunisasi" => $request->formKebidanan['imunisasi'],
              "bab" => $request->formKebidanan['bab'],
              "konsistensi" => $request->formKebidanan['konsistensi'],
              "warna" => $request->formKebidanan['warna'],
              "keluhans" => $request->formKebidanan['keluhans'],
              "peristatikUsus" => $request->formKebidanan['peristatikUsus'],
              "flatus" => $request->formKebidanan['flatus'],
              "bak" => $request->formKebidanan['bak'],
              "keluhanBak" => $request->formKebidanan['keluhanBak'],
              "jmlBak" => $request->formKebidanan['jmlBak'],
              "warnaUrine" => $request->formKebidanan['warnaUrine'],
              "kateter" => $request->formKebidanan['kateter'],
              "kttHrKe" => $request->formKebidanan['kttHrKe'],
              'user_input'=> $kdpegsimrs,
              'group_nakes' => $user->kdgroupnakes
    
            ]
          );
        } else {
          Kebidanan::where('rs209_id', $simpananamnesis->id)->delete();
        }

        // save neonatal
        if ($request->formNeoNatal !==null) {
          Neonatal::updateOrCreate(
            ['rs209_id'=> $simpananamnesis->id],
            [
              'noreg'=> $request->noreg,
              'norm'=> $request->norm,
              "crMasuk" => $request->formNeoNatal['crMasuk'],
              "asalMasuk" => $request->formNeoNatal['asalMasuk'],
              "penanggungJawab" => $request->formNeoNatal['penanggungJawab'],
              "noHpPj" => $request->formNeoNatal['noHpPj'],
              "alamatPj" => $request->formNeoNatal['alamatPj'],
              "hubPj" => $request->formNeoNatal['hubPj'],
              "rwOpname" => $request->formNeoNatal['rwOpname'],
              "g" => $request->formNeoNatal['g'],
              "p" => $request->formNeoNatal['p'],
              "a" => $request->formNeoNatal['a'],
              "usiaGestasi" => $request->formNeoNatal['usiaGestasi'],
              "sgIbu" => $request->formNeoNatal['sgIbu'],
              "rwObat" => $request->formNeoNatal['rwObat'],
              "kebiasaanIbu" => $request->formNeoNatal['kebiasaanIbu'],
              "kebiasaanLain" =>  $request->formNeoNatal['kebiasaanLain'],
              "rwPersalinan" => $request->formNeoNatal['rwPersalinan'],
              "ketuban" => $request->formNeoNatal['ketuban'],
              "volume" => $request->formNeoNatal['volume'],
              "rwTransDarah" => $request->formNeoNatal['rwTransDarah'],
              "reaksiTrans" => $request->formNeoNatal['reaksiTrans'],
              "rwImunisasi" => $request->formNeoNatal['rwImunisasi'],
              "crLahir" =>  $request->formNeoNatal['crLahir'],
              "apgarScore" => $request->formNeoNatal['apgarScore'],
              "volumeKetuban" => $request->formNeoNatal['volumeKetuban'],
              "warnaKetuban" => $request->formNeoNatal['warnaKetuban'],
              "pecahDini" => $request->formNeoNatal['pecahDini'],
              "golDarahIbu" => $request->formNeoNatal['golDarahIbu'],
              "golDarahAyah" => $request->formNeoNatal['golDarahAyah'],
              "golDarahBayi" => $request->formNeoNatal['golDarahBayi'],
              "rhDarahBayi" => $request->formNeoNatal['rhDarahBayi'],
              "rhDarahIbu" => $request->formNeoNatal['rhDarahIbu'],
              "rhDarahAyah" => $request->formNeoNatal['rhDarahAyah'],
              'user_input'=> $kdpegsimrs,
              'group_nakes' => $user->kdgroupnakes
    
            ]
          );
        } else {
          Neonatal::where('rs209_id', $simpananamnesis->id)->delete();
        }

        // save pediatri
        if ($request->formPediatrik !==null) {
          Pediatrik::updateOrCreate(
            ['rs209_id'=> $simpananamnesis->id],
            [
              'noreg'=> $request->noreg,
              'norm'=> $request->norm,
              "anakKe" => $request->formPediatrik['anakKe'],
              "jmlSaudara" => $request->formPediatrik['jmlSaudara'],
              "crKelahiran" => $request->formPediatrik['crKelahiran'],
              "umurKelahiran" => $request->formPediatrik['umurKelahiran'],
              "klainanBawaan" => $request->formPediatrik['klainanBawaan'],
              "rwImunisasi" => $request->formPediatrik['rwImunisasi'],
              "gigiPertama" => $request->formPediatrik['gigiPertama'],
              "berjalan" => $request->formPediatrik['berjalan'],
              "membaca" => $request->formPediatrik['membaca'],
              "duduk" => $request->formPediatrik['duduk'],
              "bicara" => $request->formPediatrik['bicara'],
              "sukaMknan" => $request->formPediatrik['sukaMknan'],
              "tdkSukaMknan" => $request->formPediatrik['tdkSukaMknan'],
              "nafsuMkn" => $request->formPediatrik['nafsuMkn'],
              "polaMakan" => $request->formPediatrik['polaMakan'],
              "mknYgdiberikan" => $request->formPediatrik['mknYgdiberikan'],
              "tidurSiang" => $request->formPediatrik['tidurSiang'],
              "tidurMalam" => $request->formPediatrik['tidurMalam'],
              "kebiasaanSblmMkn" => $request->formPediatrik['kebiasaanSblmMkn'],
              "nyeri" => $request->formPediatrik['nyeri'],
              "mandiSendiri" => $request->formPediatrik['mandiSendiri'],
              "dimandikan" => $request->formPediatrik['dimandikan'],
              "gosokGigi" => $request->formPediatrik['gosokGigi'],
              "keramas" => $request->formPediatrik['keramas'],
              "kbersihanKuku" => $request->formPediatrik['kbersihanKuku'],
              "aktifitas" => $request->formPediatrik['aktifitas'],
              "babFrekuensi" => $request->formPediatrik['babFrekuensi'],
              "babKonsistensi" => $request->formPediatrik['babKonsistensi'],
              "babWarna" => $request->formPediatrik['babWarna'],
              "babBau" => $request->formPediatrik['babBau'],
              "bakFrekuensi" => $request->formPediatrik['bakFrekuensi'],
              "bakWarna" => $request->formPediatrik['bakWarna'],
              "bakBau" => $request->formPediatrik['bakBau'],
              "meconium" => $request->formPediatrik['meconium'],
              'user_input'=> $kdpegsimrs,
              'group_nakes' => $user->kdgroupnakes
            ]
          );
        }else {
          Pediatrik::where('rs209_id', $simpananamnesis->id)->delete();
        }
      


        // save nyeri
        $skorNyeri = 0;
        $ketNyeri = null;
        if ($request->formKebidanan ===null && $request->formNeoNatal=== null && $request->formPediatrik=== null) {
          $skorNyeri = $request->form['keluhannyeri']['skorNyeri'] ?? 0;
          $ketNyeri = $request->form['keluhannyeri']['ket'] ?? null;
          
        }
        else if ($request->formKebidanan !==null) {
          $skorNyeri = $request->formKebidanan['keluhannyeri']['skorNyeri'] ?? 0;
          $ketNyeri = $request->formKebidanan['keluhannyeri']['ket'] ?? null;
        }
        else if ($request->formNeoNatal !==null) {
          $skorNyeri = $request->formNeoNatal['keluhannyeri']['skorNyeri'] ?? 0;
          $ketNyeri = $request->formNeoNatal['keluhannyeri']['ket'] ?? null;
        }
        else if ($request->formPediatrik !==null) {
          $skorNyeri = $request->formPediatrik['keluhannyeri']['skorNyeri'] ?? 0;
          $ketNyeri = $request->formPediatrik['keluhannyeri']['ket'] ?? null;
        }


        KeluhanNyeri::updateOrCreate(
          ['rs209_id'=> $simpananamnesis->id],
          [
            'noreg'=> $request->noreg,
            'norm'=> $request->norm,
            'dewasa'=> $request->form['keluhannyeri'] ?? null, // array
            'kebidanan'=> $request->formKebidanan['keluhannyeri'] ?? null, // array
            'neonatal'=> $request->formNeoNatal['keluhannyeri'] ?? null, // array
            'pediatrik'=> $request->formPediatrik['keluhannyeri'] ?? null, // array
            'skor'=> $skorNyeri,
            'keluhan'=> $ketNyeri,
            'user_input'=> $kdpegsimrs,
            'group_nakes' => $user->kdgroupnakes
  
          ]
        );

        // save gizi
        $skor=0;
        $ket=null;
        if ($request->formKebidanan ===null && $request->formNeoNatal=== null && $request->formPediatrik=== null) {
          $skor = $request->form['skreeninggizi']['skor'] ?? 0;
          $ket = $request->form['skreeninggizi']['ket'] ?? null;
        }
        else if ($request->formKebidanan !==null) {
          $skor= $request->formKebidanan['skreeninggizi']['skor'] ?? 0;
          $ket= $request->formKebidanan['skreeninggizi']['ket'] ?? null;
        }
        else if ($request->formNeoNatal !==null) {
          $skor= $request->formNeoNatal['skreeninggizi']['skor'] ?? 0;
          $ket= $request->formNeoNatal['skreeninggizi']['ket'] ?? null;
        }
        else if ($request->formNeoNatal !==null) {
          $skor= $request->formPediatrik['skreeninggizi']['skor'] ?? 0;
          $ket= $request->formPediatrik['skreeninggizi']['ket'] ?? null;
        }

        SkreeningGizi::updateOrCreate(
          ['rs209_id'=> $simpananamnesis->id],
          [
            'noreg'=> $request->noreg,
            'norm'=> $request->norm,
            'dewasa'=> $request->form['skreeninggizi'] ?? null, // array
            'kebidanan'=> $request->formKebidanan['skreeninggizi'] ?? null, // array
            'neonatal'=> $request->formNeoNatal['skreeninggizi'] ?? null, // array
            'pediatrik'=> $request->formPediatrik['skreeninggizi'] ?? null, // array
            'skor'=> $skor,
            'keterangan'=> $ket,
            'user_input'=> $kdpegsimrs,
            'group_nakes' => $user->kdgroupnakes
          ]
        );




        DB::commit();
        // return new JsonResponse([
        //     'message' => 'BERHASIL DISIMPAN',
        //     'result' => self::getdata($request->noreg),
        // ], 200);

        $data = [
          'success' => true,
          'message' => 'BERHASIL DISIMPAN',
          'idAnamnesis' => $simpananamnesis->id,
          'result' => self::getdata($request->noreg),
        ];

        return $data;
      } catch (\Exception $th) {
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

    public function hapusanamnesis(Request $request)
    {
        $cari = Anamnesis::find($request->id);
        if (!$cari) {
            return new JsonResponse(['message' => 'MAAF DATA TIDAK DITEMUKAN'], 500);
        }
        $hapus = $cari->delete();
        if (!$hapus) {
            return new JsonResponse(['message' => 'gagal dihapus'], 501);
        }
        return new JsonResponse(['message' => 'berhasil dihapus'], 200);
        // return new JsonResponse($cari, 200);
    }

    public function historyanamnesis()
    {
        $raw = [];
        $history = Anamnesis::select(
            'id',
            'rs2 as norm',
            'rs3 as tgl',
            'rs4 as keluhanutama',
            'riwayatpenyakit',
            'riwayatalergi',
            'keteranganalergi',
            'riwayatpengobatan',
            'riwayatpenyakitsekarang',
            'riwayatpenyakitkeluarga',
            'skreeninggizi',
            'asupanmakan',
            'kondisikhusus',
            'skor',
            'scorenyeri',
            'keteranganscorenyeri',
            'user',
        )
            ->where('rs2', request('norm'))
            ->where('rs3', '<', Carbon::now()->toDateString())
            ->with('datasimpeg:id,nip,nik,nama,kelamin,foto,kdpegsimrs')
            ->orderBy('tgl', 'DESC')
            ->get()
            ->chunk(10);

        $collapsed = $history->collapse();


        return new JsonResponse($collapsed->all());
    }


    
}
