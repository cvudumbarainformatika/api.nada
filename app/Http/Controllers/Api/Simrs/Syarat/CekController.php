<?php

namespace App\Http\Controllers\Api\Simrs\Syarat;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mpasien;
use App\Models\Simrs\Planing\Planing_Igd_Lama;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CekController extends Controller
{
    public static function ceknoktp($norm)
    {
        $cekidentitas = Mpasien::where('rs1', $norm)->first();
        if($cekidentitas->rs49 === '' || $cekidentitas->rs49 === null){
            //return new JsonResponse(['message' => 'Maaf Identitas Pasien Belum Lengkap, Hubungi Pendaftaran Pasien Untuk Melengkapi Identias Pasien...!!!'], 500);
            return "1";
        }else{
            return "2";
        }
    }

    public static function cekplan($noreg)
    {
        $cek = Planing_Igd_Lama::where('rs1', $noreg)->count();
        if($cek === 0){
            return "1";
        }else{
            return "2";
        }
    }
}
