<?php

namespace App\Http\Controllers;

use App\Events\AntreanEvent;
use App\Events\PlaygroundEvent;
use App\Helpers\BridgingbpjsHelper;
use App\Helpers\DateHelper;
use App\Helpers\FormatingHelper;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Stok\StokOpnameFarmasiController;
use App\Models\LaboratLuar;
use App\Models\TransaksiLaborat;

use App\Models\Simrs\Master\Mkamar;
use App\Models\Simrs\Master\Rstigapuluhtarif;
use App\Models\Simrs\Pendaftaran\Rajalumum\Bpjs_http_respon;
use App\Models\Simrs\Penunjang\Farmasinew\Bast\BastrinciM;
use App\Models\Simrs\Penunjang\Farmasinew\Counter;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarrinci;
use App\Models\Simrs\Penunjang\Farmasinew\Harga\DaftarHarga;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanRinci;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokopname;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokrel;
use App\Models\Simrs\Penunjang\Farmasinew\Stokreal;
use App\Models\Simrs\Ranap\Kunjunganranap;
use App\Models\Simrs\Ranap\Rs141;
use App\Models\Simrs\Ranap\Views\Kunjunganview;
use App\Models\Simrs\Tindakan\Tindakan;
use App\Models\Simrs\Tindakan\TindakanSambung;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

