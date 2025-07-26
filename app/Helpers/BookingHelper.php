<?php

namespace App\Helpers;

use App\Models\Antrean\Booking;
use App\Models\Antrean\Layanan;
use App\Models\User;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;

class BookingHelper
{
    public static function kodeBooking($pasienbaru)
    {
        $random = Str::random(2);
        $date = Carbon::parse(new DateTime());
        $kodeBooking = strtoupper($random) . $date->isoFormat('DDDDHHmmss') . 'P' . $pasienbaru;
        return $kodeBooking;
    }

    public static function cari_layanan($pasienjkn, $pasienbaru, $kodepoli)
    {
        // $data = null;
        if ($pasienjkn === false) { // jika pasien umum
            // $data = Layanan::where('kode_bpjs', $kodepoli)->first(); // umum lama
            $data = Layanan::where('id_layanan', '1')->first(); //umum baru
            // if ($pasienbaru) {
            //     $data = Layanan::where('id_layanan', '1')->first(); //umum baru
            // }
            return $data;
        } else { //jika pasien jkn
            if ($pasienbaru === false) { //jika pasien lama
                $data = Layanan::where('id_layanan', $kodepoli)->first();
                if (!$data) {
                    $data = Layanan::where('id_layanan', '2')->first();
                }
                return $data;
            } else { //jika pasien baru
                $data = Layanan::where('id_layanan', '2')->first();
                return $data;
            }
        }
    }

    public static function nomor_anteran($tanggalperiksa, $id_layanan)
    {
        // $time = date('H:i:s');
        $date = Carbon::parse($tanggalperiksa);
        $hrIni = $date->toDateString();
        // $hrIni = $date->isoFormat('YYYY-MM-DD');

        $data = Booking::whereBetween('tanggalperiksa', [$hrIni . ' 00:00:00', $hrIni . ' 23:59:59'])
            ->where('layanan_id', $id_layanan)
            ->where('statuscetak', 1)
            ->count();

        return $data + 1;
    }

    public static function jumlahKuotaTerpesan($tanggalperiksa, $id_layanan)
    {
        // $time = date('H:i:s');
        // $date = Carbon::parse($tanggalperiksa);
        // $hrIni = $date->toDateString();
        // $hrIni = $date->isoFormat('YYYY-MM-DD');

        //$query = mysqli_query($koneksi, "SELECT max(kode) as kodeTerbesar FROM barang");

        // $logAntrean = Booking::select('tanggalperiksa', 'layanan_id', 'jenispasien', 'statuscetak', 'statuspanggil', 'id')
        //     ->whereBetween('tanggalperiksa', [$tanggalperiksa . ' 00:00:00', $tanggalperiksa . ' 23:59:59'])
        //     ->where('layanan_id', $id_layanan)
        //     // ->where('statuspanggil', 1)
        //     ->orderBy('id', 'DESC')
        //     ->get();

        $logAntrean = DB::connection('antrean')
            ->select("CALL getCountDataByDateAndLayananId('$id_layanan','$tanggalperiksa')");

        $collectLog = collect($logAntrean);

        $totalantrean = $collectLog->count();

        $logJkn = $collectLog->filter(function ($value, $key) {
            return $value->jenispasien === 'JKN' && $value->statuscetak === 1;
        })->count();

        $logNonJkn = $collectLog->filter(function ($value, $key) {
            return $value->jenispasien !== 'JKN' && $value->statuscetak === 1;
        })->count();




        $data = [
            'jkn' => $logJkn,
            'nonjkn' => $logNonJkn,
            'totalantrean' => $totalantrean,
            'angkaantrean' => $totalantrean + 1
        ];

        return $data;
    }
}
