<?php
namespace App\Http\Controllers\Api\Simrs\Kasir;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Kasir\Biayamaterai;
use App\Models\Simrs\Kasir\Rstigalimax;
use App\Models\Simrs\Master\Mpoli;
use App\Models\Simrs\Penunjang\Ambulan\Ambulan;
use App\Models\Simrs\Penunjang\Bdrs\Bdrstrans;
use App\Models\Simrs\Penunjang\Endoscopy\Endoscopy;
use App\Models\Simrs\Penunjang\Farmasi\Apotekranap;
use App\Models\Simrs\Penunjang\Farmasi\Apotekranaplalu;
use App\Models\Simrs\Penunjang\Farmasi\Apotekranaplaluracikanheder;
use App\Models\Simrs\Penunjang\Farmasi\Apotekranapracikanheder;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use App\Models\Simrs\Penunjang\Farmasinew\Retur\Returpenjualan_h;
use App\Models\Simrs\Penunjang\Kamarjenazah\Kamarjenasahinap;
use App\Models\Simrs\Penunjang\Kamarjenazah\Kamarjenasahtrans;
use App\Models\Simrs\Penunjang\Kamaroperasi\Kamaroperasi;
use App\Models\Simrs\Penunjang\Kamaroperasi\Kamaroperasiigd;
use App\Models\Simrs\Penunjang\Laborat\Laboratpemeriksaan;
use App\Models\Simrs\Penunjang\Radiologi\Transradiologi;
use App\Models\Simrs\Tindakan\Tindakan;
use Illuminate\Support\Facades\DB;

class DetailbillingbynoregIgdController extends Controller
{
    public static function adminigd($noreg)
    {
        $query = Rstigalimax::where('rs3', 'A2#')->where('rs1', $noreg)->get();
        $laborat = $query->sum('subtotal');
        return $laborat;
    }

    public static function tindakan($noreg)
    {
        $tindakan = Tindakan::select('rs73.rs1 as noreg', 'rs30.rs2 as keterangan', 'rs73.rs7', 'rs73.rs13', 'rs73.rs5')
            ->join('rs30', 'rs73.rs4', 'rs30.rs1')
            ->join('rs19', 'rs73.rs22', 'rs19.rs1')
            ->where('rs19.rs4', 'Poliklinik')
            ->where('rs73.rs22', 'POL014')
            ->where('rs73.rs1', $noreg)->get();
        return $tindakan;
    }

    public static function laborat($noreg)
    {
        $laboratecer = Laboratpemeriksaan::select('rs49.rs21 as wew', DB::raw('sum((rs51.rs6+rs51.rs13)*rs51.rs5) as subtotalx'))
            ->where('rs51.rs1', $noreg)
            ->join('rs49', 'rs51.rs4', 'rs49.rs1')
            ->where('rs49.rs21', '')
            ->where('rs51.rs23','POL014')
            ->where('rs51.rs18','!=','')
            ->where('rs51.lunas','!=','1');
        $laboratx = Laboratpemeriksaan::select('rs49.rs21 as wew', DB::raw('((rs51.rs6+rs51.rs13)*rs51.rs5) as subtotalx'))
            ->where('rs51.rs1', $noreg)
            ->join('rs49', 'rs51.rs4', 'rs49.rs1')
            ->where('rs49.rs21', '!=', '')
            ->where('rs51.rs23','POL014')
            ->where('rs51.rs18','!=','')
            ->where('rs51.lunas','!=','1')
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
            ->where('rs22', 'PEN005')->where('rs25','POL014')
            ->get();
        $hd = $hd->sum('subtotal');
        return $hd;
    }

