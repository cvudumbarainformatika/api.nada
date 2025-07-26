<?php

namespace App\Http\Controllers\Api\Simrs\Ranap;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\MjenisKasus;
use App\Models\Simrs\Ranap\Kunjunganranap;
use App\Models\Simrs\Ranap\Rs23Meta;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RanapController extends Controller
{

    public function index()
    {
        $total = self::query_table()->get()->count();
        $data = self::query_table()->simplePaginate(25);

        $response = (object)[
            'total' => $total,
            'data' => $data
        ];

        return response()->json($response);
    }

    public static function query_table()
    {
        $hr_ini = date('Y-m-d') . ' 23:59:59';
        $hr_180 = Carbon::now()->subDays(10)->format('Y-m-d') . ' 00:00:00';

        $from = request('to') . ' 00:00:00';
        $to = request('from') . ' 23:59:59';

        $ruangan = request('koderuangan');
        $data = DB::table('rs23')->select(
            'rs23.rs1',
            'rs23.rs1 as noreg',
            'rs23.rs2 as norm',
            'rs23.rs3 as tglmasuk',
            // 'rs17.rs3 as tglmasuk_igd',
            'rs23.rs4 as tglkeluar',
            'rs23.rs5 as kdruangan',
            'rs23.rs5 as kodepoli', // ini khusus resep jangan diganti .... memang namanya aneh kok ranap ada kodepoli? ya? jangan dihapus yaaa.....
            'rs23.rs6 as ketruangan',
            'rs23.rs7 as nomorbed',
            'rs23.rs10 as kddokter',
            'rs23.rs10 as kodedokter',
            'rs23.rs38 as hak_kelas',
            // 'rs23.titipan',
            // 'rs21.rs2 as dokter',
            'kepegx.pegawai.nama as dokter',
            'rs23.rs19 as kdsistembayar',
            'rs23.rs19 as kodesistembayar', // ini untuk farmasi
            'rs23.rs22 as status', // '' : BELUM PULANG | '2 ato 3' : PASIEN PULANG
            'rs23.rs24 as prognosis', // PROGNOSIS
            'rs26.rs2 as prognosa', // PROGNOSA (cara keluar master)
            'rs23.rs25 as sebabkematian', // Diagnosa Penyebab Meninggal
            'rs23.rs26 as diagakhir', // Diagnosa Utama
            'rs23.rs27 as tindaklanjut', // tindaklanjut
            'rs23_sambung.ket as tindaklanjut_sambung', // tindaklanjut
            'rs23.rs23 as carakeluar', // cara keluar
            'rs15.rs2 as nama_panggil',
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
            'rs9.rs2 as sistembayar',
            'rs9.groups as groups',
            // 'rs21.rs2 as namanakes',
            'kepegx.pegawai.nama as namanakes',
            'rs227.rs8 as sep',
            'rs227.kodedokterdpjp as kodedokterdpjp',
            'rs227.dokterdpjp as dokterdpjp',
            'rs24.rs2 as ruangan',
            'rs24.rs3 as kelas_ruangan',
            'rs24.rs5 as group_ruangan',
            'rs24.rs4 as kdgroup_ruangan',
            'rs24_titipan.rs2 as dititipkanke',
            'rs23_meta.kd_jeniskasus',
            'rs23_nosurat.nosrtmeninggal',
            'rs23_nosurat.jamMeninggal',
            'rs23_nosurat.kddrygmenyatakan',
            'memodiagnosadokter.diagnosa as memodiagnosa',
            // 'tflag_covid.flagcovid as flagcovid',
        )

            ->leftjoin('rs15', 'rs15.rs1', 'rs23.rs2')
            // ->leftjoin('rs17', 'rs17.rs1', 'rs23.rs1') // IGD
            // ->leftjoin('tflag_covid', function ($q) {
            //     $q->on('tflag_covid.noreg', 'rs23.rs1')
            //       ->where('tflag_covid.stat', 'MASUK')
            //       ->where('tflag_covid.ruang', 'POL014');
            //  }) // IGD
            ->leftjoin('rs9', 'rs9.rs1', 'rs23.rs19')
            // ->leftjoin('rs21', 'rs21.rs1', 'rs23.rs10')
            ->leftjoin('kepegx.pegawai', 'kepegx.pegawai.kdpegsimrs', '=', 'rs23.rs10')
            ->leftjoin('rs227', 'rs227.rs1', 'rs23.rs1')
            ->leftjoin('rs24', 'rs24.rs1', 'rs23.rs5')
            ->leftjoin('rs24 as rs24_titipan', 'rs24_titipan.rs1', 'rs23.titipan')
            ->leftjoin('rs23_meta', 'rs23_meta.noreg', 'rs23.rs1') // jenis kasus
            ->leftjoin('memodiagnosadokter', 'memodiagnosadokter.noreg', 'rs23.rs1') // memo
            ->leftjoin('rs26', 'rs26.rs1', 'rs23.rs23') // master cara keluar
            ->leftjoin('rs23_nosurat', 'rs23_nosurat.noreg', 'rs23.rs1')
            ->leftjoin('rs23_sambung', 'rs23_sambung.noreg', 'rs23.rs1') // sambungan rs23



            ->addSelect(DB::raw(
                '(SELECT rs4 FROM rs23 WHERE rs23.rs2 = rs15.rs1 AND rs23.rs4 != "0000-00-00 00:00:00" ORDER BY rs4 DESC LIMIT 1)
                 as last_visit'
            ))
            // ->addSelect(DB::raw(
            //     '(SELECT SUBSTRING_INDEX(GROUP_CONCAT(rs4 from rs23 where rs23.rs2 = rs15.rs1 order by rs4 desc, ','), ',', 2)
            //     as last_visit'))
            // ->addSelect([
            //     'last_visit' => Kunjunganranap::query()
            //             ->selectRaw("SUBSTRING_INDEX(GROUP_CONCAT(rs23.rs4 order by rs4 desc, ','), ',', 2)")
            //             ->whereColumn('rs23.rs2','=', 'rs15.rs1')
            //             ->limit(2)
            //     ])

            ->where(function ($query) use ($ruangan) {
                if ($ruangan !== 'SEMUA') {
                    $query->where('rs24.groups', '=', $ruangan)
                        // ->orWhere('rs23.titipan', '=',  $ruangan);
                        ->orWhere('rs23.titipan', 'like',  $ruangan . '%');
                }
            })
            ->where(function ($query) use ($from, $to) {
                if (request('status') === 'Pulang') {
                    // $query->where('rs23.rs4', 'like',  '%' . request('from'). '%')
                    // $query->where('rs23.rs4', '=',  request('from'))
                    $query->whereBetween('rs23.rs4', [$from, $to])
                        ->whereIn('rs23.rs22', ['2', '3']);
                } else {
                    $query->where('rs23.rs22', '=', '')
                        ->where('rs23.rs1', '!=', '');
                }
            })


            ->where(function ($query) {
                $query->when(request('q'), function ($q) {
                    $q->where('rs23.rs1', 'like',  '%' . request('q') . '%')
                        ->orWhere('rs23.rs2', 'like',  '%' . request('q') . '%')
                        ->orWhere('rs15.rs2', 'like',  '%' . request('q') . '%');
                });
            })
            ->orderby('rs23.rs3', 'DESC')
            ->groupBy('rs23.rs1');

        return $data;
    }

    public function kunjunganpasien()
    {
        // coba lagi
        // return request()->all();
        $dokter = request('kddokter');

        if (request('to') === null || request('to') === '') {
            $tgl = Carbon::now()->format('Y-m-d');
        } else {
            $tgl = request('to');
        }

        if (request('from') === null || request('from') === '') {
            $tglx = Carbon::now()->format('Y-m-d');
        } else {
            $tglx = request('from');
        }

        // $tanggal = $tgl. ' 23:59:59';
        // $tanggalx = $tglx. ' 00:00:00';
        $tanggal = $tgl . ' 00:00:00';
        $tanggalx = $tglx . ' 23:59:59';
        // $tanggalx = Carbon::now()->subDays(180)->format('Y-m-d'). ' 00:00:00';

        // return $tanggalx;

        $hr_ini = date('Y-m-d') . ' 23:59:59';
        $hr_180 = Carbon::now()->subDays(10)->format('Y-m-d') . ' 00:00:00';

        $status = request('status') === 'Belum Pulang' ? [''] : ['2', '3'];
        $ruangan = request('koderuangan');
        $data = DB::table('rs23')->select(
            'rs23.rs1',
            'rs23.rs1 as noreg',
            'rs23.rs2 as norm',
            'rs23.rs3 as tglmasuk',
            // 'rs17.rs3 as tglmasuk_igd',
            'rs23.rs4 as tglkeluar',
            'rs23.rs5 as kdruangan',
            'rs23.rs5 as kodepoli', // ini khusus resep jangan diganti .... memang namanya aneh kok ranap ada kodepoli? ya? jangan dihapus yaaa.....
            'rs23.rs6 as ketruangan',
            'rs23.rs7 as nomorbed',
            'rs23.rs10 as kddokter',
            'rs23.rs10 as kodedokter',
            'rs23.rs38 as hak_kelas',
            // 'rs23.titipan',
            'kepegx.pegawai.nama as dokter',
            'rs23.rs19 as kdsistembayar',
            'rs23.rs19 as kodesistembayar', // ini untuk farmasi
            'rs23.rs22 as status', // '' : BELUM PULANG | '2 ato 3' : PASIEN PULANG
            'rs23.rs24 as prognosis', // PROGNOSIS
            'rs23.rs25 as sebabkematian', // Diagnosa Penyebab Meninggal
            'rs23.rs26 as diagakhir', // Diagnosa Utama
            'rs23.rs27 as tindaklanjut', // tindaklanjut
            'rs23.rs23 as carakeluar', // cara keluar
            'rs15.rs2 as nama_panggil',
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
            'rs9.rs2 as sistembayar',
            'rs9.groups as groups',
            // 'rs21.rs2 as namanakes',
            'kepegx.pegawai.nama as namanakes',
            'rs227.rs8 as sep',
            'rs227.kodedokterdpjp as kodedokterdpjp',
            'rs227.dokterdpjp as dokterdpjp',
            'rs24.rs2 as ruangan',
            'rs24.rs3 as kelas_ruangan',
            'rs24.rs5 as group_ruangan',
            'rs24.rs4 as kdgroup_ruangan',
            'rs24_titipan.rs2 as dititipkanke',
            'rs23_meta.kd_jeniskasus',
            'memodiagnosadokter.diagnosa as memodiagnosa',
            // 'tflag_covid.flagcovid as flagcovid',
        )
            ->leftjoin('rs15', 'rs15.rs1', 'rs23.rs2')
            // ->leftjoin('rs17', 'rs17.rs1', 'rs23.rs1') // IGD
            // ->leftjoin('tflag_covid', function ($q) {
            //     $q->on('tflag_covid.noreg', 'rs23.rs1')
            //       ->where('tflag_covid.stat', 'MASUK')
            //       ->where('tflag_covid.ruang', 'POL014');
            //  }) // IGD
            ->leftjoin('rs9', 'rs9.rs1', 'rs23.rs19')
            ->leftjoin('rs21', 'rs21.rs1', 'rs23.rs10')
            ->leftjoin('rs227', 'rs227.rs1', 'rs23.rs1')
            ->leftjoin('rs24', 'rs24.rs1', 'rs23.rs5')
            ->leftjoin('rs24 as rs24_titipan', 'rs24_titipan.rs1', 'rs23.titipan')
            ->leftjoin('rs23_meta', 'rs23_meta.noreg', 'rs23.rs1') // jenis kasus
            ->leftjoin('memodiagnosadokter', 'memodiagnosadokter.noreg', 'rs23.rs1') // memo


            ->where(function ($query) use ($ruangan) {
                if ($ruangan !== 'SEMUA') {
                    $query->where('rs24.groups', '=', $ruangan)
                        // ->orWhere('rs23.titipan', '=',  $ruangan);
                        ->orWhere('rs23.titipan', 'like',  $ruangan . '%');
                }
            })



            // ->whereDate('rs23.rs3', '<=', $tgl)
            // ->whereIn('rs23.rs22', $status)

            // ->where(function ($x) {
            //     $x->orWhereNull('dokterdpjp');
            // })
            ->where(function ($query) use ($hr_ini, $hr_180) {
                if (request('status') === 'Pulang') {
                    // $query->whereBetween('rs23.rs4', [$hr_180, $hr_ini])
                    $query->where('rs23.rs4', 'like',  '%' . request('from') . '%')
                        ->whereIn('rs23.rs22', ['2', '3']);
                } else {
                    $query->where('rs23.rs22', '=', '')
                        ->where('rs23.rs1', '!=', '');
                }
            })

            // ->where(function ($q) use ($status) {
            //     // if ($status === 'Belum Pulang') {
            //     //     $q->where('rs23.rs22', '');
            //     // } else {
            //     //     $q->where('rs23.rs22', '!=', '');
            //     // }
            //     $q->whereIn('rs23.rs22', $status);
            // })


            ->where(function ($query) {
                $query->when(request('q'), function ($q) {
                    $q->where('rs23.rs1', 'like',  '%' . request('q') . '%')
                        ->orWhere('rs23.rs2', 'like',  '%' . request('q') . '%')
                        ->orWhere('rs15.rs2', 'like',  '%' . request('q') . '%');
                });
            })




            // ->with([
            //     'newapotekrajal' => function ($newapotekrajal) {
            //         $newapotekrajal->with([
            //             'dokter:nama,kdpegsimrs',
            //             'permintaanresep.mobat:kd_obat,nama_obat',
            //             'permintaanracikan.mobat:kd_obat,nama_obat',
            //         ])
            //             ->orderBy('id', 'DESC');
            //     },
            //     'diagnosa' // ini sementara berhubungan dengan resep
            // ])
            // ->whereIn('rs23.rs5', $ruangan)
            // ->where('rs23.rs10', 'like', '%' . $dokter . '%')
            // ->where(function ($sts) use ($status) {
            //     if ($status !== 'all') {
            //         if ($status === '') {
            //             $sts->where('rs23.rs22', '!=', '1');
            //         } else {
            //             $sts->where('rs23.rs22', '=', $status);
            //         }
            //     }
            // })
            // ->where(function ($query) {
            //     $query->where('rs15.rs2', 'LIKE', '%' . request('q') . '%')
            //         ->orWhere('rs15.rs46', 'LIKE', '%' . request('q') . '%')
            //         ->orWhere('rs23.rs2', 'LIKE', '%' . request('q') . '%')
            //         ->orWhere('rs23.rs1', 'LIKE', '%' . request('q') . '%')
            //         ->orWhere('rs24.rs2', 'LIKE', '%' . request('q') . '%')
            //         ->orWhere('rs21.rs2', 'LIKE', '%' . request('q') . '%')
            //         ->orWhere('rs227.rs8', 'LIKE', '%' . request('q') . '%')
            //         ->orWhere('rs9.rs2', 'LIKE', '%' . request('q') . '%');
            // })
            ->orderby('rs23.rs3', 'DESC')
            ->groupBy('rs23.rs1')
            ->paginate(25);

        return new JsonResponse($data);
    }



    public function bukalayanan(Request $request)
    {
        $cekx = Kunjunganranap::query()
            ->select(
                'rs1',
                'rs1 as noreg',
                'rs2 as norm'
            )
            ->where('rs1', '=', $request->noreg)
            ->with([
                'dataigd:rs1,rs3 as tglmasuk_igd',
                'newapotekrajal' => function ($q) {
                    $q->with([
                        'dokter:nama,kdpegsimrs',
                        'permintaanresep.mobat:kd_obat,nama_obat,bentuk_sediaan,satuan_k,jenis_perbekalan',
                        'permintaanracikan.mobat:kd_obat,nama_obat,bentuk_sediaan,satuan_k,jenis_perbekalan',
                        'sistembayar'
                    ])
                        ->where('ruangan', '!=', 'POL014')
                        ->orderBy('id', 'DESC');
                },
                'diagnosa', // ini berhubungan dengan resep
                'anamnesis' => function ($q) {
                    $q->select([
                        'rs209.id',
                        'rs209.rs1',
                        'rs209.rs1 as noreg',
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
                        ->with([
                            'petugas:kdpegsimrs,nik,nama,kdgroupnakes',
                            'keluhannyeri',
                            'skreeninggizi',
                            'neonatal',
                            'pediatrik',
                            'kebidanan'
                        ])

                        ->groupBy('rs209.id');
                },
                'pemeriksaan' => function ($q) {
                    $q->select([
                        'rs253.id',
                        'rs253.rs1',
                        'rs253.rs1 as noreg',
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
                        'rs253.rs5',
                        'rs253.rs6',
                        'rs253.rs7',
                        'rs253.rs8',
                        'rs253.rs9',
                        'rs253.rs10',
                        'rs253.rs11',
                        'rs253.rs12',
                        'rs253.rs13',
                        'rs253.sax',
                        'rs253.srec',

                        'sambung.keadaanUmum',
                        'sambung.bb',
                        'sambung.tb',
                        'sambung.nadi',
                        'sambung.suhu',
                        'sambung.sistole',
                        'sambung.diastole',
                        'sambung.pernapasan',
                        'sambung.spo',
                        'sambung.tkKesadaran',
                        'sambung.tkKesadaranKet',
                        'sambung.sosial',
                        'sambung.spiritual',
                        'sambung.statusPsikologis',
                        'sambung.ansuransi',
                        'sambung.edukasi',
                        'sambung.ketEdukasi',
                        'sambung.penyebabSakit',
                        'sambung.komunikasi',
                        'sambung.makananPokok',
                        'sambung.makananPokokLain',
                        'sambung.pantanganMkanan',

                        'pegawai.nama as petugas',
                        'pegawai.kdgroupnakes as nakes',
                    ])
                        ->leftJoin('rs253_sambung as sambung', 'rs253.id', '=', 'sambung.rs253_id')
                        ->leftJoin('kepegx.pegawai as pegawai', 'rs253.user', '=', 'pegawai.kdpegsimrs')
                        //    ->where('rs253.rs1','=', $noreg)
                        ->with([
                            'petugas:kdpegsimrs,nik,nama,kdgroupnakes',
                            'neonatal',
                            'pediatrik',
                            'kebidanan',
                            //  'penilaian'
                        ])
                        ->groupBy('rs253.id');
                },
                'penilaian' => function ($q) {
                    $q->select([
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
                        ->where('kdruang', '!=', 'POL014')
                        ->with(['petugas:kdpegsimrs,nik,nama,kdgroupnakes']);
                },
                'diagnosamedis' => function ($q) {
                    $q->with('masterdiagnosa');
                },
                'diagnosakeperawatan' => function ($q) {
                    $q->with('intervensi', 'intervensi.masterintervensi')
                        ->where('kdruang', '!=', 'POL014')
                        ->orderBy('id', 'DESC');
                },
                'diagnosakebidanan' => function ($q) {
                    $q->with('intervensi', 'intervensi.masterintervensi')
                        ->where('kdruang', '!=', 'POL014')
                        ->orderBy('id', 'DESC');
                },
                'diagnosagizi' => function ($q) {
                    $q->with('intervensi', 'intervensi.masterintervensi')
                        ->where('kdruang', '!=', 'POL014')
                        ->orderBy('id', 'DESC');
                },
                'tindakan' => function ($q) {
                    $q->select(
                        'id',
                        'rs1',
                        'rs2',
                        'rs4',
                        'rs1 as noreg',
                        'rs2 as nota',
                        'rs3',

                        'rs4',
                        'rs5',
                        'rs6',
                        'rs7',
                        'rs8',
                        'rs9',
                        'rs13',
                        'rs14',
                        'rs20',
                        'rs22',
                        'rs23',
                        'rs24',
                    )
                        ->where('rs22', '!=', 'POL014')
                        ->with(['mastertindakan:rs1,rs2', 'sambungan:rs73_id,ket'])
                        ->orderBy('id', 'DESC');
                },
                'laborats' => function ($q) {

                    $q->with('details.pemeriksaanlab')->orderBy('id', 'DESC')
                        ->where('unit_pengirim', '!=', 'POL014')
                        ->where('unit_pengirim', '!=', 'PEN005'); // tambahan HD
                },
                'laboratold' => function ($t) {
                    $t->with('pemeriksaanlab')
                        ->orderBy('id', 'DESC');
                },
                'radiologi' => function ($q) {
                    $q->orderBy('id', 'DESC')
                        ->where('rs10', '!=', 'POL014')
                        ->where('rs10', '!=', 'PEN005'); // tambahan HD
                },
                'hasilradiologi' => function ($q) {
                    $q->orderBy('id', 'DESC');
                },
                'fisio' => function ($q) {
                    $q->where('rs2', '!=', '')
                        ->groupBy('rs2')->orderBy('id', 'DESC');
                },
                'operasi' => function ($q) {
                    $q->with('petugas:kdpegsimrs,nik,nama,kdgroupnakes')
                        ->orderBy('id', 'DESC');
                },
                // 'operasi_ird'=> function ($q) {
                //     $q->with('petugas:kdpegsimrs,nik,nama,kdgroupnakes')
                //     ->orderBy('id', 'DESC');
                // },
                'bankdarah' => function ($q) {
                    $q->orderBy('id', 'DESC')
                        ->where('rs11', '!=', 'POL014')
                        ->where('rs11', '!=', 'PEN005'); // tambahan HD
                },
                'apheresis' => function ($q) {
                    $q->orderBy('id', 'DESC');
                },
                'cathlab' => function ($q) {
                    $q->orderBy('id', 'DESC');
                },
                'permintaanambulan' => function ($q) {
                    $q->orderBy('id', 'DESC');
                },
                // 'oksigen'=> function ($q) {
                //     $q->orderBy('id', 'DESC');
                // },
                'penunjanglain' => function ($q) {
                    $q->with('masterpenunjang')
                        ->where('rs10', '!=', 'POL014')
                        ->orderBy('id', 'DESC');
                },
                'perawatanjenazah' => function ($q) {
                    $q->orderBy('id', 'DESC');
                },
                'hais' => function ($q) {
                    $q->orderBy('id', 'DESC');
                },
                'cppt' => function ($q) {
                    $q->with([
                        'petugas:kdpegsimrs,nik,nama,kdgroupnakes',
                        'anamnesis' => function ($query) {
                            $query->select(
                                'rs209.id',
                                'rs209.rs1',
                                'rs209.rs1 as noreg',
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
                            )->with([
                                'petugas:kdpegsimrs,nik,nama,kdgroupnakes',
                                'keluhannyeri',
                                'skreeninggizi',
                                'neonatal',
                                'pediatrik',
                                'kebidanan'
                            ]);
                            // ->where('awal','!=', '1');
                        },
                        'pemeriksaan' => function ($query) {
                            $query->select([
                                'rs253.id',
                                'rs253.rs1',
                                'rs253.rs1 as noreg',
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
                                'sambung.bb',
                                'sambung.tb',
                                'sambung.nadi',
                                'sambung.suhu',
                                'sambung.sistole',
                                'sambung.diastole',
                                'sambung.pernapasan',
                                'sambung.spo',
                                'sambung.tkKesadaran',
                                'sambung.tkKesadaranKet',
                                'sambung.sosial',
                                'sambung.spiritual',
                                'sambung.statusPsikologis',
                                'sambung.ansuransi',
                                'sambung.edukasi',
                                'sambung.ketEdukasi',
                                'sambung.penyebabSakit',
                                'sambung.komunikasi',
                                'sambung.makananPokok',
                                'sambung.makananPokokLain',
                                'sambung.pantanganMkanan',

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
                        'penilaian' => function ($query) {
                            $query->select([
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
                            ]);
                            // ->where('awal','!=', '1');
                        },
                        'cpptlama',

                    ])
                        ->orderBy('tgl', 'DESC');
                },
                'konsultasi' => function ($q) {
                    $q->where('kdruang', '!=', 'POL014')
                        ->where('kdruang', '!=', 'PEN005') // tambahan HD
                        ->with([
                            'tarif:id,rs1,rs3,rs4,rs5,rs6,rs7,rs8,rs9,rs10',
                            'nakesminta:kdpegsimrs,nama,kdgroupnakes,statusspesialis',
                        ])
                        ->orderBy('id', 'DESC'); // ini updatean baru
                },
                'edukasi' => function ($q) {
                    $q->orderBy('id', 'DESC');
                },
                'dokumenluar' => function ($neo) {
                    $neo->with(['pegawai:id,nama'])
                        ->orderBy('id', 'DESC');
                },
                'informconcern' => function ($neo) {
                    // $neo->withAccessor()
                    $neo->select([
                        'id',
                        'noreg',
                        'norm',
                        'tgl',
                        'tanggal',
                        'pelaksana',
                        'pengedukasi',
                        'penerimaEdukasi',
                        'diagnosis',
                        'diagnosis',
                        'dasarDiagnosis',
                        'tindakanMedis',
                        'indikasi',
                        'tujuan',
                        'tujuanLain',
                        'tatacara',
                        'resiko',
                        'resikoLain',
                        'komplikasi',
                        'prognosis',
                        'alternatif',
                        'hubunganDgPasien',
                        'keluarga',
                        'nama',
                        'lp',
                        'tglLahir',
                        'noKtp',
                        'alamat',
                        'telepon',
                        'ttdPetugas',
                        'ttdDokter',
                        'ttdSaksiPasien',
                        'ttdYgMenyatakan',
                        'saksiPasien',
                        'kdDokter',
                        'kdPetugas',
                        'setuju',
                        'kdRuang',
                        'user',
                        'jenis'
                    ])
                        ->orderBy('id', 'DESC');
                },
                'dischargeplanning',
                'skriningdischargeplannings',
                'summarydischargeplannings',
                'procedure',
                'planningdokter',
                'keterangantindakan:noreg,keterangan',
                'statuscovid' => function ($q) {
                    $q->where('stat', '=', 'MASUK')
                        ->where('ruang', '!=', 'POL014');
                },
                'manymemo'

            ])->first();

        if (!$cekx) {
            return new JsonResponse([
                'message' => 'Data tidak ditemukan',
            ], 500);
        }

        return new JsonResponse($cekx);
    }

    public function listjeniskasus()
    {
        //  $list = MjenisKasus::select('kode','uraian','gruping','medis','flag')->where('flag','1')->get();
        $list = Cache::remember('jeniskasus', now()->addDays(7), function () {
            return MjenisKasus::select('kode', 'uraian', 'gruping', 'medis', 'flag')->where('flag', '1')->get();
        });
        return new JsonResponse($list);
    }

    public function gantijeniskasus(Request $request)
    {
        $request->validate([
            'noreg' => 'required',
            'kd_jeniskasus' => 'required',
        ]);
        $data = Rs23Meta::where('noreg', $request->noreg)->first();

        if (!$data) {
            $saved = Rs23Meta::create([
                'noreg' => $request->noreg,
                'kd_jeniskasus' => $request->kd_jeniskasus,
                'user_input' => auth()->user()->pegawai_id
            ]);

            return new JsonResponse($saved);
        }

        $data->kd_jeniskasus = $request->kd_jeniskasus;
        $data->user_input = auth()->user()->pegawai_id;
        $data->save();

        return new JsonResponse($data);
    }

    public function gantidpjp(Request $request)
    {
        $carikunjungan = Kunjunganranap::where('rs1', $request->noreg)->first();
        $carikunjungan->rs10 = $request->kdpegsimrs;
        $carikunjungan->save();
        return new JsonResponse(
            [
                'message' => 'ok',
                'result' => $carikunjungan->load('datasimpeg:id,nip,nik,nama,kelamin,foto,kdpegsimrs,kddpjp'),
            ],
            200
        );
    }
}
