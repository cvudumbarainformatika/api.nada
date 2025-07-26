<?php

namespace App\Http\Controllers\Api\Simrs\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Rajal\KunjunganPoli;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BillingbynoregController extends Controller
{
    public  function billbynoregrajalx(){
        $noreg = request('noreg');
        $data = self::billbynoregrajal($noreg);
        return new JsonResponse($data);
    }
    public static function billbynoregrajal($noreg)
    {
        $query = KunjunganPoli::select(
            'rs17.rs1 as noreg','rs17.rs1',
        )->with(
            [
                'adminpoli',
                'kwitansilog',
                'karcislog'
            ]
        )
            ->where('rs17.rs1', $noreg)
            ->get();

        $pelayananrm = DetailbillingbynoregController::pelayananrm($noreg);
        $kartuidentitas = DetailbillingbynoregController::kartuidentitas($noreg);
        $poliklinik = DetailbillingbynoregController::poliklinik($noreg);
        $konsulantarpoli = DetailbillingbynoregController::konsulantarpoli($noreg);
        $tindakan = DetailbillingbynoregController::tindakan($noreg);
        $tindakanrinci = $tindakan->map(function ($tindakanx, $kunci) {
            return [
                'namatindakan' => $tindakanx->keterangan,
                'subtotal' => $tindakanx->subtotal,
            ];
        });
        //    $visite = DetailbillingbynoregController::visite($noreg);
        $laborat = DetailbillingbynoregController::laborat($noreg);
        $radiologi = DetailbillingbynoregController::radiologi($noreg);
        $onedaycare = DetailbillingbynoregController::onedaycare($noreg);
        $fisioterapi = DetailbillingbynoregController::fisioterapi($noreg);
        $hd = DetailbillingbynoregController::hd($noreg);
        $penunjanglain = DetailbillingbynoregController::penunjanglain($noreg);
        // $penunjanglainrinci = $penunjanglain->map(function ($penunjanglainx, $kunci) {
        //     return [
        //         'namatindakan' => $penunjanglainx->keterangan,
        //         'subtotal' => $penunjanglainx->subtotal,
        //     ];
        // });
        $psikologi = DetailbillingbynoregController::psikologi($noreg);
        $cardio = DetailbillingbynoregController::cardio($noreg);
        $eeg = DetailbillingbynoregController::eeg($noreg);
        $endoscopy = DetailbillingbynoregController::endoscopy($noreg);
        $obat = DetailbillingbynoregController::farmasi($noreg);
        $farmasi = DetailbillingbynoregController::farmasinew($noreg);

        $pelayananrm = (int) isset($pelayananrm[0]->subtotal) ? $pelayananrm[0]->subtotal : 0;
        $kartuidentitas = (int) isset($kartuidentitas[0]->subtotal) ? $kartuidentitas[0]->subtotal : 0;
        $konsulantarpoli = (int) isset($konsulantarpoli[0]->subtotal) ? $konsulantarpoli[0]->subtotal : 0;
        $poliklinik = (int) isset($poliklinik[0]->subtotal) ? $poliklinik[0]->subtotal : 0;
        $tindakanx = (int) $tindakan->sum('subtotal');

        $totalall =  $pelayananrm + $kartuidentitas + $konsulantarpoli + $poliklinik + $tindakanx + $laborat + $radiologi + $onedaycare
            + $fisioterapi + $hd + $penunjanglain
            + $psikologi + $cardio + $eeg + $endoscopy + $obat + $farmasi;
        return
            [
                'heder' => $query,
                'pelayananrm' => $pelayananrm,
                'kartuidentitas' => $kartuidentitas,
                'poliklinik' => $poliklinik,
                'konsulantarpoli' => isset($konsulantarpoli) ? $konsulantarpoli : 0,
                'tindakan' => isset($tindakanrinci) ?  $tindakanrinci : '',
                //        'visite' => isset($visite) ?  $visite : 0,
                'laborat' => isset($laborat) ?  $laborat : 0,
                'radiologi' => isset($radiologi) ?  $radiologi : 0,
                'onedaycare' => isset($onedaycare) ?  $onedaycare : 0,
                'fisioterapi' => isset($fisioterapi) ?  $fisioterapi : 0,
                'hd' => isset($hd) ?  $hd : 0,
                // 'penunjanglain' => isset($penunjanglain) ?  $penunjanglain : 0,
                'penunjanglain' => $penunjanglain,
                'psikologi' => isset($psikologi) ?  $psikologi : 0,
                'cardio' => isset($cardio) ?  $cardio : 0,
                'eeg' => isset($eeg) ?  $eeg : 0,
                'endoscopy' => isset($endoscopy) ?  $endoscopy : 0,
                'obat' => isset($obat) ?  $obat : 0,
                'farmasinew' => isset($farmasi) ?  $farmasi : 0,
                'totalall' => isset($totalall) ?  $totalall : 0,
            ];
    }

