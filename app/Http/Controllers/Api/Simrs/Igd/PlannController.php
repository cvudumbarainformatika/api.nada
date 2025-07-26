<?php

namespace App\Http\Controllers\Api\Simrs\Igd;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Planing\Planing_Igd_Lama;
use App\Models\Simrs\Planing\Planing_Igd_Pulang;
use App\Models\Simrs\Planing\Planing_Igd_ranap;
use App\Models\Simrs\Planing\Planing_Igd_Rujukan;
use App\Models\Simrs\Planing\Plann_Igd_Ranap_Ruang;
use App\Models\Simrs\Planing\SkalaTransferIgd;
use App\Models\Simrs\Rajal\KunjunganPoli;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlannController extends Controller
{
    public function simpanranap(Request $request)
    {
    //     $data = Planing_Igd_Lama::with(
    //         [
    //             'planranap' => function($planranap){
    //                 $planranap->with(
    //                     [
    //                         'ruangranap'
    //                     ]
    //                 );
    //             },
    //             'planrujukan',
    //             'planpulang'
    //         ]
    //     )->where('rs1', $request->noreg)->get();
    //     return new JsonResponse(
    //         [
    //             'message' => 'Data Berhasil Disimpan',
    //             'result' => $data
    //         ],
    //     200);
        //return $request->kelas;
        $wew = FormatingHelper::session_user();
        $kdpegsimrs = $wew['kodesimrs'];
        $kdgroupnakes = $wew['kdgroupnakes'];


        if( $kdgroupnakes !== '1')
        {
            return new JsonResponse(['message' => 'Maaf, Menu Ini Harus Di isi Dokter...!!!'],500);
        }

        $cek = Planing_Igd_Lama::where('rs1', $request->noreg)->count();
        if($cek > 0){
            return new JsonResponse(['message' => 'Pasien Ini Sudah Dilakukan Planing'],500);
        }

       if($request->panel === 'Rawat Inap' || $request->panel === 'Rujuk Ke Rumah Sakit Lain'){
            $cari = SkalaTransferIgd::where('noreg',$request->noreg)->count();
                if($cari === 0){
                    return new JsonResponse(['message' => 'Maaf SKala Transfer Harus Diisi Terlebih Dahulu...!!'],500);
                }
       }

       DB::beginTransaction();
       try {

        $simpan = Planing_Igd_Lama::create(
                [
                    'rs1' => $request->noreg,
                    'rs2' => $request->norm,
                    'rs3' => 'POL014',
                    'rs4' => $request->panel,
                    'rs5' => $request->ruangtujuan ?? '',
                    'tgl' => date('Y-m-d H:i:s'),
                    'user' =>  $kdpegsimrs ?? ''
                ]
            );

            if($request->panel === 'Rawat Inap')
            {
                $simpansambung = Planing_Igd_ranap::create(
                    [
                    'noreg' => $request->noreg,
                    'norm' => $request->norm,
                    'id_heder' => $simpan['id'] ?? '',
                    'operasi' => $request->operasi,
                    'jenisoperasi' => $request->jenisoperasi,
                    'tgloperasi' => $request->tgloperasi,
                    'ruangtujuan' => $request->ruangtujuan,
                    'keterangan' => $request->keterangan
                    ]
                );
                $isi = json_encode($request->isi);
                if($request->kelas !== 'null'){
                    $simpansampungranap = Plann_Igd_Ranap_Ruang::create(
                        [
                            'noreg' => $request->noreg,
                            'norm' => $request->norm,
                            'id_heder' => $simpansambung['id'] ?? '',
                            'isi' => $isi,
                            'kelas' => $request->kelas
                        ]
                    );
                }
            }else if($request->panel === 'Rujuk Ke Rumah Sakit Lain')
            {

                $simpansambung = Planing_Igd_Rujukan::create(
                    [
                    'noreg' => $request->noreg,
                    'norm' => $request->norm,
                    'id_heder' => $simpan['id'] ?? '',
                    'atas_dasar' => $request->atasdasar,
                    'jenis_pelayanan' => $request->jenispelayanan,
                    'tgl_rujukan' => $request->tglrujukan,
                    'tgl_rencana_kunjungan' => $request->tglrencanakunjungan,
                    'type_faskes' => $request->typefaskes,
                    'koders' => $request->koders,
                    'di_rujuk_ke' => $request->dirujukkers,
                    'kodepoli' => $request->kodepoli,
                    'poli_rujukan' => $request->polirujukan,
                    'keterangan' => $request->keterangan,
                    ]
                );


            }else if($request->panel === 'Pulang')
            {
                if($request->atasdasarpulang === 'Meninggal')
                {
                    $noSuratMeninggal = null;
                    $noSuratMeninggal = self::buatSuratMeninggal();
                    $simpansambung = Planing_Igd_Pulang::create(
                        [
                        'noreg' => $request->noreg,
                        'norm' => $request->norm,
                        'id_heder' => $simpan['id'] ?? '',
                        'atas_dasar' => $request->atasdasarpulang,
                        'tgl_meninggal' => $request->tglmeninggal,
                        'jam_meninggal' => $request->jammeninggal,
                        'alasan_meninggal' => $request->alasanmeninggal,
                        'user_dokter' => $kdpegsimrs,
                        'nosurat' => $noSuratMeninggal
                        ]
                    );
                }else{
                    $simpansambung = Planing_Igd_Pulang::create(
                        [
                        'noreg' => $request->noreg,
                        'norm' => $request->norm,
                        'id_heder' => $simpan['id'] ?? '',
                        'atas_dasar' => $request->atasdasarpulang,
                        ]
                    );
                }
            }


            DB::commit();
            $data = Planing_Igd_Lama::with(
                [
                    'planranap' => function($planranap){
                        $planranap->with(
                            [
                                'ruangranap',
                                'dokumentransfer'
                            ]
                        );
                    },
                    'planrujukan',
                    'planpulang'
                ]
            )->where('rs1', $request->noreg)->get();

            return new JsonResponse(
                [
                    'message' => 'Data Berhasil Disimpan',
                    'result' => $data
                ],
            200);
        }catch (\Exception $e) {
            DB::rollback();
            return new JsonResponse([
                'message' => 'Data Gagal Disimpan...!!!',
                'result' => 'err' . $e
            ], 410);
        }
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

    public function suratkematian()
    {
        $data = Planing_Igd_Pulang::with(
            [
                'dokterpenangungjawabpulang' => function($x){
                    $x->select('pegawai.kdpegsimrs', 'pegawai.nama',
                    'pegawai.nip','pegawai.nik','pegawai.jabatan','pegawai.golruang',
                    'm_jabatan.jabatan as ket_jabatan','m_golruang.golruang as golongan',
                    'm_golruang.keterangan as ket_golongan'
                    )
                    ->leftJoin('m_jabatan', 'pegawai.jabatan', '=', 'm_jabatan.kode_jabatan')
                    ->leftJoin('m_golruang', 'pegawai.golruang', '=', 'm_golruang.kode_gol')
                    ->where('pegawai.aktif', '=', 'AKTIF')
                    ->first();
                }
            ]
        )->where('noreg', request('noreg'))->get();

        return new JsonResponse(['data' => $data] ,200);
    }

    public function indikasimasuknicuinter()
    {
        $data = Planing_Igd_Lama::with(
            [
                'planranap' => function($plannranap){
                    $plannranap->with([
                        'dokumentransfer'
                    ]);
                }
            ]
        )
        ->where('rs1', request('noreg'))->get();
        return new JsonResponse(['data' => $data] ,200);
    }

    public function hapusplann(Request $request)
    {
        try{
            DB::beginTransaction();
            $id = $request->id;
            $rs141 = Planing_Igd_Lama::find($id);
            $hapusrs141 = $rs141->delete();
            if($request->jenis === 'Pulang')
            {
                $planigdpulang = Planing_Igd_Pulang::where('id_heder', $rs141['id']);
                $hapusigdpulang = $planigdpulang->delete();
            }else if($request->jenis === 'Rawat Inap'){
                $plannranap= Planing_Igd_ranap::where('id_heder', $id)->first();
                $Plann_Igd_Ranap_Ruang = Plann_Igd_Ranap_Ruang::where('id_heder', $plannranap['id']);

                $hapusrsplanranap = $plannranap->delete();
                $hapusrsplanranapx = $Plann_Igd_Ranap_Ruang->delete();

            }


            DB::commit();

            return new JsonResponse([
                'message' => 'BERHASIL DIHAPUS'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return new JsonResponse(['message' => 'ada kesalahan', 'error' => $e], 500);
        }
    }
}
