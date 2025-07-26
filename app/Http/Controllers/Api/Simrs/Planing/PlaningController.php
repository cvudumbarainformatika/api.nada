<?php

namespace App\Http\Controllers\Api\Simrs\Planing;

use App\Helpers\BridgingbpjsHelper;
use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Master\Mcounter;
use App\Models\Simrs\Master\Mpoli;
use App\Models\Simrs\Master\Msistembayar;
use App\Models\Simrs\Pendaftaran\Rajalumum\Seprajal;
use App\Models\Simrs\Penunjang\Kamaroperasi\JadwaloperasiController;
use App\Models\Simrs\Planing\Mplaning;
use App\Models\Simrs\Planing\RujukBalikPrb;
use App\Models\Simrs\Planing\Simpanspri;
use App\Models\Simrs\Planing\Simpansuratkontrol;
use App\Models\Simrs\Planing\Transrujukan;
use App\Models\Simrs\Rajal\JawabanKonsulPoli;
use App\Models\Simrs\Rajal\KunjunganPoli;
use App\Models\Simrs\Rajal\Listkonsulantarpoli;
use App\Models\Simrs\Rajal\WaktupulangPoli;
use App\Models\Simrs\Rekom\Rekomdpjp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use function PHPUnit\Framework\returnSelf;

class PlaningController extends Controller
{
    public function mpoli()
    {
        $mpoli = Mpoli::where('rs5', 1)
            ->where('rs4', 'Poliklinik')
            ->get();
        return new JsonResponse($mpoli);
    }
    public function mpalningrajal()
    {
        $mplanrajal = Mplaning::where('hidden', '!=', '1')->where('unit', 'RJ')->get();
        return new JsonResponse($mplanrajal);
    }

