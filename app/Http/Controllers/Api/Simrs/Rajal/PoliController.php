<?php

namespace App\Http\Controllers\Api\Simrs\Rajal;

use App\Events\ChatMessageEvent;
use App\Events\NotifMessageEvent;
use App\Helpers\BridgingbpjsHelper;
use App\Helpers\FormatingHelper;
use App\Http\Controllers\Api\Simrs\Antrian\AntrianController;
use App\Http\Controllers\Api\Simrs\Pendaftaran\Rajal\BridantrianbpjsController;
use App\Http\Controllers\Api\Simrs\Planing\PlaningController;
use App\Http\Controllers\Controller;
use App\Models\Pegawai\Mpegawaisimpeg;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Kasir\Pembayaran;
use App\Models\Simrs\Master\MtindakanX;
use App\Models\Simrs\Pendaftaran\Karcispoli;
use App\Models\Simrs\Pendaftaran\Rajalumum\Bpjsrespontime;
use App\Models\Simrs\Pendaftaran\Rajalumum\Seprajal;
use App\Models\Simrs\Penjaminan\listcasmixrajal;
use App\Models\Simrs\Rajal\KunjunganPoli;
use App\Models\Simrs\Rajal\Memodiagnosadokter;
use App\Models\Simrs\Rajal\WaktupulangPoli;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PoliController extends Controller
{
    public function kunjunganpoli()
    {
        $user = Pegawai::find(auth()->user()->pegawai_id);

        $ruangan = request('kodepoli');

        if (request('to') === '' || request('from') === null) {
            $tgl = Carbon::now()->format('Y-m-d 00:00:00');
            $tglx = Carbon::now()->format('Y-m-d 23:59:59');
        } else {
            $tgl = request('to') . ' 00:00:00';
            $tglx = request('from') . ' 23:59:59';
        }

        //$gigi = ['POL015', 'POL023', 'POL038', 'POL039', 'POL040'];
        // return $gigi;
        // $saraf = ['POL019', 'POL003'];
        $status = request('status') ?? '';
        $daftarkunjunganpasienbpjs = KunjunganPoli::select(
            'rs17.rs1',
            'rs17.rs9',
            'rs17.rs4',
            'rs17.rs1 as noreg',
            'rs17.rs2 as norm',
            'rs17.rs3 as tgl_kunjungan',
            'rs17.rs8 as kodepoli',
            'rs19.rs2 as poli',
            'rs19.rs6 as kodepolibpjs',
            'rs19.panggil_antrian as panggil_antrian',
            'rs17.rs9 as kodedokter',
            'master_poli_bpjs.nama as polibpjs',
            'rs21.rs2 as dokter',
            'rs17.rs14 as kodesistembayar',
            'rs9.rs2 as sistembayar',
            'rs9.groups as groups',
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
            'rs222.rs8 as sep',
            'rs222.rs5 as norujukan',
            'rs222.kodedokterdpjp as kodedokterdpjp',
            'rs222.dokterdpjp as dokterdpjp',
            'rs222.kdunit as kdunit',
            'memodiagnosadokter.diagnosa as memodiagnosa',
            'rs17.rs19 as status',
            'antrian_ambil.nomor as noantrian'
        )
            ->leftjoin('rs15', 'rs15.rs1', '=', 'rs17.rs2') //pasien
            ->leftjoin('rs19', 'rs19.rs1', '=', 'rs17.rs8') //poli
            ->leftjoin('rs21', 'rs21.rs1', '=', 'rs17.rs9') //dokter
            ->leftjoin('rs9', 'rs9.rs1', '=', 'rs17.rs14') //sistembayar
            ->leftjoin('rs222', 'rs222.rs1', '=', 'rs17.rs1') //sep
            ->leftjoin('master_poli_bpjs', 'rs19.rs6', '=', 'master_poli_bpjs.kode')
            ->leftjoin('memodiagnosadokter', 'memodiagnosadokter.noreg', '=', 'rs17.rs1')
            ->leftjoin('antrian_ambil', 'antrian_ambil.noreg', 'rs17.rs1')
            ->whereBetween('rs17.rs3', [$tgl, $tglx])
            ->where('rs19.rs4', '=', 'Poliklinik')
            ->whereIn('rs17.rs8', $ruangan)
            ->where('rs17.rs8', '!=', 'POL014')
            ->where(function ($sts) use ($status) {
                if ($status !== 'all') {
                    if ($status === '') {
                        $sts->where('rs17.rs19', '!=', '1');
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
                    ->orWhere('rs21.rs2', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs222.rs8', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs9.rs2', 'LIKE', '%' . request('q') . '%');
            })
            // ->where('rs17.rs8', 'LIKE', '%' . request('kdpoli') . '%')

            ->with([
                'anamnesis',
                'datasimpeg:id,nip,nik,nama,kelamin,foto,kdpegsimrs,kddpjp',
                'gambars',
                'fisio',
                'datacasmix',
                'diagnosakeperawatan' => function ($diag) {
                    $diag->with('intervensi.masterintervensi');
                },
                'laborats' => function ($t) {
                    $t->with('details.pemeriksaanlab')
                        ->orderBy('id', 'DESC');
                },
                'radiologi' => function ($t) {
                    $t->orderBy('id', 'DESC');
                },
                'penunjanglain' => function ($t) {
                    $t->with('masterpenunjang')->orderBy('id', 'DESC');
                },
                'tindakan' => function ($t) {
                    $t->with('mastertindakan:rs1,rs2', 'pegawai:nama,kdpegsimrs', 'gambardokumens:id,rs73_id,nama,original,url')
                        ->orderBy('id', 'DESC');
                },
                'diagnosa' => function ($d) {
                    $d->with('masterdiagnosa');
                },
                'pemeriksaanfisik' => function ($a) {
                    $a->with(['detailgambars', 'pemeriksaankhususmata', 'pemeriksaankhususparu'])
                        ->orderBy('id', 'DESC');
                },
                'ok' => function ($q) {
                    $q->orderBy('id', 'DESC');
                },
                'taskid' => function ($q) {
                    $q->orderBy('taskid', 'DESC');
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
                'edukasi' => function ($x) {
                    $x->orderBy('id', 'DESC');
                },
                'diet' => function ($diet) {
                    $diet->orderBy('id', 'DESC');
                },
                'sharing' => function ($sharing) {
                    $sharing->orderBy('id', 'DESC');
                },
                'newapotekrajal' => function ($newapotekrajal) {
                    $newapotekrajal->with([
                        'permintaanresep.mobat:kd_obat,nama_obat',
                        'permintaanracikan.mobat:kd_obat,nama_obat',
                    ])
                        ->orderBy('id', 'DESC');
                },
                'laporantindakan'
            ])
            ->orderby('antrian_ambil.nomor', 'Asc')
            ->groupby('rs17.rs1')
            ->paginate(request('per_page'));
        // $sorted = $daftarkunjunganpasienbpjs->map(function ($daftarkunjunganpasienbpjs) {
        //     $order = $daftarkunjunganpasienbpjs['antrian_ambil'][0]->nomor ?? 0;
        //     return [
        //         ...$daftarkunjunganpasienbpjs,
        //         'items' => $daftarkunjunganpasienbpjs->items->mapWithKeys(
        //             fn ($item) => [array_search($item['antrian_ambil'][0]->nomor, $order) => $item]
        //         )->sortKeys()
        //     ];
        // });

        // if ($ruangan === 'POL023') {
        //     $kodepoli = ['POL015', 'POL023', 'POL038', 'POL039', 'POL040'];

        //     $status = request('status') ?? '';
        //     $daftarkunjunganpasienbpjs = KunjunganPoli::select(
        //         'rs17.rs1',
        //         'rs17.rs9',
        //         'rs17.rs4',
        //         'rs17.rs1 as noreg',
        //         'rs17.rs2 as norm',
        //         'rs17.rs3 as tgl_kunjungan',
        //         'rs17.rs8 as kodepoli',
        //         'rs19.rs2 as poli',
        //         'rs19.rs6 as kodepolibpjs',
        //         'rs19.panggil_antrian as panggil_antrian',
        //         'rs17.rs9 as kodedokter',
        //         'master_poli_bpjs.nama as polibpjs',
        //         'rs21.rs2 as dokter',
        //         'rs17.rs14 as kodesistembayar',
        //         'rs9.rs2 as sistembayar',
        //         'rs9.groups as groups',
        //         'rs15.rs2 as nama_panggil',
        //         DB::raw('concat(rs15.rs3," ",rs15.gelardepan," ",rs15.rs2," ",rs15.gelarbelakang) as nama'),
        //         DB::raw('concat(rs15.rs4," KEL ",rs15.rs5," RT ",rs15.rs7," RW ",rs15.rs8," ",rs15.rs6," ",rs15.rs11," ",rs15.rs10) as alamat'),
        //         DB::raw('concat(TIMESTAMPDIFF(YEAR, rs15.rs16, CURDATE())," Tahun ",
        //                 TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()) % 12," Bulan ",
        //                 TIMESTAMPDIFF(DAY, TIMESTAMPADD(MONTH, TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()), rs15.rs16), CURDATE()), " Hari") AS usia'),
        //         'rs15.rs16 as tgllahir',
        //         'rs15.rs17 as kelamin',
        //         'rs15.rs19 as pendidikan',
        //         'rs15.rs22 as agama',
        //         'rs15.rs37 as templahir',
        //         'rs15.rs39 as suku',
        //         'rs15.rs40 as jenispasien',
        //         'rs15.rs46 as noka',
        //         'rs15.rs49 as nktp',
        //         'rs15.rs55 as nohp',
        //         'rs222.rs8 as sep',
        //         'rs222.rs5 as norujukan',
        //         'rs222.kodedokterdpjp as kodedokterdpjp',
        //         'rs222.dokterdpjp as dokterdpjp',
        //         'rs222.kdunit as kdunit',
        //         'memodiagnosadokter.diagnosa as memodiagnosa',
        //         'rs17.rs19 as status'
        //     )
        //         ->leftjoin('rs15', 'rs15.rs1', '=', 'rs17.rs2') //pasien
        //         ->leftjoin('rs19', 'rs19.rs1', '=', 'rs17.rs8') //poli
        //         ->leftjoin('rs21', 'rs21.rs1', '=', 'rs17.rs9') //dokter
        //         ->leftjoin('rs9', 'rs9.rs1', '=', 'rs17.rs14') //sistembayar
        //         ->leftjoin('rs222', 'rs222.rs1', '=', 'rs17.rs1') //sep
        //         ->leftjoin('master_poli_bpjs', 'rs19.rs6', '=', 'master_poli_bpjs.kode')
        //         ->leftjoin('memodiagnosadokter', 'memodiagnosadokter.noreg', '=', 'rs17.rs1')
        //         ->whereBetween('rs17.rs3', [$tgl, $tglx])
        //         // ->where('rs17.rs8', $user->kdruangansim ?? '')
        //         ->where('rs19.rs4', '=', 'Poliklinik')
        //         ->whereIn('rs17.rs8', $kodepoli)
        //         ->where('rs17.rs8', '!=', 'POL014')
        //         //    ->where('rs9.rs9', '=', 'BPJS')
        //         ->where(function ($sts) use ($status) {
        //             if ($status !== 'all') {
        //                 if ($status === '') {
        //                     $sts->where('rs17.rs19', '!=', '1');
        //                 } else {
        //                     $sts->where('rs17.rs19', '=', $status);
        //                 }
        //             }
        //         })
        //         ->where(function ($query) {
        //             $query->where('rs15.rs2', 'LIKE', '%' . request('q') . '%')
        //                 ->orWhere('rs15.rs46', 'LIKE', '%' . request('q') . '%')
        //                 ->orWhere('rs17.rs2', 'LIKE', '%' . request('q') . '%')
        //                 ->orWhere('rs17.rs1', 'LIKE', '%' . request('q') . '%')
        //                 ->orWhere('rs19.rs2', 'LIKE', '%' . request('q') . '%')
        //                 ->orWhere('rs21.rs2', 'LIKE', '%' . request('q') . '%')
        //                 ->orWhere('rs222.rs8', 'LIKE', '%' . request('q') . '%')
        //                 ->orWhere('rs9.rs2', 'LIKE', '%' . request('q') . '%');
        //         })
        //         // ->where('rs17.rs8', 'LIKE', '%' . request('kdpoli') . '%')

        //         ->with([
        //             'anamnesis', 'datasimpeg:id,nip,nik,nama,kelamin,foto,kdpegsimrs,kddpjp',
        //             'gambars',
        //             'fisio',
        //             'diagnosakeperawatan' => function ($diag) {
        //                 $diag->with('intervensi.masterintervensi');
        //             },
        //             'laborats' => function ($t) {
        //                 $t->with('details.pemeriksaanlab')
        //                     ->orderBy('id', 'DESC');
        //             },
        //             'radiologi' => function ($t) {
        //                 $t->orderBy('id', 'DESC');
        //             },
        //             'penunjanglain' => function ($t) {
        //                 $t->with('masterpenunjang')->orderBy('id', 'DESC');
        //             },
        //             'tindakan' => function ($t) {
        //                 $t->with('mastertindakan:rs1,rs2', 'pegawai:nama,kdpegsimrs')
        //                     ->orderBy('id', 'DESC');
        //             },
        //             'diagnosa' => function ($d) {
        //                 $d->with('masterdiagnosa');
        //             },
        //             'pemeriksaanfisik' => function ($a) {
        //                 $a->with(['detailgambars', 'pemeriksaankhususmata', 'pemeriksaankhususparu'])
        //                     ->orderBy('id', 'DESC');
        //             },
        //             'ok' => function ($q) {
        //                 $q->orderBy('id', 'DESC');
        //             },
        //             'taskid' => function ($q) {
        //                 $q->orderBy('taskid', 'DESC');
        //             },
        //             'planning' => function ($p) {
        //                 $p->with(
        //                     'masterpoli',
        //                     'rekomdpjp',
        //                     'transrujukan',
        //                     'listkonsul',
        //                     'spri',
        //                     'ranap',
        //                     'kontrol'
        //                 )->orderBy('id', 'DESC');
        //             },
        //             'edukasi' => function ($x) {
        //                 $x->orderBy('id', 'DESC');
        //             },
        //             'antrian_ambil' => function ($o) {
        //                 $o->where('pelayanan_id', request('kdpoli'));
        //             },
        //             'diet' => function ($diet) {
        //                 $diet->orderBy('id', 'DESC');
        //             },
        //             'sharing' => function ($sharing) {
        //                 $sharing->orderBy('id', 'DESC');
        //             }
        //         ])
        //         ->orderby('rs17.rs3', 'ASC')
        //         ->paginate(request('per_page'));
        // } else {
        //     $status = request('status') ?? '';
        //     $daftarkunjunganpasienbpjs = KunjunganPoli::select(
        //         'rs17.rs1',
        //         'rs17.rs9',
        //         'rs17.rs4',
        //         'rs17.rs1 as noreg',
        //         'rs17.rs2 as norm',
        //         'rs17.rs3 as tgl_kunjungan',
        //         'rs17.rs8 as kodepoli',
        //         'rs19.rs2 as poli',
        //         'rs19.rs6 as kodepolibpjs',
        //         'rs19.panggil_antrian as panggil_antrian',
        //         'rs17.rs9 as kodedokter',
        //         'master_poli_bpjs.nama as polibpjs',
        //         'rs21.rs2 as dokter',
        //         'rs17.rs14 as kodesistembayar',
        //         'rs9.rs2 as sistembayar',
        //         'rs9.groups as groups',
        //         'rs15.rs2 as nama_panggil',
        //         DB::raw('concat(rs15.rs3," ",rs15.gelardepan," ",rs15.rs2," ",rs15.gelarbelakang) as nama'),
        //         DB::raw('concat(rs15.rs4," KEL ",rs15.rs5," RT ",rs15.rs7," RW ",rs15.rs8," ",rs15.rs6," ",rs15.rs11," ",rs15.rs10) as alamat'),
        //         DB::raw('concat(TIMESTAMPDIFF(YEAR, rs15.rs16, CURDATE())," Tahun ",
        //                 TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()) % 12," Bulan ",
        //                 TIMESTAMPDIFF(DAY, TIMESTAMPADD(MONTH, TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()), rs15.rs16), CURDATE()), " Hari") AS usia'),
        //         'rs15.rs16 as tgllahir',
        //         'rs15.rs17 as kelamin',
        //         'rs15.rs19 as pendidikan',
        //         'rs15.rs22 as agama',
        //         'rs15.rs37 as templahir',
        //         'rs15.rs39 as suku',
        //         'rs15.rs40 as jenispasien',
        //         'rs15.rs46 as noka',
        //         'rs15.rs49 as nktp',
        //         'rs15.rs55 as nohp',
        //         'rs222.rs8 as sep',
        //         'rs222.rs5 as norujukan',
        //         'rs222.kodedokterdpjp as kodedokterdpjp',
        //         'rs222.dokterdpjp as dokterdpjp',
        //         'rs222.kdunit as kdunit',
        //         'memodiagnosadokter.diagnosa as memodiagnosa',
        //         'rs17.rs19 as status'
        //     )
        //         ->leftjoin('rs15', 'rs15.rs1', '=', 'rs17.rs2') //pasien
        //         ->leftjoin('rs19', 'rs19.rs1', '=', 'rs17.rs8') //poli
        //         ->leftjoin('rs21', 'rs21.rs1', '=', 'rs17.rs9') //dokter
        //         ->leftjoin('rs9', 'rs9.rs1', '=', 'rs17.rs14') //sistembayar
        //         ->leftjoin('rs222', 'rs222.rs1', '=', 'rs17.rs1') //sep
        //         ->leftjoin('master_poli_bpjs', 'rs19.rs6', '=', 'master_poli_bpjs.kode')
        //         ->leftjoin('memodiagnosadokter', 'memodiagnosadokter.noreg', '=', 'rs17.rs1')
        //         ->whereBetween('rs17.rs3', [$tgl, $tglx])
        //         // ->where('rs17.rs8', $user->kdruangansim ?? '')
        //         ->where('rs19.rs4', '=', 'Poliklinik')
        //         ->where('rs17.rs8', 'LIKE', '%' . $ruangan)
        //         ->where('rs17.rs8', '!=', 'POL014')
        //         //    ->where('rs9.rs9', '=', 'BPJS')
        //         ->where(function ($sts) use ($status) {
        //             if ($status !== 'all') {
        //                 if ($status === '') {
        //                     $sts->where('rs17.rs19', '!=', '1');
        //                 } else {
        //                     $sts->where('rs17.rs19', '=', $status);
        //                 }
        //             }
        //         })
        //         ->where(function ($query) {
        //             $query->where('rs15.rs2', 'LIKE', '%' . request('q') . '%')
        //                 ->orWhere('rs15.rs46', 'LIKE', '%' . request('q') . '%')
        //                 ->orWhere('rs17.rs2', 'LIKE', '%' . request('q') . '%')
        //                 ->orWhere('rs17.rs1', 'LIKE', '%' . request('q') . '%')
        //                 ->orWhere('rs19.rs2', 'LIKE', '%' . request('q') . '%')
        //                 ->orWhere('rs21.rs2', 'LIKE', '%' . request('q') . '%')
        //                 ->orWhere('rs222.rs8', 'LIKE', '%' . request('q') . '%')
        //                 ->orWhere('rs9.rs2', 'LIKE', '%' . request('q') . '%');
        //         })
        //         // ->where('rs17.rs8', 'LIKE', '%' . request('kdpoli') . '%')

        //         ->with([
        //             'anamnesis', 'datasimpeg:id,nip,nik,nama,kelamin,foto,kdpegsimrs,kddpjp',
        //             'gambars',
        //             'fisio',
        //             'diagnosakeperawatan' => function ($diag) {
        //                 $diag->with('intervensi.masterintervensi');
        //             },
        //             'laborats' => function ($t) {
        //                 $t->with('details.pemeriksaanlab')
        //                     ->orderBy('id', 'DESC');
        //             },
        //             'radiologi' => function ($t) {
        //                 $t->orderBy('id', 'DESC');
        //             },
        //             'penunjanglain' => function ($t) {
        //                 $t->with('masterpenunjang')->orderBy('id', 'DESC');
        //             },
        //             'tindakan' => function ($t) {
        //                 $t->with('mastertindakan:rs1,rs2', 'pegawai:nama,kdpegsimrs')
        //                     ->orderBy('id', 'DESC');
        //             },
        //             'diagnosa' => function ($d) {
        //                 $d->with('masterdiagnosa');
        //             },
        //             'pemeriksaanfisik' => function ($a) {
        //                 $a->with(['detailgambars', 'pemeriksaankhususmata', 'pemeriksaankhususparu'])
        //                     ->orderBy('id', 'DESC');
        //             },
        //             'ok' => function ($q) {
        //                 $q->orderBy('id', 'DESC');
        //             },
        //             'taskid' => function ($q) {
        //                 $q->orderBy('taskid', 'DESC');
        //             },
        //             'planning' => function ($p) {
        //                 $p->with(
        //                     'masterpoli',
        //                     'rekomdpjp',
        //                     'transrujukan',
        //                     'listkonsul',
        //                     'spri',
        //                     'ranap',
        //                     'kontrol'
        //                 )->orderBy('id', 'DESC');
        //             },
        //             'edukasi' => function ($x) {
        //                 $x->orderBy('id', 'DESC');
        //             },
        //             'antrian_ambil' => function ($o) {
        //                 $o->where('pelayanan_id', request('kdpoli'));
        //             },
        //             'diet' => function ($diet) {
        //                 $diet->orderBy('id', 'DESC');
        //             },
        //             'sharing' => function ($sharing) {
        //                 $sharing->orderBy('id', 'DESC');
        //             }
        //         ])
        //         ->orderby('rs17.rs3', 'ASC')
        //         ->paginate(request('per_page'));
        //     // ->simplePaginate(request('per_page'));
        //     // ->get();
        // }
        event(new ChatMessageEvent('hello', 'poli', auth()->user()));
        return new JsonResponse($daftarkunjunganpasienbpjs);
    }

    public function save_pemeriksaanfisik(Request $request)
    {
        return new JsonResponse($request->all());
    }

    public function flagfinish(Request $request)
    {
        $input = new Request([
            'noreg' => $request->noreg
        ]);
        $cek = Bpjsrespontime::where('noreg', $request->noreg)->where('taskid', 5)->count();

        if ($cek === 0 || $cek === '') {
            BridantrianbpjsController::updateWaktu($input, 5);
        }
        $user = Pegawai::find(auth()->user()->pegawai_id);
        if ($user->kdgroupnakes === 1 || $user->kdgroupnakes === '1') {
            $updatekunjungan = KunjunganPoli::where('rs1', $request->noreg)->first();
            $updatekunjungan->rs19 = '1';
            $updatekunjungan->rs24 = '1';
            $updatekunjungan->save();
            return new JsonResponse(['message' => 'ok'], 200);
        } else {
            return new JsonResponse(['message' => 'MAAF FITUR INI HANYA UNTUK DOKTER...!!!'], 500);
        }
    }

    public function terimapasien(Request $request)
    {


        $cekx = KunjunganPoli::select('rs1', 'rs2', 'rs3', 'rs4', 'rs9', 'rs19')->where('rs1', $request->noreg)
            ->with([
                'anamnesis',
                'datasimpeg:id,nip,nik,nama,kelamin,foto,kdpegsimrs,kddpjp,ttdpegawai',
                'gambars',
                'fisio',
                'laporantindakan',
                'psikiatri',
                'datacasmix',
                // 'pediatri'=> function($neo){
                //     $neo->with(['pegawai:id,nama']);
                // },
                'dokumenluar' => function ($neo) {
                    $neo->with(['pegawai:id,nama']);
                },
                // 'kandungan'=> function($neo){
                //     $neo->with(['pegawai:id,nama']);
                // },
                // 'neonatusmedis'=> function($neo){
                //     $neo->with(['pegawai:id,nama']);
                // },
                // 'neonatuskeperawatan'=> function($neo){
                //     $neo->with(['pegawai:id,nama']);
                // },
                'diagnosakeperawatan' => function ($diag) {
                    $diag->with('intervensi.masterintervensi');
                },
                'diagnosakebidanan' => function ($diag) {
                    $diag->with('intervensi.masterintervensi');
                },
                'laborats' => function ($t) {
                    $t->with('details.pemeriksaanlab')
                        ->orderBy('id', 'DESC');
                },
                'radiologi' => function ($t) {
                    $t->orderBy('id', 'DESC');
                },
                'hasilradiologi' => function ($t) {
                    $t->orderBy('id', 'DESC');
                },
                'penunjanglain' => function ($t) {
                    $t->with('masterpenunjang')->orderBy('id', 'DESC');
                },
                'tindakan' => function ($t) {
                    $t->with('mastertindakan:rs1,rs2', 'pegawai:nama,kdpegsimrs', 'pelaksanalamasimrs:nama,kdpegsimrs', 'gambardokumens:id,rs73_id,nama,original,url', 'sambungan:rs73_id,ket')
                        ->orderBy('id', 'DESC');
                },
                'diagnosa' => function ($d) {
                    $d->with('masterdiagnosa');
                },
                'pemeriksaanfisik' => function ($a) {
                    $a->with(['detailgambars', 'pemeriksaankhususmata', 'pemeriksaankhususparu'])
                        ->orderBy('id', 'DESC');
                },
                'ok' => function ($q) {
                    $q->orderBy('id', 'DESC');
                },
                'kamaroperasi' => function ($kamaroperasi) {
                    $kamaroperasi->with(['mastertindakanoperasi', 'laporanoperasi']);
                },
                'taskid' => function ($q) {
                    $q->orderBy('taskid', 'DESC');
                },
                'bpjssuratkontrol',
                'laboratold' => function ($t) {
                    $t->select('rs51.*', 'rs49.rs2 as pemeriksaan', 'rs49.rs21 as paket')->with(
                        [
                            'pemeriksaanlab',
                            'interpretasi'
                        ]
                    )
                        ->leftjoin('rs49', 'rs49.rs1', 'rs51.rs4')
                        ->orderBy('id', 'DESC');
                },
                'planning' => function ($p) {
                    $p->with([
                        'masterpoli',
                        'rekomdpjp' => function ($q) {
                            $q->orderBy('id', 'DESC');
                        },
                        'transrujukan.diagnosa:rs1,rs4',
                        'listkonsul',
                        'spri',
                        'ranap',
                        'kontrol',
                        'operasi',
                    ])->orderBy('id', 'DESC');
                },
                'edukasi' => function ($x) {
                    $x->orderBy('id', 'DESC');
                },
                'jawabankonsul' => function ($x) {
                    $x->with([
                        'poliAsal:rs1,rs2',
                        'poliTujuan:rs1,rs2',
                    ])
                        ->orderBy('id', 'DESC');
                },
                'jawabankonsulbynoreg' => function ($x) {
                    $x->select('jawaban_konsul_polis.noreg_lama as noreg', 'jawaban_konsul_polis.*')->with([
                        'poliAsal:rs1,rs2',
                        'poliTujuan:rs1,rs2',
                    ])
                        ->orderBy('id', 'DESC');
                },
                'diet' => function ($diet) {
                    $diet->orderBy('id', 'DESC');
                },
                'sharing' => function ($sharing) {
                    $sharing->orderBy('id', 'DESC');
                },
                'newapotekrajal' => function ($newapotekrajal) {
                    $newapotekrajal->with([
                        'permintaanresep.mobat:kd_obat,nama_obat,kode_bpjs',
                        'permintaanracikan.mobat:kd_obat,nama_obat,kode_bpjs',
                        'rincian.mobat:kd_obat,nama_obat',
                        'rincianracik.mobat:kd_obat,nama_obat',
                        // 'sistembayar',
                        // 'dokter:nama,kdpegsimrs',
                    ])
                        ->orderBy('id', 'DESC');
                },
                'intradialitikhd'
                // ini buat satset
                // resep keluar fix
                // 'apotek' => function ($apot) {
                //     $apot->whereIn('flag', ['3', '4'])->with([
                //         // 'rincian.mobat:kd_obat,nama_obat',
                //         // 'rincianracik.mobat:kd_obat,nama_obat',
                //         'rincian' => function ($ri) {
                //             $ri->select(
                //                 'resep_keluar_r.kdobat',
                //                 'resep_keluar_r.noresep',
                //                 'resep_keluar_r.jumlah',
                //                 'resep_keluar_r.aturan', // signa
                //                 'retur_penjualan_r.jumlah_retur',
                //                 DB::raw('
                //                 CASE
                //                 WHEN retur_penjualan_r.jumlah_retur IS NOT NULL THEN resep_keluar_r.jumlah - retur_penjualan_r.jumlah_retur
                //                 ELSE resep_keluar_r.jumlah
                //                 END as qty
                //                 ') // iki jumlah obat sing non racikan mas..
                //             )
                //                 ->leftJoin('retur_penjualan_r', function ($jo) {
                //                     $jo->on('retur_penjualan_r.kdobat', '=', 'resep_keluar_r.kdobat')
                //                         ->on('retur_penjualan_r.noresep', '=', 'resep_keluar_r.noresep');
                //                 })
                //                 ->with([
                //                     'mobat.kfa' // sing nang kfa iki jupuk kolom dosage_form karo active_ingredients
                //                     // 'mobat:kelompok_psikotropika' // flag obat narkotika, 1 = obat narkotika
                //                     // 'mobat:bentuk_sediaan' // bisa dijadikan patoka apakah obat minum, injeksi atau yang lain, cuma perlu di bicarakan dengan farmasi untuk detailnya
                //                 ]);
                //         },
                //         'rincianracik' => function ($ri) {
                //             $ri->select(
                //                 'resep_keluar_racikan_r.kdobat',
                //                 'resep_keluar_racikan_r.noresep',
                //                 'resep_keluar_racikan_r.jumlah',
                //                 'resep_keluar_racikan_r.jumlahdibutuhkan as qty', // MedicationRequest.dispenseRequest.quantity dan non-dtd -> Medication.ingredient.strength.denominator
                //                 'resep_keluar_racikan_r.tiperacikan', // dtd / non-dtd
                //                 'resep_permintaan_keluar_racikan.dosismaksimum', // dtd -> Medication.ingredient.strength.numerator
                //                 'resep_permintaan_keluar_racikan.aturan', // signa
                //             )
                //                 ->leftJoin('resep_permintaan_keluar_racikan', function ($jo) {
                //                     $jo->on('resep_permintaan_keluar_racikan.kdobat', '=', 'resep_keluar_racikan_r.kdobat')
                //                         ->on('resep_permintaan_keluar_racikan.noresep', '=', 'resep_keluar_racikan_r.noresep');
                //                 })
                //                 ->with([
                //                     'mobat.kfa' // sing nang kfa iki jupuk kolom dosage_form karo active_ingredients
                //                     // 'mobat:kelompok_psikotropika' // flag obat narkotika, 1 = obat narkotika
                //                     // 'mobat:bentuk_sediaan' // bisa dijadikan patoka apakah obat minum, injeksi atau yang lain, cuma perlu di bicarakan dengan farmasi untuk detailnya
                //                 ]);
                //         }

                //     ])
                //         ->orderBy('id', 'DESC');
                // },

                // resep prb
                // 'prb' => function ($p) {
                //     $p->select(
                //         'noreg'
                //     )
                //         ->whereIn('flag', ['3', '4'])
                //         ->where('tiperesep', 'prb');
                // }
            ])
            ->first();

        if ($cekx) {
            $flag = $cekx->rs19;

            if ($flag === '') {
                $cekx->rs19 = '2';
                $cekx->save();
            }

            // return new JsonResponse($cekx, 200);
            return new JsonResponse([
                'result' => $cekx
            ], 200);
        } else {
            return response()->json([
                'message' => 'Data tidak ditemukan'
            ], 500);
        }
    }

    public function updatewaktubpjs(Request $request)
    {
        $input = new Request([
            'noreg' => $request->noreg
        ]);
        $cek = Bpjsrespontime::where('noreg', $request->noreg)->where('taskid', 4)->count();

        if ($cek === 0 || $cek === '') {
            BridantrianbpjsController::updateWaktu($input, 4);
        }
    }

    public function listdokter()
    {
        $listdokter = Mpegawaisimpeg::select('kdpegsimrs', 'nama')
            ->where('aktif', 'AKTIF')->where('kdgroupnakes', '1')
            ->get();

        return new JsonResponse($listdokter);
    }

    public function gantidpjp(Request $request)
    {
        $carikunjungan = KunjunganPoli::where('rs1', $request->noreg)->first();
        $carikunjungan->rs9 = $request->kdpegsimrs;
        $carikunjungan->save();
        return new JsonResponse(
            [
                'message' => 'ok',
                'result' => $carikunjungan->load('datasimpeg:id,nip,nik,nama,kelamin,foto,kdpegsimrs,kddpjp'),
            ],
            200
        );
    }


    public function gantimemo(Request $request)
    {
        $userid = FormatingHelper::session_user();
        $data = Memodiagnosadokter::updateOrCreate(
            [
                'noreg' => $request->noreg,
                'kdruang' => $request->kdruang
            ],
            [
                'diagnosa' => $request->memo,
                'user' => $userid['kodesimrs']
            ],
        );
        return new JsonResponse(
            [
                'message' => 'ok',
                'result' => $data,
            ],
            200
        );
    }

    public function icare()
    {
        // $wew = FormatingHelper::session_user();
        // $x = $wew['kdgroupnakes'];
        // $kddpjp = $wew['kddpjp'];
        $kddpjp = request('dpjp');
        // if ($x === '1') {
        if ($kddpjp === '') {
            return new JsonResponse(['message' => 'Maaf Akun Anda Belum Termaping dengan Aplikasi Hafis...!!! '], 500);
        }
        $noka = request('noka');
        $data = [
            "param" => $noka,
            "kodedokter" => (int) $kddpjp
        ];

        // $data = [
        //     "param" => '0001538822259',
        //     "kodedokter" => 256319
        // ];

        $icare = BridgingbpjsHelper::post_url(
            'icare',
            'api/rs/validate',
            $data
        );
        return $icare;
        // } else {
        //     return new JsonResponse(['message' => 'Maaf Fitur ini Hanya Untuk Dokter...!!!'], 500);
        // }
    }

    public function konsulpoli(Request $request)
    {
        // return new JsonResponse($request->all());



        // $konsulan = KunjunganPoli::where('rs4', $request->noreg)->where('rs14','!=', 'UMUM')->count();
        $konsulan = KunjunganPoli::where('rs4', $request->noreg)->count();
        if ($konsulan > 0) {
            return new JsonResponse(['message' => 'Pasien sudah pernah di konsulkan oleh poli ini hari ini'], 500);
        }
        if ($request->kdpoli_asal == $request->kdpoli_tujuan) {
            return new JsonResponse(['message' => 'Maaf, tidak boleh konsultasi ke polinya sendiri.'], 500);
        }

        $tglmasukx = Carbon::create($request->tgl_kunjungan);
        $tglmasuk = $tglmasukx->toDateString();
        $cekpoli = KunjunganPoli::where('rs2', $request->norm)
            ->where('rs8', $request->kdpoli_tujuan)
            ->whereDate('rs3', $tglmasuk)
            ->count();

        if ($cekpoli > 0) {
            return new JsonResponse(['message' => 'PASIEN SUDAH ADA DI HARI DAN POLI YANG SAMA'], 500);
        }

        DB::select('call reg_rajal(@nomor)');
        $hcounter = DB::table('rs1')->select('rs13')->get();
        $wew = $hcounter[0]->rs13;
        $noreg = FormatingHelper::gennoreg($wew, 'J');

        $input = new Request([
            'noreg' => $noreg
        ]);

        $userid = FormatingHelper::session_user();
        $caribiaya = MtindakanX::select('rs8 as sarana', 'rs9 as pelayanan')->where('rs1', 'T00009')->first();
        $biaya = Karcispoli::firstOrCreate(
            [
                'rs2' => $request->norm,
                'rs4' => date('Y-m-d H:i:s'),
                'rs3' => 'K3#',
            ],
            [
                'rs1' => $noreg,
                'rs5' => 'D',
                'rs6' => 'Konsultasi Antar Poliklinik',
                'rs7' => $caribiaya->sarana,
                'rs8' => $request->kodesistembayar,
                'rs10' => $userid['kodesimrs'],
                'rs11' => $caribiaya->pelayanan,
                'rs12' => $userid['kodesimrs'],
                'rs13' => '1'
            ]
        );



        // 09-Mei-2024 aku hari ganti atau menambahkan kode ini disuruh otomatiskan DPJP sama keputusan rapat (jare mbak septi)
        $kdPegSimrs = $request->kddokter_asal ?? '';

        $simpankunjunganpoli = KunjunganPoli::create([
            'rs1' => $noreg,
            'rs2' => $request->norm,
            'rs3' => date('Y-m-d H:i:s'),
            'rs4' => $request->noreg_lama,
            // 'rs6' => $request->asalrujukan,
            'rs6' => '2',
            'rs8' => $request->kdpoli_tujuan,
            //'rs9' => $request->dpjp,
            'rs9' => $kdPegSimrs,
            'rs10' => 0,
            'rs11' => '',
            'rs12' => 0,
            'rs13' => 0,
            'rs14' => $request->kodesistembayar,
            'rs15' => (int) $caribiaya->sarana + (int) $caribiaya->pelayanan,
            'rs18' => $userid['kodesimrs'],
            'rs20' => $request->kdpoli_asal,

        ]);
        if (!$simpankunjunganpoli) {
            return new JsonResponse(['message' => 'kunjungan tidak tersimpan'], 500);
        }

        $plann = new PlaningController;
        $cetakantrian = AntrianController::ambilnoantrian($request, $input);
        $akhir = PlaningController::simpanakhir($request);
        PlaningController::simpankonsulantarpoli($request);
        PlaningController::createJawabanKonsul($request, $noreg, $akhir);
        $data = $plann->getAllRespPlanning($request->noreg);
        $sep = Seprajal::where('rs1', $request->noreg)->first();
        if (isset($sep)) {
            Seprajal::firstOrCreate(
                ['rs1' => $noreg],
                [
                    'rs2' => $sep->rs2,
                    'rs3' => $sep->rs3,
                    'rs4' => $sep->rs4,
                    'rs5' => $sep->rs5,
                    'rs6' => $sep->rs6,
                    'rs7' => $sep->rs7,
                    'rs8' => $sep->rs8,
                    'rs9' => $sep->rs9,
                    'rs10' => $sep->rs10,
                    'rs11' => $sep->rs11,
                    'rs12' => $sep->rs12,
                    'rs13' => $sep->rs13,
                    'rs14' => $sep->rs14,
                    'rs15' => $sep->rs15,
                    'rs16' => $sep->rs16,
                    'rs17' => $sep->rs17,
                    'rs18' => $sep->rs18,
                    'laka' => $sep->laka,
                    'lokasilaka' => $sep->lokasilaka,
                    'penjaminlaka' => '',
                    'users' => auth()->user()->pegawai_id ?? 'anu',
                    'notelepon' => $sep->notelepon,
                    'tgl_entery' => $sep->tgl_entery,
                    'noDpjp' => $sep->noDpjp,
                    'tgl_kejadian_laka' => $sep->tgl_kejadian_laka,
                    'keterangan' => $sep->keterangan,
                    'suplesi' => $sep->suplesi,
                    'nosuplesi' => $sep->nosuplesi,
                    'kdpropinsi' => $sep->kdpropinsi,
                    'propinsi' => $sep->propinsi,
                    'kdkabupaten' => $sep->kdkabupaten,
                    'kabupaten' => $sep->kabupaten,
                    'kdkecamatan' => $sep->kdkecamatan,
                    'kecamatan' => $sep->kecamatan,
                    'kodedokterdpjp' => $sep->kodedokterdpjp,
                    'dokterdpjp' => $sep->dokterdpjp,
                    'kodeasalperujuk' => $sep->kodeasalperujuk,
                    'namaasalperujuk' => $sep->namaasalperujuk,
                    'Dinsos' => $sep->Dinsos,
                    'prolanisPRB' => $sep->prolanisPRB,
                    'noSKTM' => $sep->noSKTM,
                    'jeniskunjungan' => $sep->jeniskunjungan,
                    'tujuanKunj' => $sep->tujuanKunj,
                    'flagProcedure' => $sep->flagProcedure,
                    'kdPenunjang' => $sep->kdPenunjang,
                    'assesmentPel' => $sep->assesmentPel,
                    'kdUnit' => $sep->kdUnit
                ]
            );
        }
        return new JsonResponse([
            'message' => 'Pasien sudah dikirim ke poli tujuan',
            'antrian' => $cetakantrian,
            'result' => $data,
            'noreg' => $noreg
        ], 200);
    }

    public function tidakhadir(Request $request)
    {
        $input = new Request([
            'noreg' => $request->noreg
        ]);
        $cek = Bpjsrespontime::where('noreg', $request->noreg)->where('taskid', 3)->count();

        if ($cek === 0 || $cek === '') {
            BridantrianbpjsController::updateWaktu($input, 3);
        }

        $updatekunjungan = KunjunganPoli::where('rs1', $request->noreg)->first();
        $updatekunjungan->rs19 = '3';
        $updatekunjungan->rs24 = '1';
        $updatekunjungan->save();
        return new JsonResponse(['message' => 'ok'], 200);
    }

    public function kirimpenjaminan(Request $request)
    {
        $simpan = listcasmixrajal::updateOrCreate(
            [
                'noreg' => $request->noreg
            ],
            [
                'norm' =>  $request->norm,
                'noka' =>  $request->noka,
                'nosep' => $request->nosep,
                'kodepoli' => $request->kodepoli,
                'kdsistembayar' => $request->kdsistembayar,
                'kddpjp' => $request->kddpjp,
                'flaging' => $request->flaging,
            ]
        );

        return new JsonResponse(['message' => 'Data Pasien Sudah Terkirim Ke Penjaminan'], 200);
    }
}
