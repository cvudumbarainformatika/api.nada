<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Depo;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Rajal\KunjunganPoli;
use App\Models\Simrs\Ranap\Kunjunganranap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CaripasienController extends Controller
{
    public function caripasienpoli()
    {
        $tglskrng = date('Y-m-d');

        $date = new \DateTime('-7 days');
        $prev = $date->format('Y-m-d');
        $carirajal = KunjunganPoli::select(
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
            'rs17.rs19 as status'
        )
            ->leftjoin('rs15', 'rs15.rs1', '=', 'rs17.rs2') //pasien
            ->leftjoin('rs19', 'rs19.rs1', '=', 'rs17.rs8') //poli
            ->leftjoin('rs21', 'rs21.rs1', '=', 'rs17.rs9') //dokter
            ->leftjoin('rs9', 'rs9.rs1', '=', 'rs17.rs14') //sistembayar
            ->leftjoin('rs222', 'rs222.rs1', '=', 'rs17.rs1') //sep
            ->leftjoin('master_poli_bpjs', 'rs19.rs6', '=', 'master_poli_bpjs.kode')
            ->where('rs17.rs8', '!=', 'POL014')
            ->where(function ($query) {
                $query->where('rs15.rs2', 'LIKE', '%' . request('nama') . '%')
                    ->orWhere('rs15.rs46', 'LIKE', '%' . request('nik') . '%')
                    ->orWhere('rs17.rs2', 'LIKE', '%' . request('norm') . '%')
                    ->orWhere('rs17.rs1', 'LIKE', '%' . request('noreg') . '%')
                    ->orWhere('rs222.rs8', 'LIKE', '%' . request('nosep') . '%');
            })
            ->whereBetween('rs17.rs3', [$prev . ' 00:00:00', $tglskrng . ' 23:59:59'])
            ->with([
                'diagnosa' => function ($d) {
                    $d->with('masterdiagnosa');
                }
            ])
            ->orderby('rs17.rs3', 'ASC')
            ->paginate(request('per_page'));

        return new JsonResponse($carirajal);
    }

    public function caripasienigd()
    {
        $cariigd = KunjunganPoli::select(
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
            'rs17.rs19 as status'
        )
            ->leftjoin('rs15', 'rs15.rs1', '=', 'rs17.rs2') //pasien
            ->leftjoin('rs19', 'rs19.rs1', '=', 'rs17.rs8') //poli
            ->leftjoin('rs21', 'rs21.rs1', '=', 'rs17.rs9') //dokter
            ->leftjoin('rs9', 'rs9.rs1', '=', 'rs17.rs14') //sistembayar
            ->leftjoin('rs222', 'rs222.rs1', '=', 'rs17.rs1') //sep
            ->leftjoin('master_poli_bpjs', 'rs19.rs6', '=', 'master_poli_bpjs.kode')
            ->where('rs17.rs8', 'POL014')
            ->where('rs17.rs19', '')
            ->orderby('rs17.rs3', 'ASC')
            ->paginate(request('per_page'));

        return new JsonResponse($cariigd);
    }

    public function caripasienranap()
    {
        $cariranap = Kunjunganranap::select(
            'rs23.rs1 as noreg',
            'rs23.rs2 as norm',
            'rs23.rs3 as tglmasuk',
            'rs23.rs4 as tglkeluar',
            'rs23.rs5 as kdruangan',
            'rs23.rs6 as ketruangan',
            'rs23.rs7 as nomorbed',
            'rs23.rs10 as kodedokter',
            'rs23.rs19 as kdsistembayar',
            'rs23.rs22 as status', // '' : BELUM PULANG | '2 ato 3' : PASIEN PULANG
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
            'rs21.rs2 as dokter',
            'rs227.rs8 as sep',
            'rs227.kodedokterdpjp as kodedokterdpjp',
            'rs227.dokterdpjp as dokterdpjp',
            'rs24.rs2 as ruangan',
            'rs24.rs5 as ruangan'
        )
            ->leftjoin('rs15', 'rs15.rs1', 'rs23.rs2')
            ->leftjoin('rs9', 'rs9.rs1', 'rs23.rs19')
            ->leftjoin('rs21', 'rs21.rs1', 'rs23.rs10')
            ->leftjoin('rs227', 'rs227.rs1', 'rs23.rs1')
            ->leftjoin('rs24', 'rs24.rs1', 'rs23.rs5')
            ->where(function ($query) {
                $query->where('rs15.rs2', 'LIKE', '%' . request('nama') . '%')
                    // ->orWhere('rs15.rs46', 'LIKE', '%' . request('nik') . '%')
                    ->orWhere('rs23.rs2', 'LIKE', '%' . request('norm') . '%')
                    ->orWhere('rs23.rs1', 'LIKE', '%' . request('noreg') . '%');
                // ->orWhere('rs227.rs8', 'LIKE', '%' . request('sep') . '%');
            })
            ->where('rs23.rs22', '')
            ->orderby('rs23.rs3', 'ASC')
            ->paginate(request('per_page'));
        return new JsonResponse($cariranap);
    }
}