    public function getRespPlanning($noreg)
    {
        $data = WaktupulangPoli::with([
            'masterpoli',
            'listkonsul',
            'rekomdpjp' => function ($q) {
                $q->orderBy('id', 'DESC');
            },
            'transrujukan',
            'spri',
            'kontrol',
            'ranap',
            'operasi',
        ])->where('rs1', $noreg)->first();
        return $data;
    }
    public function getAllRespPlanning($noreg)
    {
        $data = WaktupulangPoli::with([
            'masterpoli',
            'listkonsul',
            'rekomdpjp' => function ($q) {
                $q->orderBy('id', 'DESC');
            },
            'transrujukan',
            'spri',
            'kontrol',
            'ranap',
            'operasi',
        ])->where('rs1', $noreg)->get();
        $anu = collect($data);
        return $anu->all();
    }
    public function simpanplaningpasien(Request $request)
    {
        // $cek = WaktupulangPoli::where('rs1', $request->noreg)->get();
        // if (count($cek) > 0) {
        //     $before = $cek[0]['rs4'] === 'Kontrol' || $cek[0]['rs4'] === 'Konsultasi';
        //     $req = $request->planing == 'Konsultasi' || $request->planing == 'Kontrol';
        //     // return new JsonResponse(['message' => 'Maaf, data kunjungan pasien ini sudah di rencanakan...!!!', $before, $req], 500);
        //     if ($before && $req) {
        //         $col = collect($cek);
        //         $renc = $col->where('rs4', $request->planing);
        //         if (count($renc) >= 1) {
        //             $mesage = (count($renc) > 1 ? 'Sudah ada Plannig ' . $request->planing : 'Sudah Ada Planning Kontrol dan Konsultasi');
        //             return new JsonResponse(['message' => $mesage, 'data' => $renc], 500);
        //         }
        //     } else {
        //         return new JsonResponse(['message' => 'Maaf, data kunjungan pasien ini sudah di rencanakan...!!!'], 500);
        //     }
        // }
        $sistembayar = Msistembayar::select('groups')->where('rs1', $request->kodesistembayar)->first();
        $groupsistembayar = $sistembayar->groups;

        if ($request->planing == 'Konsultasi') {
            // if ($request->kdSaran == '3') {
            //     $simpanrekomdpjp = self::simpan_rekom_dpjp($request);
            //     if ($simpanrekomdpjp === 500) {
            //         return new JsonResponse(['message' => 'Maaf, Data Gagal Disimpan...!!!'], 500);
            //     }

            //     $simpanplaningpasien = self::simpankonsulantarpoli($request);
            //     if ($simpanplaningpasien == 500) {
            //         return new JsonResponse(['message' => 'Maaf, Data Pasien Ini Masih Ada Dalam List Konsulan TPPRJ...!!!'], 500);
            //     }
            //     $simpanakhir = self::simpanakhir($request);
            //     if ($simpanakhir == 500) {
            //         return new JsonResponse(['message' => 'Maaf, Data Pasien Ini Masih Ada Dalam List Konsulan TPPRJ...!!!'], 500);
            //     }
            //     $data = self::getAllRespPlanning($request->noreg); // Rekomdpjp::where('noreg', $request->noreg)->where('kdSaran', '3')->first();
            //     return new JsonResponse([
            //         'message' => 'Berhasil Mengirim Data Ke List Konsulan TPPRJ Pasien Ini...!!!',
            //         'result' => $data,
            //         'type' => gettype($data),
            //     ], 200);
            // } else {

            $simpanakhir = self::simpanakhir($request);
            if ($simpanakhir['code'] == 500) {
                return new JsonResponse(['message' => 'Maaf, Data Pasien Ini Masih Ada Dalam List Konsulan TPPRJ...!!!'], 500);
            }
            $simpanJawaban = self::createJawabanKonsul($request, null, $simpanakhir);
            if (!$simpanJawaban) {
                return new JsonResponse(['message' => 'Maaf, Gagal Menyimpan pengantar konsulan'], 410);
            }
            $simpanrekomdpjp = self::simpan_rekom_dpjp($request, $simpanakhir);
            // return new JsonResponse(['message' => $simpanrekomdpjp], 500);
            if ($simpanrekomdpjp === 500) {
                return new JsonResponse(['message' => 'Maaf, Data Gagal Disimpan...!!!'], 500);
            }

            $data = self::getAllRespPlanning($request->noreg);
            return new JsonResponse([
                'message' => 'Berhasil Mengirim Data Ke List Konsulan TPPRJ Pasien Ini...!!!',
                'result' => $data,
                'type' => gettype($data),
            ], 200);
            // }
        } elseif ($request->planing == 'Rumah Sakit Lain') {
            if ($groupsistembayar == '1') {
                $createrujukan = BridbpjsplanController::bridcretaerujukan($request);
                if ($createrujukan == 500) {
                    return new JsonResponse(['message' => 'Maaf, Data Gagal Disimpan Di RS...!!!'], 500);
                } elseif ($createrujukan == 200) {
                    $simpanakhir = self::simpanakhir($request);
                    if ($simpanakhir['code'] == 500) {
                        return new JsonResponse(['message' => 'Maaf, Data Gagal Disimpan Di RS...!!!'], 500);
                    }
                    $data = $this->getRespPlanning($request->noreg);
                    return new JsonResponse([
                        'message' => 'Data Berhasil Disimpan',
                        'result' => $data
                    ], 200);
                } else {
                    return $createrujukan;
                }
            } else {
                $simpanakhir = self::simpanakhir($request);
                if ($$simpanakhir['code'] == 500) {
                    return new JsonResponse(['message' => 'Maaf, Data Gagal Disimpan Di RS...!!!',], 500);
                }
                $simpanrujukanumum = self::simpanrujukanumum($request);
                if ($simpanrujukanumum == 500) {
                    return new JsonResponse(['message' => 'Maaf, Data Gagal Disimpan...!!!',], 500);
                }
                $data = $this->getRespPlanning($request->noreg);
                // $data = Transrujukan::with(
                //     ['masterpasien', 'relmpoli', 'relmpolix', 'rs141']
                // )
                //     ->where('rs1', $request->noreg)->first();
                return new JsonResponse([
                    'message' => 'Data Berhasil Disimpan',
                    'result' => $data
                ], 200);
            }
        } elseif ($request->planing == 'Rawat Inap') {
            if ($request->status == 'Operasi') {
                if ($groupsistembayar == '1') {
                    $createspri = BridbpjsplanController::createspri($request);
                    $xxx = $createspri['metadata']['code'];
                    if ($xxx === 200 || $xxx === '200') {
                        $nospri = $createspri['response']->noSPRI;
                        $simpanop = self::jadwaloperasi($request);
                        if ($simpanop == 500) {
                            return new JsonResponse(['message' => 'Maaf, Data Gagal Disimpan Di RS...!!!'], 500);
                        }
                        $simpanspri = self::simpanspri($request, $groupsistembayar, $nospri);
                        $simpanakhir = self::simpanakhir($request);
                        $simpanrekomdpjp = self::simpan_rekom_dpjp($request, $simpanakhir);
                        if ($simpanspri === 500) {
                            return new JsonResponse(['message' => 'Maaf, Data Gagal Disimpan Di RS...!!!'], 500);
                        }
                        $data = $this->getRespPlanning($request->noreg);
                        return new JsonResponse([
                            'message' => 'Data Berhasil Disimpan...!!!',
                            'result' => $data
                        ], 200);
                    } else {
                        $msg = $createspri['metadata']['message'] ?? '';
                        return new JsonResponse(['message' => 'Respon BPJS :  ' . $msg], 410);
                    }
                } else {
                    $nospri = $request->noreg;
                    $simpanop = self::jadwaloperasi($request);
                    // return $simpanop;
                    if ($simpanop == 500) {
                        return new JsonResponse(['message' => 'Maaf, Data Gagal Disimpan Di RS...!!!'], 500);
                    }
                    $simpanspri = self::simpanspri($request, $groupsistembayar, $nospri);
                    if ($simpanspri === 500) {
                        return new JsonResponse(['message' => 'Maaf, Data Gagal Disimpan Di RS...!!!'], 500);
                    }
                    $simpanakhir = self::simpanakhir($request);
                    $simpanrekomdpjp = self::simpan_rekom_dpjp($request, $simpanakhir);
                    $data = $this->getRespPlanning($request->noreg);
                    return new JsonResponse([
                        'message' => 'Data Berhasil Disimpan...!!!',
                        'result' => $data
                    ], 200);
                }
            } else {
                if ($groupsistembayar == '1') {
                    $createspri = BridbpjsplanController::createspri($request);
                    $xxx = $createspri['metadata']['code'];
                    if ($xxx === 200 || $xxx === '200') {
                        $nospri = $createspri['response']->noSPRI;
                        $simpanspri = self::simpanspri($request, $groupsistembayar, $nospri);
                        if ($simpanspri === 500) {
                            return new JsonResponse(['message' => 'Maaf, Data Gagal Disimpan Di RS...!!!'], 500);
                        }
                        $simpanakhir = self::simpanakhir($request);
                        $data = $this->getRespPlanning($request->noreg);
                        return new JsonResponse([
                            'message' => 'Data Berhasil Disimpan...!!!',
                            'result' => $data
                        ], 200);
                    } else {
                        $msg = $createspri['metadata']['message'] ?? '';
                        return new JsonResponse(['message' => 'Respon BPJS :  ' . $msg], 410);
                    }
                } else {
                    $nospri = $request->noreg;
                    $simpanspri = self::simpanspri($request, $groupsistembayar, $nospri);
                    $simpanakhir = self::simpanakhir($request);
                    $data = $this->getRespPlanning($request->noreg);
                    if ($simpanspri === 500) {
                        return new JsonResponse(['message' => 'Maaf, Data Gagal Disimpan Di RS...!!!'], 500);
                    }
                    return new JsonResponse([
                        'message' => 'Data Berhasil Disimpan...!!!',
                        'result' => $data
                    ], 200);
                }
            }
        } elseif ($request->planing == 'PRB') {
            if ($groupsistembayar == '1') {
                $result = BridbpjsplanController::createPrb($request);
                if ($result['code'] == 500) {
                    return new JsonResponse([
                        'message' => 'Maaf, Data Gagal Disimpan Di BPJS',
                        'data' => $result,
                    ], 410);
                } else if ($result['code'] == 410) {
                    return new JsonResponse([
                        'message' => 'Maaf, Data Gagal Disimpan Di RS',
                        'data' => $result,
                    ], 410);
                }

                return new JsonResponse([
                    'message' => 'Data Berhasil Disimpan...!!!',
                    'data' => $result,
                ], 200);
                // $createrujukan = BridbpjsplanController::bridcretaerujukan($request);
                // if ($createrujukan == 500) {
                //     return new JsonResponse(['message' => 'Maaf, Data Gagal Disimpan Di RS...!!!'], 500);
                // } elseif ($createrujukan == 200) {
                //     $simpanakhir = self::simpanakhir($request);
                //     if ($simpanakhir == 500) {
                //         return new JsonResponse(['message' => 'Maaf, Data Gagal Disimpan Di RS...!!!'], 500);
                //     }
                //     $data = $this->getRespPlanning($request->noreg);
                //     return new JsonResponse([
                //         'message' => 'Data Berhasil Disimpan',
                //         'result' => $data
                //     ], 200);
                // } else {
                //     return $createrujukan;
                // }
            } else {
                return new JsonResponse([
                    'message' => 'Bukan Pasien BPJS',
                ], 410);
            }
        } else {
            if ($groupsistembayar == '1') {
                $simpan = BridbpjsplanController::insertsuratcontrol($request);
                // return new JsonResponse(['sim' => $simpan]);
                $xxx = $simpan['metadata']['code'];

                if ($xxx === 200 || $xxx === '200') {
                    $nosuratkontrol = $simpan['response']->noSuratKontrol;
                    $simpanspri = self::simpansuratkontrol($request, $nosuratkontrol);
                    $simpanakhir = self::simpanakhir($request);
                    $data = self::getAllRespPlanning($request->noreg);
                    return new JsonResponse([
                        'message' => 'Data Berhasil Disimpan...!!!',
                        'result' => $data,
                        'type' => gettype($data),
                    ], 200);
                } else {
                    return new JsonResponse($simpan);
                }
            } else {
                $nosuratkontrol = $request->noreg;
                $simpanspri = self::simpansuratkontrol($request, $nosuratkontrol);
                $simpanakhir = self::simpanakhir($request);
                $data = self::getAllRespPlanning($request->noreg);
                return new JsonResponse([
                    'message' => 'Data Berhasil Disimpan...!!!',
                    'result' => $data,
                    'type' => gettype($data),
                ], 200);
            }
        }
    }
    // ini nanti dipanggil oleh BridbpjsplanController::createPrb
    public static function simpanprb($head)
    {
        $simpanprb = RujukBalikPrb::updateOrCreate(
            [
                'noreg' => $head->noreg
            ],
            [
                'norm' => $head->norm,
                'nosep' => $head->nosep,
                'form' => $head->form,
                'bpjs_response' => $head->bpjs_response ?? null,
                'nosrb' => $head->nosrb ?? null,
                'tgl_insert' => date('Y-m-d H:i:s'),
            ]
        );
        if (!$simpanprb) {
            return 500;
        }
        return $simpanprb;
    }
    public static function simpankonsulantarpoli($request)
    {
        $cek = Listkonsulantarpoli::where('noreg_lama', $request->noreg_lama)->where('flag', '')->count();
        if ($cek > 0) {
            return 500;
        }
        $simpankonsulantarpoli = Listkonsulantarpoli::firstOrCreate(
            [
                'noreg_lama' => $request->noreg_lama
            ],
            [
                'norm' => $request->norm,
                'tgl_kunjungan' => $request->tgl_kunjungan,
                'tgl_rencana_konsul' => $request->tgl_rencana_konsul,
                'kdpoli_asal' => $request->kdpoli_asal,
                'kdpoli_tujuan' => $request->kdpoli_tujuan,
                'kddokter_asal' => $request->kddokter_asal,
                'keterangan' => $request->ket ?? '',
                'flag' => '1',
            ]
        );

        if (!$simpankonsulantarpoli) {
            return 500;
        }

        // $updatekunjungan = KunjunganPoli::where('rs1', $request->noreg_lama)->first();
        // $updatekunjungan->rs19 = '1';
        // $updatekunjungan->rs24 = '1';
        // $updatekunjungan->save();
        // ->update(
        //     [
        //         'rs19' => 1,
        //         'rs24' => 1
        //     ]
        // );
        // if (!$updatekunjungan) {
        //     return 500;
        // }
        return 200;
    }
    public static function createJawabanKonsul($request, $noreg, $akhir)
    {
        $simpan = JawabanKonsulPoli::create([
            'norm' => $request->norm,
            'noreg_lama' => $request->noreg_lama,
            'pertanyaan' => $request->pertanyaan,
            'tgl_kunjungan' => $request->tgl_kunjungan,
            'tgl_rencana_konsul' => $request->tgl_rencana_konsul,
            'poli_asal' => $request->kdpoli_asal,
            'poli_tujuan' => $request->kdpoli_tujuan,

            'noreg_baru' => $noreg ?? null,
            'rs141_id' => $akhir['data']->id ?? null,
        ]);

        return $simpan ?? false;
    }
    public static function updateNoregJawabanKonsul($head)
    {
        $simpan = JawabanKonsulPoli::where('rs141_id', $head['rs141_id'])
            ->first();
        if (!$simpan) {
            info('gagal update noreg jawaban konsul');
        }
        $simpan->update([
            'noreg_baru' => $head['noreg']
        ]);
        info('sukses update noreg jawaban konsul' . $simpan);
        return $simpan ?? false;
    }
    public static function updateNoreg(Request $request)
    {
        $simpan = JawabanKonsulPoli::where('id', $request->id)->first();
        if (!$simpan) {
            return new JsonResponse([
                'message' => 'Data tidak ditemukan'
            ], 410);
        }
        $simpan->update([
            'noreg_baru' => $request->noreg
        ]);
        $simpan->load([
            'poliAsal:rs1,rs2',
            'poliTujuan:rs1,rs2',
        ]);
        return new JsonResponse([
            'message' => 'Data berhasil disimpan',
            'data' => $simpan
        ], 200);
    }
    public static function updateDibaca(Request $request)
    {
        $simpan = JawabanKonsulPoli::where('id', $request->id)->first();
        if (!$simpan) {
            return new JsonResponse([
                'message' => 'dibaca tidak di update'
            ], 410);
        }
        $simpan->update([
            'dibaca_poli_asal' => '1'
        ]);

        $simpan->load([
            'poliAsal:rs1,rs2',
            'poliTujuan:rs1,rs2',
        ]);
        return new JsonResponse([
            'message' => 'Data berhasil disimpan',
            'data' => $simpan
        ], 200);
    }
    public static function updatePengantarAtauJawabanKonsul(Request $request)
    {
        $simpan = JawabanKonsulPoli::where('id', $request->id)->first();
        if (!$simpan) {
            return new JsonResponse([
                'message' => 'Data tidak ditemukan'
            ], 410);
        }
        if ($request->has('edit')) {
            $simpan->update([
                'jawaban' => $request->jawaban,
            ]);
        }
        if ($request->has('editPertanyaan')) {
            $simpan->update([
                'pertanyaan' => $request->pertanyaan,

            ]);
        }

        $simpan->load([
            'poliAsal:rs1,rs2',
            'poliTujuan:rs1,rs2',
        ]);

        return new JsonResponse([
            'message' => 'Data berhasil disimpan',
            'data' => $simpan
        ], 200);
    }
    public static function simpanakhir($request)
    {
        if ($request->planing == 'Konsultasi' || $request->planing == 'Kontrol') {
            $planing = $request->planing;
            if ($request->planing == 'Konsultasi') {
                if ($request->kdSaran == '6') {
                    $planing = 'Konsultasi Internal';
                }
                if ($request->kdSaran == '9') {
                    $planing = 'Rujukan Internal';
                }
            }
            $simpanakhir = WaktupulangPoli::create(
                [
                    'rs1' => $request->noreg ?? '',
                    'rs2' => $request->norm ?? '',
                    'rs3' => $request->kdpoli_tujuan ?? '',
                    'rs4' => $planing ?? '',
                    // 'rs5' => $request->kdpoli_asal ?? '',
                    'tgl' => date('Y-m-d H:i:s'),
                    'user' => auth()->user()->pegawai_id
                ]
            );
        } else {
            $simpanakhir = WaktupulangPoli::create(
                [
                    'rs1' => $request->noreg ?? '',
                    'rs2' => $request->norm ?? '',
                    'rs3' => $request->kdruang ?? '',
                    'rs4' => $request->planing ?? '',
                    'rs5' => $request->kdruangtujuan ?? '',
                    'tgl' => date('Y-m-d H:i:s'),
                    'user' => auth()->user()->pegawai_id
                ]
            );
        }

        if (!$simpanakhir) {
            return [
                'code' => 500,
            ];
        }
        return [
            'code' => 200,
            'data' => $simpanakhir
        ];
    }

