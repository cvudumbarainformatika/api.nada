<?php

namespace App\Http\Controllers\Api\Simrs\Igd;

use App\Http\Controllers\Api\Simrs\Syarat\CekController;
use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Master\Mpasien;
use App\Models\Simrs\Rajal\Igd\TriageA;
use App\Models\Simrs\Rajal\KunjunganPoli;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IgdController extends Controller
{
    public function kunjunganpasienigd()
    {
        if (request('to') === '' || request('from') === null) {
            $tgl = Carbon::now()->format('Y-m-d 00:00:00');
            $tglx = Carbon::now()->format('Y-m-d 23:59:59');
        } else {
            $tgl = request('to') . ' 00:00:00';
            $tglx = request('from') . ' 23:59:59';
        }
        $status = request('status') ?? '';
        $kunjungan = KunjunganPoli::select(
            'rs17.rs1', // iki tak munculne maneh gawe relasi with
            'rs17.rs1 as noreg',
            'rs17.rs2 as norm',
            'rs17.rs3 as tgl_kunjungan',
            'rs17.rs26 as tglpulang',
            'rs17.rs8 as kodepoli',
            'rs19.rs2 as poli',
            'rs17.rs9 as kodedokter',
            'rs17.rs19 as flagpelayanan',
            'kepegx.pegawai.nama as dokter',
            //'rs21.rs2 as dokter',
            'rs17.rs14 as kodesistembayar',
            'rs9.rs2 as sistembayar',
            'rs9.groups as groups',
            DB::raw('concat(rs15.rs3," ",rs15.gelardepan," ",rs15.rs2," ",rs15.gelarbelakang) as nama'),
            DB::raw('concat(rs15.rs4," KEL ",rs15.rs5," RT ",rs15.rs7," RW ",rs15.rs8," ",rs15.rs6," ",rs15.rs11," ",rs15.rs10) as alamat'),
            DB::raw('concat(TIMESTAMPDIFF(YEAR, rs15.rs16, CURDATE())," Tahun ",
                        TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()) % 12," Bulan ",
                        TIMESTAMPDIFF(DAY, TIMESTAMPADD(MONTH, TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()), rs15.rs16), CURDATE()), " Hari") AS usia'),
            'rs15.rs16 as tgllahir',
            'rs15.rs17 as kelamin',
            'rs15.rs19 as pendidikan',
            'rs15.rs22 as agama',
            'rs15.rs37 as templahir',
            'rs15.rs39 as suku',
            'rs15.rs40 as jenispasien',
            'rs15.rs46 as noka',
            'rs15.rs49 as nktp',
            'rs15.rs55 as nohp',
            'rs15.bahasa as bahasa',
            'rs15.bacatulis as bacatulis',
            'rs15.kdhambatan as kdhambatan',
            'rs15.rs2 as name',
            'rs222.rs8 as sep',
            'gencons.norm as generalconsent',
            'gencons.ttdpasien as ttdpasien',
            'rs17.rs19 as statpasien',
            'rs250.rs16 as kategoritriage',
            'rs250.doa as doa',
            'listkirimcasmixRajal.flaging as kunjungancesmix'
            // 'bpjs_respon_time.taskid as taskid',
            // TIMESTAMPDIFF(DAY, TIMESTAMPADD(MONTH, TIMESTAMPDIFF(MONTH, rs15 . rs16, now()), rs15 . rs16), now(), " Hari ")
        )
            ->leftjoin('rs15', 'rs15.rs1', '=', 'rs17.rs2') //pasien
            ->leftjoin('rs19', 'rs19.rs1', '=', 'rs17.rs8') //poli
            //   ->leftjoin('rs21', 'rs21.rs1', '=', 'rs17.rs9') //dokter
            ->leftjoin('kepegx.pegawai', 'kepegx.pegawai.kdpegsimrs', '=', 'rs17.rs9') //dokter
            ->leftjoin('rs9', 'rs9.rs1', '=', 'rs17.rs14') //sistembayar
            ->leftjoin('rs222', 'rs222.rs1', '=', 'rs17.rs1') //sep
            ->leftjoin('gencons', 'gencons.norm', '=', 'rs17.rs2')
            ->leftjoin('rs250', 'rs250.rs1', '=', 'rs17.rs1')
            ->leftjoin('listkirimcasmixRajal', 'listkirimcasmixRajal.noreg', 'rs17.rs1')
            // ->leftjoin('bpjs_respon_time', 'bpjs_respon_time.noreg', '=', 'rs17.rs1')
            ->addSelect(DB::raw(
                '(SELECT rs26 FROM rs17 WHERE rs17.rs2 = rs15.rs1 AND rs17.rs26 != "0000-00-00 00:00:00"  AND rs17.rs8 = "POL014" ORDER BY rs26 DESC LIMIT 1)
                 as last_visit'
            ))
            ->whereBetween('rs17.rs3', [$tgl, $tglx])
            ->where('rs17.rs8', 'POL014')
            // ->where(function ($q) {
            //     // 'rs9.rs9', '=', request('kdbayar') ?? 'BPJS'
            //     if (request('kdbayar') !== 'ALL') {
            //         $q->where('rs9.rs9', '=', 'BPJS');
            //     }
            // })
            ->where(function ($sts) use ($status) {
                if ($status !== 'all') {
                    if ($status === null) {
                        $sts->where('rs17.rs19', 'null');
                    } else {
                        $sts->where('rs17.rs19', '=', $status);
                    }
                }
            })
            ->where(function ($query) {
                $query->where('rs15.rs2', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs15.rs46', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs17.rs2', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs17.rs1', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs19.rs2', 'LIKE', '%' . request('q') . '%')
                    //   ->orWhere('kepegx.pegawai.nama', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs222.rs8', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs9.rs2', 'LIKE', '%' . request('q') . '%');
            })
            ->with(
                [
                    'generalcons:norm,ttdpasien,ttdpetugas,hubunganpasien',
                    'planheder' => function ($planheder) {
                        $planheder->with([
                            'planranap' => function ($planranap) {
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
                ]
            )
            ->groupBy('rs17.rs1')
            ->orderby('rs17.rs3', 'DESC')
            ->paginate(request('per_page'));

        return new JsonResponse($kunjungan);
    }

    public function terimapasien(Request $request)
    {
        $cekx = KunjunganPoli::select('rs17.rs1', 'rs17.rs2', 'rs17.rs3', 'rs17.rs4', 'rs17.rs8', 'rs17.rs9', 'rs17.rs14', 'rs17.rs19', 'rs17.rs26 as tglpulang', 'rs222.rs8 as sep', 'memodiagnosadokter.diagnosa as memodiagnosa',)
            ->leftjoin('rs222', 'rs222.rs1', '=', 'rs17.rs1')
            ->leftjoin('memodiagnosadokter', 'memodiagnosadokter.noreg', 'rs17.rs1') // memo
            ->where('rs17.rs1', $request->noreg)->where('rs17.rs8', 'POL014')
            ->with([
                'rs35x' => function ($a) {
                    $a->where('rs3', 'A2#');
                },
                'anamnesis' => function ($anamnesis) {
                    $anamnesis->with(['anamnesetambahan', 'anamnesebps', 'anamnesenips', 'datasimpeg'])->where('kdruang', 'POL014');
                },
                'datasimpeg:id,nip,nik,nama,kelamin,foto,kdpegsimrs,kddpjp,ttdpegawai',
                'permintaanperawatanjenazah',
                'triage' => function ($triage) {
                    $triage->select(
                        'rs250.id',
                        'rs250.rs1 as noreg',
                        'rs250.rs1',
                        'rs250.doa',
                        'rs250.rs6 as tanggal',
                        'rs250.rs8 as suhu',
                        'rs250.rs10 as pernapasan',
                        'rs250.rs11 as nadi',
                        'rs250.rs12 as tensi',
                        'rs250.rs13 as bb',
                        'rs250.rs21 as tb',
                        'rs250.rs10 as pernapasanx',
                        'rs250.sistole',
                        'rs250.meninggaldiluarrs',
                        'rs250.barulahirmeninggal',
                        'rs250.diastole',
                        'rs250.kesadarans as kesadaran',
                        'rs250.scorediastole',
                        'rs250.scoresistole',
                        'rs250.scorekesadaran',
                        'rs250.scorelochea',
                        'rs250.scorenadi',
                        'rs250.scorenyeri',
                        'rs250.scorepernapasanx',
                        'rs250.scoreproteinurin',
                        'rs250.scorespo2',
                        'rs250.scoresuhu',
                        'rs250.totalscore',
                        'rs250.rs16 as kategoritriage',
                        'rs250.hasilprimarusurve',
                        'rs250.hasilsecondsurve',
                        'rs251.rs14 as eye',
                        'rs251.rs15 as verbal',
                        'rs251.rs16 as motorik',
                        'rs250.spo2',
                        'rs250.gangguanperilaku',
                        'rs250.falsetriage',
                        'rs251.flaghamil',
                        'rs251.haidterakir as haid',
                        'rs251.gravida',
                        'rs251.partus',
                        'rs251.abortus',
                        'rs251.nyeri',
                        'rs251.lochea',
                        'rs251.proteinurin',
                        'rs251.rs7 as jalannafas',
                        'rs251.rs9 as pernapasan',
                        'rs251.rs19 as sirkulasi',
                        'rs251.rs20 as disability'
                    )->leftjoin('rs251', 'rs250.rs1', 'rs251.rs1')->groupBy('id');
                },
                'penilaiananamnesis' => function ($penilaiananamnesis) {
                    $penilaiananamnesis->select([
                        'id',
                        'rs1',
                        'rs1 as noreg',
                        'rs2 as norm',
                        'rs3 as tgl',
                        'barthel',
                        'norton',
                        'humpty_dumpty',
                        'morse_fall',
                        'ontario',
                        'user',
                        'kdruang',
                        'awal',
                        'group_nakes'
                    ])
                        ->with(['petugas:kdpegsimrs,nik,nama,kdgroupnakes'])->where('kdruang', 'POL014');
                },
                'historyperkawinan',
                'historykehamilan',
                'anamnesekebidanan',
                'transradiologi' => function ($transradiologi) {
                    $transradiologi->with('relmasterpemeriksaan')->where('rs26', 'POL014');
                },
                'bankdarah' => function ($bankdarah) {
                    $bankdarah->where('rs11', 'POL014');
                },
                'bankdarahtrans' => function ($bankdarahtrans) {
                    $bankdarahtrans->where('rs14', 'POL014');
                },
                // 'peresepanobat' => function($peresepanobat){
                //     $peresepanobat->with(
                //         [
                //             'rincian' => function($rincian){
                //                 $rincian->with('mobat');
                //             },
                //         ]
                //     )->whereIn('flag', ['3','4'])
                //     ->where('ruangan','POL014');
                // },
                'msistembayar',
                'planheder' => function ($planheder) {
                    $planheder->with([
                        'planranap' => function ($planranap) {
                            $planranap->with(
                                [
                                    'ruangranap',
                                    'dokumentransfer'
                                ]
                            );
                        },
                        'planrujukan',
                        'planpulang' => function ($planpulang) {
                            $planpulang->with(
                                [
                                    'dokterpenangungjawabpulang' => function ($dokterpenangungjawabpulang) {
                                        $dokterpenangungjawabpulang->select('*')
                                            ->leftjoin('m_golruang', 'm_golruang.kode_gol', 'pegawai.golruang')
                                            ->leftjoin('m_jabatan', 'm_jabatan.kode_jabatan', 'pegawai.jabatan');
                                    }
                                ]
                            );
                        }
                    ]);
                },
                'ambulan' => function ($ambulan) {
                    $ambulan->with(
                        [
                            'tujuan',
                            'perawat',
                            'perawat2'
                        ]
                    )->where('rs5', 'POL014');
                },
                'ambulantrans' => function ($ambulantrans) {
                    $ambulantrans->with(
                        [
                            'tujuan',
                            'perawat',
                            'perawat2'
                        ]
                    )->where('rs20', 'POL014');
                },
                'laborats' => function ($t) {
                    $t->with('details.pemeriksaanlab')->where('unit_pengirim', 'POL014')
                        ->orderBy('id', 'DESC');
                },
                'laboratold' => function ($t) {
                    $t->select('rs51.*', 'rs49.rs2 as pemeriksaan', 'rs49.rs21 as paket')->with(
                        [
                            'pemeriksaanlab',
                            'interpretasi'
                        ]
                    )
                        ->leftjoin('rs49', 'rs49.rs1', 'rs51.rs4')
                        ->orderBy('id', 'DESC')->where('rs51.rs23', 'POL014');
                },
                'radiologi' => function ($t) {
                    $t->where('rs10', 'POL014')->orderBy('id', 'DESC');
                },
                'hasilradiologi' => function ($t) {
                    $t->orderBy('id', 'DESC');
                },
                'penunjanglain' => function ($t) {
                    $t->with('masterpenunjang')->orderBy('id', 'DESC');
                },
                'tindakan' => function ($t) {
                    $t->with('mastertindakan:rs1,rs2', 'pegawai:nama,kdpegsimrs', 'pelaksanalamasimrs:nama,kdpegsimrs', 'gambardokumens:id,rs73_id,nama,original,url', 'mpoli:rs1,rs2')
                        ->orderBy('id', 'DESC')->where('rs22', 'POL014');
                },
                'diagnosa' => function ($d) {
                    $d->with('masterdiagnosa')->where('rs13', 'POL014');
                },
                'pemeriksaanfisik' => function ($a) {
                    $a->with(['detailgambars', 'pemeriksaankhususmata', 'pemeriksaankhususparu'])
                        ->orderBy('id', 'DESC');
                },
                'ok' => function ($q) {
                    $q->where('rs10', 'POL014')->orderBy('id', 'DESC');
                },
                'oktrans' => function ($o) {
                    $o->with('mastertindakanoperasi')->where('rs15', 'POL014')->orderBy('id', 'DESC');
                },
                'diagnosakeperawatan' => function ($d) {
                    $d->with('petugas:id,nama,satset_uuid', 'intervensi.masterintervensi');
                },
                'diagnosakebidanan' => function ($diag) {
                    $diag->with('intervensi.masterintervensi');
                },
                'pemeriksaanfisikpsikologidll' => function ($pemeriksaanfisikpsikologidll) {
                    $pemeriksaanfisikpsikologidll->select('rs253.*', 'kepegx.pegawai.kdpegsimrs', 'kepegx.pegawai.nama')->leftjoin('kepegx.pegawai', 'kepegx.pegawai.kdpegsimrs', '=', 'rs253.user')
                        ->with('pemerisaanpsikologidll', 'datasimpeg')->where('kdruang', 'POL014');
                },
                'newapotekrajal' => function ($newapotekrajal) {
                    $newapotekrajal->with([
                        'permintaanresep.mobat:kd_obat,nama_obat',
                        'permintaanracikan.mobat:kd_obat,nama_obat',
                        'rincian.mobat:kd_obat,nama_obat',
                        'rincianracik.mobat:kd_obat,nama_obat'
                    ])->where('ruangan', 'POL014')
                        ->orderBy('id', 'DESC');
                },
                'newapotekrajalretur' => function ($newapotekrajalretur) {
                    $newapotekrajalretur->with([
                        'rinci.mobatnew:kd_obat,nama_obat',
                    ])->where('kdruangan', 'POL014')
                        ->orderBy('id', 'DESC');
                },
                'tinjauanulang' => function ($tinjauanulang) {
                    $tinjauanulang->select('peninjauan_ulang_igd.*', 'kepegx.pegawai.nama')->with([
                        'tinjauanulangnips',
                        'tinjauanulangbps'
                    ])->leftjoin('kepegx.pegawai', 'kepegx.pegawai.kdpegsimrs', 'peninjauan_ulang_igd.user');
                },
                // 'konsuldokterspesialis' => function ($konsuldokterspesialis){
                //     $konsuldokterspesialis->with(
                //         [
                //             'tindakan' => function($tindakans){
                //                 $tindakans->with(
                //                     [
                //                         'ruangranap',
                //                         'dokumentransfer'
                //                     ]
                //                 );
                //             },
                //             'planrujukan',
                //             'planpulang' => function ($planpulang) {
                //                 $planpulang->with(
                //                     [
                //                         'dokterpenangungjawabpulang' => function ($dokterpenangungjawabpulang) {
                //                             $dokterpenangungjawabpulang->select('*')
                //                                 ->leftjoin('m_golruang', 'm_golruang.kode_gol', 'pegawai.golruang')
                //                                 ->leftjoin('m_jabatan', 'm_jabatan.kode_jabatan', 'pegawai.jabatan');
                //                         }
                //                     ]
                //                 );
                //             }
                //         ]);
                //     },
                'ambulan' => function ($ambulan) {
                    $ambulan->with(
                        [
                            'tujuan',
                            'perawat',
                            'perawat2'
                        ]
                    )->where('rs5', 'POL014');
                },
                'ambulantrans' => function ($ambulantrans) {
                    $ambulantrans->with(
                        [
                            'tujuan',
                            'perawat',
                            'perawat2'
                        ]
                    )->where('rs20', 'POL014');
                },
                'laborats' => function ($t) {
                    $t->with('details.pemeriksaanlab')->where('unit_pengirim', 'POL014')
                        ->orderBy('id', 'DESC');
                },
                'laboratold' => function ($t) {
                    $t->select('rs51.*', 'rs49.rs2 as pemeriksaan', 'rs49.rs21 as paket')->with('pemeriksaanlab')
                        ->leftjoin('rs49', 'rs49.rs1', 'rs51.rs4')
                        ->orderBy('id', 'DESC')->where('rs51.rs23', 'POL014');
                },
                'radiologi' => function ($t) {
                    $t->where('rs10', 'POL014')->orderBy('id', 'DESC');
                },
                'hasilradiologi' => function ($t) {
                    $t->orderBy('id', 'DESC');
                },
                'penunjanglain' => function ($t) {
                    $t->with('masterpenunjang')->orderBy('id', 'DESC');
                },
                'tindakan' => function ($t) {
                    $t->with('mastertindakan:rs1,rs2', 'pegawai:nama,kdpegsimrs', 'pelaksanalamasimrs:nama,kdpegsimrs', 'gambardokumens:id,rs73_id,nama,original,url', 'mpoli:rs1,rs2')
                        ->orderBy('id', 'DESC')->where('rs22', 'POL014');
                },
                'diagnosa' => function ($d) {
                    $d->with('masterdiagnosa')->where('rs13', 'POL014');
                },
                'pemeriksaanfisik' => function ($a) {
                    $a->with(['detailgambars', 'pemeriksaankhususmata', 'pemeriksaankhususparu'])
                        ->orderBy('id', 'DESC');
                },
                'ok' => function ($q) {
                    $q->where('rs10', 'POL014')->orderBy('id', 'DESC');
                },
                'oktrans' => function ($o) {
                    $o->with('mastertindakanoperasi')->where('rs15', 'POL014')->orderBy('id', 'DESC');
                },
                'diagnosakeperawatan' => function ($d) {
                    $d->with('petugas:id,nama,satset_uuid', 'intervensi.masterintervensi');
                },
                'diagnosakebidanan' => function ($diag) {
                    $diag->with('intervensi.masterintervensi');
                },
                'pemeriksaanfisikpsikologidll' => function ($pemeriksaanfisikpsikologidll) {
                    $pemeriksaanfisikpsikologidll->select('rs253.*', 'kepegx.pegawai.kdpegsimrs', 'kepegx.pegawai.nama')->leftjoin('kepegx.pegawai', 'kepegx.pegawai.kdpegsimrs', '=', 'rs253.user')
                        ->with('pemerisaanpsikologidll', 'datasimpeg')->where('kdruang', 'POL014');
                },
                'newapotekrajal' => function ($newapotekrajal) {
                    $newapotekrajal->with([
                        'permintaanresep.mobat:kd_obat,nama_obat',
                        'permintaanracikan.mobat:kd_obat,nama_obat',
                        'rincian.mobat:kd_obat,nama_obat',
                        'rincianracik.mobat:kd_obat,nama_obat'
                    ])->where('ruangan', 'POL014')
                        ->orderBy('id', 'DESC');
                },
                'newapotekrajalretur' => function ($newapotekrajalretur) {
                    $newapotekrajalretur->with([
                        'rinci.mobatnew:kd_obat,nama_obat',
                    ])->where('kdruangan', 'POL014')
                        ->orderBy('id', 'DESC');
                },
                'tinjauanulang' => function ($tinjauanulang) {
                    $tinjauanulang->select('peninjauan_ulang_igd.*', 'kepegx.pegawai.nama')->with([
                        'tinjauanulangnips',
                        'tinjauanulangbps'
                    ])->leftjoin('kepegx.pegawai', 'kepegx.pegawai.kdpegsimrs', 'peninjauan_ulang_igd.user');
                },
                'konsuldokterspesialis' => function ($konsuldokterspesialis) {
                    $konsuldokterspesialis->with(
                        [
                            'tindakan' => function ($tindakans) {
                                $tindakans->with(
                                    [
                                        'mastertindakan'
                                    ]
                                );
                            },
                            'nakesminta',
                            'dokterkonsul'
                        ]
                    )->where('kdruang', 'POL014');
                },
                'skalatransfer',
                'dokumenluar' => function ($neo) {
                    $neo->with(['pegawai:id,nama']);
                },
                'pemberianobat' => function ($pemberianobat) {
                    $pemberianobat->with(
                        [
                            'mobat',
                            'datasimpeg'
                        ]
                    );
                },
                'rencanaterapidokter',
                'kamarjenazah' => function ($kamarjenazah) {
                    $kamarjenazah->with('pelayananjenazah')->where('rs14', 'POL014');
                },
                'manymemo'
            ])
            ->first();

        if ($cekx) {
            $flag = $cekx->rs19;

            if ($flag === '') {
                $cekx->rs19 = '2';
                $cekx->save();
            }

            return new JsonResponse($cekx, 200);
        } else {
            return response()->json([
                'message' => 'Data tidak ditemukan'
            ], 500);
        }
    }

    public function flagfinish(Request $request)
    {
        $input = new Request([
            'noreg' => $request->noreg
        ]);

        // $cekidentitas = Mpasien::where('rs1', $request->norm)->first();
        // if($cekidentitas->rs49 === '' || $cekidentitas->rs49 === null){
        //     return new JsonResponse(['message' => 'Maaf Identitas Pasien Belum Lengkap, Hubungi Pendaftaran Pasien Untuk Melengkapi Identias Pasien...!!!'], 500);
        // }


        // jika pasien dengan kondisi khusus tanpa pengecekan nip
        $kondisikhusus = $request->meninggaldiluarrs === 'Iya' || $request->barulahirmeninggal === 'Iya';


        if (!$kondisikhusus) {
            $cekidentitas = CekController::ceknoktp($request->norm);

            if ($cekidentitas === '1') {
                return new JsonResponse(['message' => 'Maaf Identitas Pasien Belum Lengkap, Hubungi Pendaftaran Pasien Untuk Melengkapi Identias Pasien...!!!'], 500);
            }
            $cekplan = CekController::cekplan($request->noreg);
            if ($cekplan === '1') {
                return new JsonResponse(['message' => 'Maaf Form Planing Belum Diisi...!!!'], 500);
            }
        }


        $user = Pegawai::find(auth()->user()->pegawai_id);
        if ($user->kdgroupnakes === 1 || $user->kdgroupnakes === '1') {
            $updatekunjungan = KunjunganPoli::where('rs1', $request->noreg)->where('rs8', 'POL014')->first();
            $updatekunjungan->rs19 = '1';
            $updatekunjungan->rs26 = date('Y-m-d H:i:s');
            $updatekunjungan->save();
            return new JsonResponse(['message' => 'ok'], 200);
        } else {
            return new JsonResponse(['message' => 'MAAF FITUR INI HANYA UNTUK DOKTER...!!!'], 500);
        }
    }

    public function updatesistembayar(Request $request)
    {
        $updatekunjungan = KunjunganPoli::where('rs1', $request->noreg)->where('rs8', 'POL014')->first();
        $updatekunjungan->rs14 = $request->kodesistembayar;
        $updatekunjungan->save();
        return new JsonResponse(
            [
                'message' => 'ok',
                'result' => $request->namasistembayar
            ],
            200
        );
    }
}
