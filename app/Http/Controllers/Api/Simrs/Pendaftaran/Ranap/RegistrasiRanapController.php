<?php

namespace App\Http\Controllers\Api\Simrs\Pendaftaran\Ranap;

use App\Helpers\BridgingbpjsHelper;
use App\Helpers\FormatingHelper;
use App\Helpers\TarifHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Kasir\Adminranap;
use App\Models\Simrs\Kasir\Pembayaran;
use App\Models\Simrs\Kasir\Rstigalimax;
use App\Models\Simrs\Master\Hutangpasien;
use App\Models\Simrs\Master\Mkamar;
use App\Models\Simrs\Master\MkamarRanap;
use App\Models\Simrs\Master\Mpasien;
use App\Models\Simrs\Pendaftaran\Ranap\Sepranap;
use App\Models\Simrs\Rajal\KunjunganPoli;
use App\Models\Simrs\Ranap\Kunjunganranap;
use App\Models\Simrs\Ranap\Rs141;
use App\Models\Simrs\Ranap\Rs23Meta;
use App\Models\Simrs\Ranap\Rsjr;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class RegistrasiRanapController extends Controller
{
    public function registrasiranap(Request $request)
    {
        if ($request->barulama === 'Baru' || $request->barulama === 'baru' || $request->barulama === 'BARU' || $request->barulama === '') {
            // $data = Mpasien::where('rs1', $request->norm)->first();
            // if ($data) {
            //     return new JsonResponse([
            //         'message' => 'Nomor RM Sudah ada',
            //         'data' => $data
            //     ], 410);
            // }
            // $data2 = Mpasien::where('rs49', $request->nik)->first();
            // if ($data2) {
            //     return new JsonResponse([
            //         'message' => 'NIK Sudah didaftarkan',
            //         'data' => $data
            //     ], 410);
            // }

            $request->validate([
                'norm' => 'required|unique:rs15,rs1|min:6|max:6',
                'noktp' => 'required|unique:rs15,rs49',
            ]);
        }

        // return 'ok';

        $masterpasien = PendaftaranByForm::store($request);
        if (!$masterpasien) {
            return new JsonResponse(['message' => 'DATA MASTER PASIEN GAGAL DISIMPAN/DIUPDATE'], 500);
        }


        return self::regKunjungan($request);
    }


    public function registrasiranapIgd(Request $request)
    {
        $request->validate([
            'norm' => 'required|string',
            'noreg' => 'required|string',
        ]);

        return self::regKunjungan($request);
    }

    public function registrasiranapSpri(Request $request)
    {
        $request->validate([
            'norm' => 'required|string',
        ]);
        return self::regKunjungan($request);
    }

    public static function regKunjungan($request)
    {
        $cekRanap = Kunjunganranap::select('rs1')->where('rs2', '=', $request->norm)->where('rs22', '=', '')->get();
        $cekHutang = Hutangpasien::where('rs8', $request->norm)->where('rs18', '1')->get();
        $cekIgd = KunjunganPoli::select('rs1')->where('rs2', $request->norm)->where('rs19', '')->where('rs8', 'POL014')->get();

        if (count($cekRanap) > 0) {
            return new JsonResponse(['message' => 'Maaf, pasien tersebut sudah rawat inap'], 500);
        }
        if (count($cekHutang) > 0) {
            return new JsonResponse(['message' => 'Maaf, Pasien Ini Masih Mempunyai Hutang Di RSUD dr. Mohamad Saleh...!!!'], 500);
        }
        if (count($cekIgd) > 0) {
            return new JsonResponse(['message' => 'Maaf, kondisi akhir di igd belum dientri. segera hubungi admin igd.'], 500);
        }


        //NOREG jk kosong ambil dari counter



        $tglMsk = Carbon::now();
        $tglmasuk = $tglMsk->toDateTimeString();

        //   $ruang = $request->isTitipan === 'Ya' ? $request->kode_ruang ?? '' : $request->hakruang ?? '';
        $ruang =  $request->hakruang ?? '';
        //   $titipan = $request->isTitipan === 'Ya'? $request->hakruang ?? '' : '';
        $titipan = $request->isTitipan === 'Ya' ? $request->kode_ruang ?? '' : '';

        $kamar = $request->kamar ?? '';
        $noBed = $request->no_bed ?? '';



        $tempNoreg = null;


        if ($request->has('noreg')) {
            if ($request->noreg === null || $request->noreg === '') {
                $tempNoreg = self::createNoreg($request, $tglmasuk, $ruang, $kamar, $noBed);
            } else {
                Kunjunganranap::updateOrCreate(
                    ['rs1' => $request->noreg],
                    [
                        'rs2' => $request->norm,
                        'rs3' => $tglmasuk,
                        'rs13' => $request->asalrujukan ?? '',
                        'rs5' => $ruang,
                        'rs6' => $kamar,
                        'rs7' => $noBed,
                        'rs10' => $request->kd_dokter ?? '',
                        'rs19' => $request->kodesistembayar ?? '',
                        'rs11' => $request->penanggungjawab ?? '',
                        'rs30' => auth()->user()->pegawai_id ?? '',
                        'rs31' => '',
                        'rs38' => $request->hakKelasBpjs ?? '', // hak Kelas dari BPJS
                        'rs39' => $request->diagnosaAwal['icd'] ?? '', // ICD
                        'rs40' => $request->diagnosaAwal['keterangan'] ?? '', // BELUM ADA di request
                        'titipan' => $titipan
                    ]
                );

                $tempNoreg = $request->noreg;
            }
        } else { // INI DARI SELAIN IGD (bisa dr poli atau lain-lain SPRI)
            $tempNoreg = self::createNoreg($request, $tglmasuk, $ruang, $kamar, $noBed);
        }

        if ($request->kodesistembayar === 'AR32') {
            Rsjr::updateOrCreate(
                ['rs1' => $tempNoreg],
                [
                    'rs2' => $request->norm,
                    'rs3' => 'JR1',
                    'rs4' => 'Pembuatan Dokumen Asuransi',
                    'rs5' => '45000',
                    'rs6' => auth()->user()->pegawai_id,
                ]
            );
        }

        // return new JsonResponse($tempNoreg, 200);

        // UPDATE rs25
        // if ($request->titpan !== null || $request->titpan !== '') {
        // $ruangan = $titipan === '' ? $request->kode_ruang : $titipan;
        $rs24 = Mkamar::where('rs1', '=', $ruang)->first();
        // return new JsonResponse($rs24, 200);
        if ($rs24) {
            $rs25 = MkamarRanap::where('rs5', '=', $ruang)->where('rs1', '=', $kamar)->where('rs2', '=', $noBed)->first();
            // return new JsonResponse($rs25, 200);
            if ($rs25) {
                $rs25->rs3 = 'S';
                $rs25->rs4 = 'N';
                $rs25->save();
            }
            $rs25NonKelas = MkamarRanap::where('rs6', '=', $rs24->groups)->where('rs1', '=', $kamar)->where('rs2', '=', $noBed)->where('rs5', '-')->first();
            if ($rs25NonKelas) {
                $rs25NonKelas->rs3 = 'S';
                $rs25NonKelas->rs4 = 'N';
                $rs25NonKelas->save();
            }
        }

        // }

        // INSERT TARIF
        $tarif = TarifHelper::ruang($tempNoreg);
        // return new JsonResponse($tarif, 200);
        if (count($tarif) === 0) {
            return new JsonResponse(['message' => 'MAAF ... TARIF TIDAK DITEMUKAN'], 500);
        }



        $kodekamar = $tarif[0]->kodekamar;
        $koderuang = $tarif[0]->koderuang;
        $kelas = $tarif[0]->kelas;
        $sistembayar = $tarif[0]->sistembayar;
        $sarana = $tarif[0]->sarana;
        $pelayanan = $tarif[0]->pelayanan;

        if ($koderuang == "WKUT" || $koderuang == "WKVVIP") {
            $koderuangkelas = $koderuang;
        } elseif ($koderuang == "DA" || $koderuang == "IC" || $koderuang == "ICC" || $koderuang == "WKKB" || $koderuang == "ISHK" || $koderuang == "TR") {
            $koderuangkelas = $kodekamar;
        } else {
            $koderuangkelas = $koderuang . $kelas;
        }



        Rstigalimax::where('rs1', '=', $tempNoreg)
            ->where('rs3', '=', 'K1#')
            ->where('rs6', '=', 'Akomodasi / Kamar')
            ->delete();

        Rstigalimax::insert([
            'rs1' => $tempNoreg,
            'rs3' => 'K1#',
            'rs4' => date("Y-m-d H:i:s"),
            'rs5' => 'D',
            'rs6' => 'Akomodasi / Kamar',
            'rs7' => $sarana,
            'rs8' => $sistembayar,
            'rs14' => $pelayanan,
            'rs16' => $koderuang,
            'rs17' => $kelas,
            'rs18' => $ruang
        ]);


        // INSERT ADMINISTRASI

        $admin = TarifHelper::admin($tempNoreg);
        if (count($tarif) === 0) {
            return new JsonResponse(['message' => 'MAAF ... TARIF ADMINISTRASI TIDAK DITEMUKAN'], 500);
        }

        Adminranap::updateOrCreate(
            ['noreg' => $tempNoreg],
            [
                'norm' => $request->norm,
                'sarana' => $admin[0]->sarana,
                'kd_sistembayar' => $admin[0]->sistembayar,
                'pelayanan' => $admin[0]->pelayanan,
                'kd_ruang' => $admin[0]->koderuang,
                'user_input' => auth()->user()->pegawai_id
            ]
        );

        //INSERT PEMBAYARAN
        // Pembayaran::updateOrCreate(
        //   ['rs1' => $tempNoreg],
        //   [
        //     'rs2' => '',
        //     'rs3' => 'A1#',
        //     'rs4' => date("Y-m-d H:i:s"),
        //     'rs5' => 'D',
        //     // 'rs6' => $namabiaya, // belum ada
        //     // 'rs7' => $biayaadmin1, // belum ada
        //     'rs8' => $sistembayar,
        //     'rs9' => '',
        //     'rs10' => auth()->user()->pegawai_id,
        //     // 'rs11' => $biayaadmin2, // belum ada
        //     'rs12' => auth()->user()->pegawai_id,
        //     'rs13' => '1'
        //   ]
        // );

        if ($request->has('noreglama')) {
            $cekSpri = Rs141::where('rs1', $request->noreglama)->first();
            if ($cekSpri) {
                $cekSpri->flag = $tempNoreg;
                $cekSpri->save();
            }
        }

        $data = [
            'noreg' => $tempNoreg,
            'noreglama' => $request->noreglama ?? null,
            'message' => 'OK',
        ];

        // Insert Ke metaranap
        Rs23Meta::updateOrCreate(
            ['noreg' => $tempNoreg],
            [
                'norm' => $request->norm,
                'kd_jeniskasus' => $request->kategoriKasus,
                'indikator_naik_kelas' => $request->indikatorPerubahanKelas ?? null,
                'notelp_penanggungjawab' => $request->notelp_penanggungjawab ?? null,
                'hub_keluarga' => $request->hub_keluarga ?? null,
                'user_input' => auth()->user()->pegawai_id,

            ]
        );


        return new JsonResponse($data, 200);
    }

    public static function createNoreg($request, $tglmasuk, $ruang, $kamar, $noBed)
    {
        DB::select('call reg_ranap(@nomor)');
        $hcounter = DB::table('rs1')->select('rs12')->get();
        $wew = $hcounter[0]->rs12;
        $noreg = FormatingHelper::gennoreg($wew, 'I');

        // $input = new Request([
        //     'noreg' => $noreg
        // ]);

        // $input->validate([
        //     'noreg' => 'required|unique:rs23,rs1'
        // ]);
        $reg = new Kunjunganranap();
        $reg->rs1 = $noreg;
        $reg->rs2 = $request->norm;
        $reg->rs3 = $tglmasuk;
        $reg->rs13 = $request->asalrujukan ?? '';
        $reg->rs5 = $ruang;
        $reg->rs6 = $kamar;
        $reg->rs7 = $noBed;
        $reg->rs10 = $request->kd_dokter ?? '';
        $reg->rs19 = $request->kodesistembayar ?? '';
        $reg->rs11 = $request->penanggungjawab ?? '';
        $reg->rs30 = auth()->user()->pegawai_id ?? '';
        $reg->rs31 = '';
        $reg->rs38 = $request->hakKelasBpjs ?? '';
        $reg->rs39 = $request->diagnosaAwal['icd'] ?? '';
        $reg->rs40 = $request->diagnosaAwal['keterangan'] ?? '';
        $reg->titipan = $titipan ?? '';
        $reg->save();


        return $noreg;
    }
}