    public static function jadwaloperasi($request)
    {
        $conter = JadwaloperasiController::count();
        $kodebooking = "JO/" . ($conter + 1) . "/" . date("d/m/Y");
        $simpan = JadwaloperasiController::firstOrCreate(
            [
                'noreg' => $request->noreg,
                'norm' => $request->norm,
                //    'nopermintaan' => $request->nopermintaan,
                'kodebooking' => $kodebooking,
                'tanggaloperasi' => $request->tanggaloperasi,
                'jenistindakan' => $request->jenistindakan,
                'icd9' => $request->icd9,
                'kodepoli' => $request->kodepolibpjs,
                'namapoli' => $request->polibpjs,
                'lastupdate' => time(),
                'ket' => $request->keterangan,
                'userid' => auth()->user()->pegawai_id,
                'kdruang' => $request->kdruang,
                'tglupdate' => $request->tglupdate,
                'kddokter' => $request->kddokter,
                'dokter' => $request->dokter,
                'kdruangtujuan' => $request->kdruangtujuan,
                'kontakpasien' => $request->kontakpasien,
                'jenisoperasi' => $request->jenisoperasi ?? '',
                'terlaksana' => 0
            ]
        );
        if (!$simpan) {
            return 500;
        }
        return 200;
    }

    public function hapusplaningpasien(Request $request)
    {
        $message = 'berhasil Dihapus';
        $cari = WaktupulangPoli::find($request->id);
        if (!$cari) {
            return new JsonResponse(['message' => 'data tidak ditemukan'], 501);
        }

        if ($request->plan === 'Konsultasi' || $request->plan === 'Konsultasi Internal' || $request->plan === 'Rujukan Internal') {
            $konsul = KunjunganPoli::where('rs4', $cari->rs1)->first();
            if ($konsul) {
                if ($konsul->rs19 !== '') {
                    return new JsonResponse(['message' => 'Kunjungan Poli Konsul tidak dapat dihapus kerena sudah dilayani / dalam proses layanan'], 500);
                } else {
                    $sepkonsul = Seprajal::where('rs1', $konsul->rs1)->first();
                    if ($sepkonsul) {
                        $sepkonsul->delete();
                    }
                    $konsul->delete();
                }
            }

            $list = Listkonsulantarpoli::where('noreg_lama', $cari->rs1)->first();
            if ($list) $list->delete();
            // rekom depjp belum dihapus
            $rekom = Rekomdpjp::where('rs141_id', $cari->id)->orderBy('id', 'ASC')->first();
            if ($rekom) $rekom->delete();
            $jawaban = JawabanKonsulPoli::where('rs141_id', $cari->id)->orderBy('id', 'ASC')->first();
            if ($jawaban) $jawaban->delete();
            // return new JsonResponse([
            //     'message' => $message,
            //     'cari' => $cari,
            //     'list' => $list,
            //     'rekom' => $rekom,
            // ], 200);
        }
        if ($request->plan === 'Kontrol') {
            $data = Simpansuratkontrol::where('noreg', $cari->rs1)->first();
            if ($data) {
                BridbpjsplanController::hapussuratcontrol($request, $data->noSuratKontrol);
                $data->delete();
            }
        }
        if ($request->plan === 'Rawat Inap') {
            Simpanspri::where('noreg', $cari->rs1)->delete();
        }
        if ($request->plan === 'Rumah Sakit Lain') {
            Transrujukan::where('rs1', $cari->rs1)->delete();
        }
        $hapus = $cari->delete();
        if (!$hapus) {
            return new JsonResponse(['message' => 'gagal dihapus'], 500);
        }

        return new JsonResponse([
            'message' => $message,
            'cari' => $cari,
            'konsul' => $konsul ?? null,
        ], 200);
    }

