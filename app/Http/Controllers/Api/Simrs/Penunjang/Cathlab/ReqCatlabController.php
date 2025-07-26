<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Cathlab;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mtarifcathlab;
use App\Models\Simrs\Penunjang\Cathlab\ReqCathlab;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

use function PHPUnit\Framework\isNull;

class ReqCatlabController extends Controller
{
    public function reqcathlab()
    {
        if (request('to') === '' || request('from') === null) {
            $tgl = Carbon::now()->format('Y-m-d 00:00:00');
            $tglx = Carbon::now()->format('Y-m-d 23:59:59');
        } else {
            $tgl = request('to') . ' 00:00:00';
            $tglx = request('from') . ' 23:59:59';
        }
        $status = request('status') ?? '';

        $req = ReqCathlab::select(
            'cathlab_req.noreg as noreg',
            'cathlab_req.nota as nota',
            'cathlab_req.norm as norm',
            'cathlab_req.tgl as tanggal',
            'cathlab_req.flag as flag',
            'cathlab_req.kelas as kelas',
            'cathlab_req.kd_ruangkelas as kd_ruangkelas',
            'kepegx.pegawai.nama as dokter',
            'rs9.rs2 as sistembayar',
            'rs9.groups as groups',
            'rs24.rs2 as ruangan',
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
            'rs15.bahasa as bahasa',
            'rs15.bacatulis as bacatulis',
            'rs15.kdhambatan as kdhambatan',
            'rs15.rs2 as name',
            'rs222.rs8 as sep',
            )
        ->leftjoin('rs15', 'rs15.rs1', '=', 'cathlab_req.norm') //pasien
        ->leftjoin('kepegx.pegawai', 'kepegx.pegawai.kdpegsimrs', '=', 'cathlab_req.dokterpengirim') //dokter
        ->leftjoin('rs9', 'rs9.rs1', '=', 'cathlab_req.sistembayar') //sistembayar
        ->leftjoin('rs222', 'rs222.rs1', '=', 'cathlab_req.noreg') //sep
        ->leftjoin('rs24', 'rs24.rs1', '=', 'cathlab_req.kd_ruangkelas') //ruangan
        ->whereBetween('cathlab_req.tgl', [$tgl, $tglx])
        ->where(function ($sts) use ($status) {
            if ($status !== 'all') {
                if ($status === '') {
                    $sts->whereNull('cathlab_req.flag');
                } else {
                    $sts->where('cathlab_req.flag', '=', $status);
                }
            }
        })
        ->paginate(request('per_page'));
        return new JsonResponse($req);
    }

    public function terimapasien(Request $request)
    {
        $cekx = ReqCathlab::with(
            [
                'datasimpeg:id,nip,nik,nama,kelamin,foto,kdpegsimrs,kddpjp,ttdpegawai',
                'cathlab',
                'cathlab.tarif',
                'cathlab.pelaksana1',
                'cathlab.pelaksana2'
            ]
        )
        ->where('nota', $request->nota)->first();
        if ($cekx) {
            $flag = $cekx->flag;

            if ($flag === null) {
                $cekx->flag = '2';
                $cekx->save();
            }

            return new JsonResponse($cekx, 200);
        } else {
            return response()->json([
                'message' => 'Data tidak ditemukan'
            ], 500);
        }
    }

    public function tarifcathlab()
    {
        $data = Cache::remember('cathlab', now()->addDays(7), function () {
            return Mtarifcathlab::query()
            ->get();
        });

        return new JsonResponse($data);
    }

    public function updateflag(Request $request)
    {
        $updatekunjungan = ReqCathlab::where('nota',$request->nota)->first();
        $updatekunjungan->flag = '1';
        $updatekunjungan->save();

        return new JsonResponse([
            'message' => 'DATA SUDAH DIKUNCI...!!!'
        ], 200);
    }
}
