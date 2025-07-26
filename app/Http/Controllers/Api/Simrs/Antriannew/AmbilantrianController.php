<?php

namespace App\Http\Controllers\Api\Simrs\Antriannew;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Antriannew\Mambilantrianadmisi;
use App\Models\Simrs\Antriannew\Trcounterantrian;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AmbilantrianController extends Controller
{
    public function ambilantrianadmisi(Request $request)
    {
        $tglsekarang = date('Y-m-d');
        $ceksisbayar = Mambilantrianadmisi::findOrFail($request->id);

        $counter = $ceksisbayar->keterangan;
        $lebel = $ceksisbayar->antrian;
        $jam_mulai = $ceksisbayar->jam_mulai;
        $lama = $ceksisbayar->lama;

        $jmlantri = 0;
        $batas = 0;
        $carijml = Trcounterantrian::whereDate('tgl', $tglsekarang)
            ->where('nama_counter', $counter)->first();
        if ($request->cara == 'online') {
            $jmlantri = $carijml->coun_ol ?? 1;
            $batas = $ceksisbayar->max_on;
        } else {
            $jmlantri = $carijml->count_off ?? 1;
            $batas = $ceksisbayar->max_of;
        }


        $cariambilantrian = Trcounterantrian::whereDate('tgl', $tglsekarang)
            ->where('nama_counter', $counter)->count();

        $procedure = 'ambil_antrian_loket("' . $counter . '","' . $tglsekarang . '","' . $request->cara . '")';

        $cariambilantrian = Trcounterantrian::whereDate('tgl', $tglsekarang)
            ->where('nama_counter', $counter)->count();

        if ($jmlantri >= $batas) {
            return new JsonResponse(['message' => 'Antrian Sudah Penuh...!!'], 500);
        }

        if ($cariambilantrian == 0) {
            Trcounterantrian::firstorcreate(
                [
                    'kdcounter' => $request->id,
                    'nama_counter' => $counter,
                    'tgl' => $tglsekarang
                ]
            );
        }

        DB::connection('newantrean')->select('call ' . $procedure);
        $x = DB::connection('newantrean')->table('conter_antrian')->select('counter', 'count_ol', 'count_off')
            ->whereDate('tgl', $tglsekarang)
            ->where('nama_counter', $counter)
            ->first();
        $wew =  $x->counter ?? 1;
        $noantrian = FormatingHelper::antrian($wew, $lebel);
        $totalmenit = (int) $lama * ((int) $wew - 1);
        $estimasi = date('Y-m-d H:i:s', strtotime('+' . $totalmenit . 'minutes', strtotime($jam_mulai)));


        return new JsonResponse(
            [
                'message' => '--- Terimah Kasih Atas Kunjungan Anda,Semoga Lekas Sembuh ---',
                'noantrian' => $noantrian,
                'totallama' => $totalmenit,
                'estimasi' => $estimasi
            ],
            200
        );
    }
}