    public static function simpanrujukanumum($request)
    {
        $simpanrujukan = Transrujukan::create(
            [
                'rs1' => $request->noreg,
                'rs2' => $request->norm,
                //    'rs3' => $norujukan,
                // 'rs4' => $request->nosep,
                'rs5' => $request->tglrujukan,
                'rs6' => $request->ppkdirujuk,
                'rs7' => $request->namappkdirujuk,
                'rs8' => $request->jenispelayanan,
                'rs9' => $request->catatan,
                'rs10' => $request->diagnosarujukan,
                'rs11' => $request->tiperujukan,
                'rs12' => $request->kodepoli,
                'rs13' => date('Y-m-d H:i:s'),
                'rs14' => auth()->user()->pegawai_id,
                //   'rs15' => $request->noka,
                'rs16' => $request->nama,
                'rs17' => $request->kelamin,
                'tglRencanaKunjungan' => $request->tglrencanakunjungan,
                'diagnosa' => $request->diagnosa,
                'poli' => $request->namapolirujukan,
                //    'tipefaskes' => $request->tipefaskes,
                'polix' => $request->polirujukan
            ]
        );

        if (!$simpanrujukan) {
            return 500;
        }
        return 200;
    }

    public static function simpanspri($request, $groupsistembayar, $nospri)
    {
        $simpanspri = Simpanspri::firstOrCreate(
            [
                'noSuratKontrol' => $nospri
            ],
            [
                'noreg' => $request->noreg,
                'norm' => $request->norm,
                'kodeDokter' => $request->kddokter,
                'poliKontrol' => $request->kodepolibpjs,
                'tglRencanaKontrol' => $request->tglrencanakunjungan,
                'namaDokter' => $request->dokter,
                'noKartu' => $request->noka,
                'nama' => $request->nama,
                'kelamin' => $request->kelamin,
                'tglLahir' => $request->tgllahir,
                'user_id' => auth()->user()->pegawai_id
            ]
        );
        if (!$simpanspri) {
            return 500;
        }
        return 200;
    }

