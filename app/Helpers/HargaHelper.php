<?php

namespace App\Helpers;

use App\Models\Simrs\Penunjang\Farmasinew\Harga\DaftarHarga;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use Illuminate\Http\JsonResponse;

class HargaHelper
{
    public static function getHarga($kdobat, $sistembayar)
    {
        // cek obat program
        $obatProgram = Mobatnew::where('kd_obat', $kdobat)
            ->where(function ($query) {
                $query->where('obat_program', '1')->orWhere('obat_donasi', '1');
            })
            ->first();
        if ($obatProgram) {
            return [
                'res' => false,
                'hargaJual' => 0,
                'harga' => 0
            ];
        }
        $data = DaftarHarga::selectRaw('max(harga) as harga')
            ->where('kd_obat', $kdobat)
            ->orderBy('tgl_mulai_berlaku', 'desc')
            ->limit(5)
            ->first();
        $harga = $data->harga;
        if (!$harga) {
            return [
                'res' => true,
                'message' => 'Tidak ada harga untuk obat ini, dan ini bukan obat Program atau Donasi',
                'data' => $data,
                'kdobat' => $kdobat,
                'sistembayar' => $sistembayar,
            ];
        }
        if ($sistembayar == 1 || $sistembayar == '1') {
            if ($harga <= 50000) {
                $hargajualx = (int) $harga + (int) $harga * (int) 28 / (int) 100;
            } elseif ($harga > 50000 && $harga <= 250000) {
                $hargajualx = (int) $harga + ((int) $harga * (int) 26 / (int) 100);
            } elseif ($harga > 250000 && $harga <= 500000) {
                $hargajualx = (int) $harga + (int) $harga * (int) 21 / (int) 100;
            } elseif ($harga > 500000 && $harga <= 1000000) {
                $hargajualx = (int) $harga + (int) $harga * (int) 16 / (int)100;
            } elseif ($harga > 1000000 && $harga <= 5000000) {
                $hargajualx = (int) $harga + (int) $harga * (int) 11 /  (int)100;
            } elseif ($harga > 5000000 && $harga <= 10000000) {
                $hargajualx = (int) $harga + (int) $harga * (int) 9 / (int) 100;
            } elseif ($harga > 10000000) {
                $hargajualx = (int) $harga + (int) $harga * (int) 7 / (int) 100;
            }
        } else if ($sistembayar == 2 || $sistembayar == '2') {
            $hargajualx = (int) $harga + (int) $harga * (int) 25 / (int)100;
        } else {
            $hargajualx = (int) $harga + (int) $harga * (int) 30 / (int)100;
        }
        return [
            'res' => false,
            'hargaJual' => $hargajualx,
            'harga' => $harga
        ];
    }
}
