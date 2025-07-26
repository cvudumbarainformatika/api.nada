<?php

namespace App\Http\Controllers\Api\Satusehat\Bundle;

use App\Helpers\AuthSatsetHelper;
use App\Helpers\BridgingLoincHelper;
use App\Helpers\BridgingSatsetHelper;
use App\Helpers\PostKunjunganHelper;
use App\Helpers\Satsets\CobaPostKunjunganRajalHelper;
use App\Helpers\Satsets\PostKunjunganHDHerlper;
use App\Helpers\Satsets\PostKunjunganRajalHelper;
use App\Helpers\Satsets\PostKunjunganRanapHelper;
use App\Http\Controllers\Controller;
use App\Models\Pasien;
use App\Models\Simrs\Rajal\KunjunganPoli;
use App\Models\Simrs\Ranap\Kunjunganranap;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KunjunganController extends Controller
{
    public function index()
    {
        // return new JsonResponse(['message' => 'kririm kunjungan satset', 'req' => request()->all()], 200);
        $jenis_kunjungan = request('jenis');

        if ($jenis_kunjungan === 'hd') {

            return PostKunjunganHDHerlper::cobarajal('110752/01/2025/J');
            // return CobaPostKunjunganRajalHelper::cekKunjungan('74740/09/2024/J');
            // return self::cekKunjunganRajal('74748/09/2024/J');
        }
        if ($jenis_kunjungan === 'rajal') {

            return PostKunjunganRajalHelper::cobarajal('93409/11/2024/J');
            // return CobaPostKunjunganRajalHelper::cekKunjungan('74740/09/2024/J');
            // return self::cekKunjunganRajal('74748/09/2024/J');
        }

        if ($jenis_kunjungan === 'ranap') {
            $ygTerkirim = 0;
            $arrayKunjungan = self::cekKunjunganRanap();
            // return self::ranap($arrayKunjungan[8]);
            for ($i = 0; $i < count($arrayKunjungan); $i++) {
                self::ranap($arrayKunjungan[$i]);
                // echo $i;
                // sleep(5);//menunggu 5 detik
                $ygTerkirim = $i + 1;
            }
            return ['yg terkirim' => $ygTerkirim, 'jml_kunjungan' => count($arrayKunjungan)];
        }

        if ($jenis_kunjungan === 'igd') {
            $ygTerkirim = 0;
            $arrayKunjungan = self::cekKunjunganIgd();

            return self::igd($arrayKunjungan[0]);

            for ($i = 0; $i < count($arrayKunjungan); $i++) {
                self::igd($arrayKunjungan[$i]);
                $ygTerkirim = $i + 1;
                // sleep(5);//menunggu 10 detik
            }
            return ['yg terkirim' => $ygTerkirim, 'jml_kunjungan' => count($arrayKunjungan)];
        }

        return new JsonResponse(['message' => 'Jenis Kunjungan Tidak Diketahui'], 500);
    }


    // KUNJUNGAN RAJAL ==========================================================================================================
    public static function cekKunjunganRajal($noreg)
    {
        $tgl = Carbon::now()->subDays(4)->toDateString();
        // $tgl = Carbon::now()->subDays(1)->toDateString();
        $bukanPoli = ['POL014', 'PEN005', 'PEN004'];
        $data = KunjunganPoli::select(
            'rs17.rs1',
            'rs17.rs9',
            'rs17.rs4',
            'rs17.rs8',
            'rs17.rs1 as noreg',
            'rs17.rs2 as norm',
            'rs17.rs3 as tgl_kunjungan',
            'rs17.rs8 as kodepoli',
            'rs19.rs2 as poli',
            'rs17.rs9 as kodedokter',
            'rs21.rs2 as dokter',
            'rs17.rs14 as kodesistembayar',
            'rs9.rs2 as sistembayar',
            'rs9.groups as groups',
            'rs15.rs2 as nama',
            'rs15.rs49 as nik',
            'rs17.rs19 as status',
            'rs15.satset_uuid as pasien_uuid',
            DB::raw('concat(TIMESTAMPDIFF(YEAR, rs15.rs16, CURDATE())) AS usiatahun'),
            // 'satsets.uuid as satset',
            // 'satset_error_respon.uuid as satset_error',
        )
            ->leftjoin('rs15', 'rs15.rs1', '=', 'rs17.rs2') //pasien
            ->leftjoin('rs19', 'rs19.rs1', '=', 'rs17.rs8') //poli
            ->leftjoin('rs21', 'rs21.rs1', '=', 'rs17.rs9') //dokter
            ->leftjoin('rs9', 'rs9.rs1', '=', 'rs17.rs14') //sistembayar
            ->with([

                'satset:uuid',
                'satset_error:uuid',
                'datasimpeg:nik,nama,kelamin,kdpegsimrs,kddpjp,satset_uuid',
                'relmpoli' => function ($q) {
                    $q->select('rs1', 'kode_ruang', 'rs7 as nama')->with('ruang:kode,uraian,groupper,satset_uuid,departement_uuid,gedung,lantai,ruang');
                },
                'taskid' => function ($q) {
                    $q->select('noreg', 'taskid', 'waktu', 'created_at')
                        ->orderBy('taskid', 'ASC');
                },

                'anamnesis',
                'pemeriksaanfisik' => function ($a) {
                    $a->with(['detailgambars:rs236_id,noreg,norm,tgl,anatomy,ket,user', 'pemeriksaankhususmata', 'pemeriksaankhususparu'])
                        ->orderBy('id', 'DESC');
                },

                'tindakan' => function ($t) {
                    $t->select('rs73.rs1', 'rs73.rs2', 'rs73.rs3', 'rs73.rs4', 'rs73.rs8', 'rs73.rs9', 'rs30.rs2 as keterangan', 'rs30.rs1 as kode');
                    $t->leftjoin('rs30', 'rs30.rs1', '=', 'rs73.rs4')
                        ->with([
                            'maapingprocedure' => function ($mp) {
                                $mp->select('prosedur_mapping.kdMaster', 'prosedur_mapping.icd9', 'prosedur_master.prosedur')
                                    ->leftjoin('prosedur_master', 'prosedur_master.kd_prosedur', '=', 'prosedur_mapping.icd9');;
                            },
                            // 'maapingprocedure:kdMaster,icd9','maapingprocedure.prosedur:kd_prosedur,prosedure',
                            'maapingsnowmed:kdMaster,kdSnowmed,display',
                            'petugas:nama,kdpegsimrs,satset_uuid'
                        ])
                        ->groupBy('rs73.rs4')
                        ->orderBy('id', 'DESC');
                },
                'diagnosa' => function ($d) {
                    $d->select('rs1', 'rs3', 'rs4', 'rs7', 'rs8');
                    $d->with('masterdiagnosa');
                },
                'planning' => function ($p) {
                    $p->select('rs1', 'rs2', 'rs3', 'rs4', 'rs5', 'tgl', 'user', 'flag');
                    $p->with([
                        'masterpoli:rs1,rs7,rs6,panggil_antrian,displaykode,kode_ruang',
                        'rekomdpjp',
                        'transrujukan',
                        // 'listkonsul:noreg_lama,norm,tgl_kunjungan,tgl_rencana_konsul,kdpoli_asal,kdpoli_tujuan,kddokter_asal,flag',
                        'listkonsul' => function ($lk) {
                            $lk->select('noreg_lama', 'norm', 'tgl_kunjungan', 'tgl_rencana_konsul', 'kdpoli_asal', 'kdpoli_tujuan', 'kddokter_asal', 'flag', 'rs17.rs9 as kdDokterKonsul', 'rs19.kode_ruang')
                                ->leftJoin('rs17', 'rs17.rs4', '=', 'listkonsulanpoli.noreg_lama')
                                ->leftJoin('rs19', 'rs19.rs1', '=', 'listkonsulanpoli.kdpoli_tujuan')
                                ->with('dokterkonsul:kdpegsimrs,nama,satset_uuid', 'lokasikonsul:kode,uraian,satset_uuid');
                        },
                        'spri:noreg,norm,kodeDokter,tglRencanaKontrol,noSuratKontrol,nama,kelamin,user_id',
                        'spri.petugas:nama,kdpegsimrs,satset_uuid',
                        'ranap:rs1,rs2,rs3,rs4,rs5,rs6,rs7,groups,status,hiddens,groups_nama,jenis',
                        'kontrol' => function ($k) {
                            $k->select('noreg', 'norm', 'kodeDokter as kdDokterKontrol', 'poliKontrol', 'tglRencanaKontrol', 'created_at', 'rs19.kode_ruang')
                                ->leftJoin('rs19', 'rs19.rs6', '=', 'bpjs_surat_kontrol.poliKontrol')
                                ->with('dokterkontrol:kddpjp,nama,satset_uuid', 'lokasikontrol:kode,uraian,satset_uuid');
                        },
                        'operasi',
                    ])->orderBy('id', 'DESC');
                },
                // 'diagnosakeperawatan.intervensi.masterintervensi',
                'diagnosakeperawatan' => function ($d) {
                    $d->with('petugas:id,nama,satset_uuid', 'intervensi.masterintervensi');
                },

                // resep keluar fix
                'apotek' => function ($apot) {
                    $apot->whereIn('flag', ['3', '4'])->with([
                        // 'rincian.mobat:kd_obat,nama_obat',
                        // 'rincianracik.mobat:kd_obat,nama_obat',
                        'rincian' => function ($ri) {
                            $ri->select(
                                'resep_keluar_r.kdobat',
                                'resep_keluar_r.noresep',
                                'resep_keluar_r.jumlah',
                                'resep_keluar_r.aturan', // signa
                                'resep_keluar_r.konsumsi', // signa
                                'resep_keluar_r.keterangan', // signa
                                'retur_penjualan_r.jumlah_retur',
                                'signa.jumlah as konsumsi_perhari',
                                DB::raw('
                          CASE
                          WHEN retur_penjualan_r.jumlah_retur IS NOT NULL THEN resep_keluar_r.jumlah - retur_penjualan_r.jumlah_retur
                          ELSE resep_keluar_r.jumlah
                          END as qty
                          ') // iki jumlah obat sing non racikan mas..
                            )
                                ->leftJoin('retur_penjualan_r', function ($jo) {
                                    $jo->on('retur_penjualan_r.kdobat', '=', 'resep_keluar_r.kdobat')
                                        ->on('retur_penjualan_r.noresep', '=', 'resep_keluar_r.noresep');
                                })
                                ->leftJoin('signa', 'signa.signa', '=', 'resep_keluar_r.aturan')
                                ->with([
                                    'mobat.kfa' // sing nang kfa iki jupuk kolom dosage_form karo active_ingredients
                                    // 'mobat:kelompok_psikotropika' // flag obat narkotika, 1 = obat narkotika
                                    // 'mobat:bentuk_sediaan' // bisa dijadikan patoka apakah obat minum, injeksi atau yang lain, cuma perlu di bicarakan dengan farmasi untuk detailnya
                                ]);
                        },
                        'rincianracik' => function ($ri) {
                            $ri->select(
                                'resep_keluar_racikan_r.kdobat',
                                'resep_keluar_racikan_r.noresep',
                                'resep_keluar_racikan_r.jumlah',
                                'resep_keluar_racikan_r.jumlahdibutuhkan as qty', // MedicationRequest.dispenseRequest.quantity dan non-dtd -> Medication.ingredient.strength.denominator
                                'resep_keluar_racikan_r.tiperacikan', // dtd / non-dtd
                                'resep_permintaan_keluar_racikan.dosismaksimum', // dtd -> Medication.ingredient.strength.numerator
                                'resep_permintaan_keluar_racikan.aturan', // signa
                            )
                                ->leftJoin('resep_permintaan_keluar_racikan', function ($jo) {
                                    $jo->on('resep_permintaan_keluar_racikan.kdobat', '=', 'resep_keluar_racikan_r.kdobat')
                                        ->on('resep_permintaan_keluar_racikan.noresep', '=', 'resep_keluar_racikan_r.noresep');
                                })
                                ->with([
                                    'mobat.kfa' // sing nang kfa iki jupuk kolom dosage_form karo active_ingredients
                                    // 'mobat:kelompok_psikotropika' // flag obat narkotika, 1 = obat narkotika
                                    // 'mobat:bentuk_sediaan' // bisa dijadikan patoka apakah obat minum, injeksi atau yang lain, cuma perlu di bicarakan dengan farmasi untuk detailnya
                                ]);
                        }

                    ])
                        ->orderBy('id', 'DESC');
                },




                // ini belum
                'neonatusmedis',
                'neonatuskeperawatan',
                'pediatri',
                'diet'
            ])
            // ->where('rs3', 'LIKE', '%' . $tgl . '%')
            ->whereNotIn('rs17.rs8', $bukanPoli)
            ->where('rs17.rs1', '=', $noreg)
            // ->where('rs17.rs19', '=', '1') // kunjungan selesai


            // ->doesntHave('satset')
            // ->doesntHave('satset_error')
            // ->whereHas('apotek', function (Builder $q) {
            //   // $q->whereHas('rincian');
            //   // //   ->where('riwayatalergi', '!=','');
            // })


            ->orderBy('rs17.rs3', 'desc')
            ->limit(10)
            ->get();


        // $arr = collect($data)->map(function ($x) {
        //   return $x->noreg;
        // });

        // return $arr->toArray();


        return $data;
    }

    public static function rajal($noreg)
    {
        // return $req;

        // $kemarin = Carbon::now()->subDay()->toDateString();


        $data = KunjunganPoli::select(
            'rs17.rs1',
            'rs17.rs9',
            'rs17.rs4',
            'rs17.rs8',
            'rs17.rs1 as noreg',
            'rs17.rs2 as norm',
            'rs17.rs3 as tgl_kunjungan',
            'rs17.rs8 as kodepoli',
            'rs19.rs2 as poli',
            'rs17.rs9 as kodedokter',
            'rs21.rs2 as dokter',
            'rs17.rs14 as kodesistembayar',
            'rs9.rs2 as sistembayar',
            'rs9.groups as groups',
            'rs15.rs2 as nama',
            'rs15.rs49 as nik',
            'rs17.rs19 as status',
            'rs15.satset_uuid as pasien_uuid',
            // 'satsets.uuid as satset',
            // 'satset_error_respon.uuid as satset_error',
        )
            ->leftjoin('rs15', 'rs15.rs1', '=', 'rs17.rs2') //pasien
            ->leftjoin('rs19', 'rs19.rs1', '=', 'rs17.rs8') //poli
            ->leftjoin('rs21', 'rs21.rs1', '=', 'rs17.rs9') //dokter
            ->leftjoin('rs9', 'rs9.rs1', '=', 'rs17.rs14') //sistembayar
            // ->leftjoin('satsets', 'satsets.uuid', '=', 'rs17.rs1') //satset
            // ->leftjoin('satset_error_respon', 'satset_error_respon.uuid', '=', 'rs17.rs1') //satset error

            ->where('rs17.rs1', $noreg)

            // ->whereBetween('rs17.rs3', [$tgl, $tglx])
            // ->where('rs17.rs8', $user->kdruangansim ?? '')
            // ->where('rs17.rs3', 'LIKE', '%' . $kemarin . '%')
            // ->where('rs17.rs8', '!=', 'POL014')
            // ->where('rs17.rs19', '=', '1') // kunjungan selesai

            // ->where('rs19.rs5', '=', '1')
            // ->where('rs19.rs4', '=', 'Poliklinik')
            // ->whereNull('satsets.uuid')

            // ->where(function ($query) {
            //     $query->where('rs15.rs2', 'LIKE', '%' . request('q') . '%') //pasien nama
            //         ->orWhere('rs15.rs46', 'LIKE', '%' . request('q') . '%') //pasien
            //         ->orWhere('rs17.rs2', 'LIKE', '%' . request('q') . '%') //KUNJUNGAN
            //         ->orWhere('rs17.rs1', 'LIKE', '%' . request('q') . '%') //KUNJUNGAN
            //         ->orWhere('rs19.rs2', 'LIKE', '%' . request('q') . '%')
            //         ->orWhere('rs21.rs2', 'LIKE', '%' . request('q') . '%')
            //         // ->orWhere('rs222.rs8', 'LIKE', '%' . request('q') . '%')
            //         ->orWhere('rs9.rs2', 'LIKE', '%' . request('q') . '%');
            // })

            ->with([
                'datasimpeg:nik,nama,kelamin,kdpegsimrs,kddpjp,satset_uuid',
                'relmpoli' => function ($q) {
                    $q->select('rs1', 'kode_ruang', 'rs7 as nama')->with('ruang:kode,uraian,groupper,satset_uuid,departement_uuid');
                },
                //   // 1 (mulai waktu tunggu admisi),
                //   // 2 (akhir waktu tunggu admisi/mulai waktu layan admisi),
                //   // 3 (akhir waktu layan admisi/mulai waktu tunggu poli),
                //   // 4 (akhir waktu tunggu poli/mulai waktu layan poli),
                //   // 5 (akhir waktu layan poli/mulai waktu tunggu farmasi),
                //   // 6 (akhir waktu tunggu farmasi/mulai waktu layan farmasi membuat obat),
                //   // 7 (akhir waktu obat selesai dibuat),
                //   // 99 (tidak hadir/batal)
                'taskid' => function ($q) {
                    $q->select('noreg', 'taskid', 'waktu', 'created_at')
                        ->orderBy('taskid', 'ASC');
                },
                'diagnosa' => function ($d) {
                    $d->select('rs1', 'rs3', 'rs4', 'rs7', 'rs8');
                    $d->with('masterdiagnosa');
                },
                'anamnesis',
                'pemeriksaanfisik' => function ($a) {
                    $a->with(['detailgambars', 'pemeriksaankhususmata', 'pemeriksaankhususparu'])
                        ->orderBy('id', 'DESC');
                },
                'planning' => function ($p) {
                    $p->with(
                        'masterpoli',
                        'rekomdpjp',
                        'transrujukan',
                        'listkonsul',
                        'spri',
                        'ranap',
                        'kontrol',
                        'operasi',
                    )->orderBy('id', 'DESC');
                },
            ])

            //   ->with([
            //     'anamnesis',
            //     'datasimpeg:id,nip,nik,nama,kelamin,foto,kdpegsimrs,kddpjp',
            //     'gambars',
            //     'fisio',
            //     'diagnosakeperawatan' => function ($diag) {
            //         $diag->with('intervensi.masterintervensi');
            //     },
            //     'laborats' => function ($t) {
            //         $t->with('details.pemeriksaanlab')
            //             ->orderBy('id', 'DESC');
            //     },
            //     'radiologi' => function ($t) {
            //         $t->orderBy('id', 'DESC');
            //     },
            //     'penunjanglain' => function ($t) {
            //         $t->with('masterpenunjang')->orderBy('id', 'DESC');
            //     },
            //     'tindakan' => function ($t) {
            //         $t->with('mastertindakan:rs1,rs2', 'pegawai:nama,kdpegsimrs', 'gambardokumens:id,rs73_id,nama,original,url')
            //             ->orderBy('id', 'DESC');
            //     },
            //     'diagnosa' => function ($d) {
            //         $d->with('masterdiagnosa');
            //     },
            //     'pemeriksaanfisik' => function ($a) {
            //         $a->with(['detailgambars', 'pemeriksaankhususmata', 'pemeriksaankhususparu'])
            //             ->orderBy('id', 'DESC');
            //     },
            //     'ok' => function ($q) {
            //         $q->orderBy('id', 'DESC');
            //     },
            //     'taskid' => function ($q) {
            //         $q->orderBy('taskid', 'DESC');
            //     },
            //     'planning' => function ($p) {
            //         $p->with(
            //             'masterpoli',
            //             'rekomdpjp',
            //             'transrujukan',
            //             'listkonsul',
            //             'spri',
            //             'ranap',
            //             'kontrol',
            //             'operasi',
            //         )->orderBy('id', 'DESC');
            //     },
            //     'edukasi' => function ($x) {
            //         $x->orderBy('id', 'DESC');
            //     },
            //     'diet' => function ($diet) {
            //         $diet->orderBy('id', 'DESC');
            //     },
            //     'sharing' => function ($sharing) {
            //         $sharing->orderBy('id', 'DESC');
            //     },
            //     'newapotekrajal' => function ($newapotekrajal) {
            //         $newapotekrajal->with([
            //             'permintaanresep.mobat:kd_obat,nama_obat',
            //             'permintaanracikan.mobat:kd_obat,nama_obat',
            //         ])
            //             ->orderBy('id', 'DESC');
            //     },
            //     'laporantindakan'
            // ])

            // ->orderby('rs17.rs3', 'ASC')
            // ->limit(1)
            // ->get();
            ->first();

        // return $data;
        return self::kirimKunjungan($data);
    }

    public static function getPasienByNikSatset($pasien)
    {
        // return $request->all();
        $nik = $pasien->nik;
        $norm = $pasien->norm;
        // if (!$nik) {
        //     return ['message' => 'failed'];
        // }

        // get data ke satset
        $token = AuthSatsetHelper::accessToken();
        $params = '/Patient?identifier=https://fhir.kemkes.go.id/id/nik|' . $nik;

        $send = BridgingSatsetHelper::get_data($token, $params);

        $data = Pasien::where([
            ['rs49', $nik],
            ['rs1', $norm],
        ])->first();

        if ($send['message'] === 'success') {
            $data->satset_uuid = $send['data']['uuid'];
            $data->save();
        }
        return $send;
    }

    public static function kirimKunjungan($data)
    {

        $pasien_uuid = $data->pasien_uuid;
        if (!$pasien_uuid) {
            $getPasienFromSatset = self::getPasienByNikSatset($data);
            $pasien_uuid = $getPasienFromSatset['data']['uuid'];
        }
        // return $pasien_uuid;
        // if (!$request->datasimpeg) {
        //     return response()->json([
        //         'message' => 'Maaf ... Dokter Ini Belum Ada di Kepegawaian RS'
        //     ], 500);
        // }

        $practitioner = $data->datasimpeg['satset_uuid'];
        $apoteker = $data->apotek ? ($data->apotek['petugas'] ? $data->apotek['petugas']['satset_uuid'] : null) : null;
        // if (!$practitioner) {
        //     return response()->json([
        //         'message' => 'Maaf ... Dokter Ini Belum Terkoneksi Ke Satu Sehat'
        //     ], 500);
        // }
        // $patient = $request->pasien_uuid;
        // if (!$patient) {
        //     return response()->json([
        //         'message' => 'Maaf ... Pasien Ini Belum Terkoneksi Ke Satu Sehat'
        //     ], 500);
        // }
        // $poli = $request->relmpoli;
        // if (!$poli) {
        //     return response()->json([
        //         'message' => 'Maaf ... DATA POLI Belum Ada'
        //     ], 500);
        // }

        // $mappingruang = $poli['ruang'];
        // if (!$mappingruang) {
        //     return response()->json([
        //         'message' => 'Maaf ... Tidak Ada Mapping Ruangan di Poli'
        //     ], 500);
        // }
        // $diagnosa = $request->diagnosa;
        // if (!$diagnosa) {
        //     return response()->json([
        //         'message' => 'Maaf ... Tidak Ada Diagnosa Pada Kunjungan Ini'
        //     ], 500);
        // }
        // return $practitioner;
        // $form = PostKunjunganHelper::form($request);

        // $send = BridgingSatsetHelper::post_bundle($request->token, $form, $request->noreg);
        // return $send;

        $send = PostKunjunganRajalHelper::form($data, $pasien_uuid, $practitioner, $apoteker);
        // if ($send['message'] === 'success') {
        //   $token = AuthSatsetHelper::accessToken();
        //   $send = BridgingSatsetHelper::post_bundle($token, $send['data'], $data->noreg);
        // }
        return $send;
    }


    // KUNJUNGAN RANAP ==========================================================================================================

    public static function cekKunjunganRanap()
    {
        $tgl = Carbon::now()->subDay()->toDateString();
        // $tgl = Carbon::now()->subDays(4)->toDateString();
        // return $tgl;
        $data = Kunjunganranap::select('rs1 as noreg', 'rs4 as tgl_pulang')
            ->where('rs4', 'LIKE', '%' . $tgl . '%')
            ->whereIn('rs22', ['2', '3']) // kunjungan selesai
            ->orderBy('rs3', 'desc')
            ->get();
        // return $data;
        $arr = collect($data)->map(function ($x) {
            return $x->noreg;
        });

        return $arr->toArray();
    }

    public static function ranap($noreg)
    {
        $query = Kunjunganranap::query();

        $select = $query->select(
            'rs23.rs1',
            'rs23.rs1 as noreg',
            'rs23.rs2 as norm',
            'rs23.rs3 as tglmasuk',
            'rs23.rs4 as tglkeluar',
            'rs23.rs5 as kdruangan',
            'rs23.rs5',
            'rs23.rs6 as ketruangan',
            'rs23.rs7 as nomorbed',
            'rs23.rs10 as kddokter',
            'rs23.rs10',
            'rs23.rs27',
            'rs21.rs2 as dokter',
            'rs23.rs19 as kodesistembayar', // ini untuk farmasi
            'rs23.rs22 as status', // '' : BELUM PULANG | '2 ato 3' : PASIEN PULANG
            'rs23.rs38 as hak_kelas',
            'rs15.rs2 as nama_panggil',

            DB::raw('concat(rs15.rs3," ",rs15.gelardepan," ",rs15.rs2," ",rs15.gelarbelakang) as nama'),
            DB::raw('concat(rs15.rs4," KEL ",rs15.rs5," RT ",rs15.rs7," RW ",rs15.rs8," ",rs15.rs6," ",rs15.rs11," ",rs15.rs10) as alamat'),
            DB::raw('concat(TIMESTAMPDIFF(YEAR, rs15.rs16, CURDATE())," Tahun ",
                      TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()) % 12," Bulan ",
                      TIMESTAMPDIFF(DAY, TIMESTAMPADD(MONTH, TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()), rs15.rs16), CURDATE()), " Hari") AS usia'),
            DB::raw("(IF(rs23.rs4='0000-00-00 00:00:00',datediff('" . date("Y-m-d") . "',rs23.rs3),
          datediff(rs23.rs4,rs23.rs3)))+1  as lama"),

            'rs15.rs4 as alamatbarcode',
            'rs15.rs16 as tgllahir',
            'rs15.rs17 as kelamin',
            'rs15.rs19 as pendidikan',
            'rs15.rs22 as agama',
            'rs15.rs37 as templahir',
            'rs15.rs39 as suku',
            'rs15.rs40 as jenispasien',
            'rs15.rs46 as noka',
            'rs15.rs49 as nik',
            'rs15.rs55 as nohp',
            'rs15.satset_uuid as pasien_uuid',
            'rs9.rs2 as sistembayar',
            'rs9.groups as groups',
            'rs21.rs2 as namanakes',
            // 'rs222.rs8 as sep_igd',
            // 'rs227.rs8 as sep',
            // 'rs227.rs10 as faskesawal',
            // 'rs227.kodedokterdpjp as kodedokterdpjp',
            // 'rs227.dokterdpjp as dokterdpjp',
            'rs24.rs2 as ruangan',
            'rs24.rs3 as kelasruangan',
            'rs24.rs5 as group_ruangan',
            // 'rs101.rs3 as kode_diagnosa'
            // 'bpjs_spri.noSuratKontrol as noSpri'
            'rs242.rs4 as tindaklanjut'
        )
            ->leftjoin('rs15', 'rs15.rs1', 'rs23.rs2')
            ->leftjoin('rs9', 'rs9.rs1', 'rs23.rs19')
            ->leftjoin('rs21', 'rs21.rs1', 'rs23.rs10')
            ->leftjoin('rs24', 'rs24.rs1', 'rs23.rs5')
            ->leftjoin('rs242', 'rs242.rs1', 'rs23.rs1') // rencana tindak lanjut
            // ->leftjoin('rs227', 'rs227.rs1', 'rs23.rs1')
            // ->leftjoin('rs222', 'rs222.rs1', 'rs23.rs1')
            // ->leftjoin('rs101', 'rs101.rs1', 'rs23.rs1')
            // ->leftjoin('bpjs_spri', 'rs23.rs1', '=', 'bpjs_spri.noreg')

            // ->with(['sepranap' => function($q) {
            //     $q->select('rs1', 'rs8 as noSep', 'rs3 as ruang', 'rs5 as noRujukan', 'rs7 as diagnosa', 'rs10 as ppkRujukan', 'rs11 as jenisPeserta');
            // }])

            ->where('rs23.rs1', $noreg)

            ->with([
                'diagnosa' => function ($q) {
                    $q->select('rs101.rs1', 'rs101.rs3 as kode', 'rs99x.rs4 as inggris', 'rs99x.rs3 as indonesia', 'rs101.rs4 as type', 'rs101.rs7 as status', 'rs101.rs12 as recordedDate')
                        ->leftjoin('rs99x', 'rs101.rs3', 'rs99x.rs1')
                        ->orderBy('rs101.id', 'asc');
                },
                'datasimpeg:nik,nama,kelamin,kdpegsimrs,kddpjp,satset_uuid',
                'relmasterruangranap' => function ($q) {
                    $q->select('rs1', 'rs2 as nama', 'kode_ruang')->with('ruang:kode,uraian,groupper,gedung,lantai,satset_uuid,departement_uuid');
                }

            ])

            ->where('rs23.rs1', $noreg)
            ->first();

        // return $select;
        return self::kirimKunjunganRanap($select);
    }

    public function kirimKunjunganRanap($data)
    {
        $pasien_uuid = $data->pasien_uuid;
        if (!$pasien_uuid) {
            $getPasienFromSatset = self::getPasienByNikSatset($data);
            $pasien_uuid = $getPasienFromSatset['data']['uuid'];
        }

        $send = PostKunjunganRanapHelper::form($data, $pasien_uuid);
        if ($send['message'] === 'success') {
            $token = AuthSatsetHelper::accessToken();
            $send = BridgingSatsetHelper::post_bundle($token, $send['data'], $data->noreg);
        }
        return $send;
    }


    // KUNJUNGAN IGD ==========================================================================================================================
    public function cekKunjunganIgd()
    {
        $tgl = Carbon::now()->subDay()->toDateString();
        // $tgl = Carbon::now()->subDays(1)->toDateString();
        $data = KunjunganPoli::select('rs1 as noreg')
            ->where('rs3', 'LIKE', '%' . $tgl . '%')
            ->where('rs8', '=', 'POL014')
            ->where('rs19', '=', '1') // kunjungan selesai
            ->orderBy('rs3', 'desc')
            ->get();
        $arr = collect($data)->map(function ($x) {
            return $x->noreg;
        });

        return $arr->toArray();
    }

    public function igd($noreg)
    {
        $data = KunjunganPoli::select(
            'rs17.rs1',
            'rs17.rs9',
            'rs17.rs4',
            'rs17.rs8',
            'rs17.rs1 as noreg',
            'rs17.rs2 as norm',
            'rs17.rs3 as tgl_kunjungan',
            'rs17.rs8 as kodepoli',
            'rs19.rs2 as poli',
            'rs17.rs9 as kodedokter',
            'rs21.rs2 as dokter',
            'rs17.rs14 as kodesistembayar',
            'rs9.rs2 as sistembayar',
            'rs9.groups as groups',
            'rs15.rs2 as nama',
            'rs15.rs49 as nik',
            'rs17.rs19 as status',
            'rs15.satset_uuid as pasien_uuid',
            // 'satsets.uuid as satset',
            // 'satset_error_respon.uuid as satset_error',
        )
            ->leftjoin('rs15', 'rs15.rs1', '=', 'rs17.rs2') //pasien
            ->leftjoin('rs19', 'rs19.rs1', '=', 'rs17.rs8') //poli
            ->leftjoin('rs21', 'rs21.rs1', '=', 'rs17.rs9') //dokter
            ->leftjoin('rs9', 'rs9.rs1', '=', 'rs17.rs14') //sistembayar
            // ->leftjoin('satsets', 'satsets.uuid', '=', 'rs17.rs1') //satset
            // ->leftjoin('satset_error_respon', 'satset_error_respon.uuid', '=', 'rs17.rs1') //satset error

            ->where('rs17.rs1', $noreg)

            ->with([
                'datasimpeg:nik,nama,kelamin,kdpegsimrs,kddpjp,satset_uuid',
                'relmpoli' => function ($q) {
                    $q->select('rs1', 'kode_ruang', 'rs7 as nama')
                        ->with('ruang:kode,uraian,groupper,gedung,lantai,satset_uuid,departement_uuid');
                },
                //   // 1 (mulai waktu tunggu admisi),
                //   // 2 (akhir waktu tunggu admisi/mulai waktu layan admisi),
                //   // 3 (akhir waktu layan admisi/mulai waktu tunggu poli),
                //   // 4 (akhir waktu tunggu poli/mulai waktu layan poli),
                //   // 5 (akhir waktu layan poli/mulai waktu tunggu farmasi),
                //   // 6 (akhir waktu tunggu farmasi/mulai waktu layan farmasi membuat obat),
                //   // 7 (akhir waktu obat selesai dibuat),
                //   // 99 (tidak hadir/batal)
                'taskid' => function ($q) {
                    $q->select('noreg', 'taskid', 'waktu', 'created_at')
                        ->orderBy('taskid', 'ASC');
                },
                'diagnosa' => function ($d) {
                    $d->select('rs1', 'rs3', 'rs4', 'rs7', 'rs8');
                    $d->with('masterdiagnosa');
                },
            ])
            ->first();

        return $data;
        // return self::kirimKunjunganIgd($data);
    }



    // cek medication

    public function medication()
    {
        $token = AuthSatsetHelper::accessToken();
        // $params = '/Practitioner?identifier=https://fhir.kemkes.go.id/id/nik|' . $nik;
        // $params = '/Medication/d4fb621c-02ab-4834-8c6c-70c9df1d8393';
        // $params = '/Medication/44aa339e-0069-47a1-b456-fd8fc7664fbe';
        // $params = '/Medication/977fa8cc-de04-4e81-9b41-2c66e4543441'; // Gabapentin 300 mg Kapsul (DEXA MEDICA)
        // $params = '/Medication/54ec09bc-4cbe-4c6c-9eeb-1eb64450b319'; // Calcium Lactate 500 mg Tablet (MERSIFARMA TIRMAKU MERCUSANA)
        // $params = '/Medication/681be0cb-9870-49cc-b179-89659ff68590'; // Calcium Lactate 500 mg Tablet (MERSIFARMA TIRMAKU MERCUSANA)
        // $params = '/Medication/d17af9df-b938-4e8f-903d-8f8a89a1a851';
        $params = '/Medication/25abe5ff-c604-42b9-be3d-b543080a8d86';

        $send = BridgingSatsetHelper::get_data_nosave($token, $params);
        return $send;
    }
    public function cari_loinc()
    {
        $send = BridgingLoincHelper::getLoincByKode('20977-5');
        return $send;
    }
}
