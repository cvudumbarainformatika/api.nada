<?php

namespace App\Http\Controllers\Api\Simrs\Historypasien;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mpasien;
use App\Models\Simrs\Rajal\KunjunganPoli;
use App\Models\Simrs\Ranap\Kunjunganranap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HistorypasienfullController extends Controller
{
    public function historypasienfullAwal()
    {
        $norm = request('norm');
        $historyx = KunjunganPoli::select(
            'rs17.rs1',
            'rs17.rs1 as noreg',
            'rs17.rs2 as norm',
            'rs17.rs3 as tanggal',
            'rs19.rs2 as ruangan',
            'rs21.rs2 as dpjp',
            'memodiagnosadokter.diagnosa as memo'

        )
            ->join('rs19', 'rs19.rs1', '=', 'rs17.rs8')
            ->join('rs21', 'rs21.rs1', '=', 'rs17.rs9')
            ->leftjoin('memodiagnosadokter', 'memodiagnosadokter.noreg', '=', 'rs17.rs1')
            ->where('rs17.rs2', $norm);
        $history = Kunjunganranap::select(
            'rs23.rs1',
            'rs23.rs1 as noreg',
            'rs23.rs2 as norm',
            'rs23.rs3 as tanggal',
            'rs24.rs2 as ruangan',
            'rs21.rs2 as dpjp',
            'memodiagnosadokter.diagnosa as memo'
        )
            ->join('rs24', 'rs24.rs1', '=', 'rs23.rs5')
            ->join('rs21', 'rs21.rs1', '=', 'rs23.rs10')
            ->leftjoin('memodiagnosadokter', 'memodiagnosadokter.noreg', '=', 'rs23.rs1')
            ->where('rs23.rs2', $norm)
            ->unionAll($historyx) // ganti jadi unioin all
            ->with(
                [
                    'anamnesis',
                    'pemeriksaanfisik' => function ($p) {
                        $p->with(['gambars', 'detailgambars', 'pemeriksaankhususmata', 'pemeriksaankhususparu'])
                            ->orderBy('id', 'DESC');
                    },
                    'diagnosa' => function ($a) {
                        $a->with(['masterdiagnosa'])
                            ->orderBy('id', 'DESC');
                    },
                    //    'diagnosa.masterdiagnosa:rs1,rs4',
                    'tindakan' => function ($t) {
                        $t->with('mastertindakan:rs1,rs2', 'pegawai:nama,kdpegsimrs', 'pelaksanalamasimrs:nama,kdpegsimrs', 'gambardokumens:id,rs73_id,nama,original,url', 'sambungan:rs73_id,ket')
                            ->orderBy('id', 'DESC');
                    },
                    //    'tindakan.mastertindakan:rs1,rs2',
                    'laborat' => function ($a) {
                        $a->with(['pemeriksaanlab'])
                            ->orderBy('id', 'DESC');
                    },
                    //    'laborat.pemeriksaanlab:rs1,rs2,rs21,nilainormal,satuan',
                    'laborats',
                    'transradiologi:rs1,rs4',
                    'transradiologi.relmasterpemeriksaan:rs1,rs2,rs3,kdmeta',
                    'hasilradiologi',
                    'apotekranap',
                    'apotekranap.masterobat',
                    'apotekranaplalu',
                    'apotekranaplalu.masterobat',
                    'apotekranapracikanheder',
                    'apotekranapracikanheder.apotekranapracikanrinci',
                    'apotekranapracikanheder.apotekranapracikanrinci.masterobat',
                    'apotekranapracikanhederlalu',
                    'apotekranapracikanhederlalu.apotekranapracikanrincilalu',
                    'apotekranapracikanhederlalu.apotekranapracikanrincilalu.masterobat',
                    'apotekrajal',
                    'apotekrajal.masterobat',
                    'apotekrajalpolilalu.masterobat',
                    'apotekracikanrajal',
                    'apotekracikanrajal.masterobat',
                    'apotekracikanrajallalu',
                    'apotekracikanrajallalu.masterobat',
                    'dokumenluar' => function ($a) {
                        $a->with(['pegawai:id,nama']);
                    },
                    'kamaroperasi' => function ($kamaroperasi) {
                        $kamaroperasi->with(['mastertindakanoperasi']);
                    },
                    'praanastesi'
                ]
            )
            ->orderby('tanggal', 'DESC')
            ->get();
        //->paginate(request('per_page'));
        return new JsonResponse(['data' => $history]);
    }
    public function historypasienfull()
    {
        $norm = request('norm');
        $historyx = KunjunganPoli::select(
            'rs17.rs1',
            'rs17.rs1 as noreg',
            'rs17.rs2 as norm',
            'rs17.rs3 as tanggal',
            'rs19.rs2 as ruangan',
            'rs21.rs2 as dpjp',
            'memodiagnosadokter.diagnosa as memo'

        )
            ->join('rs19', 'rs19.rs1', '=', 'rs17.rs8')
            ->join('rs21', 'rs21.rs1', '=', 'rs17.rs9')
            ->leftjoin('memodiagnosadokter', 'memodiagnosadokter.noreg', '=', 'rs17.rs1')
            ->where('rs17.rs2', $norm)
            // ->with(
            //     [
            //         'anamnesis',
            //         'pemeriksaanfisik' => function ($p) {
            //             $p->with(['gambars', 'detailgambars', 'pemeriksaankhususmata', 'pemeriksaankhususparu'])
            //                 ->orderBy('id', 'DESC');
            //         },
            //         'diagnosa' => function ($a) {
            //             $a->with(['masterdiagnosa'])
            //                 ->orderBy('id', 'DESC');
            //         },
            //         //    'diagnosa.masterdiagnosa:rs1,rs4',
            //         'tindakan' => function ($t) {
            //             $t->with('mastertindakan:rs1,rs2', 'pegawai:nama,kdpegsimrs', 'pelaksanalamasimrs:nama,kdpegsimrs', 'gambardokumens:id,rs73_id,nama,original,url', 'sambungan:rs73_id,ket')
            //                 ->orderBy('id', 'DESC');
            //         },
            //         //    'tindakan.mastertindakan:rs1,rs2',
            //         'laborat' => function ($a) {
            //             $a->with(['pemeriksaanlab'])
            //                 ->orderBy('id', 'DESC');
            //         },
            //         //    'laborat.pemeriksaanlab:rs1,rs2,rs21,nilainormal,satuan',
            //         'laborats',
            //         'transradiologi:rs1,rs4',
            //         'transradiologi.relmasterpemeriksaan:rs1,rs2,rs3,kdmeta',
            //         'hasilradiologi',
            //         // 'apotekranap',
            //         // 'apotekranap.masterobat',
            //         // 'apotekranaplalu',
            //         // 'apotekranaplalu.masterobat',
            //         // 'apotekranapracikanheder',
            //         // 'apotekranapracikanheder.apotekranapracikanrinci',
            //         // 'apotekranapracikanheder.apotekranapracikanrinci.masterobat',
            //         // 'apotekranapracikanhederlalu',
            //         // 'apotekranapracikanhederlalu.apotekranapracikanrincilalu',
            //         // 'apotekranapracikanhederlalu.apotekranapracikanrincilalu.masterobat',
            //         'apotekrajal',
            //         'apotekrajal.masterobat',
            //         'apotekrajalpolilalu.masterobat',
            //         'apotekracikanrajal',
            //         'apotekracikanrajal.masterobat',
            //         'apotekracikanrajallalu',
            //         'apotekracikanrajallalu.masterobat',
            //         'dokumenluar' => function ($a) {
            //             $a->with(['pegawai:id,nama']);
            //         },
            //         'kamaroperasi' => function ($kamaroperasi) {
            //             $kamaroperasi->with(['mastertindakanoperasi']);
            //         },
            //         'praanastesi'
            //     ]
            // )
        ;


        $history = Kunjunganranap::select(
            'rs23.rs1',
            'rs23.rs1 as noreg',
            'rs23.rs2 as norm',
            'rs23.rs3 as tanggal',
            'rs24.rs2 as ruangan',
            'rs21.rs2 as dpjp',
            'memodiagnosadokter.diagnosa as memo'
        )
            ->join('rs24', 'rs24.rs1', '=', 'rs23.rs5')
            ->join('rs21', 'rs21.rs1', '=', 'rs23.rs10')
            ->leftjoin('memodiagnosadokter', 'memodiagnosadokter.noreg', '=', 'rs23.rs1')
            ->where('rs23.rs2', $norm)
            // ->with(
            //     [
            //         'anamnesis',
            //         'pemeriksaanfisik' => function ($p) {
            //             $p->with(['gambars', 'detailgambars', 'pemeriksaankhususmata', 'pemeriksaankhususparu'])
            //                 ->orderBy('id', 'DESC');
            //         },
            //         'diagnosa' => function ($a) {
            //             $a->with(['masterdiagnosa'])
            //                 ->orderBy('id', 'DESC');
            //         },
            //         //    'diagnosa.masterdiagnosa:rs1,rs4',
            //         'tindakan' => function ($t) {
            //             $t->with('mastertindakan:rs1,rs2', 'pegawai:nama,kdpegsimrs', 'pelaksanalamasimrs:nama,kdpegsimrs', 'gambardokumens:id,rs73_id,nama,original,url', 'sambungan:rs73_id,ket')
            //                 ->orderBy('id', 'DESC');
            //         },
            //         //    'tindakan.mastertindakan:rs1,rs2',
            //         'laborat' => function ($a) {
            //             $a->with(['pemeriksaanlab'])
            //                 ->orderBy('id', 'DESC');
            //         },
            //         //    'laborat.pemeriksaanlab:rs1,rs2,rs21,nilainormal,satuan',
            //         'laborats',
            //         'transradiologi:rs1,rs4',
            //         'transradiologi.relmasterpemeriksaan:rs1,rs2,rs3,kdmeta',
            //         'hasilradiologi',
            //         'apotekranap',
            //         'apotekranap.masterobat',
            //         'apotekranaplalu',
            //         'apotekranaplalu.masterobat',
            //         'apotekranapracikanheder',
            //         'apotekranapracikanheder.apotekranapracikanrinci',
            //         'apotekranapracikanheder.apotekranapracikanrinci.masterobat',
            //         'apotekranapracikanhederlalu',
            //         'apotekranapracikanhederlalu.apotekranapracikanrincilalu',
            //         'apotekranapracikanhederlalu.apotekranapracikanrincilalu.masterobat',
            //         // 'apotekrajal',
            //         // 'apotekrajal.masterobat',
            //         // 'apotekrajalpolilalu.masterobat',
            //         // 'apotekracikanrajal',
            //         // 'apotekracikanrajal.masterobat',
            //         // 'apotekracikanrajallalu',
            //         // 'apotekracikanrajallalu.masterobat',
            //         'dokumenluar' => function ($a) {
            //             $a->with(['pegawai:id,nama']);
            //         },
            //         'kamaroperasi' => function ($kamaroperasi) {
            //             $kamaroperasi->with(['mastertindakanoperasi']);
            //         },
            //         'praanastesi'
            //     ]
            // )
        ;

        $rawQuery = $history->unionAll($historyx);
        // Lanjutkan dengan eager load setelah di-wrap dalam query builder
        $final = DB::table(DB::raw("({$rawQuery->toSql()}) as sub"))
            ->mergeBindings($rawQuery->getQuery()) // penting!
            ->orderBy('tanggal', 'DESC')
            ->get();
        // // ->orderby('tanggal', 'DESC')
        // ->get();
        //->paginate(request('per_page'));
        // $historyxData = $historyx->get();
        // $historyData = $history->get();

        // $merged = $historyxData->merge($historyData)->sortByDesc('tanggal')->values();
        // $rawQuery = $history->unionAll($historyx);

        // $results = DB::table(DB::raw("({$rawQuery->toSql()}) as sub"))
        //     ->mergeBindings($rawQuery->getQuery())
        //     ->orderBy('tanggal', 'DESC')
        //     ->pluck('noreg'); // ambil daftar noreg

        // // Ambil ulang data Eloquent berdasarkan daftar noreg
        // $kunjunganPoli = KunjunganPoli::whereIn('rs1', $results)->with(
        //     [
        //         'anamnesis',
        //         'pemeriksaanfisik' => function ($p) {
        //             $p->with(['gambars', 'detailgambars', 'pemeriksaankhususmata', 'pemeriksaankhususparu'])
        //                 ->orderBy('id', 'DESC');
        //         },
        //         'diagnosa' => function ($a) {
        //             $a->with(['masterdiagnosa'])
        //                 ->orderBy('id', 'DESC');
        //         },
        //         //    'diagnosa.masterdiagnosa:rs1,rs4',
        //         'tindakan' => function ($t) {
        //             $t->with('mastertindakan:rs1,rs2', 'pegawai:nama,kdpegsimrs', 'pelaksanalamasimrs:nama,kdpegsimrs', 'gambardokumens:id,rs73_id,nama,original,url', 'sambungan:rs73_id,ket')
        //                 ->orderBy('id', 'DESC');
        //         },
        //         //    'tindakan.mastertindakan:rs1,rs2',
        //         'laborat' => function ($a) {
        //             $a->with(['pemeriksaanlab'])
        //                 ->orderBy('id', 'DESC');
        //         },
        //         //    'laborat.pemeriksaanlab:rs1,rs2,rs21,nilainormal,satuan',
        //         'laborats',
        //         'transradiologi:rs1,rs4',
        //         'transradiologi.relmasterpemeriksaan:rs1,rs2,rs3,kdmeta',
        //         'hasilradiologi',
        //         // 'apotekranap',
        //         // 'apotekranap.masterobat',
        //         // 'apotekranaplalu',
        //         // 'apotekranaplalu.masterobat',
        //         // 'apotekranapracikanheder',
        //         // 'apotekranapracikanheder.apotekranapracikanrinci',
        //         // 'apotekranapracikanheder.apotekranapracikanrinci.masterobat',
        //         // 'apotekranapracikanhederlalu',
        //         // 'apotekranapracikanhederlalu.apotekranapracikanrincilalu',
        //         // 'apotekranapracikanhederlalu.apotekranapracikanrincilalu.masterobat',
        //         'apotekrajal',
        //         'apotekrajal.masterobat',
        //         'apotekrajalpolilalu.masterobat',
        //         'apotekracikanrajal',
        //         'apotekracikanrajal.masterobat',
        //         'apotekracikanrajallalu',
        //         'apotekracikanrajallalu.masterobat',
        //         'dokumenluar' => function ($a) {
        //             $a->with(['pegawai:id,nama']);
        //         },
        //         'kamaroperasi' => function ($kamaroperasi) {
        //             $kamaroperasi->with(['mastertindakanoperasi']);
        //         },
        //         'praanastesi'
        //     ]
        // )->get();
        // $kunjunganRanap = Kunjunganranap::whereIn('rs1', $results)->with(
        //     [
        //         'anamnesis',
        //         'pemeriksaanfisik' => function ($p) {
        //             $p->with(['gambars', 'detailgambars', 'pemeriksaankhususmata', 'pemeriksaankhususparu'])
        //                 ->orderBy('id', 'DESC');
        //         },
        //         'diagnosa' => function ($a) {
        //             $a->with(['masterdiagnosa'])
        //                 ->orderBy('id', 'DESC');
        //         },
        //         //    'diagnosa.masterdiagnosa:rs1,rs4',
        //         'tindakan' => function ($t) {
        //             $t->with('mastertindakan:rs1,rs2', 'pegawai:nama,kdpegsimrs', 'pelaksanalamasimrs:nama,kdpegsimrs', 'gambardokumens:id,rs73_id,nama,original,url', 'sambungan:rs73_id,ket')
        //                 ->orderBy('id', 'DESC');
        //         },
        //         //    'tindakan.mastertindakan:rs1,rs2',
        //         'laborat' => function ($a) {
        //             $a->with(['pemeriksaanlab'])
        //                 ->orderBy('id', 'DESC');
        //         },
        //         //    'laborat.pemeriksaanlab:rs1,rs2,rs21,nilainormal,satuan',
        //         'laborats',
        //         'transradiologi:rs1,rs4',
        //         'transradiologi.relmasterpemeriksaan:rs1,rs2,rs3,kdmeta',
        //         'hasilradiologi',
        //         'apotekranap',
        //         'apotekranap.masterobat',
        //         'apotekranaplalu',
        //         'apotekranaplalu.masterobat',
        //         'apotekranapracikanheder',
        //         'apotekranapracikanheder.apotekranapracikanrinci',
        //         'apotekranapracikanheder.apotekranapracikanrinci.masterobat',
        //         'apotekranapracikanhederlalu',
        //         'apotekranapracikanhederlalu.apotekranapracikanrincilalu',
        //         'apotekranapracikanhederlalu.apotekranapracikanrincilalu.masterobat',
        //         // 'apotekrajal',
        //         // 'apotekrajal.masterobat',
        //         // 'apotekrajalpolilalu.masterobat',
        //         // 'apotekracikanrajal',
        //         // 'apotekracikanrajal.masterobat',
        //         // 'apotekracikanrajallalu',
        //         // 'apotekracikanrajallalu.masterobat',
        //         'dokumenluar' => function ($a) {
        //             $a->with(['pegawai:id,nama']);
        //         },
        //         'kamaroperasi' => function ($kamaroperasi) {
        //             $kamaroperasi->with(['mastertindakanoperasi']);
        //         },
        //         'praanastesi'
        //     ]
        // )->get();

        // // Gabungkan dan urutkan
        // $final = $kunjunganPoli->merge($kunjunganRanap)->sortByDesc('tanggal')->values();

        $opoo = new JsonResponse(['data' => $final], 200);
        return $opoo;
    }
    public function getDetailHistory()
    {


        $data = null;
        $noreg = request('noreg');
        $layanan = request('layanan');
        if ($layanan == 'Rawat Inap') {
            $data = Kunjunganranap::select(
                'rs23.rs1',
                'rs23.rs1 as noreg',
                'rs23.rs2 as norm',
                'rs23.rs3 as tanggal',
                'rs24.rs2 as ruangan',
                'rs21.rs2 as dpjp',
                'memodiagnosadokter.diagnosa as memo'
            )
                ->join('rs24', 'rs24.rs1', '=', 'rs23.rs5')
                ->join('rs21', 'rs21.rs1', '=', 'rs23.rs10')
                ->leftjoin('memodiagnosadokter', 'memodiagnosadokter.noreg', '=', 'rs23.rs1')
                ->where('rs23.rs1', $noreg)
                ->with(
                    [
                        'anamnesis',
                        'pemeriksaanfisik' => function ($p) {
                            $p->with(['gambars', 'detailgambars', 'pemeriksaankhususmata', 'pemeriksaankhususparu'])
                                ->orderBy('id', 'DESC');
                        },
                        'diagnosa' => function ($a) {
                            $a->with(['masterdiagnosa'])
                                ->orderBy('id', 'DESC');
                        },
                        //    'diagnosa.masterdiagnosa:rs1,rs4',
                        'tindakan' => function ($t) {
                            $t->with('mastertindakan:rs1,rs2', 'pegawai:nama,kdpegsimrs', 'pelaksanalamasimrs:nama,kdpegsimrs', 'gambardokumens:id,rs73_id,nama,original,url', 'sambungan:rs73_id,ket')
                                ->orderBy('id', 'DESC');
                        },
                        //    'tindakan.mastertindakan:rs1,rs2',
                        'laborat' => function ($a) {
                            $a->with(['pemeriksaanlab'])
                                ->orderBy('id', 'DESC');
                        },
                        //    'laborat.pemeriksaanlab:rs1,rs2,rs21,nilainormal,satuan',
                        'laborats',
                        'transradiologi:rs1,rs4',
                        'transradiologi.relmasterpemeriksaan:rs1,rs2,rs3,kdmeta',
                        'hasilradiologi',
                        'apotekranap',
                        'apotekranap.masterobat',
                        'apotekranaplalu',
                        'apotekranaplalu.masterobat',
                        'apotekranapracikanheder',
                        'apotekranapracikanheder.apotekranapracikanrinci',
                        'apotekranapracikanheder.apotekranapracikanrinci.masterobat',
                        'apotekranapracikanhederlalu',
                        'apotekranapracikanhederlalu.apotekranapracikanrincilalu',
                        'apotekranapracikanhederlalu.apotekranapracikanrincilalu.masterobat',
                        // 'apotekrajal',
                        // 'apotekrajal.masterobat',
                        // 'apotekrajalpolilalu.masterobat',
                        // 'apotekracikanrajal',
                        // 'apotekracikanrajal.masterobat',
                        // 'apotekracikanrajallalu',
                        // 'apotekracikanrajallalu.masterobat',
                        'dokumenluar' => function ($a) {
                            $a->with(['pegawai:id,nama']);
                        },
                        'kamaroperasi' => function ($kamaroperasi) {
                            $kamaroperasi->with(['mastertindakanoperasi']);
                        },
                        'praanastesi',
                        'newapotekrajal' => function ($apt) {
                            $apt->with(
                                [
                                    'rincian.mobat:kd_obat,nama_obat,satuan_k,status_kronis',
                                    'rincianracik.mobat:kd_obat,nama_obat,satuan_k,status_kronis',
                                    'permintaanresep.mobat:kd_obat,nama_obat,satuan_k,status_kronis',
                                    'permintaanresep.aturansigna:signa,jumlah',
                                    'permintaanracikan.mobat:kd_obat,nama_obat,satuan_k,kekuatan_dosis,status_kronis,kelompok_psikotropika',
                                    'poli',
                                    'info',
                                    'ruanganranap',
                                    'sistembayar',
                                    'sep:rs1,rs8',
                                    'dokter:kdpegsimrs,nama',
                                    'datapasien' => function ($quer) {
                                        $quer->select(
                                            'rs1',
                                            'rs2 as nama',
                                            'rs46 as noka',
                                            'rs16 as tgllahir',
                                            DB::raw('concat(rs4," KEL ",rs5," RT ",rs7," RW ",rs8," ",rs6," ",rs11," ",rs10) as alamat'),
                                        );
                                    }
                                ]
                            )->whereIn('flag', ['3', '4'])
                                ->orderBy('tgl_permintaan', 'DESC');
                        },
                    ]
                )->first();
        } else {
            $data = KunjunganPoli::select(
                'rs17.rs1',
                'rs17.rs1 as noreg',
                'rs17.rs2 as norm',
                'rs17.rs3 as tanggal',
                'rs19.rs2 as ruangan',
                'rs21.rs2 as dpjp',
                'memodiagnosadokter.diagnosa as memo'

            )
                ->join('rs19', 'rs19.rs1', '=', 'rs17.rs8')
                ->join('rs21', 'rs21.rs1', '=', 'rs17.rs9')
                ->leftjoin('memodiagnosadokter', 'memodiagnosadokter.noreg', '=', 'rs17.rs1')
                ->where('rs17.rs1', $noreg)
                ->with(
                    [
                        'anamnesis',
                        'pemeriksaanfisik' => function ($p) {
                            $p->with(['gambars', 'detailgambars', 'pemeriksaankhususmata', 'pemeriksaankhususparu'])
                                ->orderBy('id', 'DESC');
                        },
                        'diagnosa' => function ($a) {
                            $a->with(['masterdiagnosa'])
                                ->orderBy('id', 'DESC');
                        },
                        //    'diagnosa.masterdiagnosa:rs1,rs4',
                        'tindakan' => function ($t) {
                            $t->with('mastertindakan:rs1,rs2', 'pegawai:nama,kdpegsimrs', 'pelaksanalamasimrs:nama,kdpegsimrs', 'gambardokumens:id,rs73_id,nama,original,url', 'sambungan:rs73_id,ket')
                                ->orderBy('id', 'DESC');
                        },
                        //    'tindakan.mastertindakan:rs1,rs2',
                        'laborat' => function ($a) {
                            $a->with(['pemeriksaanlab'])
                                ->orderBy('id', 'DESC');
                        },
                        //    'laborat.pemeriksaanlab:rs1,rs2,rs21,nilainormal,satuan',
                        'laborats',
                        'transradiologi:rs1,rs4',
                        'transradiologi.relmasterpemeriksaan:rs1,rs2,rs3,kdmeta',
                        'hasilradiologi',
                        // 'apotekranap',
                        // 'apotekranap.masterobat',
                        // 'apotekranaplalu',
                        // 'apotekranaplalu.masterobat',
                        // 'apotekranapracikanheder',
                        // 'apotekranapracikanheder.apotekranapracikanrinci',
                        // 'apotekranapracikanheder.apotekranapracikanrinci.masterobat',
                        // 'apotekranapracikanhederlalu',
                        // 'apotekranapracikanhederlalu.apotekranapracikanrincilalu',
                        // 'apotekranapracikanhederlalu.apotekranapracikanrincilalu.masterobat',
                        'apotekrajal',
                        'apotekrajal.masterobat',
                        'apotekrajalpolilalu.masterobat',
                        'apotekracikanrajal',
                        'apotekracikanrajal.masterobat',
                        'apotekracikanrajallalu',
                        'apotekracikanrajallalu.masterobat',
                        'dokumenluar' => function ($a) {
                            $a->with(['pegawai:id,nama']);
                        },
                        'kamaroperasi' => function ($kamaroperasi) {
                            $kamaroperasi->with(['mastertindakanoperasi']);
                        },
                        'praanastesi',
                        'newapotekrajal' => function ($apt) {
                            $apt->with(
                                [
                                    'rincian.mobat:kd_obat,nama_obat,satuan_k,status_kronis',
                                    'rincianracik.mobat:kd_obat,nama_obat,satuan_k,status_kronis',
                                    'permintaanresep.mobat:kd_obat,nama_obat,satuan_k,status_kronis',
                                    'permintaanresep.aturansigna:signa,jumlah',
                                    'permintaanracikan.mobat:kd_obat,nama_obat,satuan_k,kekuatan_dosis,status_kronis,kelompok_psikotropika',
                                    'poli',
                                    'info',
                                    'ruanganranap',
                                    'sistembayar',
                                    'sep:rs1,rs8',
                                    'dokter:kdpegsimrs,nama',
                                    'datapasien' => function ($quer) {
                                        $quer->select(
                                            'rs1',
                                            'rs2 as nama',
                                            'rs46 as noka',
                                            'rs16 as tgllahir',
                                            DB::raw('concat(rs4," KEL ",rs5," RT ",rs7," RW ",rs8," ",rs6," ",rs11," ",rs10) as alamat'),
                                        );
                                    }
                                ]
                            )->whereIn('flag', ['3', '4'])
                                ->orderBy('tgl_permintaan', 'DESC');
                        },
                    ]
                )->first();
        }


        return new JsonResponse([
            'data' => $data,
            'req' => request()->all(),
        ]);
    }
}
