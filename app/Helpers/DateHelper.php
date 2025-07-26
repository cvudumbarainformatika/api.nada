<?php

namespace App\Helpers;

use Carbon\Carbon;

class DateHelper
{
    public static function getDate()
    {
        $dt = new Carbon();
        return $dt->toDateString();
    }

    public static function getDateTime()
    {
        $dt = new Carbon();
        return $dt->toDateTimeString();
    }

    public static function convertToDateTimeString($tanggal)
    {
        $dt = Carbon::createFromTimestamp($tanggal);
        return $dt->toDateTimeString();
    }

    public static function getSelisihTahunByDate($tglAwal, $tglAkhir)
    {
        $awal = strtotime($tglAwal);
        $akhir = strtotime($tglAkhir);
        $diff = abs($akhir - $awal);
        return floor($diff / (365 * 60 * 60 * 24));
    }

    public static function getSelisihHariByDate($tglAwal, $tglAkhir)
    {
        $awal = strtotime($tglAwal);
        $akhir = strtotime($tglAkhir);
        $diff = $akhir - $awal;
        return floor($diff / (3600 * 24));
    }

    public static function selisihHari($tglAwal, $tglAkhir)
    {

        $tgl1 = Carbon::parse($tglAwal);
        $tgl2 = Carbon::parse($tglAkhir);

        $selisih = $tgl1->diffInDays($tgl2);
        return $selisih;
    }

    public static function usia($dateOfBirth)
    {
        if ($dateOfBirth === '' || $dateOfBirth === null) {
            return 0;
        }
        $years = Carbon::parse($dateOfBirth)->age;

        return $years;
    }
}
