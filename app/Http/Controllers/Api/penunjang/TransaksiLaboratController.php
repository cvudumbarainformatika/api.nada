<?php

namespace App\Http\Controllers\Api\penunjang;

use App\Helpers\QueryHelper;
use App\Http\Controllers\Controller;
use App\Models\Pasien;
use App\Models\TransaksiLaborat;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TransaksiLaboratController extends Controller
{
    public function index()
    {
        $query = $this->query_table('table')
            ->with([
                'kunjungan_poli',
                // 'kunjungan_poli.pasien',
                'kunjungan_rawat_inap',
                // 'kunjungan_rawat_inap.pasien',
                // 'kunjungan_poli.sistem_bayar',
                // 'kunjungan_rawat_inap.sistem_bayar',
                'sb_kunjungan_poli',
                'sb_kunjungan_rawat_inap',
                'kunjungan_rawat_inap.ruangan',
                'poli',
                'dokter',
                'pasien_kunjungan_poli:rs15.rs1,rs15.rs2,rs15.rs3,rs15.rs4,rs15.rs6,rs15.rs16,rs15.rs17,rs15.rs31',
                'pasien_kunjungan_rawat_inap:rs15.rs1,rs15.rs2,rs15.rs3,rs15.rs4,rs15.rs6,rs15.rs16,rs15.rs17,rs15.rs31',
                'pemeriksaan_laborat'
            ]);
        $data = $query->simplePaginate(request('per_page'));

        $sql = QueryHelper::getSqlWithBindings($query);
        Log::info('Query: ' . $sql);

        return new JsonResponse($data);
    }

    public function totalData()
    {
        $data = $this->query_table('total')->get()->count();
        return new JsonResponse($data);
    }

    public function query_table($val)
    {
        $y = Carbon::now()->subYears(1);
        $m = Carbon::now()->subMonth(6);
        $from = now();
        $to = $m;
        $query = TransaksiLaborat::query();
        if ($val === 'total') {
            $select = $query->selectRaw('rs2,rs3');
        } else {
            $select = $query->select(
            'rs51.rs1',
            'rs51.rs2',
            'rs51.rs3 as tanggal',
            'rs51.rs20',
            'rs51.rs8',
            'rs51.rs23',
            'rs51.rs18',
            'rs51.rs21',
            'rs51.rs4',
            'rs51.rs26',
            'rs51.rs27',
            'rs51.rs12 as cito',
            // 'rs51_meta.norm as norm', 
            // 'rs15.rs2 as nama'
            )
            // ->leftjoin('rs51_meta', 'rs51_meta.nota', '=', 'rs51.rs2')
            // ->leftjoin('rs15', 'rs51_meta.norm', '=', 'rs15.rs1')
        ;
        }
        $q = $select
            ->whereBetween('rs51.rs3', [$to, $from])
            ->filter(request(['q', 'periode', 'filter_by']))
            // ->when(request('q'), function ($search, $q) {
            //     $search->where('rs51.rs2', 'like', '%' . $q . '%');
            // })
            // ->when(request('periode'), function ($search, $query) {
            //     // pasien hari ini sudah
            //     if ($query == 2) {
            //         return $search
            //             ->whereDate('rs3', '=', date('Y-m-d'))
            //             ->where('rs20', '<>', '');
            //     } elseif ($query == 3) {
            //         // pasien lalu
            //         return
            //             $search->whereDate('rs3', '<', date('Y-m-d'))
            //             ->where('rs20', '=', '');
            //     } elseif ($query == 4) {
            //         // pasien lalu sudah
            //         return $search->whereDate('rs3', '<', date('Y-m-d'))
            //             ->where('rs20', '<>', '');
            //     } else {
            //         // pasien hari ini
            //         return $search->whereDate('rs3', '=', date('Y-m-d'))
            //             ->where('rs20', '=', '');
            //     }
            // })
            ->orderBy('rs51.rs3', 'asc')->groupBy('rs51.rs2');
        return $q;
    }

    public function get_details()
    {
        $data = TransaksiLaborat::where('rs2', request('nota'))
            ->with('pemeriksaan_laborat')->get();

        return new JsonResponse($data);
    }

    public function kirim_ke_lis(Request $request)
    {
        $xid = "4444";
        $secret_key = 'l15Test';
        date_default_timezone_set('UTC');
        $xtimestamp = strval(time() - strtotime('1970-01-01 00:00:00'));
        $sign = hash_hmac('sha256', $xid . "&" . $xtimestamp, $secret_key, true);
        $xsignature = base64_encode($sign);

        // $apiURL = 'http://172.16.24.2:83/prolims/api/lis/postOrder';
        $apiURL = 'http://192.168.101.200:83/prolims/api/lis/postOrder';


        $headers = [
            'X-id' => $xid,
            'X-timestamp' => $xtimestamp,
            'X-signature' => $xsignature,
            // 'Accept' => 'application/json'
        ];

        $response = Http::withHeaders($headers)->post($apiURL, $request->all());
        if (!$response) {
            return response()->json([
                'message' => 'Harap Ulangi... LIS ERROR'
            ], 500);
        }

        $statusCode = $response->status();
        $responseBody = json_decode($response->getBody(), true);

        TransaksiLaborat::where('rs2', $request->ONO)->update(['rs18' => "1"]);

        return $responseBody;
    }

    
}
