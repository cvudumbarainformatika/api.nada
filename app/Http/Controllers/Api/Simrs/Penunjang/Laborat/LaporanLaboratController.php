<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Laborat;

use App\Events\NotifMessageEvent;
use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Penunjang\Laborat\LaboratMeta;
use App\Models\Simrs\Penunjang\Laborat\Laboratpemeriksaan;
use App\Models\Simrs\Penunjang\Laborat\MasterLaborat;
use App\Models\TransaksiLaborat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LaporanLaboratController extends Controller
{
  public function masterlaborat()
  {
     $data = MasterLaborat::select(
       'rs1 as kode',
       'rs2 as pemeriksaan',
      //  'rs3 as hargasaranapoliumum',
      //  'rs4 as hargapelayananpoliumum',
      //  'rs5 as hargasaranapolispesialis',
      //  'rs6 as hargapelayananpolispesialis',
       'rs21 as gruper',
       'nilainormal',
       'satuan',
       'jenislab'
     )
    //  ->where('rs25', '1')
     ->where('hidden', '!=', '1')
     ->orderBy('tampilanurut', 'asc')
     ->orderBy('rs2')
     ->get();
     return new JsonResponse($data);
  }

  public function pemeriksaanByGender()
  {
    $from = request('from') . ' 00:00:00';
    $to = request('to') . ' 23:59:59';

    $data = TransaksiLaborat::select(
        'rs49.rs2 as nama_pemeriksaan',
        'rs49.rs1 as kode',
        DB::raw('DATE(rs51.rs3) as tgl_order'),
        DB::raw('COUNT(CASE WHEN rs15.rs17 = "Laki-laki" THEN 1 END) as total_laki'),
        DB::raw('COUNT(CASE WHEN rs15.rs17 = "Perempuan" THEN 1 END) as total_perempuan'),
        DB::raw('COUNT(*) as total')
    )
    ->join('rs51_meta', 'rs51_meta.nota', '=', 'rs51.rs2')
    ->join('rs49', 'rs51.rs4', '=', 'rs49.rs1')
    ->join('rs15', 'rs51_meta.norm', '=', 'rs15.rs1')
    ->whereBetween('rs51.rs3', [$from, $to])
    ->groupBy(DB::raw('DATE(rs51.rs3)'), 'rs51.rs4')
    ->orderBy('rs49.rs2')
    ->get();

    return new JsonResponse([
        'message' => 'Data pemeriksaan laboratorium berdasarkan gender',
        'data' => $data,
        'periode' => [
            'dari' => $from,
            'sampai' => $to
        ]
    ]);
  }
}