    public static function penunjanglain($noreg)
    {
        $caripenunjnag = Mpoli::where('penunjang_lain', '1')->get();
        $kdpenunjnag = $caripenunjnag[0]->rs1;
        $tindakan = Tindakan::where('rs1', $noreg)->where('rs25','POL014')
            ->whereIn('rs22', [$kdpenunjnag])
            ->get();
        $penunjanglain = $tindakan->sum('subtotal');
        return $penunjanglain;
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

    public static function bdrs($noreg)
    {
        $bdrs = Bdrstrans::where('rs1', $noreg)->where('rs14', 'POL014')->get();
        $bdrs = $bdrs->sum('subtotal');
        return $bdrs;
    }

    public static function okigd($noreg)
    {
        $okigd = Kamaroperasiigd::where('rs1', $noreg)->where('rs15', 'POL014')->get();
        $okigd = $okigd->sum('biaya');
        return $okigd;
    }

    public static function tindakanokigd($noreg)
    {
        $tindakanokigd = Tindakan::where('rs1', $noreg)->where('rs22','OPERASIIRD2')->get();
        $tindakanokigd = $tindakanokigd->sum('biaya');
        return $tindakanokigd;
    }

    public static function okranap($noreg)
    {
        $okranap = Kamaroperasi::where('rs1', $noreg)->where('rs15','POL014')->get();
        $okranap = $okranap->sum('biaya');
        return $okranap;
    }

    public static function tindakanokranap($noreg)
    {
        $tindakanokranapx = Tindakan::where('rs1', $noreg)->where('rs22','OPERASI2')->get();
        $tindakanokranap = $tindakanokranapx->sum('biaya');
        return $tindakanokranap;
    }

    public static function perawatanjenasah($noreg)
    {
        $perawatanjenasahx = Kamarjenasahinap::select(DB::raw('sum(rs5 + rs6) as subtotal'))->where('rs1', $noreg)->where('rs7','POL014');
        $perawatanjenasah = Kamarjenasahtrans::select(DB::raw('sum(rs6 + rs7) as subtotal'))->where('rs1', $noreg)-> where('rs14', 'POL014')
                            ->union($perawatanjenasahx)
                            ->get();
        $perawatanjenasahtotal = $perawatanjenasah->sum('subtotal');
        return $perawatanjenasahtotal;
    }

    public static function ambulan($noreg)
    {
        $ambulan = Ambulan::where('rs1', $noreg)->where('rs20','POL014')->get();
        $ambulan = $ambulan->sum('biaya');
        return $ambulan;
    }

    public static function farmasi($noreg)
    {
        $obatsekarang = Apotekranap::where('rs1', $noreg)->where('lunas','!=','1')->where('rs20','POL014')
                        ->where(function ($query) {
                            $query->where('rs25','CENTRAL')
                                  ->orWhere('rs25','IGD');
                        })
                        ->get();

        $obatsekaranglalu = Apotekranaplalu::where('rs1', $noreg)->where('lunas','!=','1')->where('rs20','POL014')
                            ->where(function ($query) {
                                $query->where('rs25','CENTRAL')
                                    ->orWhere('rs25','IGD');
                            })
                             ->get();

        $obatracikan = Apotekranapracikanheder::select(DB::raw('sum(rs40.rs7*rs40.rs5) as subtotal'))
                       ->join('rs40','rs39.rs2','rs40.rs2')
                       ->where('rs39.rs1', $noreg)
                       ->where('rs39.lunas','!=','1')
                       ->where('rs39.rs18', 'IRD')
                       ->where(function ($query) {
                            $query->where('rs39.rs19','CENTRAL')
                                ->orWhere('rs39.rs19','IGD');
                        })
                        ->get();

        $obatracikanlalu = Apotekranaplaluracikanheder::select(DB::raw('sum(rs64.rs7*rs64.rs5) as subtotal'))
        ->join('rs64','rs63.rs2','rs64.rs2')
        ->where('rs63.rs1', $noreg)
        ->where('rs63.lunas','!=','1')
        ->where('rs63.rs18', 'IRD')
        ->where(function ($query) {
                $query->where('rs63.rs19','CENTRAL')
                    ->orWhere('rs63.rs19','IGD');
            })
        ->get();

        $hederobatracikan = Apotekranapracikanheder::select(DB::raw('sum(rs8) as subtotal'))->where('rs1', $noreg)
                            ->where('rs18', 'IRD')
                            ->get();

        $hederobatracikanlalu = Apotekranaplaluracikanheder::select(DB::raw('sum(rs8) as subtotal'))->where('rs1', $noreg)
                                ->where('rs18', 'IRD')
                                ->get();

        $obatsekarang = $obatsekarang->sum('subtotal');
        $obatsekaranglalu = $obatsekaranglalu->sum('subtotal');
        $obatracikan = $obatracikan->sum('subtotal');
        $obatracikanlalu = $obatracikanlalu->sum('subtotal');
        $hederobatracikan = $hederobatracikan->sum('subtotal');
        $hederobatracikanlalu = $hederobatracikanlalu->sum('subtotal');

        $farmasi = $obatsekarang + $obatsekaranglalu + $obatracikan + $obatracikanlalu + $hederobatracikan + $hederobatracikanlalu;

        return $farmasi;
    }

    public static function biayamatrei($noreg)
    {
        $biayamatrei = Biayamaterai::select('rs5 as subtotal')->where('rs1', $noreg)->where('rs7', 'IRD')->get();
        return $biayamatrei;
    }

    public static function eresep($noreg)
    {
        $query_nonracikan = Resepkeluarheder::select(DB::raw('round(sum((resep_keluar_r.harga_jual * resep_keluar_r.jumlah)+resep_keluar_r.nilai_r),2) as subtotal'))
                ->leftjoin('resep_keluar_r','resep_keluar_h.noresep','resep_keluar_r.noresep')
                ->where('resep_keluar_h.noreg', $noreg)
                ->get();

        $query_racikan = Resepkeluarheder::select(DB::raw('round(sum(resep_keluar_racikan_r.harga_jual * resep_keluar_racikan_r.jumlah),2) as subtotal'))
                ->leftjoin('resep_keluar_racikan_r','resep_keluar_h.noresep','resep_keluar_racikan_r.noresep')
                ->where('resep_keluar_h.noreg', $noreg)
                ->get();
        $query_racikan_r = Resepkeluarheder::select(DB::raw('round(sum(resep_keluar_racikan_r.nilai_r),2) as subtotal'))
                ->leftjoin('resep_keluar_racikan_r','resep_keluar_h.noresep','resep_keluar_racikan_r.noresep')
                ->where('resep_keluar_h.noreg', $noreg)
                ->get();

        $query_retur = Returpenjualan_h::select(DB::raw('round(sum((retur_penjualan_r.harga_jual * retur_penjualan_r.jumlah_retur)+retur_penjualan_r.nilai_r),2) as subtotal'))
                ->leftjoin('retur_penjualan_r','retur_penjualan_h.noresep','retur_penjualan_r.noresep')
                ->where('retur_penjualan_h.noreg', $noreg)
                ->get();

        $eresep = $query_nonracikan->sum('subtotal')+$query_racikan->sum('subtotal')+$query_racikan_r->sum('subtotal')-$query_retur->sum('subtotal');
        return $eresep;
    }
}
