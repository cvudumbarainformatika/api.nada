<?php

namespace App\Http\Controllers\Api\Simrs\Penjaminan;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mpoli;
use App\Models\Simrs\Penjaminan\listcasmixrajal;
use App\Models\Simrs\Rajal\KunjunganPoli;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Klaim extends Controller
{
    public function getdataklaim()
    {
        $pelayanan = request('pelayanan');
        $bulan = request('bulan');
        $tahun = request('tahun');
        if($pelayanan === '1')
        {
            $kdpoli = ['POL014'];
        }else{
            $kdpoli = Mpoli::select('rs1')->where('rs1', '!=', 'POL014')->get();

        }

            $data = listcasmixrajal::select('listkirimcasmixRajal.noreg as noreg','listkirimcasmixRajal.norm as norm',
            'listkirimcasmixRajal.nosep as nosep','listkirimcasmixRajal.noka as noka',
            'listkirimcasmixRajal.norm as norm','listkirimcasmixRajal.nosep as nosep',
            'kepegx.pegawai.nama as dokter','rs17.rs3 as tgl_kunjungan','rs17.rs8 as kodepoli',
            'rs15.rs2 as pasien',
            'rs15.rs49 as nktp',
            'rs15.rs55 as nohp',
             'rs15.rs17 as kelamin',
             'rs17.rs26 as tglpulang',
             DB::raw('concat(rs15.rs4," KEL ",rs15.rs5," RT ",rs15.rs7," RW ",rs15.rs8," ",rs15.rs6," ",rs15.rs11," ",rs15.rs10) as alamat'),
             DB::raw('concat(TIMESTAMPDIFF(YEAR, rs15.rs16, CURDATE())," Tahun ",
            TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()) % 12," Bulan ",
            TIMESTAMPDIFF(DAY, TIMESTAMPADD(MONTH, TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()), rs15.rs16), CURDATE()), " Hari") AS usia'),
             'rs9.rs2 as sistembayar',
            'rs19.rs2 as poli','klaim_trans_rajal.status_klaim as ket',
            DB::raw('\'rajal\' as layanan'))
            ->leftjoin('rs17', 'rs17.rs1', '=', 'listkirimcasmixRajal.noreg')
            ->leftjoin('rs15', 'rs15.rs1', '=', 'rs17.rs2')
            ->leftjoin('rs19', 'rs19.rs1', '=', 'rs17.rs8')
            ->leftjoin('rs9', 'rs9.rs1', '=', 'rs17.rs14')
            ->leftjoin('kepegx.pegawai', 'kepegx.pegawai.kdpegsimrs', '=', 'rs17.rs9')
            ->leftjoin('klaim_trans_rajal', 'klaim_trans_rajal.noreg', '=', 'listkirimcasmixRajal.noreg')
            ->whereYear('rs17.rs3', $tahun )->whereMonth('rs17.rs3', $bulan)->whereIn('kodepoli',$kdpoli)
            ->where('rs9.groups', '1')
            ->where(function ($query) {
                $query->where('rs15.rs2', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs15.rs46', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs17.rs2', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs17.rs1', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs19.rs2', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('listkirimcasmixRajal.nosep', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs9.rs2', 'LIKE', '%' . request('q') . '%');
            })
            ->paginate(request('per_page'));
            return new JsonResponse($data);
        // }else{
        //     $data = listcasmixrajal::select('listkirimcasmixRajal.noreg as noreg','listkirimcasmixRajal.norm as norm',
        //     'listkirimcasmixRajal.nosep as nosep','listkirimcasmixRajal.noka as noka',
        //     'listkirimcasmixRajal.norm as norm','listkirimcasmixRajal.nosep as nosep',
        //     'kepegx.pegawai.nama as dokter','rs17.rs3 as tgl_kunjungan','rs17.rs8 as kodepoli',
        //     'rs15.rs2 as pasien',
        //     'rs15.rs49 as nktp',
        //     'rs15.rs55 as nohp',
        //      'rs15.rs17 as kelamin',
        //      'rs17.rs26 as tglpulang',
        //      DB::raw('concat(rs15.rs4," KEL ",rs15.rs5," RT ",rs15.rs7," RW ",rs15.rs8," ",rs15.rs6," ",rs15.rs11," ",rs15.rs10) as alamat'),
        //      DB::raw('concat(TIMESTAMPDIFF(YEAR, rs15.rs16, CURDATE())," Tahun ",
        //     TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()) % 12," Bulan ",
        //     TIMESTAMPDIFF(DAY, TIMESTAMPADD(MONTH, TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()), rs15.rs16), CURDATE()), " Hari") AS usia'),
        //      'rs9.rs2 as sistembayar',
        //     'rs19.rs2 as poli','klaim_trans_rajal.status_klaim as ket',
        //     DB::raw('\'rajal\' as layanan'))
        //     ->leftjoin('rs17', 'rs17.rs1', '=', 'listkirimcasmixRajal.noreg')
        //     ->leftjoin('rs15', 'rs15.rs1', '=', 'rs17.rs2')
        //     ->leftjoin('rs19', 'rs19.rs1', '=', 'rs17.rs8')
        //     ->leftjoin('rs9', 'rs9.rs1', '=', 'rs17.rs14')
        //     ->leftjoin('kepegx.pegawai', 'kepegx.pegawai.kdpegsimrs', '=', 'rs17.rs9')
        //     ->leftjoin('klaim_trans_rajal', 'klaim_trans_rajal.noreg', '=', 'listkirimcasmixRajal.noreg')
        //     ->whereYear('rs17.rs3', $tahun )->whereMonth('rs17.rs3', $bulan)->where('kodepoli','!=','POL014')
        //     ->where('rs9.groups', '1')
        //     ->paginate(request('per_page'));
        //     return new JsonResponse($data);
        // }
    }
}
