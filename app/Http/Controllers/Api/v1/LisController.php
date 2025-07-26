<?php

namespace App\Http\Controllers\Api\v1;

use App\Events\PlaygroundEvent;
use App\Http\Controllers\Controller;
use App\Models\LaboratLuar;
use App\Models\PemeriksaanLaborat;
use App\Models\TransaksiLaborat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LisController extends Controller
{
    public function get_token(Request $request)
    {
        $xid = "4444";
        $secret_key = 'l15Test';
        date_default_timezone_set('UTC');
        // $xtimestamp = strval(time() - strtotime('1970-01-01 00:00:00'));
        $xtimestamp = strtotime($request->tanggal);
        $sign = hash_hmac('sha256', $xid . "&" . $xtimestamp, $secret_key, true);
        $xsignature = base64_encode($sign);
        return $xsignature;
    }

    public function store(Request $request)
    {
        // return response()->json($request->all(), 201);
        // $message = array(
        //     'SSO' => 'LABORAT',
        //     'menu' => $request->GLOBAL_COMMENT,
        //     '__key' => $request->ONO,
        //     'data' => $request->RESULT_LIST
        // );

        // event(new PlaygroundEvent($message));
        // return response()->json(['message' => 'success'], 201);

        try {
            $request->validate([
                'ONO' => 'required',
                'GLOBAL_COMMENT' => 'required',
                'RESULT_LIST' => 'required',
            ]);
            $temp = collect($request->RESULT_LIST)->toArray();

            if ($request->GLOBAL_COMMENT == 'laborat-luar') {
                # simpan laborat luar

                foreach ($temp as $key) {
                    // L : 13-18, P : 12-16 g/dl
                    $flag = $key['FLAG'];
                    $xtimestamp = strtotime($key['VALIDATE_ON']);
                    $sampel_selesai = date('Y-m-d', $xtimestamp);
                    $jam_sampel_selesai = date('H:i:s', $xtimestamp);

                    LaboratLuar::where(['nota' => $request->ONO, 'kd_lab' => $key['ORDER_TESTID']])->update([
                        'hl' => $key['FLAG'],
                        'hasil' => $key['RESULT_VALUE'],
                        'metode' => $key['METODE'],
                        'sampel_selesai' => $sampel_selesai,
                        'jam_sampel_selesai' => $jam_sampel_selesai,
                        'tat' => $request->TAT,
                        'akhirx' => '1' // complete
                    ]);

                    PemeriksaanLaborat::where(['rs1' => $key['ORDER_TESTID']])
                        ->update([
                            'nilainormal' => $key['REF_RANGE'],
                            'satuan' => $key['UNIT'],
                            'loinc'=> $key['LOINC_OBV'] ?? '',
                            'display_loinc'=> $key['LOINC_NM'] ?? '',
                            'loinc_paket'=> $key['LOINC_REQ'] ?? '',
                            'display_loinc_paket'=> $key['LOINC_REQNM'] ?? '',
                        ]);
                }
            } else {
                // $temp = collect($request->RESULT_LIST)->toArray();
                foreach ($temp as $key) {
                    $flag = $key['FLAG'];
                    $xtimestamp = strtotime($key['VALIDATE_ON']);
                    $sampel_selesai = date('Y-m-d H:i:s', $xtimestamp);
                    // $jam_sampel_selesai = date('H:i:s', $xtimestamp);

                    TransaksiLaborat::where(['rs2' => $request->ONO, 'rs4' => $key['ORDER_TESTID']])->update([
                        'rs27' => $key['FLAG'],
                        'rs21' => $key['RESULT_VALUE'],
                        'metode' => $key['METODE'],
                        'rs29' => $sampel_selesai,
                        'tat' => $request->TAT,
                        'rs26' => '1' // complete
                    ]);

                    PemeriksaanLaborat::where(['rs1' => $key['ORDER_TESTID']])
                        ->update([
                            'nilainormal' => $key['REF_RANGE'],
                            'satuan' => $key['UNIT'],
                            'loinc'=> $key['LOINC_OBV'] ?? '',
                            'display_loinc'=> $key['LOINC_NM'] ?? '',
                            'loinc_paket'=> $key['LOINC_REQ'] ?? '',
                            'display_loinc_paket'=> $key['LOINC_REQNM'] ?? '',
                        ]);
                }

                $this->update_complete($request->ONO);
            }

            $message = array(
                'SSO' => 'LABORAT',
                'menu' => $request->GLOBAL_COMMENT,
                '__key' => $request->ONO,
                'data' => 'Hasil Selesai',
                'LIS' => $temp
            );

            

            event(new PlaygroundEvent($message));
            return response()->json(['message' => 'success'], 201);


            // return response()->json(['message' => 'success'], 201);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'failed', $th]);
        }
    }

    public function update_complete($nota)
    {
       // update rs20 berdasarkan nota
        TransaksiLaborat::where('rs2', $nota)->update(['rs20' => "2"]);
       
    }
}
