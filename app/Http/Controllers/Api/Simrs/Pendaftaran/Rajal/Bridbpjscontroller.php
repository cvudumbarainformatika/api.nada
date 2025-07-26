<?php

namespace App\Http\Controllers\Api\Simrs\Pendaftaran\Rajal;

use App\Helpers\BridgingbpjsHelper;
use App\Helpers\DateHelper;
use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mpasien;
use App\Models\Simrs\Pendaftaran\Rajalumum\Bpjs_http_respon;
use App\Models\Simrs\Pendaftaran\Rajalumum\PengajuanSep;
use App\Models\Simrs\Pendaftaran\Rajalumum\Seprajal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class Bridbpjscontroller extends Controller
{
    public function createsep(Request $request)
    {
        // return new JsonResponse($request->all());
        $tglsep = DateHelper::getDate();
        $assesmentPel = $request->assesmentPel === '' || $request->assesmentPel === null ? '' : $request->assesmentPel;
        $flagprocedure = $request->flagprocedure === '' || $request->flagprocedure === null ? '' : $request->flagprocedure;
        $kdPenunjang = $request->kdPenunjang === '' || $request->kdPenunjang === null ? '' : $request->kdPenunjang;
        $catatan = $request->catatan === null ? '' : $request->catatan;
        $tglKecelakaan = $request->tglKecelakaan === null ? '' : $request->tglKecelakaan;
        $keterangan = $request->keterangan === null ? '' : $request->keterangan;
        $nosepsuplesi = $request->nosepsuplesi === null ? '' : $request->nosepsuplesi;
        $kodepropinsikecelakaan = $request->kodepropinsikecelakaan === null ? '' : $request->kodepropinsikecelakaan;
        $kodekabupatenkecelakaan = $request->kodekabupatenkecelakaan === null ? '' : $request->kodekabupatenkecelakaan;
        $kodekecamatankecelakaan = $request->kodekecamatankecelakaan === null ? '' : $request->kodekecamatankecelakaan;
        $nosuratkontrol = $request->nosuratkontrol === null ? '' : $request->nosuratkontrol;

        $data = [
            "request" => [
                "t_sep" => [
                    "noKartu" => $request->noka,
                    // "tglSep" => $tglsep,
                    "tglSep" => $request->tglsep,
                    // "ppkPelayanan" => $request->ppkpelayanan, //'1327R001'
                    "ppkPelayanan" => '1327R001',
                    "jnsPelayanan" => $request->jnspelayanan,
                    "klsRawat" => [
                        "klsRawatHak" => $request->hakkelas,
                        "klsRawatNaik" => '',
                        "pembiayaan" => '',
                        "penanggungJawab" => '',
                    ],
                    "noMR" => $request->norm,
                    "rujukan" => [
                        "asalRujukan" => $request->asalRujukan,
                        // "asalRujukan" => '2',
                        "tglRujukan" => $request->tglrujukan,
                        // "tglRujukan" => "2023-05-17",
                        "noRujukan" => $request->norujukan,
                        "ppkRujukan" => $request->ppkRujukan,
                        // "ppkRujukan" => "0213R002",
                    ],
                    "catatan" => $catatan,
                    "diagAwal" => $request->kodediagnosa,
                    "poli" => [
                        "tujuan" => $request->kodepolibpjs,
                        "eksekutif" => '0'
                    ],
                    "cob" => [
                        "cob" => '0'
                    ],
                    "katarak" => [
                        "katarak" => $request->katarak
                    ],
                    "jaminan" => [
                        "lakaLantas" => $request->lakalantas,
                        "noLP" => "",
                        "penjamin" => [
                            "tglKejadian" => $tglKecelakaan,
                            "keterangan" => $keterangan,
                            "suplesi" => [
                                "suplesi" => $request->suplesi,
                                "noSepSuplesi" => $nosepsuplesi,
                                "lokasiLaka" => [
                                    "kdPropinsi" => $kodepropinsikecelakaan,
                                    "kdKabupaten" => $kodekabupatenkecelakaan,
                                    "kdKecamatan" => $kodekecamatankecelakaan
                                ]
                            ]
                        ]
                    ],
                    /* kontrol
                    "tujuanKunj" => '1',
                    "flagProcedure" => '0', default // * harus ada
                    "kdPenunjang" => '10', default // * harus ada
                    "assesmentPel" => '',
                    */

                    "tujuanKunj" => $request->tujuankunjungan,
                    // "tujuanKunj" => '1',
                    "flagProcedure" => $flagprocedure,
                    // "flagProcedure" => '0',
                    "kdPenunjang" => $kdPenunjang,
                    // "kdPenunjang" => '',
                    "assesmentPel" => $assesmentPel,
                    // "assesmentPel" => '',
                    "skdp" => [
                        "noSurat" => $nosuratkontrol,
                        "kodeDPJP" => $request->dpjp
                    ],
                    // "dpjpLayan" => '17432', // dokter dpjp (rencana kontrol kodeDokter)
                    "dpjpLayan" => $request->dpjp, // dokter dpjp (rencana kontrol kodeDokter)
                    "noTelp" => $request->noteleponhp,
                    // "noTelp" => '085219608688',
                    "user" => auth()->user()->pegawai_id
                ]
            ]
        ];

        // return new JsonResponse($data);

        $tgltobpjshttpres = DateHelper::getDateTime();
        $createsep = BridgingbpjsHelper::post_url(
            'vclaim',
            'SEP/2.0/insert',
            $data
        );

        Bpjs_http_respon::create(
            [
                'method' => 'POST',
                'noreg' => $request->noreg === null ? '' : $request->noreg,
                'request' => $data,
                'respon' => $createsep,
                'url' => '/SEP/2.0/insert',
                'tgl' => $tgltobpjshttpres
            ]
        );

        $xxx = $createsep['metadata']['code'];
        if ($xxx === 200 || $xxx === '200') {
            // $wew = $createsep['response']['sep'];
            $wew = $createsep['response']->sep;
            $poliBpjs = $wew->poli;
            $nosep = $wew->noSep;
            $dinsos = $wew->informasi->dinsos;
            $prolanisPRB = $wew->informasi->prolanisPRB;
            $noSKTM = $wew->informasi->noSKTM;
            $nosep = $wew->noSep;
            $insertsep = Seprajal::firstOrCreate(
                ['rs1' => $request->noreg],
                [
                    'rs2' => $request->norm,
                    'rs3' => $poliBpjs,
                    'rs4' => $request->kodesistembayar,
                    'rs5' => $request->norujukan,
                    'rs6' => $request->tglrujukan,
                    'rs7' => $request->namadiagnosa,
                    'rs8' => $nosep,
                    'rs9' => $catatan,
                    'rs10' => $request->namappkRujukan,
                    'rs11' => $request->jenispeserta,
                    'rs12' => $request->tglkunjungan !== null ? $request->tglkunjungan : ($request->tglmasuk !== null ? $request->tglmasuk : date('Y-m-d H:i:s')),
                    'rs13' => $request->noka,
                    'rs14' => $request->nama,
                    'rs15' => $request->tgllahir,
                    'rs16' => $request->kelamin,
                    'rs17' => $request->jnspelayanan === '2' ? 'Rawat Jalan' : 'Rawat Inap',
                    'rs18' => $request->kelas,
                    'laka' => $request->lakalantas,
                    'lokasilaka' => $request->lakalantas,
                    'penjaminlaka' => '',
                    'users' => auth()->user()->pegawai_id,
                    'notelepon' => $request->noteleponhp,
                    'tgl_entery' => $tgltobpjshttpres,
                    'noDpjp' => $request->noDpjp ? $request->noDpjp : '',
                    'tgl_kejadian_laka' => $request->tglKecelakaan,
                    'keterangan' => $keterangan,
                    'suplesi' => $request->suplesi,
                    'nosuplesi' => $nosepsuplesi,
                    'kdpropinsi' => $request->kodepropinsikecelakaan,
                    'propinsi' => $request->propinsikecelakaan,
                    'kdkabupaten' => $request->kodekabupatenkecelakaan,
                    'kabupaten' => $request->kabupatenkecelakaan,
                    'kdkecamatan' => $request->kodekecamatankecelakaan,
                    'kecamatan' => $request->kecamatankecelakaan,
                    'kodedokterdpjp' => $request->dpjp,
                    'dokterdpjp' => $request->namadokter,
                    'kodeasalperujuk' => $request->ppkRujukan,
                    'namaasalperujuk' => $request->namappkRujukan,
                    'Dinsos' => $dinsos,
                    'prolanisPRB' => $prolanisPRB,
                    'noSKTM' => $noSKTM,
                    'jeniskunjungan' => $request->jenis_kunjungan,
                    'tujuanKunj' => $request->tujuankunjungan,
                    'flagProcedure' => $flagprocedure,
                    'kdPenunjang' => $kdPenunjang,
                    'assesmentPel' => $assesmentPel,
                    'kdUnit' => $request->kdUnit
                ]
            );
        }


        return $createsep;
    }

    public static function hapussep(Request $request)
    {
        $user = FormatingHelper::session_user();
        $data = [
            "request" => [
                "t_sep" => [
                    // "noSep" => "1327R0010723V006829",
                    // "noSep" => "1327R0010723V006801",
                    "noSep" => $request->noSep,
                    "user" => $user['kodesimrs']
                ]
            ]
        ];
        $hapussep = BridgingbpjsHelper::delete_url(
            'vclaim',
            '/SEP/2.0/delete',
            $data
        );
        return $hapussep;
    }

    public function rencanakontrol()
    {
        $data = [
            "request" => [
                "noSEP" => "1327R0010523V004291",
                "kodeDokter" => "17432",
                "poliKontrol" => "BED",
                "tglRencanaKontrol" => DateHelper::getDate(),
                "user" => "sasa"
            ]
        ];
        $kontrol = BridgingbpjsHelper::post_url('vclaim', '/RencanaKontrol/insert', $data);
        return $kontrol;
    }
    public function cekSuratKontrol()
    {
        $suratKontrol = '1327R0010823K000371';
        $kontrol = BridgingbpjsHelper::get_url('vclaim', '/RencanaKontrol/noSuratKontrol/' . $suratKontrol);
        return $kontrol;
    }

    public function createSPRI() {}

    public function cariseppeserta()
    {
        $sep = '1327R0010523V004291';
        $a = BridgingbpjsHelper::get_url('vclaim', 'SEP/' . $sep);
        return $a;
    }

    public function cari_rujukan()
    {
        $rujukan = '0213R0020523B000114';
        $rujukanPcare = BridgingbpjsHelper::get_url('vclaim', 'Rujukan/' . $rujukan);
        return $rujukanPcare;
    }

    public function cari_rujukan_rs()
    {
        $rujukan = '0123R0020523B000114';
        $rujukanRs = BridgingbpjsHelper::get_url('vclaim', 'Rujukan/RS/0123R0020523B000114');
        return $rujukanRs;
    }
    public function ref_dokter()
    {
        // $rujukan = '0213R0020523B000114';
        $rujukanRs = BridgingbpjsHelper::get_url('antrean', 'ref/dokter');
        return $rujukanRs;
    }
    public function ref_jadwal_dokter_by_politgl()
    {
        $hrIni = DateHelper::getDate();
        $kdPoli = 'BED';

        $param = "$kdPoli/tanggal/$hrIni";
        // return $param;
        $rujukanRs = BridgingbpjsHelper::get_url('antrean', 'jadwaldokter/kodepoli/' . $param);
        return $rujukanRs;
    }

    public function pengajuansep(Request $request)
    {
        $data = [
            "request" => [
                "t_sep" => [
                    "noKartu" => $request->noka,
                    "tglSep" => DateHelper::getDate(),
                    "jnsPelayanan" => "2",
                    "jnsPengajuan" => $request->jenispengajuan,
                    "keterangan" => $request->keterangan,
                    "user" => auth()->user()->pegawai_id
                ]
            ]
        ];
        $kontrol = BridgingbpjsHelper::post_url('vclaim', '/Sep/pengajuanSEP', $data);
        $xxx = $kontrol['metadata']['code'];
        if ($xxx === 200 || $xxx === '200') {
            $simpanpengajuansep = PengajuanSep::firstOrCreate(
                ['rs1' => $request->noreg],
                [
                    'rs2' => $request->norm,
                    'rs3' => $request->noka,
                    'rs4' => $request->keterangan,
                    'rs5' => $request->tglsep,
                    'rs6' => DateHelper::getDateTime(),
                    'rs7' => auth()->user()->pegawai_id,
                    'rs9' => 2,
                    'jnsPengajuan' => $request->jnspengajuan
                ]
            );
            if (!$simpanpengajuansep) {
                return new JsonResponse(['message' => 'data gagal disimpan ke server SIMRS'], 500);
            }
            return new JsonResponse(['message' => 'OK'], 200);
        }
        return $kontrol;
    }

    public function reCreateSep(Request $request)
    {
        // cek

        $tgltobpjshttpres = DateHelper::getDateTime();
        $tgl = $request->tgl_kunjungan ?? date('Y-m-d');

        $date = date_create($tgl);
        $tglCari = date_format($date, 'Y-m-d');

        $history = BridgingbpjsHelper::get_url('vclaim', 'monitoring/HistoriPelayanan/NoKartu/' . $request->noka . '/tglMulai/' . $tglCari . '/tglAkhir/' . $tglCari);
        $type = gettype($history);
        if ($type === 'object') {
            $ori = $history->original;
            $msg = $history->original['message'];
            return new JsonResponse([
                'his' => $history,
                'ori' => $ori,
                'message' => $msg
            ], 410);
        }
        $sep = $history['metadata']['code'] === '200' ? $history['result']->histori[0]->noSep : null;
        $unit = $history['metadata']['code'] === '200' ? $history['result']->histori[0]->poliTujSep : '';
        $infoHis = $history['metadata']['code'] === '200' ? $history['result']->histori[0] : '';
        // return new JsonResponse([
        //     'message' => $history['result']->histori[0],
        //     'sep' => $sep
        // ]);
        // ambil master pasien

        // jika tidak ada history
        if (!$sep) {
            // cek tanggal
            $sepsimrs = Seprajal::where('rs1', $request->rs4)->first();
            if (isset($sepsimrs)) {
                $sepraj = Seprajal::firstOrCreate(
                    ['rs1' => $request->noreg],
                    [
                        'rs2' => $sepsimrs->rs2,
                        'rs3' => $sepsimrs->rs3,
                        'rs4' => $sepsimrs->rs4,
                        'rs5' => $sepsimrs->rs5,
                        'rs6' => $sepsimrs->rs6,
                        'rs7' => $sepsimrs->rs7,
                        'rs8' => $sepsimrs->rs8,
                        'rs9' => $sepsimrs->rs9,
                        'rs10' => $sepsimrs->rs10,
                        'rs11' => $sepsimrs->rs11,
                        'rs12' => $sepsimrs->rs12,
                        'rs13' => $sepsimrs->rs13,
                        'rs14' => $sepsimrs->rs14,
                        'rs15' => $sepsimrs->rs15,
                        'rs16' => $sepsimrs->rs16,
                        'rs17' => $sepsimrs->rs17,
                        'rs18' => $sepsimrs->rs18,
                        'laka' => $sepsimrs->laka,
                        'lokasilaka' => $sepsimrs->lokasilaka,
                        'penjaminlaka' => '',
                        'users' => auth()->user()->pegawai_id ?? 'anu',
                        'notelepon' => $sepsimrs->notelepon,
                        'tgl_entery' => $sepsimrs->tgl_entery,
                        'noDpjp' => $sepsimrs->noDpjp,
                        'tgl_kejadian_laka' => $sepsimrs->tgl_kejadian_laka,
                        'keterangan' => $sepsimrs->keterangan,
                        'suplesi' => $sepsimrs->suplesi,
                        'nosuplesi' => $sepsimrs->nosuplesi,
                        'kdpropinsi' => $sepsimrs->kdpropinsi,
                        'propinsi' => $sepsimrs->propinsi,
                        'kdkabupaten' => $sepsimrs->kdkabupaten,
                        'kabupaten' => $sepsimrs->kabupaten,
                        'kdkecamatan' => $sepsimrs->kdkecamatan,
                        'kecamatan' => $sepsimrs->kecamatan,
                        'kodedokterdpjp' => $sepsimrs->kodedokterdpjp,
                        'dokterdpjp' => $sepsimrs->dokterdpjp,
                        'kodeasalperujuk' => $sepsimrs->kodeasalperujuk,
                        'namaasalperujuk' => $sepsimrs->namaasalperujuk,
                        'Dinsos' => $sepsimrs->Dinsos,
                        'prolanisPRB' => $sepsimrs->prolanisPRB,
                        'noSKTM' => $sepsimrs->noSKTM,
                        'jeniskunjungan' => $sepsimrs->jeniskunjungan,
                        'tujuanKunj' => $sepsimrs->tujuanKunj,
                        'flagProcedure' => $sepsimrs->flagProcedure,
                        'kdPenunjang' => $sepsimrs->kdPenunjang,
                        'assesmentPel' => $sepsimrs->assesmentPel,
                        'kdUnit' => $sepsimrs->kdUnit
                    ]
                );
                $dataSep = [
                    'ins' => $sepraj,
                    'data' => $sepsimrs
                ];
                return new JsonResponse([
                    'message' => 'History SEP tanggal ' . date_format($date, 'd-M-Y') . ' tidak ditemukan, Data diambilkan dari histori sep RS',
                    'data' => $dataSep
                ], 200);
            }
            return new JsonResponse([
                'message' => 'History SEP tanggal ' . date_format($date, 'd-M-Y') . ' tidak ditemukan, sudah di lakukan pengecekan di V-Claim?',
                'req' => $request->all(),
                'ins' => $sepsimrs
            ], 410);
        }
        $infoSep = BridgingbpjsHelper::get_url('vclaim', 'SEP/' . $sep);
        $dataInfo = $infoSep['result'];
        // return new JsonResponse(['message' => $dataInfo]);
        $data = $this->getNesData($dataInfo, $request, $tgltobpjshttpres, $sep, $infoHis);
        return new JsonResponse(['data' => $data, 'message' => 'Data Berhasil disimpan']);
        // $sep = $history['result']->histori[0]->noSep;
        // cari di bppjs http respon
        return new JsonResponse([
            // 'res bpjs' => $createsep,
            // 'history' => $history,
            'tgl cari' => $tglCari,
            'sep' => $sep,
            // 'pasien' => $pasien,
            // 'http res' => $responBpjs,
            'info Sep' => $infoSep,
            'dataInfo' => $dataInfo,
            'req' => $request->all(),
            'data' => $data,
            // 'kontrol' => $kontrol,
            // 'rujukanPcare' => $rujukanPcare,
        ]);
    }
    public function getNesData($dataInfo, $request, $tgltobpjshttpres, $sep, $infoHis)
    {
        $pasien = Mpasien::select('rs55')->where('rs1', $request->norm)->first();
        $kontrol = '';
        $rujukanPcare = '';
        $tglrujukan = '';
        $namappkRujukan = '';
        $ppkRujukan = '';
        $namappkRujukan = '';
        $dinsos = '';
        $prolanisPRB = '';
        $noSKTM = '';
        $jenis_kunjungan = '';
        $kelasRawat = $dataInfo->kelasRawat;
        $rujukan = $dataInfo->noRujukan;
        $suratKontrol = $dataInfo->kontrol->noSurat ?? null;
        if ($suratKontrol) {
            $kontrol = BridgingbpjsHelper::get_url('vclaim', '/RencanaKontrol/noSuratKontrol/' . $suratKontrol);
            if ($kontrol['metadata']['code'] === '200' && $kontrol['result']) {
                $temp = $kontrol['result']->sep;
                $tglrujukan = $temp->provPerujuk->tglRujukan;
                // $namadiagnosa = $infoHis->diagnosa ?? $temp->diagnosa;
                $namappkRujukan = $temp->provPerujuk->nmProviderPerujuk;
                $ppkRujukan = $temp->provPerujuk->kdProviderPerujuk;
                $jenis_kunjungan = 'Kontrol';
            }
        }
        if ($rujukan) {
            $rujukanPcare = BridgingbpjsHelper::get_url('vclaim', 'Rujukan/' . $rujukan);
            // return new JsonResponse([
            //     'rujukanPcare' => $rujukanPcare,
            //     'rujukan' => $rujukan,
            // ]);
            if ($rujukanPcare['metadata']['code'] === '200' && $rujukanPcare['result']) {
                $temp = $rujukanPcare['result']->rujukan;
                $tglrujukan = $temp->tglKunjungan;
                // $namadiagnosa = $infoHis->diagnosa ?? ($temp->diagnosa ?? $temp->diagnosa->kode . ' - ' . $temp->diagnosa->nama);
                $namappkRujukan = $temp->provPerujuk->nama;
                $ppkRujukan = $temp->provPerujuk->kode;
                $dinsos = $temp->peserta->informasi->dinsos;
                $prolanisPRB = $temp->peserta->informasi->prolanisPRB;
                $noSKTM = $temp->peserta->informasi->noSKTM;
                $kelasRawat = $temp->peserta->hakKelas->keterangan;
                $jenis_kunjungan = $suratKontrol ? 'Kontrol' : 'Rujukan FKTP';
            }
        }

        $data = (object) [
            'noreg' => $request->noreg,
            'norm' => $request->norm,
            'poliBpjs' => $dataInfo->poli,
            'kodesistembayar' => $request->kodesistembayar,
            'norujukan' => $dataInfo->noRujukan,
            'tglrujukan' => $tglrujukan, // cek no rujukan dahulu
            'namadiagnosa' => $infoHis->diagnosa,
            'namappkRujukan' => $namappkRujukan, // cek no rujukan dahulu
            'ppkRujukan' => $ppkRujukan,
            'dinsos' => $dinsos,
            'prolanisPRB' => $prolanisPRB,
            'noSKTM' => $noSKTM,
            'jenis_kunjungan' => $jenis_kunjungan,
            'nosep' => $sep,
            'catatan' => $dataInfo->catatan,
            'jenispeserta' => $dataInfo->peserta->jnsPeserta,
            'tglkunjungan' => $request->tgl_kunjungan ?? $tgltobpjshttpres,
            'noka' => $request->noka,
            'nama' => $request->nama,
            'tgllahir' => $request->tgllahir,
            'kelamin' => $request->kelamin ? substr($request->kelamin, 0, 1) : '',
            'jnspelayanan' => '2',
            'kelas' => $kelasRawat,
            'lakalantas' => $dataInfo->lokasiKejadian->lokasi ?? '',
            'noteleponhp' => $pasien->rs55, // ambil data pasien dulu
            'tgltobpjshttpres' => $tgltobpjshttpres,
            'noDpjp' => $dataInfo->dpjp->kdDPJP ?? '',
            // 'noDpjp' => '',
            'tglKecelakaan' => $dataInfo->lokasiKejadian->tglKejadian ?? '',
            'keterangan' => $dataInfo->lokasiKejadian->ketKejadian ?? '',
            'suplesi' => '',
            'nosepsuplesi' => '',
            'kodepropinsikecelakaan' => $dataInfo->lokasiKejadian->kdProp ?? '',
            'propinsikecelakaan' => '',
            'kodekabupatenkecelakaan' => $dataInfo->lokasiKejadian->kdKab ?? '',
            'kabupatenkecelakaan' => '',
            'kodekecamatankecelakaan' => $dataInfo->lokasiKejadian->kdKec ?? '',
            'kecamatankecelakaan' => '',
            'dpjp' => $dataInfo->dpjp->kdDPJP ?? '',
            'namadokter' => $dataInfo->dpjp->nmDPJP ?? '',
            'tujuankunjungan' => $dataInfo->tujuanKunj->kode ?? '',
            'flagprocedure' => $dataInfo->flagProcedure->kode ?? '',
            'kdPenunjang' => $dataInfo->kdPenunjang->kode ?? '',
            'assesmentPel' => $dataInfo->assestmenPel->kode ?? '',
            'kdUnit' => $infoHis->poliTujSep
        ];

        // return $data->noreg;
        $toIns = $this->insertToSepRajal($data);
        return ['ins' => $toIns, 'data' => $data];
    }
    public function insertToSepRajal($data)
    {
        $insertsep = Seprajal::firstOrCreate(
            ['rs1' => $data->noreg],
            [
                'rs2' => $data->norm,
                'rs3' => $data->poliBpjs,
                'rs4' => $data->kodesistembayar,
                'rs5' => $data->norujukan,
                'rs6' => $data->tglrujukan,
                'rs7' => $data->namadiagnosa,
                'rs8' => $data->nosep,
                'rs9' => $data->catatan,
                'rs10' => $data->namappkRujukan,
                'rs11' => $data->jenispeserta,
                'rs12' => $data->tglkunjungan ?? date('Y-m-d H:i:s'),
                'rs13' => $data->noka,
                'rs14' => $data->nama,
                'rs15' => $data->tgllahir,
                'rs16' => $data->kelamin,
                'rs17' => $data->jnspelayanan === '2' ? 'Rawat Jalan' : 'Rawat Inap',
                'rs18' => $data->kelas,
                'laka' => $data->lakalantas,
                'lokasilaka' => $data->lakalantas,
                'penjaminlaka' => '',
                'users' => auth()->user()->pegawai_id,
                'notelepon' => $data->noteleponhp,
                'tgl_entery' => $data->tgltobpjshttpres,
                'noDpjp' => $data->noDpjp ?? '',
                'tgl_kejadian_laka' => $data->tglKecelakaan,
                'keterangan' => $data->keterangan,
                'suplesi' => $data->suplesi,
                'nosuplesi' => $data->nosepsuplesi,
                'kdpropinsi' => $data->kodepropinsikecelakaan,
                'propinsi' => $data->propinsikecelakaan,
                'kdkabupaten' => $data->kodekabupatenkecelakaan,
                'kabupaten' => $data->kabupatenkecelakaan,
                'kdkecamatan' => $data->kodekecamatankecelakaan,
                'kecamatan' => $data->kecamatankecelakaan,
                'kodedokterdpjp' => $data->dpjp,
                'dokterdpjp' => $data->namadokter,
                'kodeasalperujuk' => $data->ppkRujukan,
                'namaasalperujuk' => $data->namappkRujukan,
                'Dinsos' => $data->dinsos,
                'prolanisPRB' => $data->prolanisPRB,
                'noSKTM' => $data->noSKTM,
                'jeniskunjungan' => $data->jenis_kunjungan,
                'tujuanKunj' => $data->tujuankunjungan,
                'flagProcedure' => $data->flagprocedure,
                'kdPenunjang' => $data->kdPenunjang,
                'assesmentPel' => $data->assesmentPel,
                'kdUnit' => $data->kdUnit
            ]
        );
        return $insertsep;
    }

    public function carisep(Request $request)
    {
        // return $request->nosep;
        // $request->validate([
        //     'nosep' => 'required'
        // ]);

        $data = BridgingbpjsHelper::get_url('vclaim', 'SEP/' . $request->nosep);
        $datax = BridgingbpjsHelper::get_url('vclaim', 'Peserta/nokartu/' . $request->noka . '/tglSEP/' . date('Y-m-d'));
        return new JsonResponse(['data' => $data, 'datax' => $datax], 200);
    }
}
