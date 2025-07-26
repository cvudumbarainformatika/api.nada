<?php

namespace App\Http\Controllers\Api\Simrs\Pendaftaran\Ranap;

use App\Helpers\BridgingbpjsHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Pendaftaran\Ranap\Sepranap;
use App\Models\Simrs\Rajal\KunjunganPoli;
use App\Models\Simrs\Ranap\Kunjunganranap;
use App\Models\Simrs\Ranap\Rs141;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class HistoryKunjunganController extends Controller
{
    public function index()
    {
        $total = self::query_table()->get()->count();
        $data = self::query_table()->simplePaginate(request('per_page'));

        $response = (object)[
            'total' => $total,
            'data' => $data
        ];

        // opoo kok error
        return response()->json($response);

    }

    static function query_table()
    {
        if (request('to') === '' || request('from') === null) {
            $tglx = Carbon::now()->format('Y-m-d 00:00:00');
            $tgl = Carbon::now()->format('Y-m-d 23:59:59');
        } else {
            $tglx = request('to') . ' 00:00:00';
            $tgl = request('from') . ' 23:59:59';
        }
  
        $sort = request('sort') === 'terbaru'? 'DESC':'ASC';
        $status = ((request('status') === 'Belum Pulang') ? [''] : ['2','3']);
        $ruangan = request('ruangan');
        $query = Kunjunganranap::query();
  
        $select = $query->select(
            'rs23.rs1',
            'rs23.rs1 as noreg',
            'rs23.rs2 as norm',
            'rs23.rs3 as tglmasuk',
            'rs23.rs4 as tglkeluar',
            'rs23.rs5 as kdruangan',
            'rs23.rs6 as ketruangan',
            'rs23.rs7 as nomorbed',
            'rs23.rs10 as kddokter',
            'rs21.rs2 as dokter',
            'rs23.rs19 as kodesistembayar', // ini untuk farmasi
            'rs23.rs22 as status', // '' : BELUM PULANG | '2 ato 3' : PASIEN PULANG
            'rs15.rs2 as nama_panggil',

            DB::raw('concat(rs15.rs3," ",rs15.gelardepan," ",rs15.rs2," ",rs15.gelarbelakang) as nama'),
            DB::raw('concat(rs15.rs4," KEL ",rs15.rs5," RT ",rs15.rs7," RW ",rs15.rs8," ",rs15.rs6," ",rs15.rs11," ",rs15.rs10) as alamat'),
            DB::raw('concat(TIMESTAMPDIFF(YEAR, rs15.rs16, CURDATE())," Tahun ",
                        TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()) % 12," Bulan ",
                        TIMESTAMPDIFF(DAY, TIMESTAMPADD(MONTH, TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()), rs15.rs16), CURDATE()), " Hari") AS usia'),
            DB::raw("(IF(rs23.rs4='0000-00-00 00:00:00',datediff('".date("Y-m-d")."',rs23.rs3),
            datediff(rs23.rs4,rs23.rs3)))+1  as lama"),

            'rs15.rs4 as alamatbarcode',
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
            'rs222.rs8 as sep_igd',
            'rs227.rs8 as sep',
            'rs227.rs10 as faskesawal',
            'rs227.kodedokterdpjp as kodedokterdpjp',
            'rs227.dokterdpjp as dokterdpjp',
            'rs24.rs2 as ruangan',
            'rs24.rs3 as kelasruangan',
            'rs24.rs5 as group_ruangan',
            // 'rs101.rs3 as kode_diagnosa'
            // 'bpjs_spri.noSuratKontrol as noSpri'
        )
            ->leftjoin('rs15', 'rs15.rs1', 'rs23.rs2')
            ->leftjoin('rs9', 'rs9.rs1', 'rs23.rs19')
            ->leftjoin('rs21', 'rs21.rs1', 'rs23.rs10')
            ->leftjoin('rs24', 'rs24.rs1', 'rs23.rs5')
            ->leftjoin('rs227', 'rs227.rs1', 'rs23.rs1')
            ->leftjoin('rs222', 'rs222.rs1', 'rs23.rs1')
            // ->leftjoin('rs101', 'rs101.rs1', 'rs23.rs1')
            // ->leftjoin('bpjs_spri', 'rs23.rs1', '=', 'bpjs_spri.noreg')

            // ->with(['sepranap' => function($q) {
            //     $q->select('rs1', 'rs8 as noSep', 'rs3 as ruang', 'rs5 as noRujukan', 'rs7 as diagnosa', 'rs10 as ppkRujukan', 'rs11 as jenisPeserta');
            // }])
            ->addSelect(DB::raw(
                '(SELECT rs4 FROM rs23 WHERE rs23.rs2 = rs15.rs1 AND rs23.rs4 != "0000-00-00 00:00:00" ORDER BY rs4 DESC LIMIT 1) 
                 as last_visit'))

            ->with(['diagnosa' => function($q) {
                $q->select('rs101.rs1', 'rs101.rs3 as kode', 'rs99x.rs4 as inggris', 'rs99x.rs3 as indonesia', 'rs101.rs4 as type', 'rs101.rs7 as status')
                    ->leftjoin('rs99x', 'rs101.rs3', 'rs99x.rs1')
                    ->orderBy('rs101.id', 'desc');
            }])
            
            ->where(function($query) use ($tgl, $tglx) {
                $query->whereBetween('rs23.rs3', [$tglx, $tgl]);
            })
            ->where(function ($query) {
                $query->when(request('q'), function ($q) {
                    $q->where('rs23.rs1', 'like',  '%' . request('q') . '%')
                        ->orWhere('rs23.rs2', 'like',  '%' . request('q') . '%')
                        ->orWhere('rs15.rs2', 'like',  '%' . request('q') . '%');
                });
            })
            ->where(function ($query) use ($ruangan) {
                $query->when(request('ruangan'), function ($query) use ($ruangan) {
                    // for ($i = 0; $i < count($ruangan); $i++) {
                    //     $query->orWhere('rs23.rs5', 'like',  '%' . $ruangan[$i] . '%');
                    // }
                    $query->where('rs23.rs5', 'like',  '%' . $ruangan . '%');
                });
            })
            ->where(function ($q) use ($status) {
                if (request('status') !== 'Semua') {
                    $q->whereIn('rs23.rs22', $status);
                }
                
            })
            ->orderby('rs23.rs3', $sort)
            ->groupBy('rs23.rs1')
            ;


        return $select;
    }

}
