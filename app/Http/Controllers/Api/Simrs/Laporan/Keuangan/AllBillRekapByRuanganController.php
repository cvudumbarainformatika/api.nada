<?php

namespace App\Http\Controllers\Api\Simrs\Laporan\Keuangan;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Kasir\Rstigalimax;
use App\Models\Simrs\Master\Mpoli;
use App\Models\Simrs\Master\Rstigapuluhtarif;
use App\Models\Simrs\Rajal\KunjunganPoli;
use App\Models\Simrs\Ranap\Kunjunganranap;
use App\Models\Simrs\Ranap\Mruangranap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AllBillRekapByRuanganController extends Controller
{
    public function allBillRekapByRuangan()
    {
        $dari = request('tgldari') .' 00:00:00';
        $sampai = request('tglsampai') .' 23:59:59';

        $data = Kunjunganranap::select('rs1','rs2','rs3','rs4','rs5','rs19','titipan')
        ->with(
            [
                'rstigalimax' => function ($rstigalimax) {
                    $rstigalimax->select('rs1','rs4', 'rs7', 'rs14', 'rs16','rs17')->where('rs3', 'K1#')->orderBy('rs4', 'DESC');
                },
                'akomodasikamar' => function ($akomodasikamar) {
                    $akomodasikamar->select('rs1', 'rs7', 'rs14','rs16')->where('rs3', 'K1#')->orderBy('rs4', 'DESC');
                },
                'biayamaterai' => function ($biayamaterai) {
                    $biayamaterai->select('rs1', 'rs5 as subtotal')->where('rs7', '!=', 'IRD');
                },
                'tindakandokter' => function ($tindakandokterperawat) {
                    $tindakandokterperawat->select('rs73.rs1', 'rs73.rs2', 'rs73.rs7', 'rs73.rs13', 'rs73.rs5', 'rs73.rs22')
                        ->join('rs24', 'rs24.rs4', '=', 'rs73.rs22')
                        ->join('rs21', 'rs21.rs1', '=', DB::raw('SUBSTRING_INDEX(rs73.rs8,";",1)'))
                        ->where('rs21.rs13', '1')
                        ->groupBy('rs24.rs4', 'rs73.rs2', 'rs73.rs4');
                    //->where('rs73.rs22','POL014');
                },
                'visiteumum' => function ($visiteumum) {
                    $visiteumum->select('rs1', 'rs4', 'rs5','rs8');
                },
                'tindakanperawat' => function ($tindakanperawat) {
                    $tindakanperawat->select('rs73.rs1', 'rs73.rs2', 'rs73.rs7', 'rs73.rs13', 'rs73.rs5', 'rs73.rs22')
                        ->join('rs24', 'rs24.rs4', '=', 'rs73.rs22')
                        ->join('rs21', 'rs21.rs1', '=', DB::raw('SUBSTRING_INDEX(rs73.rs8,";",1)'))
                        ->where('rs21.rs13', '!=', '1')
                        ->groupBy('rs24.rs4', 'rs73.rs2', 'rs73.rs4', 'rs73.id');
                    //->where('rs73.rs22','POL014');
                },
                'makanpasien' => function ($makanpasien) {
                    $makanpasien->select('rs1', 'rs4', 'rs5','rs8')->whereIn('rs3', ['K00003', 'K00004']);
                    //$makanpasien->select('rs1','rs4','rs5')->where('rs3','K00003')->orWhere('rs3','K00004');
                },
                'oksigen' => function ($oksigen) {
                    $oksigen->select('rs1', 'rs4', 'rs5', 'rs6','rs8');
                },
                'keperawatan' => function ($keperawatan) {
                    $keperawatan->select('rs1', 'rs4', 'rs5','rs8');
                },
                'laborat' => function ($laborat) {
                    $laborat->select('rs51.rs1', 'rs51.rs2 as nota', 'rs51.rs4 as kode', 'rs49.rs2 as pemeriksaan', 'rs49.rs21 as paket', 'rs51.rs23 as ruangan',
                        DB::raw('round((rs51.rs6+rs51.rs13)*rs51.rs5) as subtotalx'))
                        ->leftjoin('rs49', 'rs51.rs4', '=', 'rs49.rs1')
                        ->where('rs51.rs23','!=','POL014')->where('rs49.rs21','!=','')
                        ->groupBy( 'rs51.rs2', 'rs49.rs21');
                },
                'laboratnonpaket' => function ($laborat) {
                    $laborat->select('rs51.rs1', 'rs51.rs2 as nota', 'rs51.rs4 as kode', 'rs49.rs2 as pemeriksaan', 'rs49.rs21 as paket', 'rs51.rs23 as ruangan',
                        DB::raw('round((rs51.rs6+rs51.rs13)*rs51.rs5) as subtotalx'))
                        ->leftjoin('rs49', 'rs51.rs4', '=', 'rs49.rs1')
                        ->where('rs51.rs23','!=','POL014')->where('rs49.rs21','=','')
                        //->groupBy( 'rs51.rs2')
                        ;
                },
                // 'laborat.pemeriksaanlab:rs1,rs2,rs21',
                'transradiologi' => function ($transradiologi) {
                    $transradiologi->select('rs48.rs1', 'rs48.rs6', 'rs48.rs8', 'rs48.rs24','rs48.rs26','rs24.rs5')
                        ->join('rs24', 'rs24.rs4', '=', 'rs48.rs26')
                        ->where('rs48.rs26','!=','POL014')
                        ->groupBy('rs24.rs4', 'rs48.rs2', 'rs48.rs4');
                },
                'tindakanendoscopy' => function ($tindakanendoscopy) {
                    $tindakanendoscopy->select('rs73.rs1', 'rs73.rs2', 'rs73.rs7', 'rs73.rs13', 'rs73.rs5','rs73.rs16','rs24.rs4')
                    ->join('rs24','rs24.rs1','rs73.rs16')
                    ->where('rs73.rs22', 'POL031')
                    ->where('rs73.rs16','!=','POL014');
                },
                'kamaroperasiibs' => function ($kamaroperasiibs) {
                    $kamaroperasiibs->select('rs54.rs1', 'rs54.rs5', 'rs54.rs6', 'rs54.rs7', 'rs54.rs8','rs54.rs15')
                        ->join('rs24', 'rs24.rs4', '=', 'rs54.rs15')
                        ->where('rs54.rs15','!=', 'POL014')
                        ->groupBy('rs54.rs2', 'rs54.rs4');;
                },
                // 'kamaroperasiigd' => function ($kamaroperasiigd) {
                //     $kamaroperasiigd->select('rs226.rs1', 'rs226.rs5', 'rs226.rs6', 'rs226.rs7', 'rs226.rs8','rs226.rs15')
                //         ->join('rs24', 'rs24.rs4', '=', 'rs226.rs15')
                //         ->where('rs226.rs15','!=', 'POL014')
                //         ->groupBy('rs226.rs2', 'rs226.rs4');
                // },
                'tindakanoperasi' => function ($tindakanoperasi) {
                    $tindakanoperasi->select('rs73.rs1', 'rs73.rs2', 'rs73.rs7', 'rs73.rs13', 'rs73.rs5', 'rs73.rs16','rs200.rs13 as ruang')
                    ->join('rs200','rs73.rs1','rs200.rs1')
                    ->where('rs73.rs22', 'OPERASI')
                   // ->where('rs73.rs16','!=','POL014')
                   ;
                },
                'tindakanoperasiigd' => function ($tindakanoperasiigd) {
                    $tindakanoperasiigd->select('rs73.rs1', 'rs73.rs2', 'rs73.rs7', 'rs73.rs13', 'rs73.rs5','rs24.rs4')
                    ->join('rs24','rs24.rs1','rs73.rs16')
                    ->where('rs73.rs22', 'OPERASIIRD')
                    ->where('rs73.rs16','!=','POL014');
                },
                'tindakanfisioterapi' => function ($tindakanfisioterapi) {
                    $tindakanfisioterapi->select('rs73.rs1', 'rs73.rs2', 'rs73.rs7', 'rs73.rs13', 'rs73.rs5','rs24.rs4')
                    ->join('rs24','rs24.rs1','rs73.rs16')
                    ->where('rs73.rs22', 'FISIO')->OrWhere('rs22', 'PEN005')
                    ->where('rs73.rs16','!=','POL014');
                },
                // 'tindakanfisioterapi' => function ($tindakanfisioterapi) {
                //     $tindakanfisioterapi->select('rs73.rs1', 'rs73.rs2', 'rs73.rs7', 'rs73.rs13', 'rs73.rs5','rs24.rs4')
                //     ->join('rs24','rs24.rs1','rs73.rs16')
                //     ->where('rs22', 'PEN005')
                //     ->where('rs73.rs16','!=','POL014');
                // },
                'tindakananastesidiluarokdanicu' => function ($tindakananastesidiluarokdanicu) {
                    $tindakananastesidiluarokdanicu->select('rs73.rs1', 'rs73.rs2', 'rs73.rs7', 'rs73.rs13', 'rs73.rs5','rs24.rs4')
                    ->join('rs24','rs24.rs1','rs73.rs16')
                    ->where('rs22', 'PEN012')
                    ->where('rs25', '!=', 'POL014');
                },
                'tindakancardio' => function ($tindakancardio) {
                    $tindakancardio->select('rs73.rs1', 'rs73.rs2', 'rs73.rs7', 'rs73.rs13', 'rs73.rs5','rs24.rs4')
                    ->join('rs24','rs24.rs1','rs73.rs16')
                    ->where('rs22', 'POL026')
                    ->where('rs25', '!=', 'POL014');
                },
                'tindakaneeg' => function ($tindakaneeg) {
                    $tindakaneeg->select('rs73.rs1', 'rs73.rs2', 'rs73.rs7', 'rs73.rs13', 'rs73.rs5','rs24.rs4')
                    ->join('rs24','rs24.rs1','rs73.rs16')
                    ->where('rs22', 'POL024')
                    ->where('rs25', '!=', 'POL014');
                },
                 'psikologtransumum' => function ($psikologtransumum) {
                    $psikologtransumum->select('psikologi_trans.rs1','psikologi_trans.rs2','psikologi_trans.rs3','psikologi_trans.rs4 as kodetindakan','psikologi_trans.rs7','psikologi_trans.rs13',
                    'psikologi_trans.rs5','rs24.rs4')
                    ->leftjoin('rs24','psikologi_trans.rs25','rs24.rs4')
                    ->where('rs24.rs1','!=','')
                    ->groupBy('psikologi_trans.rs2','psikologi_trans.rs4');
                 },
                'bdrs' => function ($bdrs) {
                    $bdrs->select('rs1', 'rs12', 'rs13','rs14')->where('rs14', '!=', 'POL014');
                },
                 'penunjangkeluar' => function ($penunjangkeluar) {
                    $penunjangkeluar->select('noreg','nota','ruangan','harga_sarana','harga_pelayanan','jumlah')
                    ->where('ruangan','!=','POL014');
                 },
                // 'apotekranap' => function ($apotekranap) {
                //     $apotekranap->select('rs1', 'rs6', 'rs8', 'rs10')->where('rs20', '!=', 'POL014')->where('lunas', '!=', '1')
                //         ->where('rs25', 'CENTRAL');
                // },
                'apotekranaplalu' => function ($apotekranaplalu) {
                    $apotekranaplalu->select('rs62.rs1','rs62.rs2', 'rs62.rs6', 'rs62.rs8', 'rs62.rs10','rs62.rs20','rs24.rs4 as ruangan','rs24.rs5',
                    DB::raw('round(sum((rs62.rs6*rs62.rs8)+rs62.rs10)) as subtotalx'))
                        ->join('rs24','rs62.rs20','rs24.rs1')
                        ->where('rs62.rs20', '!=', 'POL014')->where('lunas', '!=', '1')
                        ->where('rs62.rs25', 'CENTRAL')
                        ->groupBy('rs62.rs2');
                },
                // 'apotekranapracikanheder' => function ($apotekranapracikanheder) {
                //     $apotekranapracikanheder->select('rs1', 'rs8')->where('lunas', '!=', '1')->where('rs19', 'CENTRAL')->Where('rs18', '!=', 'IGD');
                // },
                // 'apotekranapracikanrinci:rs1,rs5,rs7',
                // 'apotekranapracikanhederlalu' => function ($apotekranapracikanhederlalu) {
                //     $apotekranapracikanhederlalu->select('rs1', 'rs8')->where('lunas', '!=', '1')->where('rs19', 'CENTRAL')->Where('rs18', '!=', 'IGD');
                // },
                'apotekranapracikanhederlalux' => function($apotekranapracikanrincilalu) {
                    $apotekranapracikanrincilalu->select('rs63.rs1','rs63.rs2','rs63.rs8','rs63.rs15','rs64.rs6','rs64.rs7','rs64.rs5','rs24.rs4 as ruangan','rs24.rs5',
                    DB::raw('round(sum(rs64.rs5*rs64.rs7)) as subtotalx'))
                    ->leftjoin('rs64','rs63.rs2','rs64.rs2')
                    ->leftjoin('rs24','rs63.rs15','rs24.rs1')
                    ->where('rs63.rs15','!=','POL014');
                },
                'newfarmasi' => function($newapotekrajal) {
                    $newapotekrajal->select('farmasi.resep_keluar_h.noreg','farmasi.resep_keluar_h.noresep','farmasi.resep_keluar_h.ruangan as koderuangan','rs.rs24.rs4 as ruangan','rs.rs24.rs5',
                    DB::raw('round(sum(farmasi.resep_keluar_r.harga_jual*farmasi.resep_keluar_r.jumlah+farmasi.resep_keluar_r.nilai_r)) as subtotalx'))
                    ->leftjoin('farmasi.resep_keluar_r','farmasi.resep_keluar_h.noresep','farmasi.resep_keluar_r.noresep')
                    ->leftjoin('rs.rs24','farmasi.resep_keluar_h.ruangan','rs.rs24.rs1')
                    ->whereIn('farmasi.resep_keluar_h.depo',['Gd-04010102', 'Gd-04010103'])
                    ->where('resep_keluar_r.kdobat','!=','')
                    ->where('farmasi.resep_keluar_h.ruangan','!=','POL014')
                    ->groupBy('farmasi.resep_keluar_h.noresep');
                },
                'newfarmasiracikan' => function($newfarmasiracikan) {
                    $newfarmasiracikan->select('farmasi.resep_keluar_h.noreg','farmasi.resep_keluar_h.noresep','farmasi.resep_keluar_h.ruangan as koderuangan','rs.rs24.rs4 as ruangan','rs.rs24.rs5',
                    DB::raw('round(sum(farmasi.resep_keluar_racikan_r.harga_jual*farmasi.resep_keluar_racikan_r.jumlah+farmasi.resep_keluar_racikan_r.nilai_r)) as subtotalx'))
                    ->join('farmasi.resep_keluar_racikan_r','farmasi.resep_keluar_h.noresep','farmasi.resep_keluar_racikan_r.noresep')
                    ->join('rs.rs24','farmasi.resep_keluar_h.ruangan','rs.rs24.rs1')
                    ->whereIn('farmasi.resep_keluar_h.depo',['Gd-04010102', 'Gd-04010103'])
                    ->where('farmasi.resep_keluar_h.ruangan','!=','POL014')
                    ->groupBy('farmasi.resep_keluar_h.noresep');
                },
                'kamaroperasiibsx' => function ($kamaroperasiibsx) {
                    $kamaroperasiibsx->select('rs1', 'rs5', 'rs6', 'rs7', 'rs8')
                        ->where('rs15', 'POL014');
                },
                // 'tindakanoperasix' => function ($tindakanoperasix) {
                //     $tindakanoperasix->select('rs1', 'rs2', 'rs7', 'rs13', 'rs5')->where('rs22', 'OPERASI2');
                // },
                'ambulan' => function ($ambulan) {
                    $ambulan->select('rs283.rs1', 'rs283.rs2', 'rs283.rs15', 'rs283.rs16', 'rs283.rs17', 'rs283.rs18', 'rs283.rs23', 'rs283.rs26', 'rs283.rs30','rs283.rs20','rs24.rs4 as ruangan')
                    ->join('rs24','rs283.rs20','rs24.rs1')
                    ->where('rs20', '!=', 'POL014');
                },
                'asuhangizi' => function ($asuhangizi) {
                    $asuhangizi->select('rs1', 'rs4', 'rs5','rs8 as ruangan')->where('rs3', 'K00013');
                },

                // //------------------igd-------------//

                // 'rstigalimaxxx' => function ($rstigalimaxxx) {
                //     $rstigalimaxxx->select('rs1', 'rs6', 'rs7')->where('rs3', 'A2#');
                // },
                // 'irdtindakan' => function ($irdtindakan) {
                //     $irdtindakan->select('rs1', 'rs2', 'rs7', 'rs13', 'rs5')->where('rs22', 'POL014');
                // },
                // 'laboratdiird' => function ($laboratdiird) {
                //     $laboratdiird->select('rs1', 'rs2', 'rs3', 'rs4', 'rs5', 'rs6', 'rs13', 'rs23')->where('rs23', 'POL014')->where('rs18', '!=', '')
                //         ->where('rs23', '!=', '1');
                // },
                // 'laboratdiird.pemeriksaanlab:rs1,rs2,rs21',
                // 'transradiologidiird' => function ($transradiologidiird) {
                //     $transradiologidiird->select('rs1', 'rs6', 'rs8', 'rs24')->where('rs26', 'POL014');
                // },
                // 'irdtindakanoperasix' => function ($irdtindakanoperasix) {
                //     $irdtindakanoperasix->select('rs1', 'rs2', 'rs7', 'rs13', 'rs5')->where('rs22', 'OPERASIIRD2');
                // },
                // 'irdkamaroperasiigd' => function ($irdkamaroperasiigd) {
                //     $irdkamaroperasiigd->select('rs226.rs1', 'rs226.rs5', 'rs226.rs6', 'rs226.rs7', 'rs226.rs8')
                //         ->where('rs226.rs15', 'POL014');
                // },
                // 'irdbdrs' => function ($irdbdrs) {
                //     $irdbdrs->select('rs1', 'rs12', 'rs13')->where('rs14', 'POL014');
                // },
                // 'irdbiayamaterai' => function ($irdbiayamaterai) {
                //     $irdbiayamaterai->select('rs1', 'rs5')->where('rs7', 'IRD');
                // },
                // 'irdambulan' => function ($irdambulan) {
                //     $irdambulan->select('rs1', 'rs2', 'rs15', 'rs16', 'rs17', 'rs18', 'rs23', 'rs26', 'rs30')->where('rs20', 'POL014');
                // },
                // 'irdtindakanhd' => function ($irdtindakanhd) {
                //     $irdtindakanhd->select('rs1', 'rs2', 'rs7', 'rs13', 'rs5')->where('rs22', 'PEN005')->where('rs25', 'POL014');
                // },
                // 'irdtindakananastesidiluarokdanicu' => function ($irdtindakananastesidiluarokdanicu) {
                //     $irdtindakananastesidiluarokdanicu->select('rs1', 'rs2', 'rs7', 'rs13', 'rs5')->where('rs22', 'PEN012')->where('rs25', 'POL014');
                // },
                // 'irdtindakanfisioterapi' => function ($irdtindakanfisioterapi) {
                //     $irdtindakanfisioterapi->select('rs1', 'rs2', 'rs7', 'rs13', 'rs5')->where('rs22', 'fisioterapi')->where('rs25', 'POL014');
                // },
                // 'apotekranapx' => function ($apotekranap) {
                //     $apotekranap->select('rs1', 'rs6', 'rs8', 'rs10')->where('rs20', 'POL014')->where('lunas', '!=', '1')
                //         ->where('rs24', 'IRD')->where('rs25', 'CENTRAL')->orWhere('rs25', 'IGD');
                // },
                // 'apotekranaplalux' => function ($apotekranaplalux) {
                //     $apotekranaplalux->select('rs1', 'rs6', 'rs8', 'rs10')->where('rs20', 'POL014')->where('lunas', '!=', '1')
                //         ->where('rs24', 'IRD')->where('rs25', 'CENTRAL')->orWhere('rs25', 'IGD');
                // },
                // 'apotekranapracikanhederx' => function ($apotekranapracikanhederx) {
                //     $apotekranapracikanhederx->select('rs1', 'rs8')->where('lunas', '!=', '1')
                //         ->where('rs18', 'IRD')->where('rs19', 'CENTRAL')->orWhere('rs18', 'IGD');
                // },
                // 'apotekranapracikanrincix:rs1,rs5,rs7',
                // 'apotekranapracikanhederlalux' => function ($apotekranapracikanhederlalux) {
                //     $apotekranapracikanhederlalux->select('rs1', 'rs8')->where('lunas', '!=', '1')
                //         ->where('rs18', 'IRD')->where('rs19', 'CENTRAL')->orWhere('rs18', 'IGD');
                // },
                // 'apotekranapracikanrincilalux:rs1,rs5,rs7',
                // 'groupingranap:noreg,nosep,cbg_code,cbg_desc,cbg_tarif,procedure_tarif,prosthesis_tarif,investigation_tarif,drug_tarif,acute_tarif,chronic_tarif',
                // 'klaimranap:noreg,nama_dokter'
            ]
        )
        ->whereBetween('rs23.rs4', [$dari, $sampai])
        ->get();

        // $ee = $data->map(function ($query) {
        //     $query->with(['rstigalimax', $query->rstigalimax->where('rs3', 'K1#')->take(1)]);
        //     return $query->rstigalimax;
        // });

        // $tarif = Rstigapuluhtarif::where('rs3', 'A1#')->first();
        //     $aa = $data->map(function ($query) use ($tarif) {
        //         $admin = $query->rstigalimaxrs[0]->rs17;
        //         $administrasi = 0;

        //         if ($admin === "3") {
        //             $administrasi = $tarif->rs6 + $tarif->rs7;
        //         } else if ($admin === "2") {
        //             $administrasi = $tarif->rs8 + $tarif->rs9;
        //         } else if ($admin === "1" || $admin === "IC" || $admin === "ICC" || $admin === "NICU" || $admin === "IN") {
        //             $administrasi = $tarif->rs10 + $tarif->rs11;
        //         } else if ($admin === "Utama") {
        //             $administrasi = $tarif->rs12 + $tarif->rs13;
        //         } else if ($admin === "VIP") {
        //             $administrasi = $tarif->rs14 + $tarif->rs15;
        //         } else if ($admin === "VVIP") {
        //             $administrasi = $tarif->rs16 + $tarif->rs17;
        //         }

        //         $query['admin'] = $administrasi;
        //         return $query;
        //     });
        return new JsonResponse($data);
    }

    public function allBillRekapByRuanganperruangan()
    {
        $dari = request('tgldari') .' 00:00:00';
        $sampai = request('tglsampai') .' 23:59:59';

        $data = Mruangranap::with(
            [
                'kunjunganranap' => function($kunjunganranap) use ($dari,$sampai) {
                    $kunjunganranap->select('rs23.rs5','rs24.rs3 as kelas','rs24.rs4 as koderuang','rs24.rs5 as namaruangan')
                    ->leftjoin('rs24','rs23.rs5','rs24.rs1')
                    ->whereBetween('rs23.rs4', [$dari, $sampai])
                    ->whereIn('rs23.rs22', [2,3]);
                },
                // 'rstigalimax' => function($rstigalimax) use ($dari,$sampai) {
                //     $rstigalimax->select('rs23.rs1','rs35x.rs4', 'rs35x.rs7', 'rs35x.rs14', 'rs35x.rs16','rs35x.rs17')->where('rs35x.rs3', 'K1#')
                //     ->whereBetween('rs23.rs4', [$dari, $sampai])
                //     ->leftjoin('rs23','rs23.rs1','rs35x.rs1')
                //     ->whereIn('rs23.rs22', ['2','3'])
                //     ->latest('rs35x.rs4');
                // },
                'akomodasikamar' => function($akomodasikamar) use ($dari,$sampai) {
                    $akomodasikamar->select('rs1','rs4', 'rs7', 'rs14', 'rs18','rs17')->where('rs3', 'K1#')
                    ->whereBetween('rs4', [$dari, $sampai])
                    ->orderBy('rs4', 'DESC');
                },
                'tindakandokter' => function ($tindakandokter) use ($dari,$sampai) {
                    $tindakandokter->select('rs73.rs1', 'rs73.rs2', 'rs73.rs7', 'rs73.rs13', 'rs73.rs5', 'rs73.rs25')
                        ->join('rs24', 'rs24.rs1', '=', 'rs73.rs25')
                        ->join('rs21', 'rs21.rs1', '=', DB::raw('SUBSTRING_INDEX(rs73.rs8,";",1)'))
                        ->where('rs21.rs13', '1')
                        ->whereBetween('rs73.rs3', [$dari, $sampai])
                    ->where('rs73.rs22','!=','POL014');
                },
                'tindakanperawat' => function ($tindakanperawat) use ($dari,$sampai) {
                    $tindakanperawat->select('rs73.rs1', 'rs73.rs2', 'rs73.rs7', 'rs73.rs13', 'rs73.rs5', 'rs73.rs25')
                        ->join('rs24', 'rs24.rs1', '=', 'rs73.rs25')
                        ->join('rs21', 'rs21.rs1', '=', DB::raw('SUBSTRING_INDEX(rs73.rs8,";",1)'))
                        ->whereIn('rs21.rs13', ['2', '3'])
                        ->whereBetween('rs73.rs3', [$dari, $sampai])
                        ->where('rs73.rs22','!=','POL014');
                },
                'keperawatan' => function ($keperawatan) use ($dari,$sampai){
                    $keperawatan->select('rs1', 'rs4', 'rs5','rs8')
                    ->whereBetween('rs2', [$dari, $sampai]);
                },
                'visiteumum' => function ($visiteumum) use ($dari,$sampai){
                    $visiteumum->select('rs1', 'rs4', 'rs5','rs8')
                    ->whereBetween('rs2', [$dari, $sampai]);
                },
            ]
        )
        //->groupBy('rs24.rs4')
        ->get();
        return new JsonResponse($data);
    }

    public function allBillRekapByRuanganperPoli()
    {
        $dari = request('tgldari') .' 00:00:00';
        $sampai = request('tglsampai') .' 23:59:59';

        $data = KunjunganPoli::select('rs19.rs1','rs19.rs2','rs17.rs1','rs19.rs1 as kodepoli')
        ->join('rs19','rs17.rs8','rs19.rs1')
        ->with(
            [
                'adminpoli' => function($adminpoli) {
                    $adminpoli->select('rs1','rs2','rs7','rs11')->where('rs3','K2#');
                },
                'konsulantarpoli' => function($konsulantarpoli) {
                    $konsulantarpoli->select('rs1','rs2','rs7','rs11')->where('rs3','K3#');
                },
                'tindakandokter' => function ($tindakandokter) use ($dari,$sampai) {
                    $tindakandokter->select('rs73.rs1', 'rs73.rs2', 'rs73.rs7', 'rs73.rs13', 'rs73.rs5', 'rs73.rs25')
                        ->join('rs19', 'rs19.rs1', '=', 'rs73.rs22')
                        ->join('rs21', 'rs21.rs1', '=', DB::raw('SUBSTRING_INDEX(rs73.rs8,";",1)'))
                        ->where('rs21.rs13', '1')->where('rs19.rs4','Poliklinik')
                        ->whereBetween('rs73.rs3', [$dari, $sampai])
                    ->where('rs73.rs22','!=','POL014');
                },
                'tindakanperawat' => function ($tindakanperawat) use ($dari,$sampai) {
                    $tindakanperawat->select('rs73.rs1', 'rs73.rs2', 'rs73.rs7', 'rs73.rs13', 'rs73.rs5', 'rs73.rs25')
                        ->join('rs19', 'rs19.rs1', '=', 'rs73.rs22')
                        ->join('rs21', 'rs21.rs1', '=', DB::raw('SUBSTRING_INDEX(rs73.rs8,";",1)'))
                        ->whereIn('rs21.rs13', ['2', '3'])->where('rs19.rs4','Poliklinik')
                        ->whereBetween('rs73.rs3', [$dari, $sampai])
                        ->where('rs73.rs22','!=','POL014');
                },
            ]
        )
        ->whereBetween('rs17.rs3', [$dari, $sampai])
        ->where('rs19.rs1','!=','POL014')->where('rs19.rs4','Poliklinik')
        ->where('rs19', '=', '1')
        ->orderBY('rs19.rs2')
        ->get();
        return new JsonResponse($data);
    }
}
