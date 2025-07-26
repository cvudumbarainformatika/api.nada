<?php

namespace App\Http\Controllers\Api\Simrs\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Kasir\Pembayaran;
use App\Models\Simrs\Master\Mpoli;
use App\Models\Simrs\Penunjang\Farmasi\Apotekrajal;
use App\Models\Simrs\Penunjang\Farmasi\Apotekrajallalu;
use App\Models\Simrs\Penunjang\Farmasi\Apotekrajalracikanheder;
use App\Models\Simrs\Penunjang\Farmasi\Apotekrajalracikanhedlalu;
use App\Models\Simrs\Penunjang\Farmasi\Apotekrajalretur;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use App\Models\Simrs\Penunjang\Kamaroperasi\Kamaroperasi;
use App\Models\Simrs\Penunjang\Laborat\Laboratpemeriksaan;
use App\Models\Simrs\Penunjang\Radiologi\Transradiologi;
use App\Models\Simrs\Psikologitrans\Psikologitrans;
use App\Models\Simrs\Tindakan\Tindakan;
use App\Models\Simrs\Visite\Visite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DetailbillingbynoregController extends Controller
{
    public static function pelayananrm($noreg)
    {
        $pelayananrm = Pembayaran::select('rs1', 'rs7', 'rs11')
            ->where('rs3', 'RM#')
            ->where('rs1', $noreg)->get();
        return $pelayananrm;
    }

    public static function kartuidentitas($noreg)
    {
        $kartuidentitas = Pembayaran::select('rs1', 'rs7', 'rs11')
            ->where('rs3', 'K1#')
            ->where('rs1', $noreg)->get();
        return $kartuidentitas;
    }

    public static function poliklinik($noreg)
    {
        $poliklinik = Pembayaran::select('rs1', 'rs7', 'rs11')
            ->where('rs3', 'K2#')
            ->where('rs1', $noreg)->get();
        return $poliklinik;
    }

    public static function konsulantarpoli($noreg)
    {
        $konsulantarpoli = Pembayaran::select('rs1', 'rs7', 'rs11')
            ->where('rs3', 'K3#')
            ->where('rs1', $noreg)->get();
        return $konsulantarpoli;
    }

    public static function tindakan($noreg)
    {
        $tindakan = Tindakan::select('rs73.rs1 as noreg', 'rs30.rs2 as keterangan', 'rs73.rs7', 'rs73.rs13', 'rs73.rs5')
            ->join('rs30', 'rs73.rs4', 'rs30.rs1')
            ->join('rs19', 'rs73.rs22', 'rs19.rs1')
            ->where('rs19.rs4', 'Poliklinik')
            ->where('rs73.rs1', $noreg)->get();
        return $tindakan;
    }

    // public static function visite($noreg)
    // {
    //     $visite = Visite::where('rs1', $noreg)->get();
    //     return $visite;
    // }

    public static function laborat($noreg)
    {
        $laboratecer = Laboratpemeriksaan::select('rs49.rs21 as wew', DB::raw('sum((rs51.rs6+rs51.rs13)*rs51.rs5) as subtotalx'))
            ->where('rs51.rs1', $noreg)
            ->join('rs49', 'rs51.rs4', 'rs49.rs1')
            ->where('rs49.rs21', '');
        $laboratx = Laboratpemeriksaan::select('rs49.rs21 as wew', DB::raw('((rs51.rs6+rs51.rs13)*rs51.rs5) as subtotalx'))
            ->where('rs51.rs1', $noreg)
            ->join('rs49', 'rs51.rs4', 'rs49.rs1')
            ->where('rs49.rs21', '!=', '')
            ->groupBy('rs49.rs21')
            ->union($laboratecer)
            ->get();
        $laborattindakan = Tindakan::where('rs1', $noreg)
            ->where('rs22', 'LAB')
            ->get();
        $laborat = $laboratx->sum('subtotalx') + $laborattindakan->sum('subtotal');
        // $laborat = $laboratx->makeHidden('subtotal')->toArray();
        return $laborat;
    }

    public static function radiologi($noreg)
    {
        $radiologix = Transradiologi::select(DB::raw('((rs6+rs8)*rs24) as subtotalx'))
            ->where('rs1', $noreg)->get();
        $radiologi = $radiologix->sum('subtotalx');
        return $radiologi;
    }

    public static function onedaycare($noreg)
    {
        $operasi = Kamaroperasi::where('rs1', $noreg)->get();
        $tindakan = Tindakan::where('rs1', $noreg)
            ->where('rs22', 'OPERASI')
            ->get();
        $onedaycare = $operasi->sum('subtotal') + $tindakan->sum('subtotal');
        return $onedaycare;
    }

    public static function fisioterapi($noreg)
    {
        $fisioterapi = Tindakan::where('rs1', $noreg)
            ->where('rs22', 'FISIO')
            ->get();
        $fisioterapi = $fisioterapi->sum('subtotal');
        return $fisioterapi;
    }

    public static function hd($noreg)
    {
        $hd = Tindakan::where('rs1', $noreg)
            ->where('rs22', 'PEN005')
            ->get();
        $hd = $hd->sum('subtotal');
        return $hd;
    }

    public static function penunjanglain($noreg)
    {
        $caripenunjnag = Mpoli::where('penunjang_lain', '1')->get();
        $kdpenunjnag = $caripenunjnag[0]->rs1;
        $tindakan = Tindakan::where('rs1', $noreg)
            ->whereIn('rs22', [$kdpenunjnag])
            ->get();
        $penunjanglain = $tindakan->sum('subtotal');
        return $penunjanglain;
    }

    public static function psikologi($noreg)
    {
        $psikologix = Psikologitrans::where('rs1', $noreg)
            ->get();
        $psikologi = $psikologix->sum('subtotal');
        return $psikologi;
    }

    public static function cardio($noreg)
    {
        $cardio = Tindakan::where('rs1', $noreg)
            ->where('rs22', 'POL026')
            ->get();
        $cardio = $cardio->sum('subtotal');
        return $cardio;
    }

    public static function eeg($noreg)
    {
        $eeg = Tindakan::where('rs1', $noreg)
            ->where('rs22', 'POL024')
            ->get();
        $eeg = $eeg->sum('subtotal');
        return $eeg;
    }

    public static function endoscopy($noreg)
    {
        $endoscopy = Tindakan::where('rs1', $noreg)
            ->where('rs22', 'POL031')
            ->get();
        $endoscopy = $endoscopy->sum('subtotal');
        return $endoscopy;
    }

    public static function farmasi($noreg)
    {
        $nonracikan = Apotekrajal::where('rs1', $noreg)->get();
        $nonracikanlalu = Apotekrajallalu::where('rs1', $noreg)->get();

        $racikan = Apotekrajalracikanheder::select(DB::raw('((rs92.rs7*rs92.rs5)+rs91.rs8) as subtotal'))
            ->join('rs92', 'rs91.rs1', 'rs92.rs1')
            ->where('rs91.rs1', $noreg)
            ->get();
        $racikanlalu = Apotekrajalracikanhedlalu::select(DB::raw('((rs164.rs7*rs164.rs5)+rs163.rs8) as subtotal'))
            ->join('rs164', 'rs163.rs1', 'rs164.rs1')
            ->where('rs164.rs1', $noreg)
            ->get();
        $retur = Apotekrajalretur::select(DB::raw('(rs88.rs3*rs88.rs4) as subtotal'))
            ->where('rs88.rs1', $noreg)
            ->get();

        $obat = $nonracikan->sum('subtotal') + $nonracikanlalu->sum('subtotal') + $racikan->sum('subtotal') + $racikanlalu->sum('subtotal') - $retur->sum('subtotal');
        return $obat;
    }

    public static function farmasinew($noreg)
    {
        $nonracikan = Resepkeluarheder::select(
        DB::raw('round((resep_keluar_r.jumlah*resep_keluar_r.harga_jual+resep_keluar_r.nilai_r)) as subtotal'))
        ->join('resep_keluar_r', 'resep_keluar_r.noresep', 'resep_keluar_h.noresep')
        ->where('resep_keluar_h.noreg', $noreg)
        ->get();

        $racikan = Resepkeluarheder::select(
        DB::raw('round((resep_keluar_racikan_r.jumlah*resep_keluar_racikan_r.harga_jual)) as subtotal'))
        ->join('resep_keluar_racikan_r', 'resep_keluar_racikan_r.noresep', 'resep_keluar_h.noresep')
        ->where('resep_keluar_h.noreg', $noreg)
        ->get();

        $racikan_R = Resepkeluarheder::select(
            DB::raw('resep_keluar_racikan_r.nilai_r as subtotal'))
            ->join('resep_keluar_racikan_r', 'resep_keluar_racikan_r.noresep', 'resep_keluar_h.noresep')
            ->where('resep_keluar_h.noreg', $noreg)
            ->groupBy('resep_keluar_h.noresep')
            ->get();

        $farmasi = $nonracikan->sum('subtotal')+$racikan->sum('subtotal')+$racikan_R->sum('subtotal');
        return $farmasi;
    }

    // public static function totalall()
    // {

    //     $totalall =
    // }
}