    public function billbynoregigd()
    {
        $noreg = request('noreg');

        $adminigd = DetailbillingbynoregIgdController::adminigd($noreg);
        $tindakan = DetailbillingbynoregIgdController::tindakan($noreg);
        $tindakanrinci = $tindakan->map(function ($tindakanx, $kunci) {
            return [
                'namatindakan' => $tindakanx->keterangan,
                'subtotal' => $tindakanx->subtotal,
            ];
        });
        $fisioterapi = DetailbillingbynoregIgdController::fisioterapi($noreg);
        $hd = DetailbillingbynoregIgdController::hd($noreg);
        $penunjanglain = DetailbillingbynoregIgdController::penunjanglain($noreg);
        $cardio = DetailbillingbynoregIgdController::cardio($noreg);
        $eeg = DetailbillingbynoregIgdController::eeg($noreg);
        $endoscopy = DetailbillingbynoregIgdController::endoscopy($noreg);
        $bdrs = DetailbillingbynoregIgdController::bdrs($noreg);
        $okigd = DetailbillingbynoregIgdController::okigd($noreg);
        $okranap = DetailbillingbynoregIgdController::okranap($noreg);
        $tindakanokranap = DetailbillingbynoregIgdController::tindakanokranap($noreg);
        $perawatanjenasah = DetailbillingbynoregIgdController::perawatanjenasah($noreg);
        $laborat = DetailbillingbynoregIgdController::laborat($noreg);
        $radiologi = DetailbillingbynoregIgdController::radiologi($noreg);
        $tindakanokigd = DetailbillingbynoregIgdController::tindakanokigd($noreg);
        $ambulan = DetailbillingbynoregIgdController::ambulan($noreg);
        $farmasi = DetailbillingbynoregIgdController::farmasi($noreg);
        $biayamatrei = DetailbillingbynoregIgdController::biayamatrei($noreg);
        $eresep = DetailbillingbynoregIgdController::eresep($noreg);
        $tindakanx = (int) $tindakan->sum('subtotal');
        $biayamatreix = (int) $biayamatrei->sum('subtotal');

        $totalall = $adminigd + $tindakanx + $laborat + $radiologi + $fisioterapi + $hd + $penunjanglain + $cardio + $eeg + $endoscopy + $bdrs + $okigd +  $tindakanokigd
                    + $okranap + $tindakanokranap + $perawatanjenasah + $ambulan + $farmasi + $biayamatreix + $eresep;
        return new JsonResponse(
            [
                'heder' => $noreg,
                'adminigd' => $adminigd,
                'tindakan' => isset($tindakanrinci) ?  $tindakanrinci : '',
                'laborat' => isset($laborat) ?  $laborat : 0,
                'radiologi' => isset($radiologi) ?  $radiologi : 0,
                'fisioterapi' => isset($fisioterapi) ?  $fisioterapi : 0,
                'hd' => isset($hd) ?  $hd : 0,
                'penunjanglain' => $penunjanglain,
                'cardio' => isset($cardio) ?  $cardio : 0,
                'eeg' => isset($eeg) ?  $eeg : 0,
                'endoscopy' => isset($endoscopy) ?  $endoscopy : 0,
                'bdrs' => isset($bdrs) ?  $bdrs : 0,
                'okigd' => isset($okigd) ?  $okigd : 0,
                'tindakanokigd' => isset($tindakanokigd) ?  $tindakanokigd : 0,
                'okranap' => isset($okranap) ?  $okranap : 0,
                'tindakanokranap' => isset($tindakanokranap) ?  $tindakanokranap : 0,
                'perawatanjenasah' => isset($perawatanjenasah) ?  $perawatanjenasah : 0,
                'ambulan' => isset($ambulan) ?  $ambulan : 0,
                'farmasi' => isset($farmasi) ?  $farmasi : 0,
                'eresep' => isset($eresep) ?  $eresep : 0,
                'biayamatrei' => isset($biayamatreix) ?  $biayamatreix : 0,
                'totalall' => isset($totalall) ?  $totalall : 0,
            ]);
    }


}
