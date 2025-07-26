<?php

namespace App\Http\Controllers\Api\Simrs\Pendaftaran\Ranap;

use App\Helpers\BridgingbpjsHelper;
use App\Http\Controllers\Controller;
use App\Models\Pasien;
use App\Models\Simrs\Master\Mpasien;
use App\Models\Simrs\Pendaftaran\Ranap\Sepranap;
use App\Models\Simrs\Rajal\KunjunganPoli;
use App\Models\Simrs\Ranap\Rs141;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class PendaftaranRanapController extends Controller
{
    public function list_pendaftaran_ranap()
    {
      
      $total = self::query_table()->get()->count();
      $data = self::query_table()->simplePaginate(request('per_page'));

      $response = (object)[
        'total' => $total,
        'data' => $data
      ];

      return response()->json($response);

    }
    

    public static function query_table()
    {
      // rs23 tabel ranap
      // rs23.rs22 status ranap
      if (request('to') === '' || request('from') === null) {
          $tgl = Carbon::now()->format('Y-m-d 00:00:00');
          $tglx = Carbon::now()->format('Y-m-d 23:59:59');
      } else {
          $tgl = request('to') . ' 00:00:00';
          $tglx = request('from') . ' 23:59:59';
      }

      $sort = request('sort') === 'terbaru'? 'DESC':'ASC';
      $status = request('status') ?? 'Semua';

      $query = KunjunganPoli::query()
        ->select(
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
        'rs141.rs4 as status_tunggu',
        'rs24.rs2 as ruangan',
        'rs24.rs1 as koderuangan',
        'rs23.rs2 as status_masuk',
        'rs23.rs5 as kode_ruangan_sekarang',
        'rs141.flag as flag'
      )
        ->leftjoin('rs15', 'rs15.rs1', '=', 'rs17.rs2') //pasien
        ->leftjoin('rs19', 'rs19.rs1', '=', 'rs17.rs8') //poli
        ->leftjoin('rs21', 'rs21.rs1', '=', 'rs17.rs9') //dokter
        ->leftjoin('rs9', 'rs9.rs1', '=', 'rs17.rs14') //sistembayar
        ->leftjoin('rs222', 'rs222.rs1', '=', 'rs17.rs1') //sep
        ->leftjoin('rs141', 'rs141.rs1', '=', 'rs17.rs1') // status pasien di IGD
        ->leftjoin('rs24', 'rs24.rs1', '=', 'rs141.rs5') // nama ruangan
        ->leftjoin('rs23', 'rs23.rs1', '=', 'rs141.rs1') // status masuk
        ->leftjoin('master_poli_bpjs', 'rs19.rs6', '=', 'master_poli_bpjs.kode')
        ->with(['tunggu_ranap:rs1,rs22'])
        ->whereBetween('rs17.rs3', [$tgl, $tglx])
        // ->where('rs17.rs8', '=', 'POL014')
        ->where('rs141.rs4', '=', 'Rawat Inap')
        ->where(function ($query) {
            if (request('unit') === 'igd') {
                $query->where('rs17.rs8', '=', 'POL014');
            } else {
                $query->where('rs17.rs8', '<>', 'POL014');
            }
        })
        ->where(function ($sts) use ($status) {
            if ($status !== 'Semua') {
                if ($status === 'Terlayani') {
                    $sts->where('rs23.rs2', '!=',null);
                } else {
                    $sts->where('rs23.rs2', '=', null);
                }
            }
        })
        ->where(function ($query) {
            $query->where('rs15.rs2', 'LIKE', '%' . request('q') . '%')
                ->orWhere('rs15.rs46', 'LIKE', '%' . request('q') . '%')
                ->orWhere('rs17.rs2', 'LIKE', '%' . request('q') . '%')
                ->orWhere('rs17.rs1', 'LIKE', '%' . request('q') . '%')
                // ->orWhere('rs19.rs2', 'LIKE', '%' . request('q') . '%')
                // ->orWhere('rs21.rs2', 'LIKE', '%' . request('q') . '%')
                ->orWhere('rs222.rs8', 'LIKE', '%' . request('q') . '%')
                ->orWhere('rs9.rs2', 'LIKE', '%' . request('q') . '%');
        })->groupBy('rs17.rs1')
        ->orderby('rs17.rs3', $sort);

        return $query;
    }

    public function wheatherapi_country()
    {
        $data = Http::get('http://api.weatherapi.com/v1/search.json?key=88a330fe969d462e919175655242101&q='.request('q'));
        return $data;
    }

    public function cekPesertaBpjs()
    {
        $no = request('no');
        $by = request('by');
        $today = date('Y-m-d');

        $cekpsereta = BridgingbpjsHelper::get_url(
            'vclaim',
            // 'Peserta/nokartu/' . $request->noka . '/tglSEP/' . $request->tglsep
            "Peserta/$by/$no/tglSEP/$today"
        );
        // $wew = $cekpsereta['result']->peserta->provUmum;
        $pasienRs = null;
        if ($by==='nik') {
            $cek = Mpasien::pasien()->where('rs49','=',$no)->first();
            if ($cek) {
                $pasienRs=$cek;
            }
        }
        else {
            $cek = Mpasien::pasien()->where('rs46','=',$no)->first();
            if ($cek) {
                $pasienRs=$cek;
            }
        }

        $data = [
            'bpjs'=> $cekpsereta,
            'rs'=> $pasienRs
        ];
        
        return ($data);
    }

    public function list_tunggu_pendaftaran_ranap()
    {
      
      $total = self::query_tabletunggu()->get()->count();
      $data = self::query_tabletunggu()->simplePaginate(request('per_page'));

      $response = (object)[
        'total' => $total,
        'data' => $data
      ];

      return response()->json($response);

    }

    static function query_tabletunggu()
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

      $query = Rs141::query()
            ->select(
              'rs141.rs1 as noreg',
              'rs141.rs2 as norm',
              'rs141.rs3 as kodepoli',
              'rs19.rs2 as namapoli',
              'rs15.rs2 as nama',
                DB::raw('concat(rs15.rs3," ",rs15.gelardepan," ",rs15.rs2," ",rs15.gelarbelakang) as nama'),
                DB::raw('concat(rs15.rs4," KEL ",rs15.rs5," RT ",rs15.rs7," RW ",rs15.rs8," ",rs15.rs6," ",rs15.rs11," ",rs15.rs10) as alamat'),
                DB::raw('concat(TIMESTAMPDIFF(YEAR, rs15.rs16, CURDATE())," Tahun ",
                        TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()) % 12," Bulan ",
                        TIMESTAMPDIFF(DAY, TIMESTAMPADD(MONTH, TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()), rs15.rs16), CURDATE()), " Hari") AS usia'),
            )
            ->leftjoin('rs15', 'rs15.rs1', '=', 'rs141.rs2') //pasien
            ->leftjoin('rs19', 'rs19.rs1', '=', 'rs141.rs3') //poli
            // ->leftjoin('rs21', 'rs21.rs1', '=', 'rs17.rs9') //dokter
            // ->leftjoin('rs9', 'rs9.rs1', '=', 'rs17.rs14') //sistembayar
            // ->leftjoin('rs222', 'rs222.rs1', '=', 'rs17.rs1') //sep
            // ->leftjoin('rs141', 'rs141.rs1', '=', 'rs17.rs1') // status pasien di IGD
            // ->leftjoin('rs24', 'rs24.rs1', '=', 'rs141.rs5') // nama ruangan
            // ->leftjoin('rs23', 'rs23.rs1', '=', 'rs141.rs1') // status masuk
            // ->leftjoin('master_poli_bpjs', 'rs19.rs6', '=', 'master_poli_bpjs.kode')
            ->whereBetween('rs141.created_at', [$tgl, $tglx])
            ->where(function ($query) {
                if (request('unit') === 'igd') {
                    $query->where('rs141.rs3', '=', 'POL014');
                } else {
                    $query->where('rs141.rs3', '<>', 'POL014');
                }
            })
            ->where('rs141.rs4', '=', 'Rawat Inap')
            ->where(function ($query) {
                $query->where('rs15.rs2', 'LIKE', '%' . request('q') . '%')
                    // ->orWhere('rs15.rs46', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs141.rs2', 'LIKE', '%' . request('q') . '%');
                    // ->orWhere('rs17.rs1', 'LIKE', '%' . request('q') . '%')
                    // ->orWhere('rs19.rs2', 'LIKE', '%' . request('q') . '%')
                    // ->orWhere('rs21.rs2', 'LIKE', '%' . request('q') . '%')
                    // ->orWhere('rs222.rs8', 'LIKE', '%' . request('q') . '%')
                    // ->orWhere('rs9.rs2', 'LIKE', '%' . request('q') . '%');
            })->groupBy('rs141.rs1')
            ->orderby('rs141.id', $sort);

        return $query;

    }

}
