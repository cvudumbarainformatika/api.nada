<?php

namespace App\Http\Controllers\Api\Simrs\Dokumen\Rajal;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Rajal\KunjunganPoli;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CatatanRawatJalanController extends Controller
{
    public function catatanRawatJalan()
    {
        if ((int)request('tahunakhir') < (int) request('tahunawal')) {
            return new JsonResponse(['message' => 'inputan tahun Salah'], 500);
        }

        $tahunawal = (string) request('tahunawal') . '-01-01 00:00:00';
        $tahunakhir = (string) request('tahunakhir') . '-12-31 23:59:59';

        $data = KunjunganPoli::select('rs1', 'rs2', 'rs3', 'rs9')
            ->with(
                [
                    'pegawai',
                    'anamnesis',
                    'edukasi',
                    'pembacaanradiologi',
                    'diagnosakeperawatan.intervensi.masterintervensi',
                    'diagnosakeperawatan.masterperawat' ,
                    'diagnosa' => function ($a) {
                        $a->with(['masterdiagnosa', 'dokter']);
                    },
                    'laborat' => function ($b) {
                        $b->with(['pemeriksaanlab', 'laboratmeta']);
                    },
                    'jampulangtaskid' => function ($c) {
                        $c->where('taskid', '5');
                    },
                    'pemeriksaanfisik' => function ($d) {
                        $d->with(['detailgambars', 'pemeriksaankhususmata', 'pemeriksaankhususparu'])
                            ->orderBy('id', 'DESC');
                    },
                    'usg' => function ($usg) {
                        $usg->select('rs73.rs1', 'rs30.rs2 as nama', 'rs73.rs20 as hasil')
                            ->join('rs30', 'rs30.rs1', 'rs73.rs4')
                            ->where('rs73.rs4', 'T00031')
                            ->orWhere('rs73.rs4', 'T00068')->orWhere('rs73.rs4', 'TX0128')
                            ->orWhere('rs73.rs4', 'TX0131');
                    },
                    'ecg' => function ($ecg) {
                        $ecg->select('rs1', 'rs4', 'rs20 as hasil')
                            ->with(['mastertindakan'])
                            ->where('rs4', 'POL009');
                    },
                    'eeg' => function ($eeg) {
                        $eeg->select('rs1', 'rs7 as tanggal', 'rs4 as klasifikasi', 'rs5 as impresi');
                    },
                    'apotekrajal' => function ($apotekrajal) {
                        $apotekrajal->select('rs90.rs1', 'rs32.rs2 as obat', 'rs90.rs8 as jumlah')
                            ->join('rs32', 'rs32.rs1', 'rs90.rs4');
                    },
                    'apotekrajalpolilalu' => function ($apotekrajalpolilalu) {
                        $apotekrajalpolilalu->select('rs162.rs1', 'rs32.rs2 as obat', 'rs162.rs8 as jumlah')
                            ->join('rs32', 'rs32.rs1', 'rs162.rs4');
                    },
                    'apotekracikanrajal' => function ($apotekracikanrajal) {
                        $apotekracikanrajal->select('rs32.rs2 as obat', 'rs92.rs5 as jumlah')
                            ->join('rs32', 'rs32.rs1', 'rs92.rs4');
                    },
                    'apotekracikanrajallalu' => function ($apotekracikanrajal) {
                        $apotekracikanrajal->select('rs32.rs2 as obat', 'rs164.rs5 as jumlah')
                            ->join('rs32', 'rs32.rs1', 'rs164.rs4');
                    },
                    'tindakan' => function ($tindakan) {
                        $tindakan->select('rs73.rs1', 'rs30.rs2 as tindakan', 'rs73.rs20 as keterangan')
                            ->join('rs30', 'rs30.rs1', 'rs73.rs4');
                    },
                    'planning' => function ($planning) {
                        $planning
                            ->where('rs4', 'not like', '%Pulang%');
                    },
                    'kamaroperasi' => function ($kamaroperasi) {
                        $kamaroperasi->with(['mastertindakanoperasi']);
                    },
                ]
            )
            ->where('rs2', request('norm'))
            ->where('rs8', '!=', 'POL014')
            ->whereBetween('rs3', [$tahunawal, $tahunakhir])
            ->orderBy('rs3', 'Desc')
            ->get();
        return new JsonResponse($data);
    }
}
