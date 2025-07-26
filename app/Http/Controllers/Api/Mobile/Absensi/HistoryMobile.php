<?php

namespace App\Http\Controllers\Api\Mobile\Absensi;

use App\Http\Controllers\Controller;
use App\Models\Pegawai\Alpha;
use App\Models\Pegawai\Extra;
use App\Models\Pegawai\JadwalAbsen;
use App\Models\Pegawai\Libur;
use App\Models\Pegawai\Prota;
use App\Models\Pegawai\TransaksiAbsen;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class HistoryMobile extends Controller
{
    public function data()
    {
        // yang berhak mengajukan extra adalah karyawan shift, jadi cari yang shift aja
        $user = JWTAuth::user();
        $thisYear = request('tahun') ? request('tahun') : date('Y');
        $month = request('bulan') ? request('bulan') : date('m');

        $from = $thisYear . '-' . $month . '-01';
        $to = $thisYear . '-' . $month . '-31';

        $masuk = TransaksiAbsen::where('user_id', $user->id)
            ->whereBetween('tanggal', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->with('kategory')
            ->latest()
            ->get();


        $data['masuk'] = $masuk;
        $libur = Libur::where(
            'user_id',
            $user->id
        )
            ->whereBetween('tanggal', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->latest()
            ->get();

        $data['libur'] = $libur; // ini data yang ijin

        $akun = User::with('pegawai')->find($user->id);
        $pegawai_id = null;
        $alpha = [];
        if ($akun) {
            $pegawai_id = $akun->pegawai ? $akun->pegawai->id : null;
            $alpha = Alpha::where('pegawai_id', $pegawai_id)->whereBetween('tanggal', [$from . ' 00:00:00', $to . ' 23:59:59'])->get();
        }
        $data['alpha'] = $alpha; // ini data yang punya jadwal tapi alpha ketutup jika ada ijin

        $today = date('l');
        $jadwal = JadwalAbsen::where(['user_id' => $user->id, 'day' => $today])->first();
        $jadwalhariini = $jadwal;
        $data['jadwal'] = $jadwalhariini;

        $prota = Prota::whereBetween('tgl_libur', [$from . ' 00:00:00', $to . ' 23:59:59'])->get();
        $data['prota'] = $prota;

        return new JsonResponse($data);
    }
}
