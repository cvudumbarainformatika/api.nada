<?php

namespace App\Http\Controllers\Api\Mobile\Absensi;

use App\Events\newQrEvent;
use App\Http\Controllers\Api\Pegawai\Absensi\JadwalController;
use App\Http\Controllers\Controller;
use App\Models\Pegawai\Alpha;
use App\Models\Pegawai\Extra;
use App\Models\Pegawai\JadwalAbsen;
use App\Models\Pegawai\Libur;
use App\Models\Pegawai\Prota;
use App\Models\Pegawai\Qrcode;
use App\Models\Pegawai\TransaksiAbsen;
use App\Models\Sigarang\Pegawai;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class ScanQrController extends Controller
{
    public function data(Request $request)
    {
        $temp = explode('#', $request->qr);
        $validator = Validator::make($request->all(), [
            'lokasi' => 'required',
        ]);
        if ($validator->fails()) {
            $message = [
                'message' => 'Lokasi Tidak ditemukan',
                'ip' => $temp[0],
            ];
            event(new newQrEvent($message));
            return new JsonResponse([
                'message' => 'Lokasi Tidak ditemukan',
                'req' => $request->all()
            ], 406);
        }


        $data = Qrcode::where('ip', $temp[0])->first();
        if ($data->path === $temp[1]) {
            $this->updateQr($temp[0]);
            $user = JWTAuth::user();
            $jadwal = JadwalController::toMatch2($user->id, $request);
            $pegawai = Pegawai::find($user->pegawai_id);
            $dataPegawai = [
                'foto' => $pegawai->foto,
                'nip' => $pegawai->nip,
            ];
            if ($jadwal) {
                $message = [
                    'jadwal' => $jadwal,
                    'ip' => $temp[0],
                    'user' => $dataPegawai,
                ];
                event(new newQrEvent($message));
                return new JsonResponse([
                    'message' => 'Absen diterima',
                    'user' => $user,
                    'jadwal' => $jadwal,
                ], 200);
            }
            $message = [
                'message' => 'tidak ada jadwal',
                'ip' => $temp[0],
                'user' => $dataPegawai,
            ];
            event(new newQrEvent($message));
            return new JsonResponse([
                'message' => 'Tidak ada jadwal',
                'req' => $request->all()
            ], 406);
        } else {
            return new JsonResponse(['message' => 'qr Code Expired'], 410);
        }
        return new JsonResponse($data, 200);
    }

    public function updateQr($ip)
    {
        // $ip = $ip;
        $user = JWTAuth::user();
        $pegawai = Pegawai::find($user->pegawai_id);
        $dataPegawai = [
            'foto' => $pegawai->foto,
            'nip' => $pegawai->nip,
        ];
        $date = date('Y-m-d H:i:s');
        $nama = $ip . '#' . $date;

        $data = Qrcode::updateOrCreate([
            'ip' => $ip,
        ], [
            'code' => $nama,
            'path' => $date,
        ]);
        $message = [
            'data' => $data,
            'user' => $dataPegawai
        ];
        event(new newQrEvent($message));
        // return new JsonResponse($data, 201);
    }
}
