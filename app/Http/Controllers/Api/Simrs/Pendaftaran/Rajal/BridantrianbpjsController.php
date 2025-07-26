<?php

namespace App\Http\Controllers\Api\Simrs\Pendaftaran\Rajal;

use App\Events\AntreanEvent;
use App\Helpers\BridgingbpjsHelper;
use App\Helpers\DateHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Bpjs\BpjsAntrian;
use App\Models\Simrs\Pendaftaran\Rajalumum\Antrianlog;
use App\Models\Simrs\Pendaftaran\Rajalumum\Bpjs_http_respon;
use App\Models\Simrs\Pendaftaran\Rajalumum\Bpjsrespontime;
use App\Models\Simrs\Pendaftaran\Rajalumum\Logantrian;
use App\Models\Simrs\Pendaftaran\Rajalumum\Seprajal;
use App\Models\Simrs\Pendaftaran\Rajalumum\Unitantrianbpjs;
use App\Models\Simrs\Rajal\KunjunganPoli;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BridantrianbpjsController extends Controller
{
    public static function addantriantobpjs($noreg, $request)
    {
        if ($request->jkn === 'JKN') {
            $jenispasien = "JKN";
        } else {
            $jenispasien = "Non JKN";
        }

        $tgl = Carbon::now()->format('Y-m-d 00:00:00');
        $tglx = Carbon::now()->format('Y-m-d 23:59:59');
        $jmlkunjunganpoli = KunjunganPoli::where('rs1', $noreg)->count();
        $unit_antrian = Unitantrianbpjs::select('kuotajkn', 'kuotanonjkn')
            ->where('pelayanan_id', '=', $request->kodepoli)->get();
        $kuotajkn = $unit_antrian[0]->kuotajkn;
        $kuotanonjkn = $unit_antrian[0]->kuotanonjkn;

        $sisakuotajkn = (int)$kuotajkn - $jmlkunjunganpoli;
        $sisakuotanonjkn = (int)$kuotanonjkn - $jmlkunjunganpoli;

        $date = Carbon::parse($request->tglsep);
        $dt = $date->addMinutes(10);
        $estimasidilayani = $dt->getPreciseTimestamp(3);

        $pasienbaru = $request->barulama == 'lama' ? 1 : 0;

        $referensi = ($request->nosuratkontrol === null || $request->nosuratkontrol === '') ?  $request->norujukan : $request->nosuratkontrol;

        $data =
            [
                "kodebooking" => $noreg,
                "jenispasien" => $jenispasien,
                "nomorkartu" => $request->noka,
                "nik" => $request->nik,
                "nohp" => $request->noteleponhp,
                "kodepoli" => $request->kodepolibpjs,
                "namapoli" => $request->namapolibpjs,
                "pasienbaru" => $pasienbaru,
                "norm" => $request->norm,
                "tanggalperiksa" => $request->tglsep,
                "kodedokter" => $request->dpjp,
                "namadokter" => $request->namadokter ? $request->namadokter : '',
                "jampraktek" => $request->jampraktek ? $request->jampraktek : '',
                "jeniskunjungan" => $request->id_kunjungan,
                "nomorreferensi" => $referensi,
                "nomorantrean" => $request->noantrian,
                "angkaantrean" => $request->angkaantrean,
                "estimasidilayani" => $estimasidilayani,
                "sisakuotajkn" => $sisakuotajkn,
                "kuotajkn" => $kuotajkn,
                "sisakuotanonjkn" => $sisakuotanonjkn,
                "kuotanonjkn" => $kuotanonjkn,
                "keterangan" => "Peserta harap 30 menit lebih awal guna pencatatan administrasi."
            ];
        $tgltobpjshttpres = DateHelper::getDateTime();

        $ambilantrian = BridgingbpjsHelper::post_url(
            'antrean',
            'antrean/add',
            $data
        );

        $simpanbpjshttprespon = Bpjs_http_respon::create(
            [
                'noreg' => $noreg,
                'method' => 'POST',
                'request' => $data,
                'respon' => $ambilantrian,
                'url' => '/antrean/add',
                'tgl' => $tgltobpjshttpres
            ]
        );

        if ($ambilantrian) {
            $message = [
                'kode' => $ambilantrian,
                'url' => 'antrean/add',
                'task' => 0,
                'user' => auth()->user()->id
            ];
            // event(new AntreanEvent($message));
        }
        //return $ambilantrian;
    }

    public function batalantrian($request)
    // public function batalantrian(Request $request)
    {
        $tgltobpjshttpres = DateHelper::getDateTime();
        $data = [
            "kodebooking" => $request,
            "keterangan" => "Terjadi perubahan jadwal dokter, silahkan daftar kembali",
        ];
        // $data = [
        //     "kodebooking" => $request->kodebooking,
        //     "keterangan" => $request->alasan,
        // ];
        $batalantrian = BridgingbpjsHelper::post_url(
            'antrean',
            'antrean/batal',
            $data
        );
        Bpjs_http_respon::create(
            [
                'method' => 'POST',
                'request' => $data,
                'respon' => $batalantrian,
                'url' => 'antrean/batal',
                'tgl' => $tgltobpjshttpres
            ]
        );
        return ($batalantrian);
    }

    public static function updateWaktu($input, $x)
    {
        // return $input;
        // $waktu = strtotime(date('Y-m-d H:i:s')) * 1000;
        // $now = date('Y-m-d H:i:s');
        // $date = Carbon::parse($now)->locale('id');;
        // $waktu = strtotime($date) * 1000;
        // $waktu = strtotime(Carbon::now('Asia/Jakarta')) * 1000;
        $waktu = strtotime(Carbon::parse(date('Y-m-d H:i:s'))->locale('id')) * 1000;
        $kodebooking = $input->noreg;
        $user_id = auth()->user()->pegawai_id;
        $bpjsantrian = BpjsAntrian::select('kodebooking')->where('noreg', $kodebooking);
        $wew = $bpjsantrian->count();
        if ($wew > 0) {
            $cari = $bpjsantrian->get();
            $kodebooking = $cari[0]->kodebooking;
        }

        $tgltobpjshttpres = date('Y-m-d H:i:s');

        Bpjsrespontime::create(
            [
                'kodebooking' => $kodebooking,
                'noreg' => $input->noreg,
                'taskid' => $x,
                'waktu' => $waktu,
                'created_at' =>  date('Y-m-d H:i:s'),
                'user_id' => $user_id
            ]
        );
        $data = [
            "kodebooking" => $kodebooking,
            "taskid" => $x,
            'waktu' => $waktu
        ];
        $updatewaktuantrian = BridgingbpjsHelper::post_url(
            'antrean',
            'antrean/updatewaktu',
            $data
        );
        Bpjs_http_respon::create(
            [
                'noreg' => $kodebooking,
                'method' => 'POST',
                'request' => $data,
                'respon' => $updatewaktuantrian,
                'url' => 'antrean/updatewaktu',
                'tgl' => $tgltobpjshttpres
            ]
        );
        if ($updatewaktuantrian && (int)$x < 4) {
            $message = [
                'kode' => $updatewaktuantrian,
                'url' => 'antrean/updatewaktu',
                'task' => $x,
                'user' => auth()->user()->id
            ];
            // event(new AntreanEvent($message));
        }
    }

    public static function updateMulaiWaktuTungguAdmisi($request, $input)
    {
        $taskid = 1;
        $kodebooking = $input->noreg;
        $user_id = auth()->user()->pegawai_id;
        $anu = BpjsAntrian::query();
        $bpjsantrian = $anu->select('kodebooking')->where('noreg', $kodebooking);
        $wew = $bpjsantrian->count();
        if ($wew > 0) {
            $cari = $bpjsantrian->get();
            $kodebooking = $cari[0]->kodebooking;
        }
        $tgl = date('Y-m-d');
        $antrianlog = Antrianlog::select('booking_type', 'waktu_ambil_tiket')->where('nomor', $request->noantrian)
            ->whereDate('waktu_ambil_tiket', $tgl)->get();
        //return($antrianlog);
        if (count($antrianlog) > 0) {
            $booking_type = $antrianlog[0]->booking_type;
            $waktu_ambil_tiket = $antrianlog[0]->waktu_ambil_tiket;
            if ($booking_type === 'b') {
                $logantrian = Logantrian::select('tgl')->where('noreg', $input->noreg)->whereDate('tgl', $tgl)->get();
                if (count($logantrian) > 0) {
                    $waktu_ambil_tiket = $logantrian[0]->tgl;
                }
            }
            $waktu = strtotime($waktu_ambil_tiket) * 1000;
            $tgltobpjshttpres = DateHelper::getDateTime();

            Bpjsrespontime::create(
                [
                    'kodebooking' => $kodebooking,
                    'noreg' => $input->noreg,
                    'taskid' => $taskid,
                    'waktu' => $waktu,
                    'created_at' => date('Y-m-d H:i:s'),
                    'user_id' => $user_id
                ]
            );

            $data = [
                "kodebooking" => $kodebooking,
                "taskid" => $taskid,
                'waktu' => $waktu
            ];

            $updatewaktuantrian = BridgingbpjsHelper::post_url(
                'antrean',
                'antrean/updatewaktu',
                $data
            );
            Bpjs_http_respon::create(
                [
                    'noreg' => $kodebooking,
                    'method' => 'POST',
                    'request' => $data,
                    'respon' => $updatewaktuantrian,
                    'url' => 'antrean/updatewaktu',
                    'tgl' => $tgltobpjshttpres
                ]
            );
            if ($updatewaktuantrian) {
                $message = [
                    'kode' => $updatewaktuantrian,
                    'url' => 'antrean/updatewaktu',
                    'task' => $taskid,
                    'user' => auth()->user()->id
                ];
                // event(new AntreanEvent($message));
            }
        }
        //  return($updatewaktuantrian);
    }

    public static function updateAkhirWaktuTungguAdmisi($input)
    {
        $waktu = '';
        $taskid = '2';
        $kodebooking = $input->noreg;
        $user_id = auth()->user()->pegawai_id;

        $bpjsantrian = BpjsAntrian::select('kodebooking')->where('noreg', $kodebooking);
        $wew = $bpjsantrian->count();
        if ($wew > 0) {
            $cari = $bpjsantrian->get();
            $kodebooking = $cari[0]->kodebooking;
            // return new JsonResponse($kodebooking);
        }
        $tgl = date('Y-m-d');
        $logantrian = Logantrian::select('tgl')->where('noreg', $input->noreg)->wheredate('tgl', $tgl);
        $wewwew = $logantrian->count();
        if ($wewwew > 0) {
            $cariwew = $logantrian->get();
            $waktu_ambil_tiket = $cariwew[0]->tgl;
            $waktu = strtotime($waktu_ambil_tiket) * 1000;
        }


        $tgltobpjshttpres =  date('Y-m-d H:i:s');

        Bpjsrespontime::create(
            [
                'kodebooking' => $kodebooking,
                'noreg' => $input->noreg,
                'taskid' => $taskid,
                'waktu' => $waktu,
                'created_at' =>  date('Y-m-d H:i:s'),
                'user_id' => $user_id
            ]
        );

        $data = [
            "kodebooking" => $kodebooking,
            "taskid" => $taskid,
            'waktu' => $waktu
        ];
        $updatewaktuantrian = BridgingbpjsHelper::post_url(
            'antrean',
            'antrean/updatewaktu',
            $data
        );
        Bpjs_http_respon::create(
            [
                'noreg' => $kodebooking,
                'method' => 'POST',
                'request' => $data,
                'respon' => $updatewaktuantrian,
                'url' => 'antrean/updatewaktu',
                'tgl' => $tgltobpjshttpres
            ]
        );
        if ($updatewaktuantrian) {
            $message = [
                'kode' => $updatewaktuantrian,
                'url' => 'antrean/updatewaktu',
                'task' => $taskid,
                'user' => auth()->user()->id
            ];
            // event(new AntreanEvent($message));
        }
    }
}
