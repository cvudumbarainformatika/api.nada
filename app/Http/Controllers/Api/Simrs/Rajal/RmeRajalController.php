<?php

namespace App\Http\Controllers\Api\Simrs\Rajal;

use App\Helpers\BridgingbpjsHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Rajal\KunjunganPoli;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RmeRajalController extends Controller
{
    public function rmerajal()
    {
        $data = KunjunganPoli::with(
            [
                'anamnesis',
                'diagnosakeperawatan.intervensi.masterintervensi',
                'pemeriksaanfisik' => function ($p) {
                    $p->with(['detailgambars', 'pemeriksaankhususmata', 'pemeriksaankhususparu'])
                        ->orderBy('id', 'DESC');
                },
                'diagnosa' => function ($a) {
                    $a->with(['masterdiagnosa'])
                        ->orderBy('id', 'DESC');
                },
                'laborat' => function ($b) {
                    $b->with(['pemeriksaanlab'])
                        ->orderBy('id', 'DESC');
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
                    $tindakan->select('rs73.rs1', 'rs30.rs2 as tindakan', 'rs73.rs20 as keterangan')
                        ->join('rs30', 'rs30.rs1', 'rs73.rs4');
                },
                'planning' => function ($planning) {
                    $planning
                        ->where('rs4', 'not like', '%Pulang%');
                },
            ]
        )->where('rs1', request('noreg'))
            ->get();
        return new JsonResponse($data);
    }

    public function suratkontrolbysuratkontrol()
    {
        $suratKontrol = request('noSuratKontrol');
        $kontrol = BridgingbpjsHelper::get_url('vclaim', '/RencanaKontrol/noSuratKontrol/' . $suratKontrol);
        return $kontrol;
    }
}
