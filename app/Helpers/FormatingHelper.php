<?php

namespace App\Helpers;

use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Master\Mpoli;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class FormatingHelper
{
    public static function gennoreg($n, $kode)
    {
        $has = null;
        $lbr = strlen($n);
        for ($i = 1; $i <= 5 - $lbr; $i++) {
            $has = $has . "0";
        }
        return $has . $n . "/" . date("m") . "/" . date("Y") . "/" . $kode;
    }

    public static function getKarcisPoli($poli, $kartu)
    {
        $data = Mpoli::select('rs3')->where('rs1', '=', $poli)->orderBY('rs1')->first();
        if ($data == '') {
            return new JsonResponse('data tidak ada');
        }
        $flag = $data->rs3;
        // $data2 = DB::table('rs30z')->select('rs2', 'rs8', 'rs9')->where('rs3', '=', 'RM#')->first();
        // $nama_biaya_rm = $data2 ? $data2->rs2 : '';
        // $kode_biaya_rm = "RM";
        // $biaya_rm1 = $data2 ? $data2->rs8 : '0';
        // $biaya_rm2 = $data2 ? $data2->rs9 : '0';

        $nama_biaya = null;
        $kode_biaya = null;
        $data3 = DB::table('rs30z')->select('rs2', 'rs8', 'rs9')->where('rs3', '=', 'K2#')
            ->where('rs4', 'LIKE', '%' . $flag . '%')->get();
        $nama_biaya =  $data3[0]->rs2;
        $kode_biaya =  "K2#";
        // $nama_biaya = $nama_biaya . "#" . $data3[0]->rs2;
        // $kode_biaya = $kode_biaya . "#" . "K2";
        $biaya_karcis1 = $data3[0]->rs8;
        $biaya_karcis2 = $data3[0]->rs9;

        // $nama_biaya_tidak_lama = null;
        // $kode_biaya_tidak_lama = null;
        // $data4 = DB::table('rs30z')->select('rs2', 'rs8', 'rs9')->where('rs3', '=', 'K1#')
        //     ->where('rs4', '=', 'RJ')->get();
        // $nama_biaya_tidak_lama = $nama_biaya_tidak_lama . "#" . $data4[0]->rs2;
        // $kode_biaya_tidak_lama = $kode_biaya_tidak_lama . "#" . "K1";
        // $biaya_kartu1 = $data4[0]->rs8;
        // $biaya_kartu2 = $data4[0]->rs9;

        // if ($kartu != 'Lama') {
        //     // $nama_biaya = $nama_biaya_rm . '' . $nama_biaya_lama . '' . $nama_biaya_tidak_lama;
        //     // $kode_biaya = $kode_biaya_rm . '' . $kode_biaya_lama . '' . $kode_biaya_tidak_lama;
        //     $nama_biaya =  $nama_biaya_lama . '' . $nama_biaya_tidak_lama;
        //     $kode_biaya =  $kode_biaya_lama . '' . $kode_biaya_tidak_lama;
        //     $biaya_kartu1 = 0;
        //     $biaya_kartu2 = 0;
        // } else {
        // $nama_biaya = $nama_biaya_rm . '' . $nama_biaya_lama;
        // $kode_biaya = $kode_biaya_rm . '' . $kode_biaya_lama;
        // $nama_biaya =  $nama_biaya_lama;
        // $kode_biaya =  $kode_biaya_lama;
        // $biaya_kartu1 = 0;
        // $biaya_kartu2 = 0;
        // }

        // if ($biaya_rm1 > 0) {
        //     $sarana = 0;
        //     $pelayanan = 0;
        //     $sarana = $sarana . $biaya_rm1;
        //     $pelayanan = $pelayanan . $biaya_rm2;
        // }

        if ($biaya_karcis1 > 0) {
            $sarana = 0;
            $pelayanan = 0;
            $sarana =  $biaya_karcis2;
            $pelayanan =  $biaya_karcis1;
            // $sarana = $sarana ?? 0 . "#" . $biaya_karcis2;
            // $pelayanan = $pelayanan ?? 0 . "#" . $biaya_karcis1;
        }

        // if ($biaya_kartu1 > 0) {
        //     $sarana = $sarana ?? 0 . "#" . $biaya_kartu1;
        //     $pelayanan = $pelayanan ?? 0 . "#" . $biaya_kartu2;
        // }


        // $tarif = $biaya_rm1 + $biaya_rm2 + $biaya_karcis1 + $biaya_karcis2 + $biaya_kartu1 + $biaya_kartu2;
        $tarif =  $biaya_karcis1 + $biaya_karcis2;

        return [
            'nama_biaya' => $nama_biaya,
            'kode_biaya' => $kode_biaya,
            'sarana' => $sarana,
            'pelayanan' => $pelayanan,
            'tarif' => $tarif,
        ];
    }

    public static function mobat($n, $kode)
    {
        $has = null;
        $lbr = strlen($n);
        for ($i = 1; $i <= 7 - $lbr; $i++) {
            $has = $has . "0";
        }
        return $has . $n . "-" . $kode;
    }

    public static function norencanabeliobat($n, $kode)
    {
        $has = null;
        $lbr = strlen($n);
        for ($i = 1; $i <= 9 - $lbr; $i++) {
            $has = $has . "0";
        }
        return $has . $n . "" . date("m") . "" . date("Y") . "/" . $kode;
    }

    public static function pemesananobat($n, $kode)
    {
        $has = null;
        $lbr = strlen($n);
        for ($i = 1; $i <= 9 - $lbr; $i++) {
            $has = $has . "0";
        }
        return $has . $n . "-" . date("m") . "-" . date("Y") . "/" . $kode;
    }

    public static function penerimaanobat($n, $kode)
    {
        $has = null;
        $lbr = strlen($n);
        for ($i = 1; $i <= 9 - $lbr; $i++) {
            $has = $has . "0";
        }
        return $has . $n . "/" . date("m") . "/" . date("Y") . "/" . $kode;
    }

    public static function notatindakan($n, $kode)
    {
        $has = null;
        $lbr = strlen($n);
        for ($i = 1; $i <= 4 - $lbr; $i++) {
            $has = $has . "0";
        }
        return date("y") . date("m") . date("d") . "/" . $has . $n . $kode;
    }

    public static function permintaandepo($n, $kode)
    {
        $has = null;
        $lbr = strlen($n);
        for ($i = 1; $i <= 9 - $lbr; $i++) {
            $has = $has . "0";
        }
        return date("y") . date("m") . date("d") . "/" . $has . $n . $kode;
    }

    public static function formatallpermintaan($n, $kode)
    {
        $has = null;
        $lbr = strlen($n);
        for ($i = 1; $i <= 4 - $lbr; $i++) {
            $has = $has . "0";
        }
        return date("y") . date("m") . date("d") . "/" . $has . $n . $kode;
    }

    public static function karcisrj($n, $kode)
    {
        $has = null;
        $lbr = strlen($n);
        for ($i = 1; $i <= 7 - $lbr; $i++) {
            $has = $has . "0";
        }
        return $kode . date("Y") . "-" . $has . $n;
    }

    public static function session_user()
    {
        $user = Pegawai::find(auth()->user()->pegawai_id);
        $kdpegsimrs = $user->kdpegsimrs;
        $kdruang = $user->kdruangansim;
        $kdgroupnakes = $user->kdgroupnakes;
        $kddpjp = $user->kddpjp;
        $kode_ruang = $user->kode_ruang;
        $kode_ruang_arsip = $user->kdarteri;
        return (
            [
                'kodesimrs' => $kdpegsimrs,
                'kdruang' => $kdruang,
                'kdgroupnakes' => $kdgroupnakes,
                'kddpjp' => $kddpjp,
                'kode_ruang' => $kode_ruang,
                'kode_ruang_arsip' => $kode_ruang_arsip
            ]);
    }

    public static function session_ruangan()
    {
        $user = Pegawai::find(auth()->user()->pegawai_id);
        $kdruang = $user->kdruangansim;
        return $kdruang;
    }

    public static function resep($n, $kode)
    {
        $has = null;
        $lbr = strlen($n);
        for ($i = 1; $i <= 5 - $lbr; $i++) {
            $has = $has . "0";
        }
        return $has . $n . "-" . date("d") . "" . date("m") . "" . date("Y") . "-" . $kode;
    }
    public static function pengembalian($n)
    {
        $has = null;
        $lbr = strlen($n);
        for ($i = 1; $i <= 5 - $lbr; $i++) {
            $has = $has . "0";
        }
        return $has . $n . "/" . date("d") . "" . date("m") . "" . date("Y") . "/PP";
    }

    public static function antrian($n, $kode)
    {
        $has = null;
        $lbr = strlen($n);
        for ($i = 1; $i <= 4 - $lbr; $i++) {
            $has = $has . "0";
        }
        return $kode . $has . $n;
    }

    public static function nopemakaianruangan($n, $kode)
    {
        $has = null;
        $lbr = strlen($n);
        for ($i = 1; $i <= 9 - $lbr; $i++) {
            $has = $has . "0";
        }
        return date("y") . date("m") . date("d") . "/" . $has . $n . $kode;
    }

    public static function nostp($n, $kode)
    {
        $has = null;
        $lbr = strlen($n);
        $rom = array('','I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII');
        for ($i = 1; $i <= 6 - $lbr; $i++) {
            $has = $has . "0";
        }
        return $has . $n  . '/' . $kode . '/' . $rom[date('n')] . '/' . date('Y');
    }

    public static function nonotadinas($n, $kode)
    {
        $has = null;
        $lbr = strlen($n);
        $rom = array('','I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII');
        for ($i = 1; $i <= 6 - $lbr; $i++) {
            $has = $has . "0";
        }
        return $has . $n  .'/' . $rom[date('n')] . '/' . $kode .  '/' . date('Y');
    }

    public static function noarsip($n, $kode, $tahun)
    {
        $has = null;
        $lbr = strlen($n);
        for ($i = 1; $i <= 7 - $lbr; $i++) {
            $has = $has . "0";
        }
        return $has . $n . "-" . $tahun . "-" . $kode;
    }
}
