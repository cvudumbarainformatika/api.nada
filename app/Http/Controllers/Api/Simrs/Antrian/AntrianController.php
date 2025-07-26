<?php

namespace App\Http\Controllers\Api\Simrs\Antrian;

use App\Events\AntreanEvent;
use App\Events\ChatMessageEvent;
use App\Events\NotifMessageEvent;
use App\Http\Controllers\Api\Simrs\Pendaftaran\Rajal\BridantrianbpjsController;
use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Pendaftaran\Rajalumum\Antrianambil;
use App\Models\Simrs\Pendaftaran\Rajalumum\Antrianbatal;
use App\Models\Simrs\Pendaftaran\Rajalumum\Bpjsrespontime;
use App\Models\Simrs\Pendaftaran\Rajalumum\Logantrian;
use App\Models\Simrs\Pendaftaran\Rajalumum\Unitantrianbpjs;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AntrianController extends Controller
{
    public function call_layanan_ruang()
    {
        $jenis = request('jenis');
        $userid = Pegawai::find(auth()->user()->pegawai_id);
        $unitantrian = Unitantrianbpjs::where('ruang', 'TPPRJ')->where('loket_id', $userid->kode_ruang)->first();
        //  return($unitantrian->pelayanan_id);
        if ($jenis === 'call') {
            $myReq["layanan"] = $unitantrian->pelayanan_id;
            $myReq["loket"] = $unitantrian->loket_id;
            $myReq["id_ruang"] = $unitantrian->ruang_id;
            $myReq["user_id"] = "a1";

            //$myVars=json_encode($myReq);
            $url = (new Client())->post('http://192.168.160.100:2000/api/api' . '/tombolcall_layanan_ruang', [
                'form_params' => $myReq,
                'http_errors' => false
            ]);
            $query = json_decode($url->getBody()->getContents(), false);
            if ($query->status === '200') {
                $simpan = Logantrian::create([
                    'unit_antrian' => $unitantrian->loket,
                    'tgl' => date('Y-m-d H:i:s'),
                    'user_id' => $userid->id,
                    'loket' => $unitantrian->loket,
                    'nomor' => $query->data->nomor,
                    'kdunit' => $unitantrian->pelayanan_id,
                ]);
                if (!$simpan) {
                    return new JsonResponse(['message' => 'gagal'], 500);
                }

                $message = [
                    'nomorAntrian' => $query->data->nomor,
                    'loketId' => $unitantrian->loket_id
                ];
                // event(new AntreanEvent($message));
                event(new NotifMessageEvent($message, 'pendaftaran', auth()->user()));

                return ($query);
            }
            return new JsonResponse(['message' => 'gagal'], 410);
        } else if ($jenis === 'recall') {
            $myReq["layanan"] = $unitantrian->pelayanan_id;
            $myReq["loket"] = $unitantrian->loket_id;
            $myReq["id_ruang"] = $unitantrian->ruang_id;
            $myReq["user_id"] = "a1";

            $url = (new Client())->post('http://192.168.160.100:2000/api/api' . '/tombolrecall_layanan_ruang', [
                'form_params' => $myReq,
                'http_errors' => false
            ]);
            $query = json_decode($url->getBody()->getContents(), false);

            $message = [
                'nomorAntrian' => $query->data->nomor,
                'loketId' => $unitantrian->loket_id
            ];

            event(new NotifMessageEvent($message, 'pendaftaran', auth()->user()));
            return $query;
        } else if ($jenis === 'call lansia') {
            $myReq["layanan"] = 3;
            $myReq["loket"] = $unitantrian->loket_id;
            $myReq["id_ruang"] = $unitantrian->ruang_id;
            $myReq["user_id"] = "a1";

            //$myVars=json_encode($myReq);
            $url = (new Client())->post('http://192.168.160.100:2000/api/api' . '/tombolcall_layanan_ruang', [
                'form_params' => $myReq,
                'http_errors' => false
            ]);
            $query = json_decode($url->getBody()->getContents(), false);
            if ($query->status === '200') {
                $simpan = Logantrian::create([
                    'unit_antrian' => $unitantrian->loket,
                    'tgl' => date('Y-m-d H:i:s'),
                    'user_id' => $userid->id,
                    'loket' => $unitantrian->loket,
                    'nomor' => $query->data->nomor,
                    'kdunit' => $unitantrian->pelayanan_id,
                ]);
                if (!$simpan) {
                    return new JsonResponse(['message' => 'gagal'], 500);
                }
                $message = [
                    'nomorAntrian' => $query->data->nomor,
                    'loketId' => $unitantrian->loket_id
                ];
                // event(new AntreanEvent($message));                
                event(new NotifMessageEvent($message, 'pendaftaran', auth()->user()));
                
                return ($query);
            }
            return new JsonResponse(['message' => 'gagal'], 410);
        } else {
            $myReq["layanan"] = 3;
            $myReq["loket"] = $unitantrian->loket_id;
            $myReq["id_ruang"] = $unitantrian->ruang_id;
            $myReq["user_id"] = "a1";

            $url = (new Client())->post('http://192.168.160.100:2000/api/api' . '/tombolrecall_layanan_ruang', [
                'form_params' => $myReq,
                'http_errors' => false
            ]);
            $query = json_decode($url->getBody()->getContents(), false);

            $message = [
                'nomorAntrian' => $query->data->nomor,
                'loketId' => $unitantrian->loket_id
            ];
            // event(new AntreanEvent($message));                
            event(new NotifMessageEvent($message, 'pendaftaran', auth()->user()));
            return $query;
        }
    }

    public static function ambilnoantrian($request, $input)
    {
        $idUnitAntrian = '';
        $noreg = $input->noreg;
        $user_id = auth()->user()->pegawai_id;
        $tglBooking = date('Y-m-d');
        $norm = $request->norm;
        $pelayanan_id_tujuan = $request->kodepoli;
        $unitantrian = Unitantrianbpjs::select('tersedia')->where('pelayanan_id', $pelayanan_id_tujuan)->first();
        $tersedia = $unitantrian->tersedia;
        $unitgroup = '';
        if ($idUnitAntrian === '') {
            $pelayanan_id = $pelayanan_id_tujuan;
        } else {
            $sqlUnitAntrian = Unitantrianbpjs::select('pelayanan_id')->where('id', $idUnitAntrian)->first();
            $pelayanan_id = $sqlUnitAntrian->pelayanan_id;
            $unitgroup = $sqlUnitAntrian->unit_group;
        }

        $tgl = date('Y-m-d');
        $sqlCekAntrian = Antrianambil::where('noreg', $noreg)->where('pelayanan_id', $pelayanan_id)->wheredate('tgl_booking', $tgl)->get();
        if (count($sqlCekAntrian) > 0) {
            $sqlCekBatal = Antrianbatal::where('id', $sqlCekAntrian[0]->id)->count();
            if ($sqlCekBatal === 0) {
                return new JsonResponse(['message' => 'Maaf, pasien tersebut telah mengambil antrian'], 500);
            }
        }

        if ($unitgroup === 'Farmasi') {
            $bpjsrespon = Bpjsrespontime::where('noreg', $noreg)->where('taskid', '=', 5);
            if ($bpjsrespon === 0 && $tersedia) {
                return new JsonResponse(['message' => 'Maaf, akhir layanan poli tujuan pasien tersebut belum diinput, silahkan hubungi poli bersangkutan.'], 500);
            }
        }

        $myReq["layanan"] = $pelayanan_id;
        $myReq["booking_type"] = 'w';
        $myReq["id_customer"] = $norm;
        $myReq["user_id"] = "a1";
        $myReq["tgl_booking"] = $tglBooking;

        $url = (new Client())->post(
            'http://192.168.160.100:2000/api/api' . '/daftar_lokal_layanan',
            [
                'form_params' => $myReq,
                'http_errors' => false
            ]
        );
        $query = json_decode($url->getBody()->getContents(), false);
        if ($query->status === 200) {
            $simpanantrian = Antrianambil::create([
                'noreg' => $noreg,
                'norm' => $norm,
                'tgl_booking' => $tglBooking,
                'pelayanan_id' => $pelayanan_id,
                'nomor' => $query->data->nomor,
                'user_id' => $user_id
            ]);

            if ($unitgroup === 'Farmasi') {
                BridantrianbpjsController::updateWaktu($input, 6);
            }
            BridantrianbpjsController::updateWaktu($input, 3);
        }
        return $query;
    }
}
