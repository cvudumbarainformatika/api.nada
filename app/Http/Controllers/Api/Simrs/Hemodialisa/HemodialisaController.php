<?php

namespace App\Http\Controllers\Api\Simrs\Hemodialisa;

use App\Events\ChatMessageEvent;
use App\Events\NotifMessageEvent;
use App\Helpers\BridgingbpjsHelper;
use App\Helpers\FormatingHelper;
use App\Http\Controllers\Api\Simrs\Antrian\AntrianController;
use App\Http\Controllers\Api\Simrs\Pendaftaran\Rajal\BridantrianbpjsController;
use App\Http\Controllers\Api\Simrs\Planing\PlaningController;
use App\Http\Controllers\Controller;
use App\Models\KunjunganRawatInap;
use App\Models\Pegawai\Mpegawaisimpeg;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Anamnesis\Anamnesis;
use App\Models\Simrs\Kasir\Pembayaran;
use App\Models\Simrs\Master\MtindakanX;
use App\Models\Simrs\Pelayanan\Diagnosa\Diagnosakeperawatan;
use App\Models\Simrs\Pendaftaran\Karcispoli;
use App\Models\Simrs\Pendaftaran\Rajalumum\Bpjsrespontime;
use App\Models\Simrs\Pendaftaran\Rajalumum\Seprajal;
use App\Models\Simrs\Penunjang\Lain\Lain;
use App\Models\Simrs\Rajal\KunjunganPoli;
use App\Models\Simrs\Rajal\Memodiagnosadokter;
use App\Models\Simrs\Rajal\WaktupulangPoli;
use App\Models\Simrs\Ranap\Kunjunganranap;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PHPUnit\TextUI\XmlConfiguration\Group;

class HemodialisaController extends Controller
{

    public function index()
    {

        $total = self::query_table()->get()->count();
        $data = self::query_table()->simplePaginate(request('per_page'));

        $response = (object)[
            'total' => $total,
            'data' => $data
        ];

        return response()->json($response);
    }

    public function query_table()
    {
        if (request('to') === '' || request('from') === null) {
            $tgl = Carbon::now()->format('Y-m-d 00:00:00');
            $tglx = Carbon::now()->format('Y-m-d 23:59:59');
        } else {
            $tgl = request('to') . ' 00:00:00';
            $tglx = request('from') . ' 23:59:59';
        }

        $sort = request('sort') === 'terbaru' ? 'DESC' : 'ASC';
        $status = request('status') ?? 'Semua';

        $permintaan = self::permintaanFisio($tgl, $tglx, $sort, $status);
        $query = KunjunganPoli::query();

        $select = $query->select(
            'rs17.rs1 as noreg',
            'rs17.rs2 as norm',
            'rs17.rs3 as tgl_kunjungan',
            'rs17.rs8 as kdruangan',
            'rs17.rs8 as koderuangan',
            'rs17.rs8 as kodepoli',
            'rs17.rs8 as kdgroup_ruangan',
            'rs17.rs14 as kodesistembayar',
            'rs17.rs19 as status',
            'rs17.rs9 as kodedokter',
            'rs21.rs2 as dokter',

            DB::raw('concat(rs15.rs3," ",rs15.gelardepan," ",rs15.rs2," ",rs15.gelarbelakang) as nama'),
            DB::raw('concat(rs15.rs4," KEL ",rs15.rs5," RT ",rs15.rs7," RW ",rs15.rs8," ",rs15.rs6," ",rs15.rs11," ",rs15.rs10) as alamat'),
            DB::raw('concat(TIMESTAMPDIFF(YEAR, rs15.rs16, CURDATE())," Tahun ",
                TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()) % 12," Bulan ",
                TIMESTAMPDIFF(DAY, TIMESTAMPADD(MONTH, TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()), rs15.rs16), CURDATE()), " Hari") AS usia'),
            'rs15.rs2 as nama_panggil',
            'rs15.rs16 as tgllahir',
            'rs15.rs17 as kelamin',
            'rs15.rs19 as pendidikan',
            'rs15.rs22 as agama',
            'rs15.rs37 as templahir',
            'rs15.rs39 as suku',
            'rs15.rs40 as jenispasien',
            'rs15.rs46 as noka',
            'rs15.rs49 as noktp',
            'rs15.rs55 as nohp',

