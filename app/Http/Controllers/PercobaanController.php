<?php

namespace App\Http\Controllers;

use App\Models\Simrs\Konsultasi\Konsultasi;
use App\Models\Simrs\Ranap\Kunjunganranap;
use App\Models\Simrs\Ranap\Pelayanan\Cppt;
use App\Models\Simrs\Visite\Visite;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class PercobaanController extends Controller
{
    public function index()
    {
        // $noSuratMeninggal = self::buatSuratMeninggal();
        // return $noSuratMeninggal;
        echo 'ok';
    }


    public function buatSuratMeninggal()
    {
        $oto = 0;
        DB::select('call no_surat_kematian(@nomor)');
        $x = DB::table('rs1')->select('kematian')->get();
        $oto = $x[0]->kematian;
        // return $oto;

        $has = str_pad($oto, 4, '0', STR_PAD_LEFT);


        $bulan = (int) date('m');
        $blnRomawi = self::intToRoman($bulan);

        $no = "472.12 / $has / 425.102.8 / KEM / $blnRomawi / " . date('Y');
        return $no;
    }

    public static function intToRoman($num) {
      $romanNumerals = [
          1000 => 'M',
          900 => 'CM',
          500 => 'D',
          400 => 'CD',
          100 => 'C',
          90 => 'XC',
          50 => 'L',
          40 => 'XL',
          10 => 'X',
          9 => 'IX',
          5 => 'V',
          4 => 'IV',
          1 => 'I'
      ];
  
      $result = '';
      foreach ($romanNumerals as $value => $numeral) {
          while ($num >= $value) {
              $result .= $numeral;
              $num -= $value;
          }
      }
      return $result;
    }

    public static function kunjunganpasien()
    {
        // return request()->page;
        // coba lagi
        // return request()->all();
        $dokter = request('kddokter');

        if (request('to') === null || request('to') === '') {
            $tgl = Carbon::now()->format('Y-m-d');
        } else {
            $tgl = request('to') ;
        }

        if (request('from') === null || request('from') === '') {
            $tglx = Carbon::now()->format('Y-m-d');
        } else {
            $tglx = request('from');
        }

        // $tanggal = $tgl. ' 23:59:59';
        // $tanggalx = $tglx. ' 00:00:00'; 
        $tanggal = $tgl. ' 00:00:00';
        $tanggalx = $tglx. ' 23:59:59'; 
        // $tanggalx = Carbon::now()->subDays(180)->format('Y-m-d'). ' 00:00:00'; 

        // return $tanggalx;

        $hr_ini = date('Y-m-d'). ' 23:59:59';
        $hr_180 = Carbon::now()->subDays(10)->format('Y-m-d'). ' 00:00:00';

        $status = request()->status === 'Belum Pulang' ? [''] : ['2', '3'];
        $ruangan = request()->koderuangan;
        $data = Kunjunganranap::select(
            'rs23.rs1',
            'rs23.rs1 as noreg',
            'rs23.rs2 as norm',
            'rs23.rs3 as tglmasuk',
            'rs17.rs3 as tglmasuk_igd',
            'rs23.rs4 as tglkeluar',
            'rs23.rs5 as kdruangan',
            'rs23.rs5 as kodepoli', // ini khusus resep jangan diganti .... memang namanya aneh kok ranap ada kodepoli? ya? jangan dihapus yaaa.....
            'rs23.rs6 as ketruangan',
            'rs23.rs7 as nomorbed',
            'rs23.rs10 as kddokter',
            'rs23.rs10 as kodedokter',
            // 'rs23.titipan',
            'rs21.rs2 as dokter',
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
            'rs21.rs2 as namanakes',
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
            ->leftjoin('rs17', 'rs17.rs1', 'rs23.rs1') // IGD
            ->leftjoin('rs9', 'rs9.rs1', 'rs23.rs19')
            ->leftjoin('rs21', 'rs21.rs1', 'rs23.rs10')
            ->leftjoin('rs227', 'rs227.rs1', 'rs23.rs1')
            ->leftjoin('rs24', 'rs24.rs1', 'rs23.rs5')
            ->leftjoin('rs24 as rs24_titipan', 'rs24_titipan.rs1', 'rs23.titipan')
            ->leftjoin('rs23_meta', 'rs23_meta.noreg', 'rs23.rs1') // jenis kasus
            ->leftjoin('memodiagnosadokter', 'memodiagnosadokter.noreg', 'rs23.rs1') // memo


            ->where(function ($query) use ($ruangan) {
                if ($ruangan !== 'SEMUA') {
                    // $query->where('rs24.groups', '=',  $ruangan)
                    // ->orWhere('rs23.titipan', '=',  $ruangan);
                    $query->where('rs24.groups', 'like',  '%' . $ruangan . '%')
                    ->orWhere('rs23.titipan', 'like',  '%' . $ruangan . '%');
                } 
                // else {
                //     $query->where('rs23.rs5', '!=',  '');
                // }
                
            })
            ->where(function($query) use ($hr_ini, $hr_180, $status) {
                if ($status === 'Pulang') {
                    $query->whereBetween('rs23.rs4', [$hr_180, $hr_ini])
                        ->whereIn('rs23.rs22',['2','3']);
                } else {
                    $query->where('rs23.rs22','=','')
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
            // ->paginate(25);

        return $data->toSql();
    }

    public function updateTable()
    {
        $data = Visite::where('rs2', 'LIKE', '%2025-02%')
        ->where('rs4','=', 0)
        ->where('rs6','=','K5#')
        ->where('rs8','=','SKR')
        ->get();

        return response()->json($data);
    }
}