use function PHPUnit\Framework\isNull;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AutogenController extends Controller
{
    public function pass()
    {
        echo bcrypt('123456789');
    }

    public function tindakanId()
    {
        // Tindakan
        $nota = TindakanSambung::whereNull('rs73_id')
            ->whereNotNull('ket')
            // ->limit(10)
            ->get();
        foreach ($nota as $key) {
            $tind = Tindakan::where('rs2', '=', $key->nota)
                ->where('rs1', '=', $key->noreg)
                ->where('rs4', '=', $key->kd_tindakan)
                ->orderBy('id', 'ASC')
                ->first();
            if ($tind) {
                $key->update(['rs73_id' => $tind->id]);
            }
        }
        $data = [
            'nota' => $nota,
        ];
        return $data;
    }
    public function coba()
    {
        $text = 'BAB darah mulai tadi pagi >3x,';
        $q = preg_replace('/[^a-z\d]+/i', ' ', $text);
        $q = preg_replace('/\s+/', ' ', $q);
        $q = trim($q);

        echo $q;
    }
    public function hargaResep()
    {
        $resReffAwal = Resepkeluarrinci::select(
            'resep_keluar_r.kdobat',
            'resep_keluar_r.nopenerimaan',
            'resep_keluar_r.harga_beli',
            'stokreal.harga',
        )
            ->join('stokreal', function ($jo) {
                $jo->on('stokreal.kdobat', '=', 'resep_keluar_r.kdobat')
                    ->on('stokreal.nopenerimaan', '=', 'resep_keluar_r.nopenerimaan')
                    ->on('stokreal.harga', '!=', 'resep_keluar_r.harga_beli');
            })
            ->groupBy('resep_keluar_r.kdobat', 'resep_keluar_r.nopenerimaan',)
            ->get();

        $resReff = Resepkeluarrinci::select(
            'resep_keluar_r.kdobat',
            'resep_keluar_r.nopenerimaan',
            'resep_keluar_r.harga_beli',
            'penerimaan_r.harga_netto_kecil',
        )
            ->join('penerimaan_r', function ($jo) {
                $jo->on('penerimaan_r.kdobat', '=', 'resep_keluar_r.kdobat')
                    ->on('penerimaan_r.nopenerimaan', '=', 'resep_keluar_r.nopenerimaan')
                    ->on('penerimaan_r.harga_netto_kecil', '!=', 'resep_keluar_r.harga_beli');
            })
            ->groupBy('resep_keluar_r.kdobat', 'resep_keluar_r.nopenerimaan',)
            ->get();

        $data = [
            'resReffAwal' => $resReffAwal,
            'resReff' => $resReff,
        ];
        return $data;
    }
    public function hargaStok()
    {

        $opnameMei = Stokopname::where('tglopname', 'like', '2024-05%')
            ->where('nopenerimaan', 'like', '%awal%')
            ->where('harga', '>', 0)
            ->groupBy('kdobat', 'nopenerimaan')
            ->get();
        $stokAwal = Stokrel::where('nopenerimaan', 'like', '%awal%')
            ->where('nobatch', '!=', '-')
            ->where('nobatch', '!=', '')
            ->get();
        $referenceAwal = collect($opnameMei);
        // cek
        $adaBedaStAwal = [];
        foreach ($stokAwal as $key) {
            $reff = $referenceAwal->where('nopenerimaan', $key['nopenerimaan'])
                ->where('kdobat', $key['kdobat'])
                ->where('harga', '!=', $key['harga'])
                ->first();
            if ($reff) {
                // $harga = $referenceHarga->where('nopenerimaan', $key['nopenerimaan'])
                //     ->where('kd_obat', $key['kdobat']);
                $adaBedaStAwal[] = [
                    'reff' => $reff,
                    'key' => $key,
                    // 'harga' => $harga
                ];
                $key->update(['harga' => $reff->harga]);
                // return $adaBedaStAwal;
            }
        }

        $stokReff = Stokrel::select(
            'stokreal.kdobat',
            'stokreal.nopenerimaan',
            'stokreal.harga',
            'penerimaan_r.harga_netto_kecil',
        )
            ->join('penerimaan_r', function ($jo) {
                $jo->on('penerimaan_r.kdobat', '=', 'stokreal.kdobat')
                    ->on('penerimaan_r.nopenerimaan', '=', 'stokreal.nopenerimaan')
                    ->on('penerimaan_r.harga_netto_kecil', '!=', 'stokreal.harga');
            })
            ->groupBy('stokreal.kdobat', 'stokreal.nopenerimaan',)
            ->get();
        $obat = collect($stokReff)->map(function ($it) {
            return $it->kdobat;
        })->toArray();
        $trmny = collect($stokReff)->map(function ($it) {
            return $it->nopenerimaan;
        })->toArray();
        $kdobat = array_unique($obat);
        $noper = array_unique($trmny);

        $stokTrm = Stokrel::whereIn('nopenerimaan', $noper)
            ->whereIn('kdobat', $kdobat)
            ->where('nobatch', '!=', '-')
            ->where('nobatch', '!=', '')
            // ->groupBy('kdobat', 'nopenerimaan')
            ->get();
        $rincTrm = PenerimaanRinci::select(
            'nopenerimaan',
            'kdobat',
            'no_batch',
            'tgl_exp',
            'harga_netto_kecil as harga',
        )
            ->whereIn('nopenerimaan', $noper)
            ->whereIn('kdobat', $kdobat)
            ->groupBy('nopenerimaan', 'no_batch', 'kdobat', 'tgl_exp', 'harga_netto_kecil')
            ->get();
        $reffTrm = collect($rincTrm);
        $adaBedaStTrm = [];
        foreach ($stokTrm as $key) {
            $reff = $reffTrm->where('nopenerimaan', $key['nopenerimaan'])
                ->where('kdobat', $key['kdobat'])
                ->where('no_batch', $key['nobatch'])
                ->where('tgl_exp', $key['tglexp'])
                ->where('harga', '!=', $key['harga'])
                ->first();
            if ($reff) {
                $adaBedaStTrm[] = [
                    'reff' => $reff,
                    'key' => $key,
                    // 'harga' => $harga
                ];
                $key->update(['harga' => $reff->harga]);
                // return $adaBedaStTrm;
            }
        }
        $data = [
            'count adaBedaStAwal' => sizeof($adaBedaStAwal),
            'count adaBedaStTrm' => sizeof($adaBedaStTrm),
            'adaBedaStAwal' => $adaBedaStAwal,
            'adaBedaStTrm' => $adaBedaStTrm,
        ];
        return $data;
    }
    public function hargaOpname()
    {
        $opname = Stokopname::where('tglopname', 'like', '2024-05%')
            ->where('nopenerimaan', 'like', '%awal%')
            ->groupBy('kdobat', 'nopenerimaan')
            ->get();
        $opnameLater = Stokopname::where('tglopname', 'not like', '2024-05%')
            ->where('nopenerimaan', 'like', '%awal%')
            ->get();

        $adaStTrm = Stokopname::select(
            'stokopname.kdobat',
            'stokopname.nopenerimaan',
            'stokopname.harga',
            'penerimaan_r.harga_netto_kecil',
        )
            ->join('penerimaan_r', function ($jo) {
                $jo->on('penerimaan_r.kdobat', '=', 'stokopname.kdobat')
                    ->on('penerimaan_r.nopenerimaan', '=', 'stokopname.nopenerimaan')
                    ->on('penerimaan_r.harga_netto_kecil', '!=', 'stokopname.harga');
            })
            // ->where('stokopname.harga', '!=', 'penerimaan_r.harga_netto_kecil')
            ->groupBy('stokopname.kdobat', 'stokopname.nopenerimaan',)
            ->get();
        $obat = collect($adaStTrm)->map(function ($it) {
            return $it->kdobat;
        })->toArray();
        $trmny = collect($adaStTrm)->map(function ($it) {
            return $it->nopenerimaan;
        })->toArray();
        $kdobat = array_unique($obat);
        $noper = array_unique($trmny);
        // return $noper;

        $opnameTrm = Stokopname::whereIn('nopenerimaan', $noper)
            ->whereIn('kdobat', $kdobat)
            ->where('nobatch', '!=', '-')
            ->where('nobatch', '!=', '')
            // ->groupBy('kdobat', 'nopenerimaan')
            ->get();
        $rincTrm = PenerimaanRinci::select(
            'nopenerimaan',
            'kdobat',
            'no_batch',
            'tgl_exp',
            'harga_netto_kecil as harga',
        )
            ->whereIn('nopenerimaan', $noper)
            ->whereIn('kdobat', $kdobat)
            ->groupBy('nopenerimaan', 'no_batch', 'kdobat', 'tgl_exp', 'harga_netto_kecil')
            ->get();
        $col = collect($opnameLater);
        $opna = collect($opnameTrm);
        $trmLtr = collect($rincTrm);
        $adaOpnam = [];
        $adaOpnamTrm = [];
        foreach ($opname as $key) {
            $temp = $col->where('kdobat', $key['kdobat'])
                ->where('nopenerimaan', $key['nopenerimaan'])
                ->where('harga', '!=', $key['harga']);


            if (sizeof($temp) > 0) {
                $adaOpnam[] = [
                    'bln 5' => $key,
                    'after' => $temp
                ];
                foreach ($temp as $t) {
                    $t->update(['harga' => $key['harga']]);
                    // return [$t, $key];
                }
            }
        }
        foreach ($adaStTrm as $trm) {
            $opm = $opna->where('kdobat', $trm['kdobat'])
                ->where('nopenerimaan', $trm['nopenerimaan']);
            if (sizeof($opm) > 0) {
                foreach ($opm as $key) {
                    $temp = $trmLtr->where('kdobat', $key['kdobat'])
                        ->where('nopenerimaan', $key['nopenerimaan'])
                        ->where('no_batch', $key['nobatch'])
                        ->where('tgl_exp', $key['tglexp'])
                        ->where('harga', '!=', $key['harga'])
                        ->first();
                    // if ($temp) return [$key, $temp];

                    if ($temp) {
                        $adaOpnamTrm[] = [
                            'key' => $key,
                            'trm' => $temp
                        ];
                        $key->update(['harga' => $temp->harga]);
                    }
                }
            }
        }


        $data = [
            'size ada opname' => count($adaOpnam),
            // 'size opname mei' => count($opname),
            'size opname penerimaan' => count($adaOpnamTrm),
            // 'opname mei' => $opname,
            // 'opname after mei' => $opnameLater,
            'ada opname later' => $adaOpnamTrm,
            // 'opname nopenerimaan' => $opnameTrm,
            'ada opname' => $adaOpnam,
        ];
        return $data;
    }
    public function index(Request $request)
    {

        // echo $this->translate("nyeri kepala, mual", "id", "en");

        // $text = 'sesak , panas(+) batuk(+)';
        // $q = preg_replace('/[^a-z\d]+/i', ' ', $text);
        // $q = preg_replace('/\s+/', ' ', $q);
        // $q = trim($q). "-" . bin2hex(random_bytes(10));

        // $a = Str::random(20);
        //                     $bbb = Str::random(20);
        //                     $c = Str::random(20);
        //                     $d = Str::random(20);
        //                     $eee = Str::random(20);
        //                     $f = Str::random(20);

        // // echo $q;
        // return [
        //     'q' => $q,
        //     'a' => $a,
        //     'bbb' => $bbb,
        //     'c' => $c,
        //     'd' => $d,
        //     'eee' => $eee,
        //     'f' => $f
        // ];

        // $request = new Request();
        // $request->replace([
        //     'kdgroup_ruangan' => 'ASK',
        //     'kelas_ruangan' => 'PS',
        //     'hak_kelas'=> '3',
        // ]);

        // $spesialis = true;

        // return self::cekTarip($spesialis, $request);
        // echo "PERCOBAAN DEPLOY SWOOLE";
        $table = 'permintaan_r';
        return Schema::connection('farmasi')->getColumnListing($table);
    }

    public static function cekTarip($spesialis, $request)
    {

        $sarana = 0;
        $pelayanan = 0;
        $flag_biaya = null;

        if ($spesialis) {
            // "select * from rs30tarif where (rs3='V2#' or rs3='V3#'
            $rsx = null;
            if ($request->kelas_ruangan === "IC" || $request->kelas_ruangan === "ICC" || $request->kelas_ruangan === "NICU") {
                $rsx = Rstigapuluhtarif::where('rs3', 'V3#')
                    ->where('rs4', 'like', '%|' . $request->kdgroup_ruangan . '|%')
                    ->where('rs5', 'like', '%|' . $request->kelas_ruangan . '|%')
                    ->first();
            } else {
                $rsx = Rstigapuluhtarif::where('rs3', 'V2#')
                    ->where('rs4', 'like', '%|' . $request->kdgroup_ruangan . '|%')
                    ->where('rs5', 'like', '%|' . $request->kelas_ruangan . '|%')
                    ->first();
            }

            // return $rsx;
            //   return $rsx;
            if (!$rsx) {
                $sarana = 0;
                $pelayanan = 0;
                $flag_biaya = null;
            }

            $flag_biaya = $rsx->rs3;

            if ($request->kelas_ruangan === "3" || $request->kelas_ruangan === "IC" || $request->kelas_ruangan === "ICC" || $request->kelas_ruangan === "NICU" || $request->kelas_ruangan === "IN") {
                $sarana = $rsx->rs6;
                $pelayanan = $rsx->rs7;
            } else if ($request->kelas_ruangan == "2") {
                $sarana = $rsx->rs8;
                $pelayanan = $rsx->rs9;
            } else if ($request->kelas_ruangan == "1") {
                $sarana = $rsx->rs10;
                $pelayanan = $rsx->rs11;
            } else if ($request->kelas_ruangan == "Utama") {
                $sarana = $rsx->rs12;
                $pelayanan = $rsx->rs13;
            } else if ($request->kelas_ruangan == "VIP") {
                $sarana = $rsx->rs14;
                $pelayanan = $rsx->rs15;
            } else if ($request->kelas_ruangan == "VVIP") {
                $sarana = $rsx->rs16;
                $pelayanan = $rsx->rs17;
            } else if ($request->kelas_ruangan == "HCU") {


                $hakKelas = $request->hak_kelas;
                if ($hakKelas === '1') {
                    $sarana = $rsx->rs10;
                    $pelayanan = $rsx->rs11;
                } else if ($hakKelas === '2') {
                    $sarana = $rsx->rs8;
                    $pelayanan = $rsx->rs9;
                } else if ($hakKelas === '3') {
                    $sarana = $rsx->rs6;
                    $pelayanan = $rsx->rs7;
                }
            } else if ($request->kelas_ruangan == "PS") {

                $sarana = $rsx->pss;
                $pelayanan = $rsx->psp;
            }
        } else {

            //select * from rs30tarif where (rs3='V1#

            $rsx = null;
            if ($request->kelas_ruangan === "IC" || $request->kelas_ruangan === "ICC" || $request->kelas_ruangan === "NICU") {
                $rsx = Rstigapuluhtarif::where('rs3', 'V5#')
                    ->where('rs4', 'like', '%|' . $request->kdgroup_ruangan . '|%')
                    ->where('rs5', 'like', '%|' . $request->kelas_ruangan . '|%')
                    ->first();
            } else {
                $rsx = Rstigapuluhtarif::where('rs3', 'V1#')
                    ->where('rs4', 'like', '%|' . $request->kdgroup_ruangan . '|%')
                    ->where('rs5', 'like', '%|' . $request->kelas_ruangan . '|%')
                    ->first();
            }

            if (!$rsx) {
                return null;
            }


            $flag_biaya = $rsx->rs3;

            if ($request->kelas_ruangan === "3" || $request->kelas_ruangan === "IC" || $request->kelas_ruangan === "ICC" || $request->kelas_ruangan === "NICU" || $request->kelas_ruangan === "IN") {
                $sarana = $rsx->rs6;
                $pelayanan = $rsx->rs7;
            } else if ($request->kelas_ruangan === "2") {
                $sarana = $rsx->rs8;
                $pelayanan = $rsx->rs9;
            } else if ($request->kelas_ruangan === "1") {
                $sarana = $rsx->rs10;
                $pelayanan = $rsx->rs11;
            } else if ($request->kelas_ruangan === "Utama") {
                $sarana = $rsx->rs12;
                $pelayanan = $rsx->rs13;
            } else if ($request->kelas_ruangan === "VIP") {
                $sarana = $rsx->rs14;
                $pelayanan = $rsx->rs15;
            } else if ($request->kelas_ruangan === "VVIP") {
                $sarana = $rsx->rs16;
                $pelayanan = $rsx->rs17;
            } else if ($request->kelas_ruangan == "HCU") {


                $hakKelas = $request->hak_kelas;
                if ($hakKelas === '1') {
                    $sarana = $rsx->rs10;
                    $pelayanan = $rsx->rs11;
                } else if ($hakKelas === '2') {
                    $sarana = $rsx->rs8;
                    $pelayanan = $rsx->rs9;
                } else if ($hakKelas === '3') {
                    $sarana = $rsx->rs6;
                    $pelayanan = $rsx->rs7;
                }
            } else if ($request->kelas_ruangan == "PS") {

                $sarana = $rsx->pss;
                $pelayanan = $rsx->psp;
            }
        }

        $tarif = (int) $sarana + (int) $pelayanan;

        return [
            'flag_biaya' => $flag_biaya,
            'tarif' => $tarif,
            'sarana' => $sarana,
            'pelayanan' => $pelayanan
        ];
    }

    public function translate($q, $sl, $tl)
    {
        $res = file_get_contents("https://translate.googleapis.com/translate_a/single?client=gtx&ie=UTF-8&oe=UTF-8&dt=bd&dt=ex&dt=ld&dt=md&dt=qca&dt=rw&dt=rm&dt=ss&dt=t&dt=at&sl=" . $sl . "&tl=" . $tl . "&hl=hl&q=" . urlencode($q), $_SERVER['DOCUMENT_ROOT'] . "/transes.html");
        $res = json_decode($res);
        return $res[0][0][0];
    }

    public function resetCounter()
    {
        $counter = Counter::first();
        // if($counter){
        //     $counter->update([
        //         'deporajal'=>0,
        //         'deporanap'=>0,
        //         'depook'=>0,
        //         'depoigd'=>0,
        //     ]);
        // }
        return $counter;
    }
    public function iniSipUntukmenggantiWith()
    {
        $query = Rs141::select(
            'rs141.rs1 as noreg',
            'rs141.rs2 as norm',
        )
            ->where('rs141.rs3', '=', 'POL014')
            // ->leftJoin('rs23', 'rs23.rs2', '=', 'rs141.rs2')
            ->addSelect([
                'kunjungan' => KunjunganRanap::query()
                    ->selectRaw(
                        "substring_index(GROUP_CONCAT(
                    rs23.rs1 order by rs23.rs3 desc
                SEPARATOR '|+|'), '|+|', 3)"
                    )
                    // ->whereColumn('rs23.rs2', 'rs141.rs2')
                    ->whereColumn('rs23.rs1', 'rs141.flag')
                    // ->where('rs23.rs22', '=', '')
                    ->groupBy('rs23.rs1')
                    ->take(3)
            ])
            ->orderBy('rs141.id', 'desc')
            // ->groupBy('rs141.rs1')
            ->simplePaginate(10);

        $kodes = $query->pluck('kunjungan')
            ->map(function ($arr) {
                if ($arr !== null) {
                    return explode('|+|', $arr);
                }

                return [];
            })
            ->flatten();

        $datax = Kunjunganranap::select('rs1', 'rs2')
            ->whereIn('rs1', $kodes)
            ->orderBy('rs3', 'desc')
            ->get();

        foreach ($query as $yy) {
            // menjadikan array dari string $yy->harga_tertinggi_ids
            $ids = explode(',', $yy->kunjungan);

            $yyHargaTertinggi = $datax
                ->whereIn('rs1', $ids)
                ->sortBy(fn(Kunjunganranap $xx) => array_flip($ids)[$xx->rs1])
                ->values();

            // masukkan ke object harga_teringgi_kodes
            $yy->setRelation('kunjungan', $yyHargaTertinggi)->toArray();
        }

        return $query;
    }

    public function lihatSaranaDanPelayananRuangan()
    {
        $noreg = '11125/07/2024/X';
        // $data = DB::table('rs23')
        //     ->select(
        //         'rs23.rs1 as noreg',
        //         'rs23.rs5 as kodekamar',
        //         'rs23.rs19 as sistembayar',
        //         'rs24.rs4 as koderuang',
        //         'rs24.rs3 as kelas',
        //     )
        //     ->leftJoin('rs24', 'rs24.rs1', '=', 'rs23.rs5')
        //     ->where('rs23.rs1','=', $noreg)
        //     ->first();
        // $tarif = DB::table('rs30tarif')
        //     ->select('*')
        //     ->where('rs3', '=', 'K1#')
        //     ->where('rs4', 'like', '%'.$data->koderuang.'%')
        //     ->where('rs5', 'like', '%'.$data->kelas.'%')
        //     ->get();
        // return $tarif;

        $data = DB::select(
            "SELECT rs23.rs1 as noreg,rs23.rs5 as kodekamar,rs24.rs4 as koderuang,rs24.rs3 as kelas,rs23.rs19 as sistembayar,
			CASE rs24.rs3
				 WHEN '3' THEN rs30tarif.rs6
				 WHEN 'IC' THEN rs30tarif.rs6
				 WHEN 'ICC' THEN rs30tarif.rs6
				 WHEN 'NICU' THEN rs30tarif.rs6
				 WHEN 'IN' THEN rs30tarif.rs6
				 WHEN 'ISO' THEN rs30tarif.rs6
				 WHEN '2' THEN rs30tarif.rs8
				 WHEN '1' THEN rs30tarif.rs10
				 WHEN 'Utama' THEN rs30tarif.rs12
				 WHEN 'VIP' THEN rs30tarif.rs14
				 WHEN 'VVIP' THEN rs30tarif.rs16
			END as sarana,CASE rs24.rs3
				 WHEN '3' THEN rs30tarif.rs7
				 WHEN 'IC' THEN rs30tarif.rs7
				 WHEN 'ICC' THEN rs30tarif.rs7
				 WHEN 'NICU' THEN rs30tarif.rs7
				 WHEN 'IN' THEN rs30tarif.rs7
				 WHEN 'ISO' THEN rs30tarif.rs7
				 WHEN '2' THEN rs30tarif.rs9
				 WHEN '1' THEN rs30tarif.rs11
				 WHEN 'Utama' THEN rs30tarif.rs13
				 WHEN 'VIP' THEN rs30tarif.rs15
				 WHEN 'VVIP' THEN rs30tarif.rs17
			END as pelayanan
			from rs23,rs24,rs30tarif where rs24.rs1=rs23.rs5
			and rs30tarif.rs3='K1#' and rs30tarif.rs4 like concat('%',rs24.rs4,'%')
			and rs30tarif.rs5 like concat('%',rs24.rs3,'%') and rs23.rs1='" . $noreg . "'"
        );

        return $data;
    }

    public function showKamar()
    {
        $data = Mkamar::query()
            ->select('groups', 'rs5')
            ->with([
                'kamars' => function ($q) {
                    $q->where('rs7', '<>', '1')
                        ->addSelect([
                            'kunjungan' => Kunjunganview::query()
                                ->join('rs24', 'v_15_23.kamar', '=', 'rs24.rs2')
                                ->selectRaw("GROUP_CONCAT(v_15_23.noreg order by v_15_23.tgl_masuk asc, ',')")
                                ->whereColumn('v_15_23.no_bed', '=', 'rs25.rs2')
                                ->whereColumn('v_15_23.kd_kmr', '=', 'rs25.rs1')
                                ->whereColumn('v_15_23.kamar', '=', 'rs24.rs2')
                                ->where('v_15_23.status_inap', '=', '')
                        ])
                        ->orderBy('rs5', 'asc');
                },
                'kamars.kamar' => function ($q) {
                    $q->select('rs1', 'rs2', 'rs3', 'rs4', 'rs5', 'groups');
                }
            ])
            ->where('status', '<>', '1')
            ->where('groups', '=', 'BG')
            ->distinct('groups')
            ->get();

        $flat = [];
        foreach ($data as $x) {
            $xy = $x->kamars;
            foreach ($xy as $y) {
                if ($y->kunjungan !== null) {
                    $temp = [];
                    foreach (explode(',', $y->kunjungan) as $key => $value) {
                        $temp[$key] = $value;
                    }
                    $flat[] = $temp;
                }
            }
        }
        $flatten = collect(array_merge(...$flat))->unique()->values()->all();

        $kunjungan = Kunjunganview::whereIn('noreg', $flatten)
            ->orderBy('tgl_masuk', 'desc')
            ->groupBy('noreg')
            ->get();

        foreach ($data as $x) {
            $xy = $x->kamars;
            foreach ($xy as $y) {
                $noregs = explode(',', $y->kunjungan);
                $ee = $kunjungan
                    ->whereIn('noreg', $noregs)
                    ->sortBy(fn(Kunjunganview $kj) => array_flip($noregs)[$kj->noreg])
                    ->values();

                // masukkan ke object harga_teringgi_kodes
                $y->setRelation('kunjungan', $ee)->toArray();
            }
        }


        return new JsonResponse($data);
    }

    public function lihatstokobateresepBydokter()
    {
        // return request()->groups;
        // $req = new Request();
        // $req->request->add([
        //     'groups' => '1',
        //     'kdruang' => 'Gd-05010101',
        //     'q'=>'para',
        //     'tiperesep' => 'normal'
        // ]);
        // penccarian termasuk tiperesep
        $groupsistembayar = request()->groups;
        if ($groupsistembayar == '1') {
            $sistembayar = ['SEMUA', 'BPJS'];
        } else {
            $sistembayar = ['SEMUA', 'UMUM'];
        }
        $cariobat = Stokreal::select(
            'stokreal.kdobat as kdobat',
            'stokreal.kdruang as kdruang',
            'stokreal.tglexp',
            'new_masterobat.nama_obat as namaobat',
            'new_masterobat.kandungan as kandungan',
            'new_masterobat.bentuk_sediaan as bentuk_sediaan',
            'new_masterobat.satuan_k as satuankecil',
            'new_masterobat.status_fornas as fornas',
            'new_masterobat.status_forkid as forkit',
            'new_masterobat.status_generik as generik',
            'new_masterobat.status_kronis as kronis',
            'new_masterobat.status_prb as prb',
            'new_masterobat.kode108',
            'new_masterobat.uraian108',
            'new_masterobat.kode50',
            'new_masterobat.uraian50',
            'new_masterobat.kekuatan_dosis as kekuatandosis',
            'new_masterobat.volumesediaan as volumesediaan',
            DB::raw('sum(stokreal.jumlah) as total')
        )
            ->with(
                [
                    'minmax',
                    'transnonracikan' => function ($transnonracikan) {
                        $transnonracikan->select(
                            // 'resep_keluar_r.kdobat as kdobat',
                            // 'resep_permintaan_keluar.kdobat as kdobat',
                            // 'resep_keluar_h.depo as kdruang',
                            DB::raw('sum(resep_permintaan_keluar.jumlah) as jumlah')
                        )
                            ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_permintaan_keluar.noresep')
                            ->where('resep_keluar_h.depo', request()->kdruang)
                            ->whereIn('resep_keluar_h.flag', ['', '1', '2'])
                            ->groupBy('resep_permintaan_keluar.kdobat');
                    },
                    'transracikan' => function ($transracikan) {
                        $transracikan->select(
                            // 'resep_keluar_racikan_r.kdobat as kdobat',
                            // 'resep_permintaan_keluar_racikan.kdobat as kdobat',
                            // 'resep_keluar_h.depo as kdruang',
                            DB::raw('sum(resep_permintaan_keluar_racikan.jumlah) as jumlah')
                        )
                            ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_permintaan_keluar_racikan.noresep')
                            ->where('resep_keluar_h.depo', request()->kdruang)
                            ->whereIn('resep_keluar_h.flag', ['', '1', '2'])
                            ->groupBy('resep_permintaan_keluar_racikan.kdobat');
                    },
                    'permintaanobatrinci' => function ($permintaanobatrinci) {
                        $permintaanobatrinci->select(
                            // 'permintaan_r.no_permintaan',
                            // 'permintaan_r.kdobat',
                            DB::raw('sum(permintaan_r.jumlah_minta) as allpermintaan')
                        )
                            ->leftJoin('permintaan_h', 'permintaan_h.no_permintaan', '=', 'permintaan_r.no_permintaan')
                            // biar yang ada di tabel mutasi ga ke hitung
                            ->leftJoin('mutasi_gudangdepo', function ($anu) {
                                $anu->on('permintaan_r.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                                    ->on('permintaan_r.kdobat', '=', 'mutasi_gudangdepo.kd_obat');
                            })
                            ->whereNull('mutasi_gudangdepo.kd_obat')

                            ->where('permintaan_h.tujuan', request()->kdruang)
                            ->whereIn('permintaan_h.flag', ['', '1', '2'])
                            ->groupBy('permintaan_r.kdobat');
                    },
                    'persiapanrinci' => function ($res) {
                        $res->select(
                            // 'persiapan_operasi_rincis.kd_obat',

                            DB::raw('sum(persiapan_operasi_rincis.jumlah_minta) as jumlah'),
                        )
                            ->leftJoin('persiapan_operasis', 'persiapan_operasis.nopermintaan', '=', 'persiapan_operasi_rincis.nopermintaan')
                            ->whereIn('persiapan_operasis.flag', ['', '1'])
                            ->groupBy('persiapan_operasi_rincis.kd_obat');
                    },
                ]
            )
            ->leftjoin('new_masterobat', 'new_masterobat.kd_obat', '=', 'stokreal.kdobat')
            ->where('stokreal.kdruang', request()->kdruang)
            ->where('stokreal.jumlah', '>', 0)
            ->whereIn('new_masterobat.sistembayar', $sistembayar)
            ->where('new_masterobat.status_konsinyasi', '')
            ->when(request()->tiperesep === 'prb', function ($q) {
                $q->where('new_masterobat.status_prb', '!=', '');
            })
            ->when(request()->tiperesep === 'iter', function ($q) {
                $q->where('new_masterobat.status_kronis', '!=', '');
            })
            ->where(function ($query) {
                $query->where('new_masterobat.nama_obat', 'LIKE', '%' . request()->q . '%')
                    ->orWhere('new_masterobat.kandungan', 'LIKE', '%' . request()->q  . '%')
                    ->orWhere('stokreal.kdobat', 'LIKE', '%' . request()->q . '%');
            })
            ->groupBy('stokreal.kdobat')
            ->limit(10)
            ->get();
        $wew = collect($cariobat)->map(function ($x, $y) {
            $total = $x->total ?? 0;
            $jumlahper = request()->kdruang === 'Gd-04010103' ? $x['persiapanrinci'][0]->jumlah ?? 0 : 0;
            $jumlahtrans = $x['transnonracikan'][0]->jumlah ?? 0;
            $jumlahtransx = $x['transracikan'][0]->jumlah ?? 0;
            $permintaanobatrinci = $x['permintaanobatrinci'][0]->allpermintaan ?? 0; // mutasi antar depo
            $x->alokasi = (float) $total - (float)$jumlahtrans - (float)$jumlahtransx - (float)$permintaanobatrinci - (float)$jumlahper;
            return $x;
        });
        return new JsonResponse(
            [
                'dataobat' => $wew
            ]
        );
    }

    public function gennoreg()
    {
        $n = 1;
        $kode = 'mm';
        $has = null;
        $lbr = strlen($n);
        for ($i = 1; $i <= 5 - $lbr; $i++) {
            $has = $has . "0";
        }
        return $has . $n . "/" . date("m") . "/" . date("Y") . "/" . $kode;
    }


    public function query_table()
    {
        $y = Carbon::now()->subYears(2);
        $query = TransaksiLaborat::query()
            ->selectRaw('rs1,rs2,rs3 as tanggal,rs20,rs8,rs23,rs18,rs21')
            ->groupBy('rs2')
            ->whereYear('rs3', '<', $y)
            ->filter(request(['q', 'periode', 'filter_by']))
            ->with([
                'kunjungan_poli',
                'kunjungan_rawat_inap',
                'kunjungan_poli.pasien',
                'kunjungan_poli.sistem_bayar',
                'kunjungan_rawat_inap.pasien',
                'kunjungan_rawat_inap.ruangan',
                'kunjungan_rawat_inap.sistem_bayar',
                'poli',
                'dokter'
            ])
            ->orderBy('rs3', 'desc');

        return $query->get();
    }

    public function coba_api()
    {

        $xid = "4444";
        $secret_key = 'l15Test';
        date_default_timezone_set('UTC');
        $xtimestamp = strval(time() - strtotime('1970-01-01 00:00:00'));
        $sign = hash_hmac('sha256', $xid . "&" . $xtimestamp, $secret_key, true);
        $xsignature = base64_encode($sign);

        // $apiURL = 'http://172.16.24.2:83/prolims/api/lis/postOrder';
        $apiURL = 'http://192.168.101.200:83/prolims/api/lis/postOrder';
        $postInput = [
            "ADDRESS" => "JL BANTARAN RT5/10 NO.07 SUMBERKEDAWUNG LECES - KOTA PROBOLINGGO",
            "BOD" => "19981127",
            "CLASS" => "-",
            "CLASS_NAME" => "-",
            "COMPANY" => "-",
            "COMPANY_NAME" => "RSUD MOCH SALEH",
            "DATE_ORDER" => "20220916141249",
            "DIAGNOSA" => "-",
            "DOCTOR" => "17",
            "DOCTOR_NAME" => "Abdul Muis, dr. Sp.THT",
            "GLOBAL_COMMENT" => "-",
            "IDENTITY_N" => "-",
            "IS_CITO" => "-",
            "KODE_PRODUCT" => "LAB183",
            "ONO" => "220915/37334L",
            "PATIENT_NAME" => "RAHMAD ARDIANSYAH",
            "EMAIL" => "aabb@aaa.com",
            "PATIENT_NO" => "120038",
            "ROOM" => "POL014",
            "ROOM_NAME" => "IRD",
            "SEX" => "1",
            "STATUS" => "N",
            "TYPE_PATIENT" => "-"
        ];

        $headers = [
            'X-id' => $xid,
            'X-timestamp' => $xtimestamp,
            'X-signature' => $xsignature,
        ];

        $response = Http::withHeaders($headers)->post($apiURL, $postInput);

        $statusCode = $response->status();
        $responseBody = json_decode($response->getBody(), true);

        // dd($responseBody);
        return response()->json($responseBody);
    }

    public function getDetOrderList()
    {
        $xid = "4444";
        $secret_key = 'l15Test';
        date_default_timezone_set('UTC');
        $now = Carbon::now()->toDateTimeString();
        $xtimestamp = strval($now - strtotime('1970-01-01 00:00:00'));
        // $xtimestamp = strval(time() - strtotime($now));
        // $xtimestamp = strtotime($now);
        $sign = hash_hmac('sha256', $xid . "&" . $xtimestamp, $secret_key, true);
        $xsignature = base64_encode($sign);

        // $apiURL = 'http://135.148.145.64:83/prolims/api/lis/getResult?ONO=220915/37334L';
        $apiURL = 'http://45.77.35.181:83/prolims/api/lis/order?startDate=20220916&endDate=20220916'; // alamat ini sudah gak dipake

        $headers = [
            'X-id' => $xid,
            'X-timestamp' => $xtimestamp,
            'X-signature' => $xsignature,
        ];

        // $response = Http::withHeaders($headers)->get($apiURL);

        // $statusCode = $response->status();
        // $responseBody = json_decode($response->getBody(), true);

        $response = Http::withHeaders($headers)->get($apiURL)->json();
        // dd($response);
        return response()->json($response);


        $xid = '4444';
        $xtimestamp = time();
        $secret_key = 'l15Test';
        $signature = hash_hmac('sha256', $xid, $secret_key);
    }



    public function coba_post_hasil(Request $request)
    {
        $request->validate([
            'ONO' => 'required',
            'GLOBAL_COMMENT' => 'required',
            'RESULT_LIST' => 'required',
        ]);

        if ($request->GLOBAL_COMMENT === 'laborat-luar') {
            # simpan laborat luar
            // L : 13-18, P : 12-16 g/dl
            $temp = collect($request->RESULT_LIST);
            foreach ($temp as $key) {
                LaboratLuar::where(['nota' => $request->ONO, 'kd_lab' => $key['KODE_PRODUCT']])->update([
                    'hasil' => $key['FLAGE'] . " : " . $key['REF_RANGE'] . " " . $key['UNIT']
                ]);
            }
        } else {
            $temp = collect($request->RESULT_LIST);
            foreach ($temp as $key) {
                TransaksiLaborat::where(['rs2' => $request->ONO, 'rs4' => $key['KODE_PRODUCT']])->update([
                    'rs21' => $key['FLAGE'] . " : " . $key['REF_RANGE'] . " " . $key['UNIT']
                ]);
            }
        }

        event(new PlaygroundEvent('coba'));
        return response()->json(['message' => 'success'], 201);
    }

    public function tglSelesaiResep()
    {
        // $tgl=
        $data = Resepkeluarheder::select(
            'id',
            'noresep',
            'tgl_permintaan',
            'tgl',
            'tgl_kirim',
            'tgl_selesai',
            'updated_at',
            'ruangan',
            'flag',
        )
            ->whereNull('tgl_selesai')
            ->whereIn('flag', ['3', '4'])
            ->orderBy('tgl_kirim', 'DESC')
            // ->limit(5)
            ->get();
        foreach ($data as $key) {
            $jam = Carbon::parse($key['updated_at'])->format('H:i:s');
            // $key['jam'] = $jam;
            $tglSelesai = isNull($key['tgl']) ? Carbon::parse($key['updated_at'])->format('Y-m-d H:i:s') : Carbon::parse($key['tgl'])->format('Y-m-d ') . $jam;
            $key['tgl_selesai'] = $tglSelesai;
            $key->save();
            // $key->update([
            //     'tgl_selesai' => $tglSelesai
            // ]);
        }
        return [
            'count' => count($data),
            'data' => $data
        ];
    }
    public function hapusSKontrol()
    {
        $surat = request('nosurat');
        $noreg = request('noreg');
        $tgltobpjshttpres = DateHelper::getDateTime();
        $data = [
            "request" => [
                "t_suratkontrol" => [
                    "noSuratKontrol" => request('nosurat'),
                    "user" => "xxx"
                ]
            ]
        ];
        $insernokontrol = BridgingbpjsHelper::delete_url(
            'vclaim',
            'RencanaKontrol/Delete',
            $data
        );

        Bpjs_http_respon::create(
            [
                'method' => 'POST',
                'noreg' => $noreg ?? '',
                'request' => $data,
                'respon' => $insernokontrol,
                'url' => '/RencanaKontrol/Delete',
                'tgl' => $tgltobpjshttpres
            ]
        );
        return new JsonResponse([
            'no surat' => $surat,
            'noreg' => $noreg,
            'req' => $data,
            'res bpjs' => $insernokontrol
        ]);
    }
    public function httpRespBpjs()
    {
        $data = Bpjs_http_respon::where('noreg', request('noreg'))
            ->get();
        $wew = $data[0]->respon['response']['sep'];
        $poliBpjs = $wew['poli'];
        $nosep = $wew['noSep'];
        $dinsos = $wew['informasi']['dinsos'];
        $prolanisPRB = $wew['informasi']['prolanisPRB'];
        $noSKTM = $wew['informasi']['noSKTM'];
        $nosep = $wew['noSep'];

        return new JsonResponse([
            'poli' => $poliBpjs,
            'nosep' => $nosep,
            'dinsos' => $dinsos,
            'prolanisPRB' => $prolanisPRB,
            'noSKTM' => $noSKTM,
        ]);
    }
    public function wawan()
    {
        // $data = Pengguna::where('level_3', '<>', null)
        //     ->where('level_4', '=', null)
        //     ->get();
        // $koleksi = collect($data);
        // $draft = Permintaanruangan::where('reff', '=', 'TPN-l9pa1meah1nyu')
        //     ->where('status', '=', 1)
        //     // ->latest('id')->with(['details.barangrs', 'details.satuan', 'details.ruang', 'details.gudang'])->get();
        //     ->latest('id')->with(['details'])->get();
        // $kolek = collect($draft[0]->details)->groupBy('dari');
        // $apem = $draft[0];
        // $apem->details[0] = $kolek;
        // $draft[0]->gedung = $kolek;
        // $data = Permintaanruangan::where('status', '=', 1)
        //     ->with('details', 'pj', 'pengguna')->get();
        // if (count($data)) {
        //     foreach ($data as $key) {
        //         $key->gudang = collect($key->details)->groupBy('dari');
        //     }
        // }
        // $data = Kategory::with('pertama')->get();
        // $data = Prota::get();
        // $tahun = [];
        // foreach ($data as $key) {
        //     $temp = date('Y', strtotime($key->tgl_libur));
        //     array_push($tahun, $temp);
        // }
        // $ip2 = request()->ip();
        // $ip = $_SERVER['REMOTE_ADDR'];
        // $sekarang = date('W');
        // $tgl = '2022-11-17';
        // $mingguDepan = date('W', strtotime($tgl));

        // return new JsonResponse([
        //     'sekarang' => $sekarang,
        //     'next' => $mingguDepan
        //     // 'ip' => $ip,
        //     // 'ip2' => $ip2,
        //     // 'tahun' => array_unique($tahun),
        //     // 'data' => $data,
        //     // 'kolek' => $kolek,

        // ]);


        // bikin qr
        // $ip = request()->ip();
        // $date = date('Y-m-d H:i:s');
        // $nama = $ip . ' ' . $date;

        // $data = Qrcode::create([
        //     'ip' => $ip,
        //     'code' => $nama,
        //     // 'path' => 'qr/' . $nama . '.svg'
        // ]);
        // $data = JadwalController::toMatch(6, 'pulang');
        // $data = TransaksiAbsen::with('kategory')->find(2);
        // $data = Kategory::latest()->first();
        // event(new PlaygroundEvent($data));
        // broadcast(new newQrEvent($data));
        // return new JsonResponse($data, 200);
        // $data = DetailsPenerimaanruangan::distinct()->get(['kode_rs']);
        // "P-01020600"
        // $data = Penerimaanruangan::with('details')->get();
        // $collection = collect($data);
        // // $grouped = collect($data)->groupBy('kode_penanggungjawab');

        // $grouped = $collection->mapToGroups(function ($item, $key) {
        //     $clDet = collect($item['details']);
        //     $details = $clDet->groupBy('kode_rs');
        //     $details->sum('jumlah');
        //     return [
        //         $item['kode_penanggungjawab'] => [
        //             'all' => $clDet,
        //             'kode_rs' => $details,
        //         ]
        //     ];
        // });
        // $grouped->all();
        // $data = DetailsPenerimaanruangan::selectRaw('kode_rs, sum(jumlah) as jml')
        //     ->whereHas('penerimaanruangan', function ($wew) {
        //         $wew->where('kode_penanggungjawab', '=', 'P-01020600')
        //             ->where('status', '=', 1);
        //     })->groupBy('kode_rs')->get();

        // $data = Penerimaanruangan::select('kode_penanggungjawab')->with('pj')->distinct()->get();
        // $collection = collect($data);
        // $maping = $collection->map(function ($item, $key) {
        //     $decode = json_decode($item);
        //     $a = '';
        //     $b = '';
        //     foreach ($decode as $satu => $dua) {
        //         $a = $satu;
        //         $b = $dua;
        //     }
        //     $temp = strval($item);
        //     $temp1 = explode('{', $temp);
        //     $temp2 = explode('}', $temp1[1]);
        //     $temp3 = explode(':', $temp2[0]);
        //     return [
        //         'a' => $a,
        //         'b' => $b,
        //         'nama' => $temp3[0],
        //         'value' => $temp3[1],
        //         'item' => $item['pj']
        //     ];
        // });


        // return new JsonResponse(
        //     [
        //         'data' => $data,
        //         'maping' => $maping,
        //     ],
        //     200
        // );
        // $barangUser = MinMaxPengguna::distinct()->get('kode_rs');
        // $barangDepo = MinMaxDepo::distinct()->get('kode_rs');
        // $data = BarangRS::get('kode');
        // $barang = [];


        // $coll = collect($data);

        // $filteredUser = $coll->diffAssoc($barangUser);
        // // $filteredDepo = $coll->diff($barangDepo);
        // return new JsonResponse([
        //     'filtered user' => $filteredUser,
        //     // $coll,
        //     // 'filtrered depo' => $filteredDepo,
        //     'user' => $barangUser,
        //     'depo' => $barangDepo,
        //     'Rs' => $barang,
        // ]);
        // $data = DistribusiDepo::with('details')->find(1);
        // foreach ($data->details as $key) {
        //     $stok = RecentStokUpdate::where('kode_ruang', 'Gd-00000000')
        //         // ->where('kode_rs', $key->kode_rs)
        //         ->where('kode_rs', 'RS-00896')
        //         ->where('sisa_stok', '>', 0)
        //         // ->where('no_penerimaan', '4LH1E/12/12/2022')
        //         ->oldest()
        //         ->get();
        //     $collection = collect($stok)->sum('sisa_stok');
        // $collection->sum('sisa_stok');
        // $collection->only('sisa_stok');
        // $diStok = $stok->sisa_stok;
        // $jumlah = $key->jumlah;
        // $sisa = $diStok - $jumlah;

        // return new JsonResponse([$sisa, $jumlah, $diStok, $stok, $key, $data]);
        // return new JsonResponse([$data, $collection, $stok]);
        // $data = RecentStokUpdate::get();
        // $data = RecentStokUpdate::selectRaw('* , sum(sisa_stok) as stok')
        //     ->groupBy('kode_rs', 'kode_ruang')
        //     ->get();
        // $collection = collect($data)->unique('kode_rs');
        // $collection->values()->all();
        // return new JsonResponse([$data, $collection[0]]);
        // $umum = Gudang::where('gedung', 0)
        //     ->first();
        // $data = Gudang::where('gedung', 2)
        //     ->where('depo', '>', 0)
        //     ->get();
        // $data[count($data)] = $umum;
        // // array_push($data, $umum);
        // return new JsonResponse([
        //     $umum,
        //     count($data),
        //     $data,

        // ]);
        // $user = User::find(19);
        // $thisYear = request('tahun') ? request('tahun') : date('Y');
        // $month = request('bulan') ? request('bulan') : date('m');
        // $per_page = request('per_page') ? request('per_page') : 10;
        // $masuk = TransaksiAbsen::where('user_id', $user->id)
        //     ->whereDate('tanggal', '>=', $thisYear . '-' . $month . '-01')
        //     ->whereDate('tanggal', '<=', $thisYear . '-' . $month . '-31')
        //     // ->paginate($per_page);
        //     ->with('kategory')
        //     ->latest()
        //     ->get();


        // $data['masuk'] = $masuk;
        // $libur = Libur::where('user_id', $user->id)
        //     ->whereDate('tanggal', '>=', $thisYear . '-' . $month . '-01')
        //     ->whereDate('tanggal', '<=', $thisYear . '-' . $month . '-31')
        //     ->latest()
        //     ->get();

        // $data['libur'] = $libur;
        // return new JsonResponse($data);

        // $pegawai = Pegawai::where('aktif', 'AKTIF')
        //     ->where('account_pass', null)
        //     ->get();
        // $pegawai1 = Pegawai::where('aktif', 'AKTIF')
        //     ->where('account_pass', null)
        //     ->orWhere('account_pass', '')
        //     ->with('ruangan')
        //     ->get();


        // $data = collect($pegawai);
        // $excel = $data->only('nip', 'nip_baru', 'nama');
        // return Excel::download($excel, 'pegawai.xlsx');
        // return Excel::download(new pegawaiExport, 'pegawai.xlsx');
        // return view('list_user_not_registered', [
        //     'jml' => count($pegawai1),
        //     'pegawaies' => $pegawai1
        // ]);
        // return view('list_user_not_registered', [
        //     'jml' => count($pegawai),
        //     'pegawai' => $pegawai
        // ]);
        // return new JsonResponse([
        //     'jml' => count($pegawai),
        //     'jml1' => count($pegawai1),
        //     // 'pegawai' => $pegawai,
        //     'pegawai1' => $pegawai1
        // ]);

        // $data = BarangRS::oldest('id')
        //     ->filter(request(['q']))
        //     // ->with('satuan')
        //     ->paginate(request('per_page'));
        // // return BarangRSResource::collection($data);
        // $collect = collect($data);
        // $balik = $collect->only('data');
        // $balik['meta'] = $collect->except('data');
        // return new JsonResponse($balik);

        // $data = collect($mentah);
        // $data->groupBy('kode_gudang');
        // $data->all();
        // $mentah = MapingBarangDepo::with('barangrs.satuan', 'barangrs.barang108', 'gudang')->get();
        // $data = collect($mentah)->groupBy('kode_gudang');
        // return new JsonResponse($data);

        // cari pengguna dan penanggung jawab ruangan
        // $pengguna = PenggunaRuang::with('ruang', 'pengguna', 'penanggungjawab')->get();
        // $temp = collect($pengguna);
        // $apem = $temp->map(function ($item, $key) {
        //     if ($item->kode_penanggungjawab === null || $item->kode_penanggungjawab === '') {
        //         $item->kode_penanggungjawab = $item->kode_pengguna;
        //     }
        //     return $item;
        // });

        // $apem->all();
        // $group = $apem->groupBy('kode_penanggungjawab');

        // $rawStok = RecentStokUpdate::selectRaw('* , sum(sisa_stok) as stok')
        //     ->groupBy('kode_rs', 'kode_ruang')
        //     ->where('kode_ruang', 'LIKE', 'R-' . '%')
        //     ->get();
        // return new JsonResponse($rawStok);

        // cari ruangan yang punya stok

        // $raw = RecentStokUpdate::where('sisa_stok', '>', 0)
        //     ->with('depo', 'ruang')->get();
        // $data = collect($raw)->unique('kode_ruang');
        // $data->all();
        // return new JsonResponse($data);


        // $akses = User::find(12);
        // $pegawai = Pegawai::find($akses->pegawai_id);
        // $submenu = Access::where('role_id', $pegawai->role_id)->with('role', 'aplikasi', 'submenu.menu')->get();
        // $menu = Menu::get();

        // $col = collect($submenu);
        // $role = $col->map(function ($item, $key) {
        //     return $item->role;
        // })->unique();
        // $apli = $col->map(function ($item, $key) {
        //     if ($item->aplikasi !== null) {
        //         return $item->aplikasi;
        //     }
        // })->unique('id');
        // $subm = $col->map(function ($item, $key) {
        //     return $item->submenu;
        // });

        // // $menu = $col->map(function ($item, $key) {
        // //     return $item->submenu->menu;
        // // })->unique('id');
        // $into = $menu->map(function ($item, $key) use ($subm) {
        //     // $mbuh=[];
        //     $temp = $subm->where('menu_id', $item->id);
        //     $map = $temp->map(function ($ki, $ke) {
        //         return
        //             [
        //                 'nama' => $ki->nama,
        //                 'name' => $ki->name,
        //                 'icon' => $ki->icon,
        //                 'link' => $ki->link,

        //             ];
        //     });
        //     // $item->submenus = $temp;
        //     $apem = [
        //         'aplikasi_id' => $item->aplikasi_id,
        //         'nama' => $item->nama,
        //         'name' => $item->name,
        //         'icon' => $item->icon,
        //         'link' => $item->link,
        //         'submenus' => $map,
        //     ];
        //     return $apem;
        // });

        // $aplikasi = $apli->map(function ($item, $key) use ($into) {
        //     $mo = $into->where('aplikasi_id', $item->id);
        //     $map = $mo->map(function ($mbuh, $ke) {
        //         // return $mbuh;
        //         return [
        //             'nama' => $mbuh['nama'],
        //             'name' => $mbuh['name'],
        //             'icon' => $mbuh['icon'],
        //             'link' => $mbuh['link'],
        //             'submenus' => $mbuh['submenus'],

        //         ];
        //     });
        //     $kucur = [
        //         'aplikasi' => $item->aplikasi,
        //         'id' => $item->id,
        //         'nama' => $item->nama,
        //         'menus' => $map,
        //     ];
        //     return $kucur;
        // });
        // $data['user'] = $akses;
        // // $data['pegawai'] = $pegawai;
        // $data['role'] = $role;
        // $data['aplikasi'] = $aplikasi;
        // $data['into'] = $into;
        // $data['menu'] = $menu;
        // $data['sub'] = $subm;
        // $data['sumbenu'] = $submenu;

        // return new JsonResponse($data);
        // ambil berdasarkna kode rs
        // $kode_rs = 'RS-0974';
        // $kode_rs = 'RS-0982';
        // $data = DetailPermintaanruangan::SelectRaw('*, sum(jumlah) as jml, sum(jumlah_disetujui) as disetujui')
        // })->where('kode_rs', $kode_rs)->groupBy('kode_rs')->get();

        /*
        * hitung alokasi
        */
        // $kode_rs = '';
        // $kode_ruangan = '';
        // $permintaan = Permintaanruangan::where('status', '=', 5)
        //     ->with('details.barangrs', 'details.satuan', 'pj', 'pengguna')->get();


        // // ambil data barang
        // $barang = BarangRS::where('kode', $kode_rs)->first();

        // // cari barang ini masuk depo mana
        // $depo = MapingBarangDepo::where('kode_rs', $kode_rs)->first();

        // // ambil stok ruangan
        // $stokRuangan = RecentStokUpdate::where('kode_rs', $kode_rs)
        //     ->where('kode_ruang', $kode_ruangan)->get();
        // $totalStokRuangan = collect($stokRuangan)->sum('sisa_stok');
        // // cari stok di depo
        // $stok = RecentStokUpdate::where('kode_rs', $kode_rs)
        //     ->where('kode_ruang', $depo->kode_gudang)->get();
        // $totalStok = collect($stok)->sum('sisa_stok');

        // // ambil alokasi barang
        // $data = DetailPermintaanruangan::whereHas('permintaanruangan', function ($q) {
        //     $q->where('status', '>=', 5)
        //         ->where('status', '<', 7);
        // })->where('kode_rs', $kode_rs)->get();
        // $col = collect($data);
        // $gr = $col->map(function ($item) {
        //     $jumsem = $item->jumlah_disetujui ? $item->jumlah_disetujui : $item->jumlah;
        //     $item->alokasi = $jumsem;
        //     return $item;
        // });
        // $sum = $gr->sum('alokasi');
        // $alokasi = 0;
        // // hitung alokasi
        // if ($totalStok >= $sum) {
        //     $alokasi =  $totalStok - $sum;
        // } else {
        //     $alokasi = 0;
        // }

        // $barang->alokasi = $alokasi;
        // $barang->stok = $totalStok;
        // $barang->stokRuangan = $totalStokRuangan;
        // $balik['barang'] = $barang;
        // $balik['permintaan'] = $permintaan;
        // $balik['totalStok'] = $totalStok;
        // $balik['stok'] = $stok;
        // $balik['depo'] = $depo;
        // $balik['sum'] = $sum;
        // $balik['gr'] = $gr;
        // $balik['data'] = $data;
        // return new JsonResponse($balik);

        /*
        * hitung mundur jam
        */

        // $now = date('d-m-Y H:i:s');
        // $str = strtotime($now);
        // $yst = date('d-m-Y H:i:s', $str);
        // $tgl = strtotime('23-01-2023 07:56:23');
        // $diff = round(($str - $tgl) / 3600, 1);
        // return new JsonResponse([
        //     $now,
        //     $str,
        //     $yst,
        //     $tgl,
        //     $diff
        // ]);

        /** cari permintaan ruangan */

        // $permintaanRuangan = Permintaanruangan::with('details')->find(9);
        // return new JsonResponse($permintaanRuangan);


        /** cari alokasi penerimaan ruangn */

        // $data = Permintaanruangan::where('status', '=', 7)
        //     ->with('details.barangrs.barang108', 'details.barangrs.satuan', 'pj', 'pengguna')->get();
        // // $col = collect($data);

        // foreach ($data as $key) {
        //     foreach ($key->details as $detail) {
        //         $temp = StockController::getDetailsStok($detail['kode_rs'], $detail['tujuan']);
        //         $max = MaxRuangan::where('kode_rs', $detail['kode_rs'])->where('kode_ruang', $detail['tujuan'])->first();
        //         $detail['barangrs']->maxStok = $max->max_stok;
        //         $detail['barangrs']->alokasi = $temp->alokasi;
        //         $detail['barangrs']->stokDepo = $temp->stok;
        //         $detail['barangrs']->stokRuangan = $temp->stokRuangan;
        //     }
        // }
        // return new JsonResponse($data);

        /** Cari banrang yang punya stok */
        // cari depo dibawah gudang habis pakai
        // $depos = Gudang::where('gedung', 2)
        //     ->where('lantai', 1)
        //     ->where('gudang', 1)
        //     ->where('depo', '>', 0)
        //     ->get();
        // $stok = [];
        // foreach ($depos as $depo) {
        //     $temp = RecentStokUpdate::where('sisa_stok', '>', 0)
        //         ->where('kode_ruang', $depo->kode)
        //         ->get();
        //     array_push($stok, $temp);
        // }

        // return new JsonResponse([
        //     $stok,
        //     $depos,
        // ]);

        // $user = User::find(3);
        // $pegawai = Pegawai::with('ruang', 'mapingpengguna')->find($user->pegawai_id);

        // $data['user'] = $user;
        // $data['pegawai'] = $pegawai;
        // return new JsonResponse($data);
        // $kode_rs = [
        //     'RS-2218',
        //     'RS-2219',
        //     'RS-2220',
        //     'RS-2221',
        //     'RS-2222',
        //     'RS-2224',
        //     'RS-2232',
        //     'RS-2233',
        //     'RS-2237',
        //     'RS-2246',
        //     'RS-2247',
        //     'RS-2248',
        //     'RS-2251',
        //     'RS-2252',
        //     'RS-2253',
        //     'RS-2254',
        //     'RS-2255',
        //     'RS-2256',
        //     'RS-2265',
        //     'RS-2266',
        //     'RS-2267',
        //     'RS-2281',
        //     'RS-2282',
        //     'RS-2283',
        //     'RS-2284',
        //     'RS-2291',
        //     'RS-2292',
        //     'RS-2293',
        //     'RS-2294',
        //     'RS-2295',
        //     'RS-2296',
        //     'RS-2299',
        //     'RS-2316',
        //     'RS-2317',
        //     'RS-2318',
        //     'RS-2319',
        //     'RS-2320',
        //     'RS-2321',
        //     'RS-2323',
        //     'RS-2324',
        //     'RS-2325',
        //     'RS-2326',
        //     'RS-2327',
        //     'RS-2328',
        //     'RS-2329',
        //     'RS-2330',
        //     'RS-2331',
        //     'RS-2338',
        //     'RS-2339',
        //     'RS-2340',
        //     'RS-2341',
        //     'RS-2346',
        //     'RS-2347',
        //     'RS-2348',
        //     'RS-2349',
        //     'RS-2350',
        //     'RS-2351',
        //     'RS-2352',
        //     'RS-2353',
        //     'RS-2373',
        //     'RS-2374',
        //     'RS-3730',
        //     'RS-3747',
        //     'RS-3749',
        //     'RS-3750',
        //     'RS-3763',
        //     'RS-3764',
        //     'RS-3765',
        //     'RS-3766',
        //     'RS-3767',
        //     'RS-3776',
        //     'RS-3777',
        //     'RS-3778',
        //     'RS-3779',
        //     'RS-3780',
        //     'RS-3784',
        //     'RS-3785',
        //     'RS-3787',
        //     'RS-3789',
        //     'RS-3820',
        //     'RS-3821',
        //     'RS-3822',
        //     'RS-3823',
        //     'RS-3824',
        //     'RS-3827',
        //     'RS-3830',
        //     'RS-3831',
        //     'RS-3832',
        //     'RS-3833',
        //     'RS-3835',
        //     'RS-3836',
        //     'RS-3845',
        //     'RS-3847',
        //     'RS-3848',
        // ];
        // $rs_maping = [
        //     'RS-3903',
        //     'RS-3904',
        //     'RS-3905',
        //     'RS-3906',
        //     'RS-3907',
        //     'RS-3908',
        //     'RS-3909',
        //     'RS-3910',
        //     'RS-3911',
        //     'RS-3912',
        //     'RS-3913',
        //     'RS-3914',
        //     'RS-3915',
        //     'RS-3916',
        //     'RS-3917',
        //     'RS-3918',
        //     'RS-3919',
        //     'RS-3920',
        //     'RS-3921',
        //     'RS-3922',
        //     'RS-3923',
        //     'RS-3924',
        //     'RS-3925',
        //     'RS-3926',
        //     'RS-3927',
        //     'RS-3928',
        //     'RS-3929',
        //     'RS-3930',
        //     'RS-3931',
        //     'RS-3932',
        //     'RS-3933',
        //     'RS-3934',
        //     'RS-3935',
        //     'RS-3936',
        //     'RS-3937',
        //     'RS-3938',
        //     'RS-3939',
        //     'RS-3940',
        //     'RS-3941',
        //     'RS-3942',
        //     'RS-3943',
        //     'RS-3944',
        //     'RS-3945',
        //     'RS-3946',
        //     'RS-3947',
        //     'RS-3948',
        //     'RS-3949',
        //     'RS-3950',
        //     'RS-3951',
        //     'RS-3952',
        //     'RS-3953',
        //     'RS-3954',
        //     'RS-3955',
        //     'RS-3956',
        //     'RS-3957',
        //     'RS-3958',
        //     'RS-3959',
        //     'RS-3960',
        //     'RS-3961',
        //     'RS-3962',
        //     'RS-3963',
        //     'RS-3964',
        //     'RS-3965',
        //     'RS-3966',
        //     'RS-3967',
        //     'RS-3968',
        //     'RS-3969',
        //     'RS-3970',
        //     'RS-3971',
        //     'RS-3972',
        //     'RS-3973',
        //     'RS-3974',
        //     'RS-3975',
        //     'RS-3976',
        //     'RS-3977',
        //     'RS-3978',
        //     'RS-3979',
        //     'RS-3980',
        //     'RS-3981',
        //     'RS-3982',
        //     'RS-3983',
        //     'RS-3984',
        //     'RS-3985',
        //     'RS-3986',
        //     'RS-3987',
        //     'RS-3988',
        //     'RS-3989',
        //     'RS-3990',
        //     'RS-3991',
        //     'RS-3992',
        //     'RS-3993',
        //     'RS-3994',
        //     'RS-3995',
        //     'RS-3996',
        //     'RS-3997',
        //     'RS-3998',
        //     'RS-3999',
        //     'RS-4000',
        //     'RS-4001',
        //     'RS-4002',
        //     'RS-4003',
        //     'RS-4004',
        //     'RS-4005',
        //     'RS-4006',
        //     'RS-4007',
        //     'RS-4008',
        //     'RS-4009',
        //     'RS-4010',
        //     'RS-4011',
        //     'RS-4012',
        //     'RS-4013',
        //     'RS-4014',
        //     'RS-4015',
        //     'RS-4016',
        //     'RS-4017',
        //     'RS-4018',
        //     'RS-4019',
        //     'RS-4020',
        //     'RS-4021',
        //     'RS-4022',
        //     'RS-4023',
        //     'RS-4024',
        //     'RS-4025',
        //     'RS-4026',
        //     'RS-4027',
        //     'RS-4028',
        //     'RS-4029',
        //     'RS-4030',
        //     'RS-4031',
        //     'RS-4032',
        //     'RS-4033',
        //     'RS-4034',
        //     'RS-4035',
        //     'RS-4036',
        //     'RS-4037',
        //     'RS-4038',
        //     'RS-4039',
        //     'RS-4040',
        //     'RS-4041',
        //     'RS-4042',
        //     'RS-4043',
        //     'RS-4044',
        //     'RS-4045',
        //     'RS-4046',
        //     'RS-4047',
        //     'RS-4048',
        //     'RS-4049',
        //     'RS-4050',
        //     'RS-4051',
        //     'RS-4052',
        //     'RS-4053',
        //     'RS-4054',
        //     'RS-4055',
        //     'RS-4056',
        //     'RS-4057',
        //     'RS-4058',
        //     'RS-4059',
        //     'RS-4060',
        //     'RS-4061',
        //     'RS-4062',
        //     'RS-4063',
        //     'RS-4064',
        //     'RS-4065',
        //     'RS-4066',
        //     'RS-4067',
        //     'RS-4068',
        //     'RS-4069',
        //     'RS-4070',
        //     'RS-4071',
        //     'RS-4072',
        //     'RS-4073',
        //     'RS-4074',
        //     'RS-4075',
        //     'RS-4076',
        //     'RS-4077',
        //     'RS-4078',
        //     'RS-4079',
        //     'RS-4080',
        //     'RS-4081',
        //     'RS-4082',
        //     'RS-4083',
        //     'RS-4084',
        //     'RS-4085',
        //     'RS-4086',
        //     'RS-4087',
        //     'RS-4088',
        //     'RS-4089',
        //     'RS-4090',
        //     'RS-4091',
        //     'RS-4092',
        //     'RS-4093',
        //     'RS-4094',
        //     'RS-4095',
        //     'RS-4096',
        //     'RS-4097',
        //     'RS-4098',
        //     'RS-4099',
        //     'RS-4100',
        //     'RS-4101',
        //     'RS-4102',
        //     'RS-4103',
        //     'RS-4104',
        //     'RS-4105',
        //     'RS-4106',
        //     'RS-4107',
        //     'RS-4108',
        //     'RS-4109',
        //     'RS-4110',
        //     'RS-4111',
        //     'RS-4112',
        //     'RS-4113',
        //     'RS-4114',
        //     'RS-4115',
        //     'RS-4116',
        //     'RS-4117',
        //     'RS-4118',
        //     'RS-4119',
        //     'RS-4120',
        //     'RS-4121',
        //     'RS-4122',
        //     'RS-4123',
        //     'RS-4124',
        //     'RS-4125',
        //     'RS-4126',
        //     'RS-4127',
        //     'RS-4128',
        //     'RS-4129',
        //     'RS-4130',
        //     'RS-4131',
        //     'RS-4132',
        //     'RS-4133',
        //     'RS-4134',
        //     'RS-4135',
        //     'RS-4136',
        //     'RS-4137',
        //     'RS-4138',
        //     'RS-4139',
        //     'RS-4140',
        //     'RS-4141',
        //     'RS-4142',
        //     'RS-4143',
        //     'RS-4144',
        //     'RS-4145',
        //     'RS-4146',
        //     'RS-4147',
        //     'RS-4148',
        //     'RS-4149',
        //     'RS-4150',
        //     'RS-4151',
        //     'RS-4152',
        //     'RS-4153',
        //     'RS-4154',
        //     'RS-4155',
        //     'RS-4156',
        //     'RS-4157',
        //     'RS-4158',
        //     'RS-4159',
        //     'RS-4160',
        //     'RS-4161',
        //     'RS-4162',
        //     'RS-4163',
        //     'RS-4164',
        //     'RS-4165',
        //     'RS-4166',
        //     'RS-4167',
        //     'RS-4168',
        //     'RS-4169',
        //     'RS-4170',
        //     'RS-4171',
        //     'RS-4172',
        //     'RS-4173',
        //     'RS-4174',
        //     'RS-4175',
        //     'RS-4176',
        //     'RS-4177',
        //     'RS-4178',
        //     'RS-4179',
        //     'RS-4180',
        //     'RS-4181',
        //     'RS-4182',
        //     'RS-4183',
        //     'RS-4184',
        //     'RS-4185',
        //     'RS-4186',
        //     'RS-4187',
        //     'RS-4188',
        //     'RS-4189',
        //     'RS-4190',
        //     'RS-4191',
        //     'RS-4192',
        //     'RS-4193',
        //     'RS-4194',
        //     'RS-4195',
        //     'RS-4196',
        //     'RS-4197',
        //     'RS-4198',
        //     'RS-4199',
        //     'RS-4200',
        //     'RS-4201',
        //     'RS-4202',
        //     'RS-4203',
        //     'RS-4204',
        //     'RS-4205',
        //     'RS-4206',
        //     'RS-4207',
        //     'RS-4208',
        //     'RS-4209',
        //     'RS-4210',
        //     'RS-4211',
        //     'RS-4212',
        //     'RS-4213',
        //     'RS-4214',
        //     'RS-4215',
        //     'RS-4216',
        //     'RS-4217',
        //     'RS-4218',
        //     'RS-4219',
        //     'RS-4220',
        //     'RS-4221',
        //     'RS-4222',
        //     'RS-4223',
        //     'RS-4224',
        //     'RS-4225',
        //     'RS-4226',
        //     'RS-4227',
        //     'RS-4228',
        //     'RS-4229',
        //     'RS-4230',
        //     'RS-4231',
        //     'RS-4232',
        //     'RS-4233',
        //     'RS-4234',
        //     'RS-4235',
        //     'RS-4236',
        //     'RS-4237',
        //     'RS-4238',
        //     'RS-4239',
        //     'RS-4240',
        //     'RS-4241',
        //     'RS-4242',
        //     'RS-4243',
        //     'RS-4244',
        //     'RS-4245',
        //     'RS-4246',
        //     'RS-4247',
        //     'RS-4248',
        //     'RS-4249',
        //     'RS-4250',
        //     'RS-4251',
        //     'RS-4252',
        //     'RS-4253',
        //     'RS-4254',
        //     'RS-4255',
        //     'RS-4256',
        //     'RS-4257',
        //     'RS-4258',
        //     'RS-4259',
        //     'RS-4260',
        //     'RS-4261',
        //     'RS-4262',
        //     'RS-4263',
        //     'RS-4264',
        //     'RS-4265',
        //     'RS-4266',
        //     'RS-4267',
        //     'RS-4268',
        //     'RS-4269',
        //     'RS-4270',
        //     'RS-4271',
        //     'RS-4272',
        //     'RS-4273',
        //     'RS-4274',
        //     'RS-4275',
        //     'RS-4276',
        //     'RS-4277',
        //     'RS-4278',
        //     'RS-4279',
        //     'RS-4280',
        //     'RS-4281',
        //     'RS-4282',
        //     'RS-4283',
        //     'RS-4284',
        //     'RS-4285',
        //     'RS-4286',
        //     'RS-4287',
        //     'RS-4288',
        //     'RS-4289',
        //     'RS-4290',
        //     'RS-4291',
        //     'RS-4292',
        //     'RS-4293',
        //     'RS-4294',
        //     'RS-4295',
        //     'RS-4296',
        //     'RS-4297',
        //     'RS-4298',
        //     'RS-4299',
        //     'RS-4300',
        //     'RS-4301',
        //     'RS-4302',
        //     'RS-4303',
        //     'RS-4304',
        //     'RS-4305',
        //     'RS-4306',
        //     'RS-4307',
        //     'RS-4308',
        //     'RS-4309',
        //     'RS-4310',
        //     'RS-4311',
        //     'RS-4312',
        //     'RS-4313',
        //     'RS-4314',
        //     'RS-4315',
        //     'RS-4316',
        //     'RS-4317',
        //     'RS-4318',
        //     'RS-4319',
        //     'RS-4320',
        //     'RS-4321',
        //     'RS-4322',
        //     'RS-4323',
        //     'RS-4324',
        //     'RS-4325',
        //     'RS-4326',
        //     'RS-4327',
        //     'RS-4328',
        //     'RS-4329',
        //     'RS-4330',
        //     'RS-4331',
        //     'RS-4332',
        //     'RS-4333',
        //     'RS-4334',
        //     'RS-4335',
        //     'RS-4336',
        //     'RS-4337',
        //     'RS-4338',
        //     'RS-4339',
        //     'RS-4340',
        //     'RS-4341',
        //     'RS-4342',
        //     'RS-4343',
        //     'RS-4344',
        //     'RS-4345',
        //     'RS-4346',
        //     'RS-4347',
        //     'RS-4348',
        //     'RS-4349',
        //     'RS-4350',
        //     'RS-4351',
        //     'RS-4352',
        //     'RS-4353',
        //     'RS-4354',
        //     'RS-4355',
        //     'RS-4356',
        //     'RS-4357',
        //     'RS-4358',
        //     'RS-4359',
        //     'RS-4360',
        //     'RS-4361',
        //     'RS-4362',
        //     'RS-4363',
        //     'RS-4364',
        //     'RS-4365',
        //     'RS-4366',
        //     'RS-4367',
        //     'RS-4368',
        //     'RS-4369',
        //     'RS-4370',
        //     'RS-4371',
        //     'RS-4372',
        //     'RS-4373',
        //     'RS-4374',
        //     'RS-4375',
        //     'RS-4376',
        //     'RS-4377',
        //     'RS-4378',
        //     'RS-4379',
        //     'RS-4380',
        //     'RS-4381',
        //     'RS-4382',
        //     'RS-4383',
        //     'RS-4384',
        //     'RS-4385',
        //     'RS-4386',
        //     'RS-4387',
        //     'RS-4388',
        //     'RS-4389',
        //     'RS-4390',
        //     'RS-4391',
        //     'RS-4392',
        //     'RS-4393',
        //     'RS-4394',
        //     'RS-4395',
        //     'RS-4396',
        //     'RS-4397',
        //     'RS-4398',
        //     'RS-4399',
        //     'RS-4400',
        //     'RS-4401',
        //     'RS-4402',
        //     'RS-4403',
        //     'RS-4404',
        //     'RS-4405',
        //     'RS-4406',
        //     'RS-4407',
        //     'RS-4408',
        //     'RS-4409',
        //     'RS-4410',
        //     'RS-4411',
        //     'RS-4412',
        //     'RS-4413',
        //     'RS-4414',
        //     'RS-4415',
        //     'RS-4416',
        //     'RS-4417',
        //     'RS-4418',
        //     'RS-4419',
        //     'RS-4420',
        //     'RS-4421',
        //     'RS-4422',
        //     'RS-4423',
        //     'RS-4424',
        //     'RS-4425',
        //     'RS-4426',
        //     'RS-4427',
        //     'RS-4428',
        //     'RS-4429',
        //     'RS-4430',
        //     'RS-4431',
        //     'RS-4432',
        //     'RS-4433',
        //     'RS-4434',
        //     'RS-4435',
        //     'RS-4436',
        //     'RS-4437',
        //     'RS-4438',
        //     'RS-4439',
        //     'RS-4440',
        //     'RS-4441',
        //     'RS-4442',
        //     'RS-4443',
        //     'RS-4444',
        //     'RS-4445',
        //     'RS-4446',
        //     'RS-4447',
        //     'RS-4448',
        //     'RS-4449',
        //     'RS-4450',
        //     'RS-4451',
        //     'RS-4452',
        //     'RS-4453',
        //     'RS-4454',
        //     'RS-4455',
        //     'RS-4456',
        //     'RS-4457',
        //     'RS-4458',
        //     'RS-4459',
        //     'RS-4460',
        //     'RS-4461',
        //     'RS-4462',
        //     'RS-4463',
        //     'RS-4464',
        //     'RS-4465',
        //     'RS-4466',
        //     'RS-4467',
        //     'RS-4468',
        //     'RS-4469',
        //     'RS-4470',
        //     'RS-4471',
        //     'RS-4472',
        //     'RS-4473',
        //     'RS-4474',
        //     'RS-4475',
        //     'RS-4476',
        //     'RS-4477',
        //     'RS-4478',
        //     'RS-4479',
        //     'RS-4480',
        //     'RS-4481',
        //     'RS-4482',
        //     'RS-4483',
        //     'RS-4484',
        //     'RS-4485',
        //     'RS-4486',
        //     'RS-4487',
        //     'RS-4488',
        //     'RS-4489',
        //     'RS-4490',
        //     'RS-4491',
        //     'RS-4492',
        //     'RS-4493',
        //     'RS-4494',
        //     'RS-4495',
        //     'RS-4496',
        //     'RS-4497',
        //     'RS-4498',
        //     'RS-4499',
        //     'RS-4500',
        //     'RS-4501',
        //     'RS-4502',
        //     'RS-4503',
        //     'RS-4504',
        //     'RS-4505',
        //     'RS-4506',
        //     'RS-4507',

        // ];
        // $all = [];
        // foreach ($kode_rs as $key) {
        //     $barang = BarangRS::where('kode', $key)->first();
        //     $barang->update(['tipe' => 'basah']);
        //     array_push($all, $barang);
        //     // return new JsonResponse($barang);
        // }
        // foreach ($rs_maping as $key) {
        //     $barang = MapingBarangDepo::where('kode_rs', $key)->first();
        //     $barang->update(['kode_gudang' => 'Gd-02010103']);
        //     array_push($all, $barang);
        //     // return new JsonResponse($barang);
        // }
        // foreach ($rs_maping as $key) {
        //     $barang = RecentStokUpdate::where('kode_rs', $key)->first();
        //     if ($barang) {
        //         $barang->update(['kode_ruang' => 'Gd-02010103']);
        //     }
        //     array_push($all, $barang);
        //     // return new JsonResponse($barang);
        // }
        // foreach ($rs_maping as $key) {
        //     $barang = MonthlyStokUpdate::where('kode_rs', $key)->first();
        //     if ($barang) {
        //         $barang->update(['kode_ruang' => 'Gd-02010103']);
        //     }
        //     array_push($all, $barang);
        //     // return new JsonResponse($barang);
        // }


        // return new JsonResponse($all);
        // return new JsonResponse([$_GET, request()->all()]);
        // $data = BarangRS::oldest('id')->with('barang108', 'satuan', 'satuankecil')->get(); //paginate(request('per_page'));
        // $data = BarangRS::with('barang108', 'satuan', 'satuankecil')->get(); //paginate(request('per_page'));
        // return BarangRSResource::collection($data);
        // return new JsonResponse($data);
        // return new JsonResponse($kode_rs);
        // $permintaan = Permintaanruangan::with('details')->get();
        // $col = collect($permintaan)->map(function ($item, $a) {
        //     return $item->id;
        // });
        // foreach ($col as $key) {
        //     $minta = Permintaanruangan::find($key);
        //     $det = DetailPermintaanruangan::where('permintaanruangan_id', $key)->first();
        //     $minta->update([
        //         'dari' => $det->dari,
        //         'kode_ruang' => $det->tujuan
        //     ]);
        //     // return new JsonResponse([$minta, $det]);
        // }
        // $data['count'] = count($permintaan);
        // $data['col'] = $col;
        // $data['permintaan'] = $permintaan;
        // return new JsonResponse($data);
        // $before = RecentStokUpdate::selectRaw('* , sum(sisa_stok) as stok');
        // $raw = $before->where('sisa_stok', '>', 0)
        //     ->where('kode_ruang', '<>', 'Gd-02010100')
        //     ->groupBy('kode_ruang')
        //     ->with(
        //         'barang.barang108',
        //         'barang.satuan',
        //         'depo',
        //         'barang.mapingdepo.gudang',
        //         'ruang'
        //     )
        //     ->get();
        // $col = collect($raw);
        // $data = $col->unique('kode_ruang');
        // $data->all();
        // return new JsonResponse(['col' => $col, 'data' => $data]);

        // $pegawai = Pegawai::find(1539);
        // $pengguna = PenggunaRuang::where('kode_ruang', $pegawai->kode_ruang)->first();
        // $ruang = PenggunaRuang::where('kode_pengguna', $pengguna->kode_pengguna)->get();
        // $raw = collect($ruang);
        // $only = $raw->map(function ($y) {
        //     return $y->kode_ruang;
        // });
        // // R-0101071

        // return new JsonResponse([
        //     'only' => $only,
        //     'pegawai' => $pegawai,
        //     'pengguna' => $pengguna,
        //     'ruang' => $ruang,
        // ]);
        // $distr = DistribusiLangsung::where(
        //     'reff',
        //     'DSTL-leyclbx3cnjkb'
        // )->first();

        // return new JsonResponse([
        //     'id' => $distr->id,
        //     'distr' => $distr,
        // ]);
        // $ps = Pemesanan::where('reff', 'TRP-le6f72emqmekz')->with('details')->first();
        // $de = collect($ps->details);
        // $det = $de->map(function ($x) {
        //     return $x->kode_rs;
        // });
        // $raw = Gudang::where('gedung', 2)
        //     ->where('lantai', '>', 0)
        //     ->where('gudang', '>', 0)
        //     ->where('depo', '>', 0)
        //     ->get();
        // $wew = collect($raw);
        // $depos = $wew->map(function ($anu) {
        //     return $anu->kode;
        // });
        // $data['depos'] = $depos;
        // $data['ps'] = $ps;
        // $data['det'] = $det;
        // return new JsonResponse($data);

        //$data = Penerimaan::selectRaw('nomor')->where('nomor', '000.3.2/02.0/10/SP-GIZI/1.02.2.14.0.00.03.0301/II/2023')->count();
        //return new JsonResponse($data);
        // $today = date('l');
        // $date = date('Y-m-d');
        // $jadwal = JadwalAbsen::where('day', $today)
        //     ->where('status', 2)
        //     ->get();
        // $absen = TransaksiAbsen::where('tanggal', $date)->get();
        // $peg = collect($absen)->map(function ($x) {
        //     return $x->pegawai_id;
        // });
        // $not = collect($jadwal)->whereNotIn('pegawai_id', $peg);
        // foreach ($not as $tidak) {
        //     $anu = Alpha::firstOrCreate(
        //         [
        //             'pegawai_id' => $tidak->pegawai_id,
        //             'tanggal' => $date
        //         ],
        //         ['flag' => 'ABSEN']
        //     );
        // }
        // $tidakDaftar = Pegawai::where('account_pass', '')->where('aktif', 'AKTIF')->get();
        // foreach ($tidakDaftar as $tidak) {
        //     Alpha::updateOrCreate(
        //         [
        //             'pegawai_id' => $tidak->id,
        //             'tanggal' => $date
        //         ],
        //         ['flag' => 'ABSEN']
        //     );
        // }

        // $data['tidak masuk'] = Alpha::where('tanggal', $date)->get();
        // $data['count'] = count($tidakDaftar);
        // $data['tidakDaftar'] = $tidakDaftar;
        // $data['not'] = $not;
        // $data['peg'] = $peg;
        // $data['today'] = $today;
        // $data['date'] = $date;
        // $data['jadwal'] = $jadwal;
        // $data['absen'] = $absen;
        // return new JsonResponse($data);

        // $data = TransaksiGudangController::fromPenerimaan(177);
        // $kd_depo = request('kd_tempat');
        // $kd_depo = 'Gd-02010103';
        // // $kd_barang = request('kd_barang');
        // $kd_barang = 'RS-3974';
        // // $bln    = request('bln');
        // $bln    = 6;
        // $thn    = 2023;

        // if ($bln == 1) {
        //     $blnx = 12;
        //     $thnx = $thn - 1;
        // } else {
        //     $blnx = $bln - 1;
        //     $thnx = $thn;
        // }
        // $data = MonthlyStokUpdate::where('kode_rs', $kd_barang)
        //     ->whereMonth('monthly_stok_updates.tanggal', $blnx)
        //     ->whereYear('monthly_stok_updates.tanggal', $thnx)
        //     ->where('monthly_stok_updates.kode_ruang', '=', $kd_depo)->get();

        // $akhir = MonthlyStokUpdate::where('kode_rs', $kd_barang)
        //     ->whereMonth('monthly_stok_updates.tanggal', $bln)
        //     ->whereYear('monthly_stok_updates.tanggal', $thn)
        //     ->where('monthly_stok_updates.kode_ruang', '=', $kd_depo)->get();
        // return new JsonResponse([
        //     'bln' => $bln,
        //     'blnx' => $blnx,
        //     'thn' => $thn,
        //     'thnx' => $thnx,
        //     'awal' => $data,
        //     'akhir' => $akhir,
        // ]);
        // $tanggal = request('tahun') . '-' . request('bulan') . '-' . date('d');
        // // $today = request('tahun') ? $tanggal : date('Y-m-d');
        // $today = date('2023-05-31');
        // $lastDay = date('Y-m-t', strtotime($today));
        // $dToday = date_create($today);
        // $dLastDay = date_create($lastDay);
        // $diff = date_diff($dToday, $dLastDay);

        // return new JsonResponse([
        //     'tanggal' => $tanggal,
        //     'today' => $today,
        //     'dToday' => $dToday,
        //     'lastDay' => $lastDay,
        //     'dLastDay' => $dLastDay,
        //     'diff d' => $diff->d,
        //     'diff m' => $diff->m,
        //     'diff y' => $diff->y,
        //     'diff ' => $diff,

        // ]);
        // $recent = RecentStokUpdate::where('sisa_stok', '>', 0)
        //     ->where('kode_ruang', 'like', '%Gd-%')
        //     ->where('kode_rs', 'like', '%4637%')
        //     ->with('barang')
        //     ->get();

        // $month = MonthlyStokUpdate::where('sisa_stok', '>', 0)
        //     ->where('kode_ruang', 'like', '%Gd-%')
        //     ->where('kode_rs', 'like', '%4637%')
        //     ->with('barang')
        //     ->get();


        // return new JsonResponse(['month' => $month, 'recent' => $recent]);

        // $tanggal = request('tahun') . '-' . request('bulan') . '-' . date('d');
        // $today = request('tahun') ? $tanggal : date('Y-m-d');
        // // $today = date('2023-08-01');
        // $yesterday = date('Y-m-d', strtotime('-1 days'));
        // // $lastDay = date('Y-m-t', strtotime($today));
        // $firstDay = date('Y-m-01', strtotime($today));
        // $dToday = date_create($today);
        // $dLastDay = date_create($firstDay);
        // $diff = date_diff($dToday, $dLastDay);

        // return new JsonResponse([
        //     'tanggal' => $tanggal,
        //     'today' => $today,
        //     'yesterday' => $yesterday,
        //     // 'lastDay' => $lastDay,
        //     'firstDay' => $firstDay,
        //     'dToday' => $dToday,
        //     'dLastDay' => $dLastDay,
        //     'diff' => $diff,
        // ]);
        // $sep = '1327R0010523V004291';
        // $tgl = DateHelper::getDateTime();
        // $a = BridgingbpjsHelper::get_url('vclaim', 'SEP/' . $sep);
        // // return $a;

        // Bpjs_http_respon::create(
        //     [
        //         'method' => 'POST',
        //         'noreg' => 'noreg',
        //         'request' => $sep,
        //         'respon' => $a,
        //         'url' => '/SEP',
        //         'tgl' => $tgl
        //     ]
        // );
        // return new JsonResponse('sudah lagi');
        // $noreg = "60192/08/2023/J";
        // $data = [
        //     "kodebooking" => "60192\/08\/2023\/J",
        //     "jenispasien" => "JKN",
        //     "nomorkartu" => "0000112197227",
        //     "nik" => "3513176312900003",
        //     "nohp" => "082332922520",
        //     "kodepoli" => "BDM",
        //     "namapoli" => "GIGI BEDAH MULUT",
        //     "pasienbaru" => 0,
        //     "norm" => "253555",
        //     "tanggalperiksa" => "2023-08-18",
        //     "kodedokter" => "427875",
        //     "namadokter" => '',
        //     "jampraktek" => '',
        //     "jeniskunjungan" => 1,
        //     "nomorreferensi" => "132822020823P000243",
        //     "nomorantrean" => "B071",
        //     "angkaantrean" => 71,
        //     "estimasidilayani" => 1692292200000,
        //     "sisakuotajkn" => 15,
        //     "kuotajkn" => 16,
        //     "sisakuotanonjkn" => 3,
        //     "kuotanonjkn" => 4,
        //     "keterangan" => "Peserta harap 30 menit lebih awal guna pencatatan administrasi."
        // ];
        // $tgltobpjshttpres = DateHelper::getDateTime();
        // $ambilantrian = BridgingbpjsHelper::post_url(
        //     'antrean',
        //     'antrean/add',
        //     $data
        // );

        // $simpanbpjshttprespon = Bpjs_http_respon::create(
        //     [
        //         'noreg' => $noreg,
        //         'method' => 'POST',
        //         'request' => $data,
        //         'respon' => $ambilantrian,
        //         'url' => '/antrean/add',
        //         'tgl' => $tgltobpjshttpres
        //     ]
        // );
        // return new JsonResponse($simpanbpjshttprespon);

        // $data = Mobatnew::with([
        //     'perencanaanrinci' => function ($perencanaanrinci) {
        //         $perencanaanrinci->select(
        //             'kdobat',
        //             DB::raw(
        //                 'sum(jumlahdpesan) as jumlah'
        //             )
        //         )->where('flag', '')->groupBy('kdobat');
        //     }
        // ])->get();
        // return new JsonResponse($data);
        // $rencanabeli = RencanabeliH::with([
        //     'rincian',
        //     'rincian.mobat',
        //     'rincian' => function ($anu) {
        //         $anu->leftjoin('pemesanan_r', function ($join) {
        //             $join->select(
        //                 'pemesanan_r.jumlahdpesan as jumlahDipesan',
        //                 'pemesanan_r.noperencanaan',
        //                 'pemesanan_r.kdobat as kode',
        //                 'perencana_pebelian_r.kdobat',
        //                 'perencana_pebelian_r.no_rencbeliobat',
        //                 'perencana_pebelian_r.flag',
        //                 'perencana_pebelian_r.jumlahdpesan',
        //             );
        //             $join->on('pemesanan_r.noperencanaan', '=', 'perencana_pebelian_r.no_rencbeliobat')
        //                 ->on('pemesanan_r.kdobat', '=', 'perencana_pebelian_r.kdobat');
        //         });
        //     },
        // ])->where('no_rencbeliobat', 'LIKE', '%' . request('no_rencbeliobat') . '%')
        //     ->orderBy('tgl', 'desc')
        //     ->get();

        // return new JsonResponse($rencanabeli);
        // $id = Mruangan::where('uraian', 'LIKE', '%' . request('r') . '%')->pluck('kode');
        // $gd = Gudang::where('gudang', '<>', '')->where('nama', 'LIKE', '%' . request('r') . '%')->pluck('kode');
        // return new JsonResponse($gd);
        // array_push($id, $gd);
        // $ruang = array_merge($id, $gd);
        // return new JsonResponse($id);

        // $qwerty = Mminmaxobat::with([
        //     'obat:kd_obat,nama_obat as namaobat',
        //     'ruanganx:kode,uraian as namaruangan',
        //     'gudang:kode,nama as namaruangan'
        // ])
        //     ->whereHas('obat', function ($e) {
        //         $e->where('new_masterobat.nama_obat', 'LIKE', '%' . request('o') . '%');
        //     })
        //     ->whereIn('kd_ruang',  $id)
        //     ->orWhereIn('kd_ruang',  $gd)
        //     ->paginate(request('per_page'));
        // return new JsonResponse($qwerty, 200);
        // $data = Penerimaan::with('details')
        //     ->where('nilai_tagihan', '>', 0)
        //     ->get();
        // $penerimaan = Penerimaan::select('no_penerimaan')->get();
        // $penerimaan->append('count');

        // return new JsonResponse($penerimaan);

        // $paginate = request('per_page') ? request('per_page') : 10;
        // $ruang = 'Gd-02010102';
        // $distribute = DistribusiLangsung::where('reff', request('reff'))
        //     ->where('status', 1)
        //     ->first();
        // if (!$distribute) {
        // return new JsonResponse(['data' => $distribute]);
        // }
        // $data = RecentStokUpdate::leftJoin(
        //     'penerimaans',
        //     'recent_stok_updates.no_penerimaan',
        //     '=',
        //     'penerimaans.no_penerimaan'
        // )
        //     ->join('barang_r_s', 'recent_stok_updates.kode_rs', '=', 'barang_r_s.kode')
        //     ->join('satuans', 'satuans.kode', '=', 'barang_r_s.kode_satuan')
        //     ->select(
        //         'barang_r_s.nama',
        //         'barang_r_s.kode',
        //         'barang_r_s.kode_satuan',
        //         'recent_stok_updates.id',
        //         'recent_stok_updates.kode_rs',
        //         'recent_stok_updates.kode_ruang',
        //         'recent_stok_updates.sisa_stok',
        //         'recent_stok_updates.no_penerimaan as no_penerimaan_stok',
        //         'penerimaans.no_penerimaan',
        //         'penerimaans.tanggal',
        //         'satuans.nama as satuan',
        //     )
        //     ->where('recent_stok_updates.no_penerimaan', 'DPGIZI_00002X/JAN/2023')

        //     ->where('recent_stok_updates.kode_ruang', $ruang)
        //     ->where('recent_stok_updates.sisa_stok', '>', 0)
        //     ->when(request('q'), function ($search) {
        //         $search->where(function ($anu) {
        //             $anu->where('barang_r_s.nama', 'LIKE', '%' . request('q') . '%')
        //                 ->orWhere('barang_r_s.kode', 'LIKE', '%' . request('q') . '%');
        //         })
        //             ->where('barang_r_s.tipe', request('tipe'));
        //     })
        //     ->where('barang_r_s.tipe', request('tipe'))
        //     ->orderBy('recent_stok_updates.id', 'ASC')
        // ->orderBy('penerimaans.tanggal', 'ASC')
        // ->with([
        //     'detaildistribusilangsung' => function ($detail) {
        //         $detail->select(
        //             'detail_distribusi_langsungs.*',
        //             'distribusi_langsungs.*',
        //         )
        //             ->join('distribusi_langsungs', function ($langsung) {
        //                 $langsung->on('detail_distribusi_langsungs.distribusi_langsung_id', '=', 'distribusi_langsungs.id')
        //                     ->where('status', '=', 1)
        //                     ->where('reff', request('reff'));
        //             });
        //     }
        // ])
        // $data = RecentStokUpdate::select(
        //     'barang_r_s.nama',
        //     'barang_r_s.kode',
        //     'barang_r_s.kode_satuan',
        //     'recent_stok_updates.id',
        //     'recent_stok_updates.kode_rs',
        //     'recent_stok_updates.kode_ruang',
        //     'recent_stok_updates.sisa_stok',
        //     'recent_stok_updates.no_penerimaan as no_penerimaan_stok',
        //     'penerimaans.no_penerimaan',
        //     'penerimaans.tanggal',
        //     // 'satuans.nama as satuan',
        // )->leftjoin('penerimaans', 'recent_stok_updates.no_penerimaan', '=', 'penerimaans.no_penerimaan')
        //     ->join('barang_r_s', 'recent_stok_updates.kode_rs', '=', 'barang_r_s.kode')
        //     ->paginate($paginate);


        // return new JsonResponse(['data' => $data]);
        // $anu = collect($data);
        // $balik['data'] = $anu->only('data');
        // $balik['meta'] = $anu->except('data');
        // $balik['penerimaan'] = $penerimaan;
        // $balik['transaksi'] = $distribute;

        // return new JsonResponse($balik);
        // $anu = substr("2023-11-27 10:23:59", 0, 11);
        // $anu = "2023-11-27";
        // $date = date_create('2023-10-05 10:23:59');
        // $date = date_create('2023-10-05 10:23:59');
        // $anu = date_format($date, 'Y-m-d');
        // $comp = $anu !== date('Y-m-d');
        // return new JsonResponse([
        //     'date comp' => $comp
        // ]);

        // $history = BridgingbpjsHelper::get_url('vclaim', 'monitoring/HistoriPelayanan/NoKartu/' . '0001387099642' . '/tglMulai/' . $anu . '/tglAkhir/' . $anu);
        // $sep = BridgingbpjsHelper::get_url('vclaim', 'sep/' . '1327R0010923V008197');
        // $sep = $history['metadata']['code'] === '200' ? $history['result']->histori[0]->noSep : null;

        // $unit = $history['metadata']['code'] === '200' ? $history['result']->histori[0]->poliTujSep : '';
        // $infoSep = BridgingbpjsHelper::get_url('vclaim', 'SEP/' . $sep);
        // $kontrol = BridgingbpjsHelper::get_url('vclaim', '/RencanaKontrol/noSuratKontrol/' . "1327R0011023K000206");
        // $rujukanPcare = BridgingbpjsHelper::get_url('vclaim', 'Rujukan/' . "1327R0010923V008304");

        // $history2 = BridgingbpjsHelper::get_url('vclaim', 'monitoring/HistoriPelayanan/NoKartu/' . '0000112357664' . '/tglMulai/' . $anu . '/tglAkhir/' . $anu);
        // $sep2 = $history['metadata']['code'] === '200' ? $history['result']->histori[0]->noSep : null;

        // $unit = $history['metadata']['code'] === '200' ? $history['result']->histori[0]->poliTujSep : '';
        // $infoSep2 = BridgingbpjsHelper::get_url('vclaim', 'SEP/' . $sep);
        // $kontrol2 = BridgingbpjsHelper::get_url('vclaim', '/RencanaKontrol/noSuratKontrol/' . "1327R0011023K000206");
        // $rujukanPcare2 = BridgingbpjsHelper::get_url('vclaim', 'Rujukan/' . "1327R0010923V008304");

        // return new JsonResponse([
        //     'his' => $history,
        //     'sep' => $sep,
        //     'anu' => $anu,
        // 'info' => $infoSep,
        // 'his2' => $history2,
        // 'info2' => $infoSep2,
        // 'kontrol' => $kontrol,
        // 'rujukanPcare' => $rujukanPcare,
        // ]);
        // $result = Penerimaan::where('no_kwitansi', '<>', '')
        //     ->with('details')
        //     ->orderBy('no_kwitansi')
        //     ->get();

        // $groupedResult = $result->groupBy('no_kwitansi')->map(function ($group) {
        //     return $group->map(function ($item) {
        //         return $item;
        //     });
        // });

        // // Convert the result to the desired format
        // $formattedResult = $groupedResult->map(function ($items, $kwitansi) {
        //     $total = $items->sum('total');
        //     return [
        //         'kwitansi' => $kwitansi,
        //         'totalSemua' => $total,
        //         'penerimaan' => $items,
        //     ];
        // })->values();

        // return response()->json($formattedResult);
        // $result = RecentStokUpdate::selectRaw('*, (sisa_stok * harga) as subtotal, sum(sisa_stok * harga) as total, sum(sisa_stok) as totalStok')
        //     ->where('sisa_stok', '>', 0)
        //     ->where('kode_ruang', 'LIKE', '%Gd-%')
        //     ->when(request('kode_ruang'), function ($anu) {
        //         $anu->whereKodeRuang(request('kode_ruang'));
        //     })
        //     ->when(request('kode_rs'), function ($anu) {
        //         $anu->whereKodeRs(request('kode_rs'));
        //     })
        //     ->with(
        //         'barang:kode,nama',
        //         'penerimaan:id,no_penerimaan',
        //         'penerimaan.details:kode_rs,penerimaan_id,harga,harga_kontrak,diskon,ppn,harga_jadi'
        //     )
        //     // ->with('penerimaan.details')
        //     ->groupBy('kode_rs', 'kode_ruang', 'no_penerimaan')
        //     ->paginate(request('per_page'));

        // $data = $result;
        // return new JsonResponse($data);
        // $barang = BarangRS::select('kode', 'nama', 'kode_satuan', 'kode_108', 'uraian_108')
        //     ->whereIn('kode', ['RS-0982'])
        //     ->filter(request(['q']))
        //     ->with([
        //         'satuan:kode,nama',
        //         'monthly' => function ($m) {
        //             $m->select(
        //                 'tanggal',
        //                 // 'sisa_stok as totalStok',
        //                 'harga',
        //                 'no_penerimaan',
        //                 'kode_rs',
        //                 'kode_ruang'
        //             )
        //                 ->selectRaw('round(sum(sisa_stok),2) as totalStok')
        //                 // ->selectRaw('round(sisa_stok*harga,2) as totalRp')
        //                 ->whereBetween('tanggal', ['2023-01-01 00:00:00', '2023-12-31 23:59:59'])
        //                 ->groupBy('kode_rs', 'tanggal')
        //                 ->orderBy('tanggal', 'ASC');
        //         },
        //         'recent' => function ($m) {
        //             $m->select(
        //                 // 'sisa_stok as totalStok',
        //                 'harga',
        //                 'kode_rs',
        //                 'kode_ruang',
        //                 'no_penerimaan'
        //             )
        //                 ->selectRaw('round(sum(sisa_stok),2) as totalStok')
        //                 // ->selectRaw('round(sisa_stok*harga,2) as totalRp')
        //                 ->where('sisa_stok', '>', 0)
        //                 ->groupBy('kode_rs');
        //         },
        //         'stok_awal' => function ($m) {
        //             $m->select(
        //                 'tanggal',
        //                 //  'sisa_stok as totalStok',
        //                 'harga',
        //                 'no_penerimaan',
        //                 'kode_rs',
        //                 'kode_ruang'
        //             )
        //                 ->selectRaw('round(sum(sisa_stok),2) as totalStok')
        //                 ->selectRaw('round(sisa_stok*harga,2) as totalRp')
        //                 ->whereBetween('tanggal', ['2022-12-01 00:00:00', '2022-12-31 23:59:59'])
        //                 ->groupBy('kode_rs', 'tanggal');
        //         },
        //         'detailPenerimaan' => function ($m) {
        //             $m->select(
        //                 'detail_penerimaans.kode_rs',
        //                 // 'detail_penerimaans.qty as total',
        //                 'penerimaans.tanggal',
        //             )
        //                 ->selectRaw('round(sum(qty),2) as total')
        //                 // ->selectRaw('round(qty*harga_jadi,2) as totalRp')
        //                 ->leftJoin(
        //                     'penerimaans',
        //                     function ($p) {
        //                         $p->on('penerimaans.id', '=', 'detail_penerimaans.penerimaan_id');
        //                     }
        //                 )
        //                 // ->whereBetween('penerimaans.tanggal', [$fromN, $toN])
        //                 ->where('penerimaans.status', '>', 1)
        //                 ->groupBy('detail_penerimaans.kode_rs', 'penerimaans.tanggal');
        //         },
        //         'detailDistribusiLangsung' => function ($m) {
        //             $m->select(
        //                 'distribusi_langsungs.ruang_tujuan',
        //                 'detail_distribusi_langsungs.kode_rs',
        //                 'detail_distribusi_langsungs.no_penerimaan',
        //                 'detail_distribusi_langsungs.jumlah as total',
        //             )
        //                 ->leftJoin('distribusi_langsungs', function ($p) {
        //                     $p->on('distribusi_langsungs.id', '=', 'detail_distribusi_langsungs.distribusi_langsung_id');
        //                 })
        //                 // ->whereBetween('distribusi_langsungs.tanggal', [$from, $to])
        //                 // ->with('recentstok')
        //                 ->where('distribusi_langsungs.status', '>', 1);
        //         },
        //         'detailPemakaianruangan' => function ($m) {
        //             $m->select(
        //                 'details_pemakaianruangans.kode_rs',
        //                 'details_pemakaianruangans.no_penerimaan',
        //                 // 'details_pemakaianruangans.jumlah as total',
        //                 'pemakaianruangans.tanggal',
        //                 'pemakaianruangans.kode_ruang',
        //             )
        //                 ->selectRaw('round(sum(jumlah),2) as total')
        //                 ->leftJoin('pemakaianruangans', function ($p) {
        //                     $p->on('pemakaianruangans.id', '=', 'details_pemakaianruangans.pemakaianruangan_id');
        //                 })

        //                 // ->whereBetween('pemakaianruangans.tanggal', [$from, $to])
        //                 ->where('pemakaianruangans.status', '>', 1)
        //                 ->groupBy('details_pemakaianruangans.kode_rs', 'pemakaianruangans.kode_ruang')
        //                 ->orderBy('pemakaianruangans.tanggal', 'ASC');
        //         },
        //         // 'hargastok'


        //     ]);


        // $data = $barang->orderBy('kode_108', 'ASC')->withTrashed()->get();
        // foreach ($data as $barang) {
        //     foreach ($barang->detailPemakaianruangan as $det) {
        //         $det->append('harga');
        //     }
        // }
        // return new JsonResponse($data);
        // $date = '2023-10-24'; // Replace with your date
        // $hari = date('l', strtotime($date));

        // $anu = JadwalAbsen::select('pegawai_id', 'day', 'masuk', 'pulang', 'kategory_id')
        //     ->where('kategory_id', '>=', 3)
        //     ->where('day', '=', $hari);
        // if (request('dispen_masuk') === 'true' && request('dispen_pulang') === 'false') {
        //     $anu->whereBetween('masuk', [request('mulai'), request('selesai')]);
        // } else if (request('dispen_masuk') === 'false' && request('dispen_pulang') === 'true') {
        //     $anu->whereBetween('pulang', [request('mulai'), request('selesai')]);
        // } else if (request('dispen_masuk') === 'true' && request('dispen_pulang') === 'false') {
        //     $anu->where(function ($q) {
        //         $q->whereBetween('masuk', [request('mulai'), request('selesai')])
        //             ->orWhereBetween('pulang', [request('mulai'), request('selesai')]);
        //     });
        // }
        // $idpeg = $anu->distinct('pegawai_id')
        //     ->orderBy('pegawai_id', 'ASC')
        //     ->get();
        // return new JsonResponse([
        //     'hari' => $hari,
        //     'dm' => request('dispen_masuk') === 'true',
        //     'req' => request()->all(),
        //     'data' => $idpeg,
        // ]);
        // $user = auth()->user();
        // $pegawai = Pegawai::find($user->pegawai_id);
        // $data = Gudang::get();
        // return new JsonResponse($data);

        // $p = Permintaanruangan::query();
        // // if ($pegawai->role_id === 4) {
        // //     $p->where('dari', $pegawai->kode_ruang);
        // // }
        // // $data = $p->where('status', '>=', 4)
        // //     ->where('status', '<=', 7)
        // //     ->orderBy(request('order_by'), request('sort'))
        // if (
        //     request('status') && request('status') !== null
        // ) {
        //     $p->where('status', '=', request('status'));
        // } else {
        //     $p->where('status', '>=', 4)
        //         ->where('status', '<=', 7);
        // }

        // $per = $p->select('id')->paginate(request('per_page'));
        // $colId = collect($per)->only('data');
        // $perId = $colId['data'];
        // $det = DetailPermintaanruangan::select('kode_rs')->whereIn('permintaanruangan_id', $perId)->distinct('kode_rs')->get();

        // $data = $p->orderBy(request('order_by'), request('sort'))
        //     ->with([

        //         'pj', 'pengguna', 'details' => function ($wew) use ($det) {
        //             // if ($pegawai->role_id === 4) {
        //             //     $wew->where('dari', $pegawai->kode_ruang);
        //             // }
        //             $wew->select(
        //                 'detail_permintaanruangans.id',
        //                 'detail_permintaanruangans.permintaanruangan_id',
        //                 'detail_permintaanruangans.dari',
        //                 'detail_permintaanruangans.tujuan',
        //                 'detail_permintaanruangans.kode_rs',
        //                 'detail_permintaanruangans.kode_satuan',
        //                 'detail_permintaanruangans.jumlah',
        //                 'detail_permintaanruangans.jumlah_disetujui',
        //                 'detail_permintaanruangans.jumlah_distribusi',
        //                 'detail_permintaanruangans.alasan',

        //             )

        //                 ->with([
        //                     'satuan:kode,nama',
        //                     'ruang:kode,uraian',
        //                     'maxruangan' => function ($ma) use ($det) {
        //                         $ma->select(
        //                             'kode_rs',
        //                             'kode_ruang',
        //                             'max_stok',
        //                             'minta'
        //                         )->whereIn('kode_rs', $det);
        //                     },
        //                     'sisastok' => function ($s) {
        //                         $s->select(
        //                             'kode_rs',
        //                             'kode_ruang',
        //                             'sisa_stok',
        //                         )
        //                             ->selectRaw('sum(sisa_stok) as stok_total')
        //                             ->where('sisa_stok', '>', 0)
        //                             ->groupBy('kode_rs', 'kode_ruang');
        //                     },
        //                     'barangrs' => function ($anu) {
        //                         $anu->select(
        //                             'kode',
        //                             'nama',
        //                             'kode_satuan',
        //                             'kode_depo'
        //                         );
        //                     }
        //                 ]);
        //         }
        //     ])
        //     ->filter(request(['q', 'r']))
        //     ->paginate(request('per_page'));


        // foreach ($data as $key) {
        //     foreach ($key->details as $detail) {
        //         $detail->append('all_minta');
        //         $sisastok = collect($detail['sisastok']);

        //         $stokMe = $sisastok->where('kode_ruang', $detail['tujuan'])->all();
        //         $stokR = 0;
        //         foreach ($stokMe as $st) {
        //             $stokR = $st->stok_total;
        //         }

        //         $stokDe = $sisastok->where('kode_ruang', $detail['barangrs']->kode_depo)->all();
        //         $stokD = 0;
        //         foreach ($stokDe as $st) {
        //             $stokD = $st->stok_total;
        //         }

        //         $maxruangan = collect($detail['maxruangan']);
        //         $maxRe = $maxruangan->where('kode_rs', $detail['kode_rs'])->all();
        //         $maxR = 0;
        //         $mintaR = 0;
        //         foreach ($maxRe as $st) {
        //             $maxR = $st->max_stok;
        //             $mintaR = $st->minta;
        //         }

        //         $sum = $detail['all_minta'];
        //         $alokasi = 0;
        //         if ($stokD >= $sum) {
        //             $alokasi =  $stokD - $sum;
        //         } else {
        //             $alokasi = 0;
        //         }
        //         $detail['barangrs']->maxStok = $maxR > 0 ? $maxR : $mintaR;
        //         $detail['barangrs']->stokRuangan = $stokR;
        //         $detail['barangrs']->stokDepo = $stokD;
        //         $detail['barangrs']->alokasi = $alokasi;
        //     }
        // }

        // // if (count($data)) {
        // //     foreach ($data as $key) {
        // //         $key->gudang = collect($key->details)->groupBy('dari');
        // //     }
        // // }
        // $collection = collect($data);
        // return new JsonResponse([
        //     // 'per' => $det,
        //     'data' => $collection->only('data'),
        //     'meta' => $collection->except('data'),
        // ], 200);
        // $tanggalPulang = '2023-09-25'; // yyyy-mm-dd
        // $jenisPelayanan = '2'; //Jenis Pelayanan (1. Inap 2. Jalan)
        // $status = '3'; //Status Klaim (1. Proses Verifikasi 2. Pending Verifikasi 3. Klaim)
        // $data = BridgingbpjsHelper::get_url('vclaim', '/Monitoring/Klaim/Tanggal/' . $tanggalPulang . '/JnsPelayanan/' . $jenisPelayanan . '/Status/' . $status);
        // $data = DetailbillingbynoregController::konsulantarpoli('89502/11/2023/J');
        // return new JsonResponse($data);
        // $no = '53571/11/2023/J';
        // $no1 = '53565/11/2023/J';
        // $sep = Seprajal::where('rs1', $no1)->first();
        // if (isset($sep)) {
        //     $sep1 = Seprajal::firstOrCreate(
        //         ['rs1' => $no],
        //         [
        //             'rs2' => $sep->rs2,
        //             'rs3' => $sep->rs3,
        //             'rs4' => $sep->rs4,
        //             'rs5' => $sep->rs5,
        //             'rs6' => $sep->rs6,
        //             'rs7' => $sep->rs7,
        //             'rs8' => $sep->rs8,
        //             'rs9' => $sep->rs9,
        //             'rs10' => $sep->rs10,
        //             'rs11' => $sep->rs11,
        //             'rs12' => $sep->rs12,
        //             'rs13' => $sep->rs13,
        //             'rs14' => $sep->rs14,
        //             'rs15' => $sep->rs15,
        //             'rs16' => $sep->rs16,
        //             'rs17' => $sep->rs17,
        //             'rs18' => $sep->rs18,
        //             'laka' => $sep->laka,
        //             'lokasilaka' => $sep->lokasilaka,
        //             'penjaminlaka' => '',
        //             'users' => auth()->user()->pegawai_id ?? 'anu',
        //             'notelepon' => $sep->notelepon,
        //             'tgl_entery' => $sep->tgl_entery,
        //             'noDpjp' => $sep->noDpjp,
        //             'tgl_kejadian_laka' => $sep->tgl_kejadian_laka,
        //             'keterangan' => $sep->keterangan,
        //             'suplesi' => $sep->suplesi,
        //             'nosuplesi' => $sep->nosuplesi,
        //             'kdpropinsi' => $sep->kdpropinsi,
        //             'propinsi' => $sep->propinsi,
        //             'kdkabupaten' => $sep->kdkabupaten,
        //             'kabupaten' => $sep->kabupaten,
        //             'kdkecamatan' => $sep->kdkecamatan,
        //             'kecamatan' => $sep->kecamatan,
        //             'kodedokterdpjp' => $sep->kodedokterdpjp,
        //             'dokterdpjp' => $sep->dokterdpjp,
        //             'kodeasalperujuk' => $sep->kodeasalperujuk,
        //             'namaasalperujuk' => $sep->namaasalperujuk,
        //             'Dinsos' => $sep->Dinsos,
        //             'prolanisPRB' => $sep->prolanisPRB,
        //             'noSKTM' => $sep->noSKTM,
        //             'jeniskunjungan' => $sep->jeniskunjungan,
        //             'tujuanKunj' => $sep->tujuanKunj,
        //             'flagProcedure' => $sep->flagProcedure,
        //             'kdPenunjang' => $sep->kdPenunjang,
        //             'assesmentPel' => $sep->assesmentPel,
        //             'kdUnit' => $sep->kdUnit
        //         ]
        //     );
        // }
        // return $sep1;
        // $listrujukankeluarrs = BridgingbpjsHelper::get_url(
        //     'Rujukan',
        //     'Rujukan/Keluar/List/tglMulai/' . request('tglawal') . '/tglAkhir/' . request('tglakhir')
        // );
        // $listrujukankeluarrs = BridgingbpjsHelper::get_url(
        //     'vclaim',
        //     '/Rujukan/Keluar/1327R0011123B000248'
        // );

        // return $listrujukankeluarrs;
        // $querysx = array(
        //     "metadata" => array(
        //         "method" => "grouper",
        //         "stage" => "1"
        //     ),
        //     "data" => array(
        //         "nomor_sep" => '93976/12/2023/J'
        //     )
        // );
        // $anu = BridgingeklaimHelper::curl_func($querysx);
        // $anu = date('Y-m-d H:i:s ', 1700438819);
        // return $anu;
        // $responsesx = EwseklaimController::ewseklaimrajal_newclaim('93746/12/2023/J');
        // return $responsesx;
        // $det = DetailPermintaanruangan::where('permintaanruangan_id', 4647)->get();
        // $recentStok = [];
        // $detailPenerimaan = [];
        // foreach ($det as $key => $detail) {
        //     // gaween whereIn
        //     $dari = RecentStokUpdate::where('kode_ruang', $detail['dari'])
        //         ->where('kode_rs', $detail['kode_rs'])
        //         ->where('sisa_stok', '>', 0)
        //         ->oldest()
        //         ->get();

        //     $sisaStok = collect($dari)->sum('sisa_stok');
        //     $index = 0;
        //     $jumlahDistribusi = $detail['jumlah_disetujui'];
        //     if ($jumlahDistribusi > 0) {
        //         // masukkan detail sesuai order FIFO
        //         $masuk = $jumlahDistribusi;
        //         // do {
        //         while ($masuk > 0) {
        //             $ada = $dari[$index]->sisa_stok;
        //             if ($ada < $masuk) {
        //                 $sisa = $masuk - $ada;

        //                 // pake insert dellok d Simrs->Penunjang->Laborat->LaboratController->simpanpermintaanlaboratbaru
        //                 $stok = [

        //                     'kode_rs' => $detail['kode_rs'],
        //                     'kode_ruang' => $detail['tujuan'],
        //                     'sisa_stok' => $ada,
        //                     'harga' => $dari[$index]->harga,
        //                     'no_penerimaan' => $dari[$index]->no_penerimaan,
        //                 ];
        //                 $penerimaanruangan = [
        //                     'no_penerimaan' => $dari[$index]->no_penerimaan,
        //                     'jumlah' => $ada,
        //                     'no_distribusi' => '$request->no_distribusi',
        //                     'kode_rs' => $detail['kode_rs'],
        //                     'kode_satuan' => $detail['kode_satuan'],
        //                 ];
        //                 $recentStok[] = $stok;
        //                 $detailPenerimaan[] = $penerimaanruangan;

        //                 $index = $index + 1;
        //                 $masuk = $sisa;
        //                 $loop = true;
        //             } else {
        //                 $sisa = $ada - $masuk;

        //                 $stok = [

        //                     'kode_rs' => $detail['kode_rs'],
        //                     'kode_ruang' => $detail['tujuan'],
        //                     'sisa_stok' => $masuk,
        //                     'harga' => $dari[$index]->harga,
        //                     'no_penerimaan' => $dari[$index]->no_penerimaan,
        //                 ];
        //                 $penerimaanruangan = [
        //                     'no_penerimaan' => $dari[$index]->no_penerimaan,
        //                     'jumlah' => $masuk,
        //                     'no_distribusi' => '$request->no_distribusi',
        //                     'kode_rs' => $detail['kode_rs'],
        //                     'kode_satuan' => $detail['kode_satuan'],
        //                 ];
        //                 $recentStok[] = $stok;
        //                 $detailPenerimaan[] = $penerimaanruangan;
        //                 $masuk = 0;
        //                 $loop = false;
        //             }
        //         };
        //         // } while ($loop);
        //     }
        // }
        // return [
        //     'recent' => $recentStok,
        //     'detail penerimaan' => $detailPenerimaan,
        // ];
        // $tglskrng = date('Y-m-d');
        // $date = new \DateTime('-7 days');
        // $prev = $date->format('Y-m-d');

        // return [
        //     'sekarang' => $tglskrng,
        //     'prev' => $prev,
        // ];
        // $simpan = Resepkeluarheder::updateOrCreate(
        //     [
        //         'noreg' => 'XXXXX'
        //     ],
        //     [
        //         'tgl' => date('Y-m-d H:i:s'),
        //     ]

        // );

        // return new JsonResponse(['sim' => $simpan]);
        // $waktu = strtotime(date('Y-m-d H:i:s')) * 1000;
        // $waktu2 = strtotime(date('Y-m-d H:i:s'));
        // $waktu3 = date('Y-m-d H:i:s');
        // $carbon = Carbon::now('Asia/Jakarta');
        // $carbon2 = strtotime(Carbon::now('Asia/Jakarta'));
        // $carbon3 = Carbon::parse($waktu3)->locale('id');
        // $carbon4 = strtotime($carbon3);
        // $carbon5 = Carbon::now('UTC');
        // $carbon6 = Carbon::parse($carbon5)->locale('id');
        // $carbon7 = $carbon6->format('l, j F Y ; h:i a');
        // $carbon8 = $carbon3->format('l, j F Y ; h:i a');
        // return [
        //     'waktu' => $waktu,
        //     'waktu2' => $waktu2,
        //     'waktu3' => $waktu3,
        //     'carbon' => $carbon,
        //     'carbon2' => $carbon2,
        //     'carbon3' => $carbon3,
        //     'carbon4' => $carbon4,
        //     'carbon5' => $carbon5,
        //     'carbon6' => $carbon6,
        //     'carbon7' => $carbon7,
        //     'carbon8' => $carbon8,
        // ];
        // $idpeg = Pegawai::select('id')->where('kode_ruang', 'Gd-02010102')->get();
        // $co = collect($idpeg);
        // $id = $co->except('ttdpegawai_url');
        // $mapp = $co->map(function ($item) {
        //     return $item->id;
        // });
        // $anu = $mapp;
        // $anu[] = 0;
        // return [
        //     'id' => $idpeg,
        //     'col' => $co,
        //     'map' => $mapp,
        //     'anu' => $anu,
        //     'id aja' => $id,
        // ];
        // $ant = new BridantrianbpjsController;
        // $temp = $ant->batalantrian('K20240226211236');
        // return $temp;
        // $class = new StockController;

        // $data = [
        //     'kode' => 'RS-0852',
        //     'no_penerimaan' => 'DPBHP_00002X/JAN/2023',
        //     'harga' => '8000',
        // ];
        // $data = [
        //     'kode' => request('k'),
        //     'no_penerimaan' => request('n'),
        //     'harga' => request('h'),
        // ];
        // $result = $class->updateHarga($data);
        // return $result;
        // $harga = HargaHelper::getHarga('0000054-FAR', '1');
        // return $harga['harga'];
        // $jadwaldokter = BridgingbpjsHelper::get_url(
        //     'antrean',
        //     'jadwaldokter/kodepoli/BDM/tanggal/2024-04-16'
        // );
        // return ($jadwaldokter);
        $opname = new StokOpnameFarmasiController;
        $data = $opname->storeMonthly();
        return $data;
    }


    public function cari_dokter()
    {
        return BridgingbpjsHelper::get_url('antrean', 'jadwaldokter/kodepoli/' . request('kodepoli') . "/tanggal/" . request('tanggal'));
    }

    public function bpjsCoba()
    {
        // $rujukan = '1327R0010125B000006';
        $data = BridgingbpjsHelper::get_url('vclaim', 'referensi/poli/Hemodialisa');
        return new JsonResponse([
            'data' => $data
        ]);
    }
    public function baru()
    {
        // $data['normal'] = BridgingbpjsHelper::get_url('vclaim', 'RencanaKontrol/noSuratKontrol/' . '1327R0011123K002107');
        // $data['inap'] = BridgingbpjsHelper::get_url('vclaim', 'RencanaKontrol/noSuratKontrol/' . '1327R0011123K002121');
        // $data['inap1'] = BridgingbpjsHelper::get_url('vclaim', 'RencanaKontrol/noSuratKontrol/' . '1327R0011123K002120');
        // $data['inap2'] = BridgingbpjsHelper::get_url('vclaim', 'RencanaKontrol/noSuratKontrol/' . '1327R0011123K002119');
        // $data['inap3'] = BridgingbpjsHelper::get_url('vclaim', 'RencanaKontrol/noSuratKontrol/' . '1327R0011123K002118');
        // $data['inap4'] = BridgingbpjsHelper::get_url('vclaim', 'RencanaKontrol/noSuratKontrol/' . '1327R0011123K002117');
        // $data['inap5'] = BridgingbpjsHelper::get_url('vclaim', 'RencanaKontrol/noSuratKontrol/' . '1327R0011123K002116');
        // return $data;
        $data = DB::table('rs30z')->select('rs2', 'rs8', 'rs9')->where('rs3', '=', 'RM#')->first();
        return $data;
    }
    public function wawanpost(Request $request)
    {
        // $data = JadwalController::toMatch2($request->id, $request);
        $antrian = $request->antrian ? $request->antrian : '1';
        $pesan = $request->pesan ? $request->pesan : 'tidak ada';
        $url = $request->url ? $request->url : 'url';
        $task = $request->task ? $request->task : '1';
        $metadata['metadata'] =  [
            'code' => $antrian,
            'message' => $pesan
        ];
        $message = [
            'kode' => $metadata,
            'url' => $url,
            'task' => $task,
            'user' => auth()->user()->id
        ];
        event(new AntreanEvent($message));


        return new JsonResponse($request->all());

        // $ip2 = $_SERVER['REMOTE_ADDR'];
        // $ip = request()->ip();
        // return new JsonResponse([
        //     'ip' => $ip,
        //     'ip2' => $ip2,
        // ]);
    }

    // sigarang set min max stok depo dan pengguna
    public function setMinMax()
    {
        // $barang = BarangRS::oldest('id')->get();
        // $pengguna = Pengguna::where('id', '>=', request('from'))
        //     ->where('id', '<=', request('to'))
        //     ->get();
        // $totruang = Ruang::get();
        // $ruang = Ruang::where('id', '>=', request('from'))
        //     ->where('id', '<=', request('to'))
        //     ->get();
        // $depo = Gudang::where('depo', '<>', null)
        //     ->where('depo', '<>', '')
        //     ->where('gedung', '=', 2)
        //     ->get();

        // if ($ruang) {
        //     foreach ($ruang as $room) {
        //         foreach ($barang as $goods) {
        //             MaxRuangan::firstOrCreate(
        //                 [
        //                     'kode_rs' => $goods['kode'],
        //                     'kode_ruang' => $room['kode'],
        //                 ],
        //                 [
        //                     'max_stok' => 100,
        //                 ]
        //             );
        //         }
        //     }
        // }

        // return new JsonResponse([
        //     'id ' . request('from') . ' - ' . request('to') . ' dari total ' . count($totruang),
        //     $ruang,
        //     count($barang),
        // ]);
        // foreach ($barang as $goods) {
        //     foreach ($pengguna as $user) {
        //         MinMaxPengguna::firstOrCreate(
        //             [
        //                 'kode_rs' => $goods['kode'],
        //                 'kode_pengguna' => $user['kode'],
        //             ],
        //             [
        //                 'min_stok' => 1,
        //                 'max_stok' => 4,
        //             ]
        //         );
        //     }
        // }

        // foreach ($barang as $goods) {
        //     foreach ($depo as $apem) {
        //         MinMaxDepo::firstOrCreate(
        //             [
        //                 'kode_rs' => $goods['kode'],
        //                 'kode_depo' => $apem['kode'],
        //             ],
        //             [
        //                 'min_stok' => 5,
        //                 'max_stok' => 10,
        //             ]
        //         );
        //     }
        // }


        // return new JsonResponse('ok');


    }



    // Encryption Function
    function inacbg_encrypt($data, $key)
    {

        /// make binary representasion of $key
        $key = hex2bin($key);
        /// check key length, must be 256 bit or 32 bytes
        if (mb_strlen($key, "8bit") !== 32) {
            throw new Exception("Needs a 256-bit key!");
        }
        /// create initialization vector
        $iv_size = openssl_cipher_iv_length("aes-256-cbc");
        $iv = openssl_random_pseudo_bytes($iv_size); // dengan catatan dibawah
        /// encrypt
        $encrypted = openssl_encrypt($data, "aes-256-cbc", $key, OPENSSL_RAW_DATA, $iv);
        /// create signature, against padding oracle attacks
        $signature = mb_substr(hash_hmac("sha256", $encrypted, $key, true), 0, 10, "8bit");
        /// combine all, encode, and format
        $encoded = chunk_split(base64_encode($signature . $iv . $encrypted));
        return $encoded;
    }

    // Decryption Function
    function inacbg_decrypt($str, $strkey)
    {
        /// make binary representation of $key
        $key = hex2bin($strkey);
        /// check key length, must be 256 bit or 32 bytes
        if (mb_strlen($key, "8bit") !== 32) {
            throw new Exception("Needs a 256-bit key!");
        }
        /// calculate iv size
        $iv_size = openssl_cipher_iv_length("aes-256-cbc");
        /// breakdown parts
        $decoded = base64_decode($str);
        $signature = mb_substr($decoded, 0, 10, "8bit");
        $iv = mb_substr($decoded, 10, $iv_size, "8bit");
        $encrypted = mb_substr($decoded, $iv_size + 10, NULL, "8bit");
        /// check signature, against padding oracle attack
        $calc_signature = mb_substr(hash_hmac(
            "sha256",
            $encrypted,
            $key,
            true
        ), 0, 10, "8bit");
        if (!$this->inacbg_compare($signature, $calc_signature)) {
            return "SIGNATURE_NOT_MATCH"; /// signature doesn't match
        }
        $decrypted = openssl_decrypt(
            $encrypted,
            "aes-256-cbc",
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
        return $decrypted;
    }
    /// Compare Function
    // © 2022 Kementerian Kesehatan Republik Indonesia Halaman 4 dari 56
    function inacbg_compare($a, $b)
    {
        /// compare individually to prevent timing attacks

        /// compare length
        if (strlen($a) !== strlen($b)) return false;

        /// compare individual
        $result = 0;
        for ($i = 0; $i < strlen($a); $i++) {
            $result |= ord($a[$i]) ^ ord($b[$i]);
        }

        return $result == 0;
    }

    public function getkarciscontoller()
    {
        $kd_poli = 'POL012';
        $flag = 'Baru';
        $getkarcis = FormatingHelper::getKarcisPoli($kd_poli, $flag);
        return new JsonResponse($getkarcis);
    }
}
