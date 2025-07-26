<?php

namespace App\Http\Controllers\Api\Dashboardexecutive;

use App\Http\Controllers\Controller;
use App\Models\KunjunganPoli;
use App\Models\Pegawai\Libur;
use App\Models\Pegawai\TransaksiAbsen;
use App\Models\Poli;
use App\Models\Sigarang\Pegawai;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PelayananController extends Controller
{
    public function index()
    {

        $m = request('month');
        $y = request('year');
        $d = request('d');
        $tglF = $y . '-' . $m . '-' . $d;
        $time = strtotime($tglF);

        $tgl = date('Y-m-d', $time);

        $periode1 = $y . '-' . '01' . '-' . '01';
        $periode2 = $y . '-' . '12' . '-' . '31';

        $tempat_tidur = DB::select(
            "SELECT * FROM (
                    SELECT UPPER(rs24.rs2) AS ruang,COUNT(vBed.rs5) AS total,SUM(vBed.terisi) AS terisi,( COUNT(vBed.rs5) - SUM(vBed.terisi) ) AS sisa FROM (
                    SELECT rs5,IF(rs3='S',1,0) AS terisi FROM rs25 WHERE rs7<>'1' AND extra<>'1' AND rs5<>'-' AND rs8<>'1'
                    ) AS vBed,rs24
                    WHERE rs24.rs1=vBed.rs5 AND rs24.status<>'1' AND rs24.rs4<>'BR' GROUP BY vBed.rs5
                    UNION ALL
                    SELECT UPPER(ruang) AS ruang,COUNT(ruang) AS total,SUM(terisi) AS terisi,(COUNT(ruang)-SUM(terisi)) AS sisa FROM (
                        SELECT CONCAT('Ruang ',rs1) AS ruang,IF(rs3='S',1,0) AS terisi FROM rs25 WHERE rs7<>'1' AND extra<>'1' AND rs5='-' AND rs8<>'1' AND rs6<>'BR'
                        ) AS vBed GROUP BY ruang
                        ) AS vKamar ORDER BY ruang ASC"
        );


        $igd_harini = DB::select("
            select tanggalmasuk,noreg,norm,nama,alamat,kelamin,IF(thn<1,IF(bln<1,concat(hari,' hari'),concat(bln,' bln')),concat(thn,' thn')) as umur,
            poli,tipe,kodedokter,sistembayar,flagcovid from(select distinct rs17.rs1 as noreg,IF(rs15.rs16='1900-01-01',floor((datediff(rs17.rs3,'1970-01-01')/365)),floor((datediff(rs17.rs3,rs15.rs16)/365))) as thn,
            IF(rs15.rs16='1900-01-01',floor((datediff(rs17.rs3,'1970-01-01')-(floor((datediff(rs17.rs3,'1970-01-01')/365))*365))/30),floor((datediff(rs17.rs3,rs15.rs16)-(floor((datediff(rs17.rs3,rs15.rs16)/365))*365))/30)) as bln,
            IF(rs15.rs16='1900-01-01',(datediff(rs17.rs3,'1970-01-01')-((floor((datediff(rs17.rs3,'1970-01-01')/365))*365)+(floor((datediff(rs17.rs3,'1970-01-01')-(floor((datediff(rs17.rs3,'1970-01-01')/365))*365))/30)*30))),
            (datediff(rs17.rs3,rs15.rs16)-((floor((datediff(rs17.rs3,rs15.rs16)/365))*365)+(floor((datediff(rs17.rs3,rs15.rs16)-(floor((datediff(rs17.rs3,rs15.rs16)/365))*365))/30)*30)))) as hari,
            rs17.rs2 as norm,rs17.rs14 as kd_akun,rs15.rs2 as nama,rs15.rs3 as sapaan,rs15.rs4 as alamat,rs15.rs5 as kelurahan,
            rs15.rs6 as kecamatan,rs15.rs7 as rt,rs15.rs8 as rw,rs15.rs10 as propinsi,rs15.rs11 as kabupaten,rs15.rs16 as tgllahir,
            rs15.rs17 as kelamin,rs15.rs36 as normlama,rs15.rs37 as tmplahir,rs17.rs3 as tanggalmasuk,rs17.rs4 as penanggungjawab,
            rs17.rs6 as kodeasalrujukan,rs17.rs20 as asalpendaftaran,rs17.rs7 as namaperujuk,rs17.rs8 as kodepoli,rs19.rs2 as poli,
            rs17.rs18 as userid,rs17.rs19 as status,rs9.rs2 as sistembayar,IF(rs15.rs31>1,'Lama','Baru') as tipe,rs17.rs9 as kodedokter,'' as nosep,
            rs15.flag_covid as flagcovid
            from rs15,rs17,rs19,rs9
            where rs15.rs1=rs17.rs2 and rs17.rs8=rs19.rs1 and rs9.rs1=rs17.rs14
            and rs17.rs19='' and year(rs17.rs3)='" . date("Y") . "' and month(rs17.rs3)='" . date("m") . "' and dayofmonth(rs17.rs3)='" . date("d") . "'
            and rs17.rs8='POL014') as v_15_17 order by tanggalmasuk
        ");

        // $poli_hariinibelum = DB::select(
        //     "
        //         SELECT tanggalmasuk,noreg,norm,nama,alamat,kelamin,IF(thn<1,IF(bln<1,concat(hari,' hari'),concat(bln,' bln')),concat(thn,' thn')) as umur,
        //         poli,tipe,kodedokter,sistembayar,prmrj
        //         FROM(select distinct rs17.rs1 as noreg,
        //             IF(rs15.rs16='1900-01-01',floor((datediff(rs17.rs3,'1970-01-01')/365)),floor((datediff(rs17.rs3,rs15.rs16)/365))) as thn,
        //             IF(rs15.rs16='1900-01-01',floor((datediff(rs17.rs3,'1970-01-01')-(floor((datediff(rs17.rs3,'1970-01-01')/365))*365))/30),
        //             floor((datediff(rs17.rs3,rs15.rs16)-(floor((datediff(rs17.rs3,rs15.rs16)/365))*365))/30)) as bln,
        //             IF(rs15.rs16='1900-01-01',(datediff(rs17.rs3,'1970-01-01')-((floor((datediff(rs17.rs3,'1970-01-01')/365))*365)+(floor((datediff(rs17.rs3,'1970-01-01')-(floor((datediff(rs17.rs3,'1970-01-01')/365))*365))/30)*30))),
        //             (datediff(rs17.rs3,rs15.rs16)-((floor((datediff(rs17.rs3,rs15.rs16)/365))*365)+(floor((datediff(rs17.rs3,rs15.rs16)-(floor((datediff(rs17.rs3,rs15.rs16)/365))*365))/30)*30)))) as hari,
        //         rs17.rs2 as norm,rs17.rs14 as kd_akun,
        //          rs15.rs2 as nama,rs15.rs3 as sapaan,rs15.rs4 as alamat,rs15.rs5 as kelurahan,
        //         rs15.rs6 as kecamatan,rs15.rs7 as rt,rs15.rs8 as rw,rs15.rs10 as propinsi,rs15.rs11 as kabupaten,rs15.rs16 as tgllahir,
        //         rs15.rs17 as kelamin,rs15.rs36 as normlama,rs15.rs37 as tmplahir,rs17.rs3 as tanggalmasuk,rs17.rs4 as penanggungjawab,
        //         rs17.rs6 as kodeasalrujukan,rs17.rs20 as asalpendaftaran,rs17.rs7 as namaperujuk,rs17.rs8 as kodepoli,rs19.rs2 as poli,
        //         rs17.rs18 as userid,rs17.rs19 as status,rs9.rs2 as sistembayar,IF(rs15.rs31>1,'Lama','Baru') as tipe,rs17.rs9 as kodedokter,'' as nosep,'' as prmrj from rs15,rs17,rs19,rs9
        //         where rs15.rs1=rs17.rs2
        //         and rs17.rs8=rs19.rs1 and
        //         rs9.rs1=rs17.rs14
        //         and rs17.rs19=''
        //         and year(rs17.rs3)='" . date("Y") . "'
        //         and month(rs17.rs3)='" . date("m") . "' and
        //         dayofmonth(rs17.rs3)='" . date("d") . "'
        //         and rs17.rs8<>'POL014'
        //         and rs17.rs8<>'POL005' and
        //         rs17.rs8<>'POL025') as v_15_17 order by tanggalmasuk
        //     "
        // );



        // $poli_hariinisudah = DB::select(
        //     "select tanggalmasuk,noreg,norm,nama,alamat,kelamin,IF(thn<1,IF(bln<1,concat(hari,' hari'),concat(bln,' bln')),concat(thn,' thn')) as umur,
        //         poli,tipe,kodedokter,sistembayar,kondisiakhir from(select distinct rs17.rs1 as noreg,IF(rs15.rs16='1900-01-01',floor((datediff(rs17.rs3,'1970-01-01')/365)),floor((datediff(rs17.rs3,rs15.rs16)/365))) as thn,
        //         IF(rs15.rs16='1900-01-01',floor((datediff(rs17.rs3,'1970-01-01')-(floor((datediff(rs17.rs3,'1970-01-01')/365))*365))/30),floor((datediff(rs17.rs3,rs15.rs16)-(floor((datediff(rs17.rs3,rs15.rs16)/365))*365))/30)) as bln,
        //         IF(rs15.rs16='1900-01-01',(datediff(rs17.rs3,'1970-01-01')-((floor((datediff(rs17.rs3,'1970-01-01')/365))*365)+(floor((datediff(rs17.rs3,'1970-01-01')-(floor((datediff(rs17.rs3,'1970-01-01')/365))*365))/30)*30))),
        //         (datediff(rs17.rs3,rs15.rs16)-((floor((datediff(rs17.rs3,rs15.rs16)/365))*365)+(floor((datediff(rs17.rs3,rs15.rs16)-(floor((datediff(rs17.rs3,rs15.rs16)/365))*365))/30)*30)))) as hari,
        //         rs17.rs2 as norm,rs17.rs14 as kd_akun,rs15.rs2 as nama,rs15.rs3 as sapaan,rs15.rs4 as alamat,rs15.rs5 as kelurahan,
        //         rs15.rs6 as kecamatan,rs15.rs7 as rt,rs15.rs8 as rw,rs15.rs10 as propinsi,rs15.rs11 as kabupaten,rs15.rs16 as tgllahir,
        //         rs15.rs17 as kelamin,rs15.rs36 as normlama,rs15.rs37 as tmplahir,rs17.rs3 as tanggalmasuk,rs17.rs4 as penanggungjawab,
        //         rs17.rs6 as kodeasalrujukan,rs17.rs20 as asalpendaftaran,rs17.rs7 as namaperujuk,rs17.rs8 as kodepoli,rs19.rs2 as poli,
        //         rs17.rs18 as userid,rs17.rs19 as status,rs9.rs2 as sistembayar,IF(rs15.rs31>1,'Lama','Baru') as tipe,rs17.rs9 as kodedokter,if(rs141.rs5='',concat(rs141.rs4,' ',masterpoli.rs2),concat(rs141.rs4,' ',rs141.rs5)) as kondisiakhir,'' as nosep
        //         from rs15,rs17,rs19,rs9,rs141,rs19 as masterpoli
        //         where rs15.rs1=rs17.rs2 and
        //         rs17.rs8=rs19.rs1 and
        //         rs9.rs1=rs17.rs14 and
        //         rs141.rs1=rs17.rs1 and
        //         masterpoli.rs1=rs141.rs3
        //         and rs17.rs19='1' and year(rs17.rs3)='" . date("Y") . "' and month(rs17.rs3)='" . date("m") . "' and dayofmonth(rs17.rs3)='" . date("d") . "'
        //         and rs17.rs8<>'POL014' and rs17.rs8<>'POL005' and rs17.rs8<>'POL025') as v_15_17 order by tanggalmasuk"
        // );

        $poli_hariinibelum = DB::table('rs17')
            ->join('rs19', 'rs17.rs8', '=', 'rs19.rs1')
            ->select('rs17.rs1', 'rs17.rs3', 'rs17.rs2', 'rs17.rs8', 'rs17.rs14', 'rs17.rs19')
            ->where('rs19.rs4', 'Poliklinik')->where('rs19.rs5', '1')
            ->whereNotIn('rs19.rs1', ['POL027','POL011','POL035'])
           // ->whereNotIn('rs8', ['POL014', 'POL005', 'POL025','POL036'])
            // ->whereDate('rs3', '=', Carbon::today())
            ->whereBetween('rs17.rs3', [request('tgl') . ' 00:00:00', request('tgl') . ' 23:59:59'])
            ->where('rs17.rs19', '=', '')
            ->get();

        $poli_hariinisudah = DB::table('rs17')
            ->join('rs141', 'rs17.rs1', '=', 'rs141.rs1')
            ->join('rs19', 'rs17.rs8', '=', 'rs19.rs1')
            ->select('rs17.rs1', 'rs17.rs3', 'rs17.rs2', 'rs17.rs8', 'rs17.rs14', 'rs17.rs19')
            ->where('rs19.rs4', 'Poliklinik')->where('rs19.rs5', '1')
            ->whereNotIn('rs19.rs1', ['POL027','POL011','POL035'])
           // ->whereNotIn('rs17.rs8', ['POL014', 'POL005', 'POL025','POL036'])
            // ->whereBetween('rs17.rs3', ['2023-02-21 00:00:00', '2023-02-21 23:59:59']) // super cepat
            ->whereBetween('rs17.rs3', [request('tgl') . ' 00:00:00', request('tgl') . ' 23:59:59']) // super cepat
            // ->where(DB::raw("(DATE_FORMAT(rs17.rs3, '%Y-%m-%d'))"), request('tgl'))
            // ->whereDate('rs3', '=', Carbon::today())
            ->where('rs17.rs19', '=', '1')
            ->get();

        $poli_tahun = DB::table('rs17')
            ->join('rs141', 'rs17.rs1', '=', 'rs141.rs1')
            ->join('rs19', 'rs17.rs8', '=', 'rs19.rs1')
            ->selectRaw('count(rs17.rs1) as jumlah, MONTH(rs17.rs3) month')
            //->whereNotIn('rs17.rs8', ['POL014', 'POL005', 'POL025'])
            ->where('rs19.rs4', 'Poliklinik')->where('rs19.rs5', '1')
            ->whereNotIn('rs19.rs1', ['POL027','POL011','POL035'])
            ->whereBetween('rs17.rs3', [$periode1 . ' 00:00:00', $periode2 . ' 23:59:59']) // super cepat
            ->where('rs17.rs19', '=', '1')
            ->groupBy('month')
            ->get();


        // $ranap_lalu = DB::select(
        //     "
        //         select tanggalmasuk,tglkeluar,datediff(tglkeluar,tanggalmasuk)+1 as lama,noreg,norm,nama,alamat,kabupaten,kelamin,tipe,datediff(tanggalmasuk,tgllahir) umur,floor((datediff(tanggalmasuk,tgllahir)/365)) as thn,
        //         floor((datediff(tanggalmasuk,tgllahir)-(floor((datediff(tanggalmasuk,tgllahir)/365))*365))/30) as bln,
        //         (datediff(tanggalmasuk,tgllahir)-((floor((datediff(tanggalmasuk,tgllahir)/365))*365)+(floor((datediff(tanggalmasuk,tgllahir)-(floor((datediff(tanggalmasuk,tgllahir)/365))*365))/30)*30))) as hari,
        //         ruang,kodedokter,sistembayar,diagnosa,kodediag
        //         from(select distinct rs23.rs1 as noreg,rs23.rs2 as norm,rs23.rs19 as kd_akun,rs15.rs2 as nama,rs23.rs26 as kodediag,
        //         rs15.rs3 as sapaan,rs15.rs4 as alamat,rs15.rs5 as kelurahan,rs15.rs6 as kecamatan,rs15.rs7 as rt,rs15.rs8 as rw,
        //         rs15.rs10 as propinsi,rs15.rs11 as kabupaten,rs15.rs16 as tgllahir,rs15.rs17 as kelamin,rs15.rs36 as normlama,
        //         rs15.rs37 as tmplahir,rs23.rs3 as tanggalmasuk,rs23.rs11 as penanggungjawab,rs23.rs13 as kodeasalrujukan,
        //         rs23.rs20 as asalpendaftaran,rs23.rs16 as namaperujuk,rs23.rs5 as koderuang,rs24.rs2 as ruang,rs23.rs30 as userid,
        //         rs23.rs28 as status,rs9.rs2 as sistembayar,IF(rs15.rs30>1,'Lama','Baru') as tipe,rs23.rs4 as tglkeluar,rs23.rs26 as diagnosa,rs23.rs10 as kodedokter

        //         from rs15,rs23,rs24,rs9
        //         where rs15.rs1=rs23.rs2
        //         and rs23.rs5=rs24.rs1
        //         and rs23.rs4<'" . date("Y-m-d") . "' and year(rs23.rs4)='" . date("Y") . "'
        //         and rs9.rs1=rs23.rs19
        //         and (rs23.rs22='2' or rs23.rs22='3'))as v_15_23  order by tglkeluar desc
        //     "
        // );

        $ranap_tahun = DB::table('rs23')
            ->join('rs15', 'rs23.rs2', '=', 'rs15.rs1')
            ->join('rs24', 'rs23.rs5', '=', 'rs24.rs1')
            ->selectRaw('count(rs23.rs1) as jumlah, MONTH(rs23.rs4) month')
            ->whereBetween('rs23.rs4', [$periode1 . ' 00:00:00', $periode2 . ' 23:59:59']) // super cepat
            ->where(function ($x) {
                $x->where('rs23.rs22', '=', '2')
                    ->orWhere('rs23.rs22', '=', '3');
            })
            ->groupBy('month')
            ->get();


        $poli = Poli::where('rs5', '1')->where('rs4', 'Poliklinik')
            ->whereNotIn('rs1', ['POL027','POL011','POL035'])
            ->orderBy('rs2', 'ASC')->get();

        $data = array(
            "tempat_tidur" => $tempat_tidur,
            'igd_harini' => $igd_harini,
            'poli_hariinibelum' => $poli_hariinibelum,
            'poli_hariinisudah' => $poli_hariinisudah,
            'poli_tahun' => $poli_tahun,
            'ranap_tahun' => $ranap_tahun,
            'poli' => $poli
        );
        return response()->json($data);
    }
}