            // 'permintaan.rs2 as nota_permintaan',
            DB::raw('(CASE WHEN permintaan.rs2 ="" THEN NULL ELSE permintaan.rs2 END) as nota_permintaan'),

            // 'rs17.rs4 as penanggungjawab',
            // 'rs17.rs6 as kodeasalrujukan',
            // 'rs17.rs20 as asalpendaftaran',
            // 'rs17.rs7 as namaperujuk',
            'rs19.rs2 as ruangan',
            // 'rs19.rs6 as kodepolibpjs',
            // 'rs19.panggil_antrian as panggil_antrian',
            // 'rs17.rs9 as kodedokter',
            // // 'master_poli_bpjs.nama as polibpjs',
            // 'rs21.rs2 as dokter',
            'rs9.rs2 as sistembayar',
            'rs9.groups as groups',
            'listkirimcasmixRajal.noreg as kesmik',


            // 'rs222.rs8 as sep',
            // 'rs222.rs5 as norujukan',
            // 'rs222.kodedokterdpjp as kodedokterdpjp',
            // 'rs222.dokterdpjp as dokterdpjp',
            // 'rs222.kdunit as kdunit',
            // 'memodiagnosadokter.diagnosa as memodiagnosa',
            // 'rs141.rs4 as status_tunggu',
            // 'rs24.rs2 as ruangan',
            // 'rs23.rs2 as status_masuk',
            // 'antrian_ambil.nomor as noantrian'
        )
            ->leftjoin('rs107 as permintaan', 'rs17.rs1', '=', 'permintaan.rs1') //permintaan
            ->leftjoin('rs15', 'rs15.rs1', '=', 'rs17.rs2') //pasien
            ->leftjoin('rs21', 'rs21.rs1', '=', 'rs17.rs9') //dokter
            ->leftjoin('rs9', 'rs9.rs1', '=', 'rs17.rs14') //sistembayar
            ->leftjoin('rs19', 'rs19.rs1', '=', 'rs17.rs8') //poli
            ->leftjoin('listkirimcasmixRajal', 'listkirimcasmixRajal.noreg', 'rs17.rs1')
            // ->leftjoin('rs222', 'rs222.rs1', '=', 'rs17.rs1') //sep
            // ->leftjoin('rs141', 'rs141.rs1', '=', 'rs17.rs1') // status pasien di IGD
            // ->leftjoin('rs24', 'rs24.rs1', '=', 'rs141.rs5') // nama ruangan
            // ->leftjoin('rs23', 'rs23.rs1', '=', 'rs141.rs1') // status masuk
            // ->leftjoin('master_poli_bpjs', 'rs19.rs6', '=', 'master_poli_bpjs.kode')
            // ->leftjoin('memodiagnosadokter', 'memodiagnosadokter.noreg', '=', 'rs17.rs1')
            // ->leftjoin('antrian_ambil', 'antrian_ambil.noreg', 'rs17.rs1');
        ;

        $q = $select
            ->whereBetween('rs17.rs3', [$tgl, $tglx])
            ->where('rs17.rs8', '=', 'PEN005')
            ->where(function ($sts) use ($status) {
                if ($status !== 'Semua') {
                    if ($status === 'Terlayani') {
                        $sts->where('rs17.rs19', '=', '1');
                    } else {
                        $sts->where('rs17.rs19', '=', '');
                    }
                }
            })
            ->where(function ($query) {
                $query->where('rs17.rs1', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs17.rs2', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs15.rs46', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs15.rs2', 'LIKE', '%' . request('q') . '%')
                    // ->orWhere('rs19.rs2', 'LIKE', '%' . request('q') . '%')
                    // ->orWhere('rs21.rs2', 'LIKE', '%' . request('q') . '%')
                    // ->orWhere('rs222.rs8', 'LIKE', '%' . request('q') . '%')
                    // ->orWhere('rs9.rs2', 'LIKE', '%' . request('q') . '%')
                ;
            })

            ->union($permintaan)
            // ->groupBy('rs17.rs1')
        ;
        // ->orderby('rs17.rs3', $sort);

        // dd($q->toSql());
        $result = $q
            // $result = DB::table(DB::raw("({$q->toSql()}  UNION ALL {$permintaan->toSql()} ) as fisio"))
            // ->mergeBindings($q)
            // ->mergeBindings($permintaan)
            // ->whereBetween('tgl_kunjungan', [$tgl, $tglx])
            // ->orderBy('tgl_kunjungan', $sort)
            // ->limit(100)
            // ->get()
        ;

        return $result;
    }

    static function permintaanFisio($tgl, $tglx, $sort, $status)
    {
        $data = Lain::query();
        $select = $data->select(
            'rs107.rs1 as noreg',
            // 'rs107.rs2 as norm',
            DB::raw('( CASE WHEN rs17.rs2 IS NOT NULL THEN rs17.rs2 ELSE rs23.rs2 END ) as norm'),
            'rs107.rs3 as tgl_kunjungan',
            'rs107.rs10 as kdruangan',
            'rs107.rs10 as koderuangan',
            'rs107.rs10 as kodepoli',
            'rs107.rs10 as kdgroup_ruangan',
            DB::raw('coalesce(rs17.rs14, rs23.rs19) as kodesistembayar'),
            // DB::raw('coalesce(rs17.rs19, rs23.rs22) as status'),
            // 'rs107.rs9 as status',
            DB::raw('CASE WHEN rs107.rs9 = "2" THEN "1" ELSE "" END as status'),
            DB::raw('coalesce(pasien17.rs9, pasien23.rs10) as kddokter'),
            'rs21.rs2 as dokter',

            DB::raw(
                'coalesce(
          concat(pasien17.rs3," ",pasien17.gelardepan," ",pasien17.rs2," ",pasien17.gelarbelakang),
          concat(pasien23.rs3," ",pasien23.gelardepan," ",pasien23.rs2," ",pasien23.gelarbelakang)
        ) as nama'
            ),
            DB::raw('coalesce(
          concat(pasien17.rs4," KEL ",pasien17.rs5," RT ",pasien17.rs7," RW ",pasien17.rs8," ",pasien17.rs6," ",pasien17.rs11," ",pasien17.rs10),
          concat(pasien23.rs4," KEL ",pasien23.rs5," RT ",pasien23.rs7," RW ",pasien23.rs8," ",pasien23.rs6," ",pasien23.rs11," ",pasien23.rs10)
        )
        as alamat'),
            DB::raw('coalesce(
          concat(TIMESTAMPDIFF(YEAR, pasien17.rs16, CURDATE())," Tahun ",
          TIMESTAMPDIFF(MONTH, pasien17.rs16, CURDATE()) % 12," Bulan ",
          TIMESTAMPDIFF(DAY, TIMESTAMPADD(MONTH, TIMESTAMPDIFF(MONTH, pasien17.rs16, CURDATE()), pasien17.rs16), CURDATE()), " Hari"),
          concat(TIMESTAMPDIFF(YEAR, pasien23.rs16, CURDATE())," Tahun ",
          TIMESTAMPDIFF(MONTH, pasien23.rs16, CURDATE()) % 12," Bulan ",
          TIMESTAMPDIFF(DAY, TIMESTAMPADD(MONTH, TIMESTAMPDIFF(MONTH, pasien23.rs16, CURDATE()), pasien23.rs16), CURDATE()), " Hari")
        )
        AS usia'),
            DB::raw('coalesce(pasien17.rs2, pasien23.rs2) as nama_panggil'),
            DB::raw('coalesce(pasien17.rs16, pasien23.rs16) as tgllahir'),
            DB::raw('coalesce(pasien17.rs17, pasien23.rs17) as kelamin'),
            DB::raw('coalesce(pasien17.rs18, pasien23.rs18) as pendidikan'),
            DB::raw('coalesce(pasien17.rs22, pasien23.rs22) as agama'),
            DB::raw('coalesce(pasien17.rs37, pasien23.rs37) as templahir'),
            DB::raw('coalesce(pasien17.rs39, pasien23.rs39) as suku'),
            DB::raw('coalesce(pasien17.rs40, pasien23.rs40) as jenispasien'),
            DB::raw('coalesce(pasien17.rs46, pasien23.rs46) as noka'),
            DB::raw('coalesce(pasien17.rs49, pasien23.rs49) as noktp'),
            DB::raw('coalesce(pasien17.rs55, pasien23.rs55) as nohp'),
            DB::raw('(CASE WHEN rs107.rs2 ="" THEN NULL ELSE rs107.rs2 END) as nota_permintaan'),
            // 'rs107.rs2 as nota_permintaan',


            // 'rs17.rs8 as koderuangan',
            // 'rs17.rs4 as penanggungjawab',
            // 'rs17.rs6 as kodeasalrujukan',
            // 'rs17.rs20 as asalpendaftaran',
            // 'rs17.rs7 as namaperujuk',
            // 'rs19.rs2 as ruangan',
            DB::raw(
                '(
            CASE
                WHEN rs19.rs4 IS NOT NULL THEN rs19.rs2 ELSE rs24.rs2
            END
        ) as ruangan'
            ),
            // DB::raw('coalesce(rs19.rs2, rs24.rs2, null) as ruangan'),
            //   ),
            // 'rs19.rs6 as kodepolibpjs',
            // 'rs19.panggil_antrian as panggil_antrian',
            // 'rs17.rs9 as kodedokter',
            // // 'master_poli_bpjs.nama as polibpjs',
            // 'rs21.rs2 as dokter',
            'rs9.rs2 as sistembayar',
            'rs9.groups as groups',
            // 'rs15.rs2 as nama_panggil',
            // DB::raw('concat(rs15.rs3," ",rs15.gelardepan," ",rs15.rs2," ",rs15.gelarbelakang) as nama'),
            // DB::raw('concat(rs15.rs4," KEL ",rs15.rs5," RT ",rs15.rs7," RW ",rs15.rs8," ",rs15.rs6," ",rs15.rs11," ",rs15.rs10) as alamat'),
            // DB::raw('concat(TIMESTAMPDIFF(YEAR, rs15.rs16, CURDATE())," Tahun ",
            //         TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()) % 12," Bulan ",
            //         TIMESTAMPDIFF(DAY, TIMESTAMPADD(MONTH, TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()), rs15.rs16), CURDATE()), " Hari") AS usia'),
            // 'rs15.rs16 as tgllahir',
            // 'rs15.rs17 as kelamin',
            // 'rs15.rs19 as pendidikan',
            // 'rs15.rs22 as agama',
            // 'rs15.rs37 as templahir',
            // 'rs15.rs39 as suku',
            // 'rs15.rs40 as jenispasien',
            // 'rs15.rs46 as noka',
            // 'rs15.rs49 as nktp',
            // 'rs15.rs55 as nohp',
            // 'rs222.rs8 as sep',
            // 'rs222.rs5 as norujukan',
            // 'rs222.kodedokterdpjp as kodedokterdpjp',
            // 'rs222.dokterdpjp as dokterdpjp',
            // 'rs222.kdunit as kdunit',
            // 'memodiagnosadokter.diagnosa as memodiagnosa',
            // 'rs141.rs4 as status_tunggu',
            // 'rs24.rs2 as ruangan',
            // 'rs23.rs2 as status_masuk',
            // 'antrian_ambil.nomor as noantrian'
            // 'rs17.rs19 as status',
        ) ->selectRaw('"" as keterangan')
            ->leftjoin('rs17', 'rs107.rs1', '=', 'rs17.rs1') //rajal
            ->leftjoin('rs23', 'rs107.rs1', '=', 'rs23.rs1') //ranap
            ->leftjoin('rs24', 'rs24.rs1', '=', 'rs107.rs10') //ruangan ranap
            // ->leftjoin('rs15 as pasien', 'rs15.rs1', '=', 'rs107.rs2') //pasien
            ->leftjoin('rs15 as pasien17', 'pasien17.rs1', '=', 'rs17.rs2') //pasien
            ->leftjoin('rs15 as pasien23', 'pasien23.rs1', '=', 'rs23.rs2') //pasien
            // ->leftjoin('rs15', function($q){
            //   $q->on('rs17.rs2', '=', 'rs15.rs1');
            //   $q->on('rs23.rs2','=', 'rs15.rs1');
            // }) //pasien
            ->leftjoin('rs19', 'rs19.rs1', '=', 'rs107.rs10') //poli
            ->leftjoin('rs21', 'rs21.rs1', '=', 'rs107.rs8') //dokter
            // // ->leftjoin('rs21', 'rs21.rs1', '=', 'rs107.rs8') //mboh
            ->leftjoin('rs9', 'rs9.rs1', '=', 'rs107.rs15') //sistembayar
            // ->leftjoin('rs222', 'rs222.rs1', '=', 'rs17.rs1') //sep
            // ->leftjoin('rs141', 'rs141.rs1', '=', 'rs17.rs1') // status pasien di IGD
            // ->leftjoin('rs24', 'rs24.rs1', '=', 'rs141.rs5') // nama ruangan
            // ->leftjoin('rs23', 'rs23.rs1', '=', 'rs141.rs1') // status masuk
            // ->leftjoin('master_poli_bpjs', 'rs19.rs6', '=', 'master_poli_bpjs.kode')
        ;

        $q = $select
            ->whereBetween('rs107.rs3', [$tgl, $tglx])
            // ->where('rs17.rs8', '=', 'PEN004')
            // ->where('rs141.rs4', '=', 'Rawat Inap')
            // ->where('rs107.rs13', 'LIKE', '%' . 'POL' . '%')
            // ->whereNotIn('rs107.rs13', ['Pendafataran'])
            ->where('rs107.rs2', '!=', '')
            ->whereNotNull('rs107.rs2')
            // ->whereNull('rs107.rs13')
            ->where(function ($sts) use ($status) {
                if ($status !== 'Semua') {
                    if ($status === 'Terlayani') {
                        $sts->where('rs107.rs9', '=', '2');
                    } else {
                        $sts->where('rs107.rs9', '=', '1');
                    }
                }
            })
            ->where(function ($query) {
                $query->where('rs107.rs1', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs107.rs2', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('pasien17.rs46', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('pasien17.rs2', 'LIKE', '%' . request('q') . '%')
                    // ->orWhere('rs19.rs2', 'LIKE', '%' . request('q') . '%')
                    // ->orWhere('rs21.rs2', 'LIKE', '%' . request('q') . '%')
                    // ->orWhere('rs222.rs8', 'LIKE', '%' . request('q') . '%')
                    // ->orWhere('rs9.rs2', 'LIKE', '%' . request('q') . '%')
                ;
            })
            ->groupBy('rs107.rs2');
        // ->orderby('rs17.rs3', $sort);

        return $q;
    }

    public function terima(Request $request)
    {
        /**
         * data yang berbeda antara ranap dan rajal
         * 1. rajal berdasarkan noreg, ranap berdasarkan no-permintaan
         * 2. di - anamnesis
         *       - diagnosa
         *       - tindakan
         *       - pemeriksaan
         */

        $noreg = $request->noreg;
        $rajal = KunjunganPoli::select(
            // 'rs17.rs1',
            DB::raw('(CASE WHEN rs107.rs2 IS NULL THEN rs17.rs1 ELSE rs107.rs2 END) as rs1'), // ini untuk relasi antara permintaan dan noreg
            'rs17.rs1 as noreg',
            'rs17.rs2 as norm',
            'rs17.rs9 as kdpeg',
            'rs23_meta.kd_jeniskasus',
            'memodiagnosadokter.diagnosa as memodiagnosa',
            'rs19.rs6 as kodepolibpjs',
            'rs222.rs8 as sep',
        )
            ->where('rs17.rs1', $noreg)
            ->leftjoin('rs23_meta', 'rs23_meta.noreg', 'rs17.rs1')
            ->leftjoin('memodiagnosadokter', 'memodiagnosadokter.noreg', 'rs17.rs1') // memo
            ->leftjoin('rs19', 'rs19.rs1', '=', 'rs17.rs8') //poli
            ->leftjoin('rs222', 'rs222.rs1', '=', 'rs17.rs1') //sep
            ->leftjoin('rs107', 'rs17.rs1', '=', 'rs107.rs1') //permintaan
            ->first();

        $ranap = Kunjunganranap::select(
            // 'rs23.rs1',
            DB::raw('(CASE WHEN rs107.rs2 IS NULL THEN rs23.rs1 ELSE rs107.rs2 END) as rs1'), // ini untuk relasi antara permintaan dan noreg
            'rs23.rs1 as noreg',
            'rs23.rs2 as norm',
            'rs23.rs10 as kdpeg',
            'rs23_meta.kd_jeniskasus',
            'memodiagnosadokter.diagnosa as memodiagnosa',
        )
            ->where('rs23.rs1', $noreg)
            ->leftjoin('rs23_meta', 'rs23_meta.noreg', 'rs23.rs1')
            ->leftjoin('memodiagnosadokter', 'memodiagnosadokter.noreg', 'rs23.rs1') // memo
            ->leftjoin('rs107', 'rs23.rs1', '=', 'rs107.rs1') //permintaan
            ->first();
        $data = $ranap ?? $rajal;

        // cari diagnosa awalHd
        $kepHd = Diagnosakeperawatan::where('norm', $data->norm)->where('kdruang', 'PEN005')->orderBy('id', 'asc')->first();
        $data->load([
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
            'anamnesisAwalHd' => function ($q) {
                $q->select([
                    'rs209.id',
                    'rs209.rs1',
                    'rs209.rs2',
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

                    ->where('rs209.awal', '1')
                    ->where('rs209.kdruang', 'PEN005')
                    ->groupBy('rs209.id');
            },
            'pemeriksaanAwalHd' => function ($q) {
                $q->select([
                    'rs253.id',
                    'rs253.rs1',
                    'rs253.rs2',
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
                    ->where('rs253.awal', '1')
                    ->where('rs253.kdruang', 'PEN005')
                    ->groupBy('rs253.id');
            },
            'diagnosakeperawatanAwalHd' => function ($q) use ($kepHd) {
                // ini has many, satu kali unout bisa banyak. idenya ambil dari norm yang created at nya paling awal yang koderuangan nya pen 005
                $q->with('intervensi', 'intervensi.masterintervensi')
                    ->where('kdruang', '=', 'PEN005')
                    ->when($kepHd, function ($q) use ($kepHd) {
                        $q->where('created_at', '=', $kepHd->created_at);
                    })
                    ->orderBy('id', 'DESC');
            },
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
                    ->where(function ($q) {
                        $q->where('rs209.kdruang', '=', 'PEN005')
                            ->orWhere('rs209.awal', '!=', '1');
                    })
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
                    ->where(function ($q) {
                        $q->where('rs253.kdruang', '=', 'PEN005')
                            ->orWhere('rs253.awal', '!=', '1');
                    })
                    ->groupBy('rs253.id');
            },
            'diagnosakeperawatan' => function ($q) {
                $q->with('intervensi', 'intervensi.masterintervensi')
                    ->where('kdruang', '=', 'PEN005')
                    ->orderBy('id', 'DESC');
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
            'intradialitik.user:nama,kdpegsimrs',
            'pengkajian',

            'laborats' => function ($q) {

                $q->with('details.pemeriksaanlab')->orderBy('id', 'DESC')
                    ->where('unit_pengirim', '=', 'PEN005');
            },
            'laboratold' => function ($t) {
                $t->with('pemeriksaanlab')
                    ->orderBy('id', 'DESC');
            },
            'radiologi' => function ($q) {
                $q->orderBy('id', 'DESC')
                    ->where('rs10', '=', 'PEN005');
            },
            'hasilradiologi' => function ($q) {
                $q->orderBy('id', 'DESC');
            },
            'bankdarah' => function ($q) {
                $q->orderBy('id', 'DESC')
                    ->where('rs11', '=', 'PEN005');
            },
            'konsultasi' => function ($q) {
                $q->where('kdruang', '=', 'PEN005')
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
            'pegsim:kdpegsimrs,nik,nama,id,nip',
        ]);

        return new JsonResponse([
            'data' => $data,
            'kepHd' => $kepHd,
        ]);
    }
}