    public static function simpansuratkontrol($request, $nosuratkontrol)
    {
        $dokter = Pegawai::where('kddpjp', $request->kodedokterdpjp)->first();
        $simpansuratkontrol = Simpansuratkontrol::firstOrCreate(
            [
                'noSuratKontrol' => $nosuratkontrol
            ],
            [
                'noreg' => $request->noreg,
                'norm' => $request->norm,
                'noSEP' => $request->nosep ?? '',
                'kodeDokter' => $request->kodedokterdpjp,
                'poliKontrol' => $request->kodepolibpjs,
                'tglRencanaKontrol' => $request->tglrencanakunjungan,
                // 'namaDokter' => $request->dokter,
                'namaDokter' => $dokter->nama ?? 'Dokter tidak di temukan di data pegawai',
                'noKartu' => $request->noka,
                'nama' => $request->nama,
                'kelamin' => $request->kelamin,
                'tglLahir' => $request->tgllahir,
                'keterangan' => $request->keterangan2,
                'user_id' => auth()->user()->pegawai_id
            ]
        );
        if (!$simpansuratkontrol) {
            return 500;
        }
        return 200;
    }

    public static function simpan_rekom_dpjp($request, $akhir)
    {
        // return $akhir['data']->id;
        // ini dibikin aupdate or create, piye carane
        if ($akhir) {
            $adaRekom = Rekomdpjp::where('rs141_id', $akhir['data']->id)->first();
            if ($adaRekom) {
                $nomor = $adaRekom->noDpjp;
                $tglMasaAktif = $adaRekom->tglMasaAktif;
            } else {
                $nomordpjp = Mcounter::first();
                $nomor = (int) $nomordpjp->rekom_dpjp + (int) 1;
                $updateconter = Mcounter::first();
                $updateconter->rekom_dpjp = $nomor;
                $updateconter->save();
                $tglMasaAktif = date("Y-m-d", strtotime("+90 day", strtotime(date("Y-m-d"))));
            }
        } else {
            $nomordpjp = Mcounter::first();
            $nomor = (int) $nomordpjp->rekom_dpjp + (int) 1;
            $updateconter = Mcounter::first();
            $updateconter->rekom_dpjp = $nomor;
            $updateconter->save();
            $tglMasaAktif = date("Y-m-d", strtotime("+90 day", strtotime(date("Y-m-d"))));
        }
        $saran = '';
        if ($request->kdSaran === '3') {
            $saran = 'Konsul Intern ke Poli';
        } else {
            $saran = 'Masih Memerlukan Kontrol di RS';
        }

        $simpan = Rekomdpjp::updateOrCreate(
            [
                'rs141_id' => $akhir['data']->id ?? null,
                'noDpjp' => $nomor,
            ],
            [
                'noRm' => $request->norm,
                'noreg' => $request->noreg,
                'kdSaran' => $request->kdSaran ?? '',
                'saran' => $saran,
                'ket' => $request->ket ?? '',
                'tglInsert' => date('Y-m-d'),
                'usersInsert' => auth()->user()->pegawai_id,
                'unit' => $request->kodepoli,
                'dpjp' => $request->kodedokter,
                'tglMasaAktif' => $tglMasaAktif ?? null,
                'tglKontrol' => $request->tgl_rencana_konsul ?? null
            ]
        );
        if (!$simpan) {
            return 500;
        }
        return 200;
    }

