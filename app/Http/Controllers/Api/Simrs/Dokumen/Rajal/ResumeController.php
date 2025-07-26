<?php

namespace App\Http\Controllers\Api\Simrs\Dokumen\Rajal;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Rajal\KunjunganPoli;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResumeController extends Controller
{
    public function resume()
    {
        $resume = KunjunganPoli::select(
            'rs17.rs1',
            'rs17.rs9'
        )->with(
            [
                'dokter:rs1,rs2 as dokter',
                'diagnosa:rs1,rs2,rs3,rs4 as jenisdiagnosa,rs5,rs6,rs7 as kasus,rs8,rs9,rs10,rs11,rs12',
                'diagnosa.masterdiagnosa:rs1,rs4 as diagnosa',
                'anamnesis',
                'edukasi',
                'laborat:rs1,rs2,rs4,rs21,metode,tat',
                'laborat.pemeriksaanlab:rs1,rs2',
                'pemeriksaanfisik' => function ($a) {
                    $a->with(['detailgambars', 'pemeriksaankhususmata', 'pemeriksaankhususparu'])
                        ->orderBy('id', 'DESC');
                },
                'usg' => function ($usg) {
                    $usg->select('rs1', 'rs20 as hasil')->where('rs4', 'T00031')
                        ->orWhere('rs4', 'T00068')->orWhere('rs4', 'TX0128')
                        ->orWhere('rs4', 'TX0131');
                },
                'ecg' => function ($ecg) {
                    $ecg->select('rs1', 'rs20 as hasil')->where('rs4', 'POL009');
                },
                'eeg' => function ($eeg) {
                    $eeg->select('rs1', 'rs7 as tanggal', 'rs4 as klasifikasi', 'rs5 as impresi');
                },
                'pembacaanradiologi',
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
                    $tindakan->select('rs73.rs1', 'rs30.rs2 as tindakan', 'rs73.rs20 as keterangan', 'rs73_sambung.ket as ket')
                        ->join('rs30', 'rs30.rs1', 'rs73.rs4')
                        ->join('rs73_sambung', 'rs73.id', 'rs73_sambung.rs73_id');
                },
                'planning' => function ($planning) {
                    $planning->with([
                        // 'masterpoli',
                        'rekomdpjp' => function ($q) {
                            $q->orderBy('id', 'DESC');
                        },
                        'transrujukan.diagnosa:rs1,rs4',
                        'listkonsul',
                        'spri',
                        'ranap',
                        'kontrol',
                        'operasi',
                    ])
                        ->where('rs4', 'not like', '%Pulang%');
                },
                'diagnosakeperawatan.intervensi.masterintervensi',

                'newapotekrajal' => function ($newapotekrajal) {
                    $newapotekrajal->select(
                        'noresep',
                        'noreg',
                        'sistembayar',
                        'dokter',
                    )->whereIn('flag', ['3', '4'])->with([
                        'rincian:kdobat,noresep,jumlah',
                        'rincianracik:kdobat,noresep,jumlah',
                        'permintaanresep:kdobat,noresep,jumlah',
                        'permintaanracikan:kdobat,noresep,jumlah',
                        'permintaanresep.mobat:kd_obat,nama_obat,kode_bpjs',
                        'permintaanracikan.mobat:kd_obat,nama_obat,kode_bpjs',
                        'sistembayar',
                        'dokter:nama,kdpegsimrs',
                    ])
                        ->orderBy('id', 'DESC');
                },
                'newapotekrajalretur:noreg,noretur',
                'newapotekrajalretur.rinci:noretur,noresep,kdobat,jumlah_retur',
            ]
        )->where('rs17.rs1', request('noreg'))
            ->first();
        return new JsonResponse([
            'data' => $resume
        ]);
    }
}
