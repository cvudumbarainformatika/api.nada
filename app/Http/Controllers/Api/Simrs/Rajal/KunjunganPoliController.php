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
use App\Models\Simrs\Rajal\KunjunganPoli;
use App\Models\Simrs\Rajal\Memodiagnosadokter;
use App\Models\Simrs\Rajal\WaktupulangPoli;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KunjunganPoliController extends Controller
{

    public function index()
    {
        $query = $this->query_table('table');
        $data = $query->simplePaginate(request('per_page'));

        // $total = 0;
        $total = $this->query_table('total')->get()->count();
        $result = [
            'table' => $data,
            'total' => $total
        ];
        return new JsonResponse($result);
    }

    public function query_table($val)
    {
        // $user = Pegawai::find(auth()->user()->pegawai_id);

        $ruangan = request('kodepoli');

        if (request('to') === '' || request('from') === null) {
            $tgl = Carbon::now()->format('Y-m-d 00:00:00');
            $tglx = Carbon::now()->format('Y-m-d 23:59:59');
        } else {
            $tgl = request('to') . ' 00:00:00';
            $tglx = request('from') . ' 23:59:59';
        }
        $status = request('status') ?? '';
        $query = KunjunganPoli::query();
        if ($val === 'total') {
            $select = $query->select(
                'rs17.rs1',
                'rs17.rs2',
                'rs17.rs3',
                'rs17.rs4',
                'rs17.rs8',
                'rs17.rs9',
                'rs17.rs14',
                'rs17.rs19',
                'rs17.rs21'
            )->leftjoin('rs15', 'rs15.rs1', '=', 'rs17.rs2') //pasien
                ->leftjoin('rs19', 'rs19.rs1', '=', 'rs17.rs8') //poli
                ->leftjoin('rs21', 'rs21.rs1', '=', 'rs17.rs9') //dokter
                ->leftjoin('rs9', 'rs9.rs1', '=', 'rs17.rs14') //sistembayar
                ->leftjoin('rs222', 'rs222.rs1', '=', 'rs17.rs1') //sep
                ->leftjoin('master_poli_bpjs', 'rs19.rs6', '=', 'master_poli_bpjs.kode')
                ->leftjoin('memodiagnosadokter', 'memodiagnosadokter.noreg', '=', 'rs17.rs1')
                ->leftjoin('antrian_ambil', 'antrian_ambil.noreg', 'rs17.rs1');
        } else {
            $select = $query->select(
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
                'antrian_ambil.nomor as noantrian',
                'listkirimcasmixRajal.flaging as kunjungancesmix'
            )
                ->leftjoin('rs15', 'rs15.rs1', '=', 'rs17.rs2') //pasien
                ->leftjoin('rs19', 'rs19.rs1', '=', 'rs17.rs8') //poli
                ->leftjoin('rs21', 'rs21.rs1', '=', 'rs17.rs9') //dokter
                ->leftjoin('rs9', 'rs9.rs1', '=', 'rs17.rs14') //sistembayar
                ->leftjoin('rs222', 'rs222.rs1', '=', 'rs17.rs1') //sep
                ->leftjoin('master_poli_bpjs', 'rs19.rs6', '=', 'master_poli_bpjs.kode')
                ->leftjoin('memodiagnosadokter', 'memodiagnosadokter.noreg', '=', 'rs17.rs1')
                ->leftjoin('antrian_ambil', 'antrian_ambil.noreg', 'rs17.rs1')
                ->leftjoin('listkirimcasmixRajal', 'listkirimcasmixRajal.noreg', 'rs17.rs1')
                ->whereBetween('rs17.rs3', [$tgl, $tglx])
                ->where('rs19.rs4', '=', 'Poliklinik')
                ->whereIn('rs17.rs8', $ruangan)
                ->where('rs17.rs8', '!=', 'POL014')
                ->where(function ($sts) use ($status) {
                    if ($status !== 'all') {
                        if ($status === '') {
                            $sts->where('rs17.rs19', '!=','1');
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
                });
        }
        $q = $select
            ->whereBetween('rs17.rs3', [$tgl, $tglx])
            ->where('rs19.rs4', '=', 'Poliklinik')
            ->whereIn('rs17.rs8', $ruangan)
            ->where('rs17.rs8', '!=', 'POL014')
            ->where(function ($sts) use ($status) {
                if ($status !== 'all') {
                    if ($status === '') {
                        $sts->where('rs17.rs19', '!=','1');
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
            ->orderby('antrian_ambil.nomor', 'Asc')
            ->groupby('rs17.rs1');
        return $q;
    }

    public function totalData()
    {
        $data = $this->query_table('total')->get()->count();
        return $data;
    }
}