    public function cariSep()
    {
        $history = BridgingbpjsHelper::get_url(
            'vclaim',
            'monitoring/HistoriPelayanan/NoKartu/' . request('noka') . '/tglMulai/' . request('tglawal') . '/tglAkhir/' . request('tglakhir')
        );
        return new JsonResponse($history);
    }

    public function updatePlanningPasien(Request $request)
    {
        $sistembayar = Msistembayar::select('groups')->where('rs1', $request->kodesistembayar)->first();
        $groupsistembayar = $sistembayar->groups;
        if ($request->planing == 'Rumah Sakit Lain') {
            if ($groupsistembayar == '1') {
                $createrujukan = BridbpjsplanController::bridUpdateRujukan($request);
                if ($createrujukan == 500) {
                    return new JsonResponse(['message' => 'Maaf, Data Gagal Diupdate Di RS...!!!'], 500);
                } elseif ($createrujukan == 200) {
                    $simpanakhir = self::updateakhir($request);
                    if ($$simpanakhir['code'] == 500) {
                        return new JsonResponse(['message' => 'Maaf, Data Gagal Diupdate Di RS...!!!'], 500);
                    }
                    $data = $this->getRespPlanning($request->noreg);
                    return new JsonResponse([
                        'message' => 'Data Berhasil Diupdate',
                        'result' => $data
                    ], 200);
                } else {
                    return $createrujukan;
                }
            } else {
                $simpanakhir = self::updateakhir($request);
                if ($$simpanakhir['code'] == 500) {
                    return new JsonResponse(['message' => 'Maaf, Data Gagal Diupdate Di RS...!!!',], 500);
                }
                $simpanrujukanumum = self::updaterujukanumum($request);
                if ($simpanrujukanumum == 500) {
                    return new JsonResponse(['message' => 'Maaf, Data Gagal Diupdate...!!!',], 500);
                }
                $data = $this->getRespPlanning($request->noreg);
                return new JsonResponse([
                    'message' => 'Data Berhasil Diupdate',
                    'result' => $data
                ], 200);
            }
        } elseif ($request->planing == 'Rawat Inap') {
            if ($request->status == 'Operasi') {
                if ($groupsistembayar == '1') {
                    $createspri = BridbpjsplanController::updateSpri($request);
                    if (!$createspri['metadata']['code']) {
                        return $createspri;
                    }
                    $xxx = $createspri['metadata']['code'];
                    if ($xxx === 200 || $xxx === '200') {
                        $nospri = $createspri['result']->noSPRI;
                        $simpanop = self::updatejadwaloperasi($request);
                        if ($simpanop == 500) {
                            return new JsonResponse(['message' => 'Maaf, Data Gagal Diupdate Di RS...!!!'], 500);
                        }
                        $simpanspri = self::updatespri($request, $groupsistembayar, $nospri);
                        $simpanakhir = self::updateakhir($request);
                        if ($simpanspri === 500) {
                            return new JsonResponse(['message' => 'Maaf, Data Gagal Diupdate Di RS...!!!'], 500);
                        }
                        $data = $this->getRespPlanning($request->noreg);
                        return new JsonResponse([
                            'message' => 'Data Berhasil Diupdate...!!!',
                            'result' => $data
                        ], 200);
                    }
                } else {
                    $nospri = $request->noreg;
                    $simpanop = self::updatejadwaloperasi($request);
                    // return $simpanop;
                    if ($simpanop == 500) {
                        return new JsonResponse(['message' => 'Maaf, Data Gagal Diupdate Di RS...!!!'], 500);
                    }
                    $simpanspri = self::updatespri($request, $groupsistembayar, $nospri);
                    if ($simpanspri === 500) {
                        return new JsonResponse(['message' => 'Maaf, Data Gagal Diupdate Di RS...!!!'], 500);
                    }
                    $simpanakhir = self::updateakhir($request);
                    $data = $this->getRespPlanning($request->noreg);
                    return new JsonResponse([
                        'message' => 'Data Berhasil Diupdate...!!!',
                        'result' => $data
                    ], 200);
                }
            } else {
                if ($groupsistembayar == '1') {
                    $createspri = BridbpjsplanController::updateSpri($request);
                    $xxx = $createspri['metadata']['code'];
                    if ($xxx === 200 || $xxx === '200') {
                        $nospri = $createspri['response']->noSPRI ?? $createspri['result']->noSPRI;
                        $simpanspri = self::updatespri($request, $groupsistembayar, $nospri);
                        if ($simpanspri === 500) {
                            return new JsonResponse(['message' => 'Maaf, Data Gagal Diupdate Di RS...!!!'], 500);
                        }
                        $simpanakhir = self::updateakhir($request);
                        $data = $this->getRespPlanning($request->noreg);
                        return new JsonResponse([
                            'message' => 'Data Berhasil Diupdate...!!!',
                            'result' => $data
                        ], 200);
                    }
                } else {
                    $nospri = $request->noreg;
                    $simpanspri = self::updatespri($request, $groupsistembayar, $nospri);
                    $simpanakhir = self::updateakhir($request);
                    $data = $this->getRespPlanning($request->noreg);
                    if ($simpanspri === 500) {
                        return new JsonResponse(['message' => 'Maaf, Data Gagal Diupdate Di RS...!!!'], 500);
                    }
                    return new JsonResponse([
                        'message' => 'Data Berhasil Diupdate...!!!',
                        'result' => $data
                    ], 200);
                }
            }
        }
    }
    public static function updateakhir($request)
    {
        $noreg = $request->noreg ? ($request->noreg !== null ? $request->noreg : false) : false;
        if ($noreg) {
            $simpanakhir = WaktupulangPoli::updateOrCreate(
                [
                    'id' => $request->id,
                ],
                [
                    'rs1' => $request->noreg,
                    'rs2' => $request->norm ?? '',
                    'rs3' => $request->kdruang ?? '',
                    'rs4' => $request->planing ?? '',
                    'rs5' => $request->kdruangtujuan ?? '',
                    'tgl' => date('Y-m-d H:i:s'),
                    'user' => auth()->user()->pegawai_id
                ]
            );
            if (!$simpanakhir) {
                return 500;
            }
            return 200;
        } else {
            return 500;
        }
    }
    public static function updaterujukanumum($request)
    {
        $simpanrujukan = Transrujukan::updateOrCreate(
            [
                'rs1' => $request->noreg,
            ],
            [
                'rs2' => $request->norm,
                //    'rs3' => $norujukan,
                // 'rs4' => $request->nosep,
                'rs5' => $request->tglrujukan,
                'rs6' => $request->ppkdirujuk,
                'rs7' => $request->namappkdirujuk,
                'rs8' => $request->jenispelayanan,
                'rs9' => $request->catatan,
                'rs10' => $request->diagnosarujukan,
                'rs11' => $request->tiperujukan,
                'rs12' => $request->kodepoli,
                'rs13' => date('Y-m-d H:i:s'),
                'rs14' => auth()->user()->pegawai_id,
                //   'rs15' => $request->noka,
                'rs16' => $request->nama,
                'rs17' => $request->kelamin,
                'tglRencanaKunjungan' => $request->tglrencanakunjungan,
                'diagnosa' => $request->diagnosa,
                'poli' => $request->namapolirujukan,
                //    'tipefaskes' => $request->tipefaskes,
                'polix' => $request->polirujukan
            ]
        );

        if (!$simpanrujukan) {
            return 500;
        }
        return 200;
    }
    public static function updatejadwaloperasi($request)
    {
        $conter = JadwaloperasiController::count();
        // $kodebooking = "JO/" . ($conter + 1) . "/" . date("d/m/Y");
        $simpan = JadwaloperasiController::updateOrCreate(
            [
                'noreg' => $request->noreg,
            ],
            [
                'norm' => $request->norm,
                //    'nopermintaan' => $request->nopermintaan,
                // 'kodebooking' => $kodebooking,
                'tanggaloperasi' => $request->tanggaloperasi,
                'jenistindakan' => $request->jenistindakan,
                'icd9' => $request->icd9,
                'kodepoli' => $request->kodepolibpjs,
                'namapoli' => $request->polibpjs,
                'lastupdate' => time(),
                'ket' => $request->keterangan,
                'userid' => auth()->user()->pegawai_id,
                'kdruang' => $request->kdruang,
                'tglupdate' => $request->tglupdate,
                'kddokter' => $request->kddokter,
                'dokter' => $request->dokter,
                'kdruangtujuan' => $request->kdruangtujuan,
                'kontakpasien' => $request->kontakpasien,
                'jenisoperasi' => $request->jenisoperasi ?? '',
                'terlaksana' => 0
            ]
        );
        if (!$simpan) {
            return 500;
        }
        return 200;
    }
    public static function updatespri($request, $groupsistembayar, $nospri)
    {
        $simpanspri = Simpanspri::updateOrCreate(
            [
                'noreg' => $request->noreg,
            ],
            [
                'noSuratKontrol' => $nospri,
                'norm' => $request->norm,
                'kodeDokter' => $request->kddokter,
                'poliKontrol' => $request->kodepolibpjs,
                'tglRencanaKontrol' => $request->tglrencanakunjungan,
                'namaDokter' => $request->dokter,
                'noKartu' => $request->noka,
                'nama' => $request->nama,
                'kelamin' => $request->kelamin,
                'tglLahir' => $request->tgllahir,
                'user_id' => auth()->user()->pegawai_id
            ]
        );
        if (!$simpanspri) {
            return 500;
        }
        return 200;
    }
}
