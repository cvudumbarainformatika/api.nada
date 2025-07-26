<?php

namespace App\Http\Controllers\Api\Pegawai\Master;

use App\Events\newQrEvent;
use App\Http\Controllers\Api\Pegawai\Absensi\JadwalController;
use App\Http\Controllers\Controller;
use App\Models\Pegawai\Qrcode;
use App\Models\Sigarang\Pegawai;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class QrcodeController extends Controller
{
    //

    public function getQr(Request $request)
    {
        $data = Qrcode::latest()->first();
        return new JsonResponse($data, 200);
    }

    public function createQr(Request $request)
    {
        $ip = $request->id;
        $date = date('Y-m-d H:i:s');
        $nama = $ip . '#' . $date;

        $data = Qrcode::updateOrCreate([
            'ip' => $ip,
        ], [
            'code' => $nama,
            'path' => $date
        ]);
        return new JsonResponse($data, 201);
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

    public function qrScanned(Request $request)
    {
        $temp = explode('#', $request->qr);
        $data = Qrcode::where('ip', $temp[0])->first();
        if ($data->path === $temp[1]) {
            $this->updateQr($temp[0]);
            $user = JWTAuth::user();
            $jadwal = JadwalController::toMatch($user->id, $request);
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


    public function qrScanned2(Request $request)
    {
        return new JsonResponse(['message' => 'Silahkan Update Aplikasi'], 410);
        $temp = explode('#', $request->qr);
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
    public function scanWajah(Request $request)
    {
        $user = JWTAuth::user();
        $jadwal = JadwalController::toMatch2($user->id, $request);
        // $pegawai = Pegawai::find($user->pegawai_id);

        if (!$jadwal) {
            return new JsonResponse(['message' => 'Tidak Ada jadwal',], 410);
        }
        return new JsonResponse([
            'message' => 'Absen diterima',
            'user' => $user,
            'jadwal' => $jadwal,
        ], 200);
    }
}
