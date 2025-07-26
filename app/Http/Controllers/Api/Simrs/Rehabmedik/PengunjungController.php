<?php

namespace App\Http\Controllers\Api\Simrs\Rehabmedik;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Fisioterapi\Fisioterapipermintaan;
use App\Models\Simrs\Rajal\KunjunganPoli;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PengunjungController extends Controller
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

      $sort = request('sort') === 'terbaru'? 'DESC':'ASC';
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
        'rs17.rs14 as kodesistembayar',
        'rs17.rs19 as status',

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
        DB::raw('(CASE WHEN rs17.rs8 ="" THEN "rjl" ELSE "rjl" END) as flagdepo'),
        
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
        ->leftjoin('rs201 as permintaan', 'rs17.rs1', '=', 'permintaan.rs1') //permintaan
        ->leftjoin('rs15', 'rs15.rs1', '=', 'rs17.rs2') //pasien
        ->leftjoin('rs19', 'rs19.rs1', '=', 'rs17.rs8') //poli
        // ->leftjoin('rs21', 'rs21.rs1', '=', 'rs17.rs9') //dokter
        ->leftjoin('rs9', 'rs9.rs1', '=', 'rs17.rs14') //sistembayar
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
            ->where('rs17.rs8', '=', 'PEN004')
            ->where(function ($sts) use ($status) {
                if ($status !== 'Semua') {
                    if ($status === 'Terlayani') {
                        $sts->where('rs17.rs19', '=','1');
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
        $data = Fisioterapipermintaan::query();
        $select = $data->select(
        'rs201.rs1 as noreg',
        // 'rs201.rs2 as norm',
        DB::raw('( CASE WHEN rs17.rs2 IS NOT NULL THEN rs17.rs2 ELSE rs23.rs2 END ) as norm'),
        'rs201.rs3 as tgl_kunjungan',
        'rs201.rs10 as kdruangan',
        'rs201.rs10 as koderuangan',
        'rs201.rs10 as kodepoli',
        DB::raw('coalesce(rs17.rs14, rs23.rs19) as kodesistembayar'),
        // DB::raw('coalesce(rs17.rs19, rs23.rs22) as status'),
        // 'rs201.rs9 as status',
        DB::raw('CASE WHEN rs201.rs9 = "2" THEN "1" ELSE "" END as status'),

        DB::raw('coalesce(
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
        DB::raw('(CASE WHEN rs201.rs2 ="" THEN NULL ELSE rs201.rs2 END) as nota_permintaan'),
        // 'rs201.rs2 as nota_permintaan',
        DB::raw('(CASE WHEN rs19.rs1 IS NOT NULL THEN "rjl" ELSE "rnp" END) as flagdepo'),
        // DB::raw('COUNT(CASE WHEN rs17.rs1 > 0 THEN "rjl" ELSE "rnp" END) as flagdepo'),


        // 'rs17.rs8 as koderuangan',
        // 'rs17.rs4 as penanggungjawab',
        // 'rs17.rs6 as kodeasalrujukan',
        // 'rs17.rs20 as asalpendaftaran',
        // 'rs17.rs7 as namaperujuk',
        // 'rs19.rs2 as ruangan',
        // DB::raw('( CASE 
        //         WHEN rs19.rs4 IS NOT NULL THEN rs19.rs2 ELSE rs24.rs2    
        //     END) as ruangan'),
        DB::raw('coalesce(rs19.rs2, rs24.rs2) as ruangan'),
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
        )
        ->leftjoin('rs17', 'rs201.rs1', '=', 'rs17.rs1') //rajal
        ->leftjoin('rs23', 'rs201.rs1', '=', 'rs23.rs1') //ranap
        ->leftjoin('rs24', 'rs24.rs1', '=', 'rs201.rs10') //ruangan ranap
        // ->leftjoin('rs15 as pasien', 'rs15.rs1', '=', 'rs201.rs2') //pasien
        ->leftjoin('rs15 as pasien17', 'pasien17.rs1', '=', 'rs17.rs2') //pasien
        ->leftjoin('rs15 as pasien23', 'pasien23.rs1', '=', 'rs23.rs2') //pasien
        // ->leftjoin('rs15', function($q){
        //   $q->on('rs17.rs2', '=', 'rs15.rs1');
        //   $q->on('rs23.rs2','=', 'rs15.rs1');
        // }) //pasien
        ->leftjoin('rs19', 'rs19.rs1', '=', 'rs201.rs10') //poli
        // ->leftjoin('rs21', 'rs21.rs1', '=', 'rs17.rs9') //dokter
        // // ->leftjoin('rs21', 'rs21.rs1', '=', 'rs201.rs8') //mboh
        ->leftjoin('rs9', 'rs9.rs1', '=', 'rs201.rs14') //sistembayar
        // ->leftjoin('rs222', 'rs222.rs1', '=', 'rs17.rs1') //sep
        // ->leftjoin('rs141', 'rs141.rs1', '=', 'rs17.rs1') // status pasien di IGD
        // ->leftjoin('rs24', 'rs24.rs1', '=', 'rs141.rs5') // nama ruangan
        // ->leftjoin('rs23', 'rs23.rs1', '=', 'rs141.rs1') // status masuk
        // ->leftjoin('master_poli_bpjs', 'rs19.rs6', '=', 'master_poli_bpjs.kode')
        ;

        $q = $select
            ->whereBetween('rs201.rs3', [$tgl, $tglx])
            // ->where('rs17.rs8', '=', 'PEN004')
            // ->where('rs141.rs4', '=', 'Rawat Inap')
            // ->where('rs201.rs13', 'LIKE', '%' . 'POL' . '%')
            // ->whereNotIn('rs201.rs13', ['Pendafataran'])
            ->where('rs201.rs2', '!=', '')
            ->whereNotNull('rs201.rs2')
            // ->whereNull('rs201.rs13')
            ->where(function ($sts) use ($status) {
                if ($status !== 'Semua') {
                    if ($status === 'Terlayani') {
                        $sts->where('rs201.rs9', '=','2');
                    } else {
                        $sts->where('rs201.rs9', '=', '1');
                    }
                }
            })
            ->where(function ($query) {
                $query->where('rs201.rs1', 'LIKE', '%' . request('q') . '%')
                    // ->orWhere('rs201.rs2', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('pasien17.rs46', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('pasien17.rs2', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('pasien17.rs1', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('pasien23.rs2', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('pasien23.rs1', 'LIKE', '%' . request('q') . '%')
                    // ->orWhere('rs19.rs2', 'LIKE', '%' . request('q') . '%')
                    // ->orWhere('rs21.rs2', 'LIKE', '%' . request('q') . '%')
                    // ->orWhere('rs222.rs8', 'LIKE', '%' . request('q') . '%')
                    // ->orWhere('rs9.rs2', 'LIKE', '%' . request('q') . '%')
                    ;
            })
            ->groupBy('rs201.rs2')
            ;
            // ->orderby('rs17.rs3', $sort);

        return $q;
    }
}
