<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Stok;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasi\MapingObat;
use App\Models\Simrs\Penunjang\Farmasi\StokOpname;
use App\Models\Simrs\Penunjang\Farmasi\StokReal;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaandepoheder;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaandeporinci;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarrinci;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarrinciracikan;
use App\Models\Simrs\Penunjang\Farmasinew\Harga\DaftarHarga;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Mutasi\Mutasigudangkedepo;
use App\Models\Simrs\Penunjang\Farmasinew\Obat\BarangRusak;
use App\Models\Simrs\Penunjang\Farmasinew\Obatoperasi\PersiapanOperasi;
use App\Models\Simrs\Penunjang\Farmasinew\Obatoperasi\PersiapanOperasiDistribusi;
use App\Models\Simrs\Penunjang\Farmasinew\Obatoperasi\PersiapanOperasiRinci;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanRinci;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PengembalianRinciFifo;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\Returpbfheder;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\Returpbfrinci;
use App\Models\Simrs\Penunjang\Farmasinew\Retur\ReturGudang;
use App\Models\Simrs\Penunjang\Farmasinew\Retur\ReturGudangDetail;
use App\Models\Simrs\Penunjang\Farmasinew\Retur\Returpenjualan_h;
use App\Models\Simrs\Penunjang\Farmasinew\Retur\Returpenjualan_r;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\PenyesuaianStok;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokopname as StokStokopname;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\StokopnameSementara;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\StokrealSementara;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokrel;
use App\Models\Simrs\Penunjang\Farmasinew\Stokreal as FarmasinewStokreal;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use function PHPUnit\Framework\throwException;

class SetNewStokController extends Controller
{

    public function setNewStok()
    {
        $create = date('Y-m-d H:i:s');
        $mapingGudang = [
            ['nama' => 'Gudang Farmasi ( Kamar Obat )', 'kode' => 'Gd-05010100', 'lama' => 'GU0001'],
            ['nama' => 'Gudang Farmasi (Floor Stok)', 'kode' => 'Gd-03010100', 'lama' => 'GU0002'],
            ['nama' => 'Floor Stock 1 (AKHP)', 'kode' => 'Gd-03010101', 'lama' => 'RC0001'],
            ['nama' => 'Depo Rawat inap', 'kode' => 'Gd-04010102', 'lama' => 'AP0002'],
            ['nama' => 'Depo OK', 'kode' => 'Gd-04010103', 'lama' => 'AP0005'],
            ['nama' => 'Depo Rawat Jalan', 'kode' => 'Gd-05010101', 'lama' => 'AP0001'],
            ['nama' => 'Depo IGD', 'kode' => 'Gd-02010104', 'lama' => 'AP0007']
        ];
        $gudBaru = ['Gd-05010100', 'Gd-03010100', 'Gd-03010101', 'Gd-04010102', 'Gd-04010103', 'Gd-05010101', 'Gd-02010104'];

        $mapingDep = ['GU0001', 'GU0002', 'RC0001', 'AP0002', 'AP0005', 'AP0001', 'AP0007'];

        $mapingObat = MapingObat::with([
            'master:rs1,rs4',
            'stok' => function ($stok) use ($mapingDep) {
                $stok->where('rs2', '>', 0)
                    ->whereIn('rs4', $mapingDep);
            },
            'rincipenerimaan' => function ($tr) {
                $tr->select(
                    'rs82.rs1',
                    'rs82.rs2',
                    'rs82.rs6',
                    'rs82.rs7',
                    'rs81.rs2 as tanggal',
                )
                    ->leftJoin('rs81', 'rs81.rs1', '=', 'rs82.rs1')
                    ->orderBy('rs81.rs2', 'DESC');
                // ->limit(5);
            }
        ])
            ->where('obatbaru', '<>', '')
            ->orderBy('obatbaru', 'ASC')
            // ->limit(10)
            ->get();
        $newStok = [];
        foreach ($mapingObat as $key) {
            foreach ($key['stok'] as $st) {
                $raw = collect($mapingGudang)
                    ->where('lama', $st['rs4'])
                    ->map(function ($it, $key) {
                        return $it['kode'] ?? null;
                    });
                /**
                 * Catatan:
                 * $anu dan $item ada karena key nya ($anu) dinamis, maka key nya harus dicari berdasarkan nilai objeck yang sekarang ($item)
                 * key nya tidak bisa langsung diambil dari $raw, karena $raw masih belum menjadi nilai dari object, maka nilai dari object harus di akses terlebih dahulu di $item
                 */
                $item = current((array)$raw); // value of current object
                $anu = key((array)$item); // key of the object value
                if ($item[$anu] === 'Gd-05010100') $nPen = 'G-KO';
                else if ($item[$anu] === 'Gd-03010100') $nPen = 'G-FO';
                else if ($item[$anu] === 'Gd-03010101') $nPen = 'D-FO';
                else if ($item[$anu] === 'Gd-04010102') $nPen = 'D-RI';
                else if ($item[$anu] === 'Gd-04010103') $nPen = 'D-OK';
                else if ($item[$anu] === 'Gd-05010101') $nPen = 'D-RJ';
                else if ($item[$anu] === 'Gd-02010104') $nPen = 'D-IGD';
                else  $nPen = 'NDF';
                $temp = [
                    'nopenerimaan' => '001/' . date('m/Y') . '/awal/' . $nPen,
                    'tglpenerimaan' => $key['rincipenerimaan']['tanggal'] ?? $create,
                    'kdobat' => $key['obatbaru'],
                    'jumlah' => (float)$st['rs2'],
                    'kdruang' => $item[$anu],
                    'harga' => (float)$key['master']['rs4'],
                    'tglexp' => $key['rincipenerimaan']['rs7'] ?? null,
                    'nobatch' => $key['rincipenerimaan']['rs6'] ?? '',
                    'created_at' => $create,
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
                $newStok[] = $temp;
            }
        }

        if (count($newStok) <= 0) {
            return new JsonResponse($newStok);
        }

        FarmasinewStokreal::truncate();
        foreach (array_chunk($newStok, 100) as $t) {
            $data['ins'] = FarmasinewStokreal::insert($t);
        }
        // if (count($daftarHarga) > 0) {
        //     DaftarHarga::truncate();
        //     $uni = array_unique($daftarHarga, SORT_REGULAR);
        //     foreach (array_chunk($uni, 1000) as $t) {
        //         $data['ins'] = DaftarHarga::insert($t);
        //     }
        // }

        // $data['mapingObat'] = $mapingObat;
        // sleep(20);
        $data['new stok'] = $newStok;
        // $data['har'] = $this->cekHargaGud();

        return new JsonResponse($data);
    }
    public function cekHargaGud()
    {
        $gKo = 'Gd-05010100';
        $gFo = 'Gd-03010100';
        $dFo = 'Gd-03010101';
        $dep = ['Gd-04010102', 'Gd-04010103', 'Gd-05010101', 'Gd-02010104'];
        $obKo = FarmasinewStokreal::select('kdobat')->where('kdruang', $gKo)->distinct()->get('kdobat');
        $obFo = FarmasinewStokreal::select('kdobat')->where('kdruang', $gFo)->distinct()->get('kdobat');
        $obDFo = FarmasinewStokreal::where('kdruang', $dFo)->whereNotIn('kdobat', $obFo)->groupBy('kdobat')->get();
        $obDep = FarmasinewStokreal::whereIn('kdruang', $dep)->whereNotIn('kdobat', $obKo)->groupBy('kdobat')->get();
        $stok = [];
        if (count($obDFo) > 0) {
            foreach ($obDFo as $key) {
                $temp['nopenerimaan'] = $key['nopenerimaan'];
                $temp['tglpenerimaan'] = $key['tglpenerimaan'];
                $temp['kdobat'] = $key['kdobat'];
                $temp['jumlah'] = 0;
                $temp['kdruang'] = 'Gd-03010100';
                $temp['harga'] = (float)$key['harga'] ?? 0;
                $temp['flag'] = $key['flag'];
                $temp['tglexp'] = $key['tglexp'];
                $temp['nobatch'] = $key['nobatch'];
                $temp['nodistribusi'] = $key['nodistribusi'];
                $temp['created_at'] = date('Y-m-d H:i:s');
                $temp['updated_at'] = date('Y-m-d H:i:s');
                $stok[] = $temp;
            }
        }
        if (count($obDep) > 0) {
            foreach ($obDep as $key) {

                $temp['nopenerimaan'] = $key['nopenerimaan'];
                $temp['tglpenerimaan'] = $key['tglpenerimaan'];
                $temp['kdobat'] = $key['kdobat'];
                $temp['jumlah'] = 0;
                $temp['kdruang'] = 'Gd-05010100';
                $temp['harga'] = $key['harga'];
                $temp['flag'] = $key['flag'];
                $temp['tglexp'] = $key['tglexp'];
                $temp['nobatch'] = $key['nobatch'];
                $temp['nodistribusi'] = $key['nodistribusi'];
                $temp['created_at'] = date('Y-m-d H:i:s');
                $temp['updated_at'] = date('Y-m-d H:i:s');
                $stok[] = $temp;
            }
        }
        if (count($stok) <= 0) {
            return [
                'stok' => false,
            ];
        }
        foreach (array_chunk($stok, 1000) as $t) {
            $data = FarmasinewStokreal::insert($t);
        }
        // sleep(20);
        return [
            'obDFo' => $obDFo,
            'stok' => $stok,
            'data' => $data ?? false,
        ];
    }

    public function insertHarga()
    {

        // insert harga
        $harga = [];
        $allGud = ['Gd-05010100', 'Gd-03010100'];
        $obAllDep = FarmasinewStokreal::selectRaw('* ,sum(jumlah) as total, avg(harga) as rharga')
            ->whereNotNull('harga')
            ->where('harga', '>', 0)
            ->groupBy('kdobat')
            ->get();

        if (count($obAllDep) > 0) {
            foreach ($obAllDep as $key) {

                // if ((float)$key['harga'] > 0) {
                $tHarga['nopenerimaan'] = $key['nopenerimaan'];
                $tHarga['kd_obat'] = $key['kdobat'];
                $tHarga['harga'] = (float)$key['harga'] > 0 ? (float)$key['harga'] : (float)$key['rharga'];
                $tHarga['tgl_mulai_berlaku'] = date('Y-m-d H:i:s');
                $tHarga['created_at'] = date('Y-m-d H:i:s');
                $tHarga['updated_at'] = date('Y-m-d H:i:s');
                $harga[] = $tHarga;
                // }
            }
        }
        if (count($harga) <= 0) {
            return [
                'harga' => false,
                'data' => $data ?? false,
            ];
        }
        DaftarHarga::truncate();
        foreach (array_chunk($harga, 1000) as $t) {
            $dataHarga = DaftarHarga::insert($t);
        }

        return [
            'obAllDep' => $obAllDep,
            'harga' => $dataHarga,
            'data' => $data ?? false,
        ];
    }

    public function setStokOpnameAwal()
    {
        $tanggal = StokOpname::select('rs5')->distinct()->orderBy('rs5', 'DESC')->first('rs5');
        $tglVal = $tanggal['rs5'];
        $newOpname = [];
        $opname = StokStokopname::whereNotBetween('tglopname', [$tglVal, $tglVal])->get();
        foreach ($opname as $key) {
            $temp = [
                'nopenerimaan' => $key['nopenerimaan'],
                'tglpenerimaan' => $key['tglpenerimaan'],
                'kdobat' => $key['kdobat'],
                'jumlah' => $key['jumlah'],
                'kdruang' => $key['kdruang'],
                'harga' => $key['harga'],
                'flag' => $key['flag'],
                'tglexp' => $key['tglexp'],
                'nobatch' => $key['nobatch'],
                'nodistribusi' => $key['nodistribusi'],
                'tglopname' => $key['tglopname'],
                'created_at' => $key['created_at'],
                'updated_at' => $key['updated_at'],
            ];
            $newOpname[] = $temp;
        }
        $mapingGudang = [
            ['nama' => 'Gudang Farmasi ( Kamar Obat )', 'kode' => 'Gd-05010100', 'lama' => 'GU0001'],
            ['nama' => 'Gudang Farmasi (Floor Stok)', 'kode' => 'Gd-03010100', 'lama' => 'GU0002'],
            ['nama' => 'Floor Stock 1 (AKHP)', 'kode' => 'Gd-03010101', 'lama' => 'RC0001'],
            ['nama' => 'Depo Rawat inap', 'kode' => 'Gd-04010102', 'lama' => 'AP0002'],
            ['nama' => 'Depo OK', 'kode' => 'Gd-04010103', 'lama' => 'AP0005'],
            ['nama' => 'Depo Rawat Jalan', 'kode' => 'Gd-05010101', 'lama' => 'AP0001'],
            ['nama' => 'Depo IGD', 'kode' => 'Gd-02010104', 'lama' => 'AP0007']
        ];

        $mapingDep = ['GU0001', 'GU0002', 'RC0001', 'AP0002', 'AP0005', 'AP0001', 'AP0007'];

        $mapingObat = MapingObat::with([
            // 'master:rs1,rs4',
            'stokopname' => function ($stok) use ($mapingDep, $tglVal) {
                $stok->whereIn('rs4', $mapingDep)
                    ->whereBetween('rs5', [$tglVal, $tglVal]);
            },
            'rincipenerimaan' => function ($tr) {
                $tr->select(
                    'rs82.rs1',
                    'rs82.rs2',
                    'rs82.rs6',
                    'rs82.rs7',
                    'rs81.rs2 as tanggal',
                )
                    ->leftJoin('rs81', 'rs81.rs1', '=', 'rs82.rs1')
                    ->orderBy('rs81.rs2', 'DESC');
                // ->limit(10);
            }
        ])
            ->where('obatbaru', '<>', '')
            // ->limit(50)
            ->get();

        foreach ($mapingObat as $key) {
            foreach ($key['stokopname'] as $st) {
                $raw = collect($mapingGudang)
                    ->where('lama', $st['rs4'])
                    ->map(function ($it, $key) {
                        return $it['kode'] ?? null;
                    });

                $item = current((array)$raw); // value of current object
                $anu = key((array)$item); // key of the object value
                $ruang = $item[$anu] ?? $st['rs4'];
                if ($ruang === 'Gd-05010100') $nPen = 'G-KO';
                else if ($ruang === 'Gd-03010100') $nPen = 'G-FO';
                else if ($ruang === 'Gd-03010101') $nPen = 'D-FO';
                else if ($ruang === 'Gd-04010102') $nPen = 'D-RI';
                else if ($ruang === 'Gd-04010103') $nPen = 'D-OK';
                else if ($ruang === 'Gd-05010101') $nPen = 'D-RJ';
                else if ($ruang === 'Gd-02010104') $nPen = 'D-IGD';
                else  $nPen = 'NDF';
                $temp = [
                    'nopenerimaan' => '001/' . date('m/Y') . '/opnameAwal/' . $nPen,
                    'tglpenerimaan' => $key['rincipenerimaan']['tanggal'] ?? date('Y-m-d H:i:s'),
                    'kdobat' => $key['obatbaru'],
                    'jumlah' => $st['rs2'],
                    'kdruang' => $ruang,
                    'harga' => $st['rs3'],
                    'flag' => '',
                    'tglexp' => $key['rincipenerimaan']['rs7'] ?? null,
                    'nobatch' => $key['rincipenerimaan']['rs6'] ?? '',
                    'nodistribusi' => '',
                    'tglopname' => $st['rs5'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
                $newOpname[] = $temp;
            }
        }
        if (count($newOpname) > 0) {
            StokStokopname::truncate();
            foreach (array_chunk($newOpname, 100) as $t) {
                $data['ins'] = StokStokopname::insert($t);
            }
        }
        // $data['mapingObat'] = $mapingObat;
        $data['newOpname'] = $newOpname;
        return new JsonResponse($data);
    }

    public function PerbaikanStokPerDepo(Request $request)
    {
        $depo = $request->kdruang;
        // $obat = $request->kdobat;
        $stok = Stokrel::select('kdobat')
            ->where('kdruang', $depo)
            ->distinct('kdobat')
            ->orderBy('kdobat', 'ASC')
            ->pluck('kdobat');
        $data = [];
        foreach ($stok as $obat) {
            // return new JsonResponse($obat);

            $temp = self::getDataTrans($depo, $obat);

            if ($temp['data']['tts'] !== $temp['data']['sisa']) $data[] = $temp['data'];
        }

        return new JsonResponse([
            'count data' => sizeof($data),
            'data' => $data
        ]);
    }
    public function cekPenerimaan(Request $request)
    {
        $gudangs = ['Gd-05010100', 'Gd-03010100'];
        if (!in_array($request->kdruang, $gudangs)) {
            return new JsonResponse([
                'message' => 'Yang bisa cek penerimaan hanya gudang',
            ], 410);
        }

        $penrimaanrinci = PenerimaanRinci::where('kdobat', $request->kdobat)->get();
        $nope = PenerimaanRinci::where('kdobat', $request->kdobat)->distinct('nopenerimaan')->pluck('nopenerimaan');
        $stok = FarmasinewStokreal::whereIn('nopenerimaan', $nope)
            ->where('kdobat', $request->kdobat)
            ->where('kdruang', $request->kdruang)
            ->get();
        $noba = $stok->pluck('nobatch')->toArray();
        $tgl = $stok[0]->tglpenerimaan ?? null;
        $da = [];
        $msg = 'Tidak Ditemukan data penerimaan yang membutuhkan perubahan';
        if (count($penrimaanrinci) !== count($stok)) {
            foreach ($penrimaanrinci as $key) {
                if (!in_array($key['no_batch'], $noba)) {
                    // $da[]=$key;
                    FarmasinewStokreal::updateOrCreate(
                        [
                            'nopenerimaan' => $key['nopenerimaan'],
                            'kdobat' => $key['kdobat'],
                            'kdruang' => $request->kdruang,
                            'nobatch' => $key['no_batch'],
                        ],
                        [
                            'tglexp' => $key['tgl_exp'],
                            'harga' => $key['harga_netto_kecil'],
                            'tglpenerimaan' => $tgl,
                            'jumlah' => 0,
                            'flag' => ''

                        ]
                    );
                    $msg = 'Ada Penambahan Penerimaan';
                }
            }
        }


        return new JsonResponse([
            'message' => $msg,
            'penrimaanrinci' => $penrimaanrinci,
            'nope' => $nope,
            'stok' => $stok,
            'noba' => $noba,
            'da' => $da,
        ]);
    }
    public function newPerbaikanStok(Request $request)
    {
        $depo = $request->kdruang;
        $obat = $request->kdobat;
        $data = self::getDataTrans($depo, $obat);


        return new JsonResponse($data['data'], $data['status']);
    }
    public static function getDataTrans($koderuangan, $kdobat)
    {
        // $mapingGudang = [
        //     ['nama' => 'Gudang Farmasi ( Kamar Obat )', 'kode' => 'Gd-05010100', 'lama' => 'GU0001'],
        //     ['nama' => 'Gudang Farmasi (Floor Stok)', 'kode' => 'Gd-03010100', 'lama' => 'GU0002'],
        //     ['nama' => 'Floor Stock 1 (AKHP)', 'kode' => 'Gd-03010101', 'lama' => 'RC0001'],
        //     ['nama' => 'Depo Rawat inap', 'kode' => 'Gd-04010102', 'lama' => 'AP0002'],
        //     ['nama' => 'Depo OK', 'kode' => 'Gd-04010103', 'lama' => 'AP0005'],
        //     ['nama' => 'Depo Rawat Jalan', 'kode' => 'Gd-05010101', 'lama' => 'AP0001'],
        //     ['nama' => 'Depo IGD', 'kode' => 'Gd-02010104', 'lama' => 'AP0007']
        // ];

        try {
            DB::connection('farmasi')->beginTransaction();
            $data = [];
            $gudangs = ['Gd-05010100', 'Gd-03010100'];
            $depos = ['Gd-03010101', 'Gd-04010102', 'Gd-04010103', 'Gd-05010101', 'Gd-02010104'];
            $koderuangan = $koderuangan;
            $bulan = date('m');
            $tahun = date('Y');
            // $bulan = request('bulan');
            // $tahun = request('tahun');
            $x = $tahun . '-' . $bulan;
            $tglAwal = $x . '-01';
            $tglAkhir = $x . date('-t', strtotime($x . '-01'));
            $dateAwal = Carbon::parse($tglAwal);
            $dateAkhir = Carbon::parse($tglAkhir);
            $blnLaluAwal = $dateAwal->subMonth()->format('Y-m');
            $blnLaluAkhir = $dateAkhir->subMonth()->format('Y-m-t');

            $rawNoper = [];
            $message = 'Stok sudah Sesuai tidak ada yang perlu di update';
            if (in_array($koderuangan, $gudangs)) {
                $saldoAwalRinci = StokStokopname::select('tglopname', 'nopenerimaan', 'kdobat', DB::raw('sum(jumlah) as total'))
                    // ->whereBetween('tglopname', [$blnLaluAwal . ' 00:00:00', $blnLaluAkhir . ' 23:59:59'])
                    ->where('tglopname', 'LIKE', $blnLaluAwal . '%')
                    ->where('kdruang', $koderuangan)
                    ->where('kdobat', $kdobat)
                    ->groupBy('nopenerimaan', 'tglopname', 'kdruang', 'kdobat')
                    ->get();
                $saldoAwal = collect($saldoAwalRinci)->sum('total');
                $stokid = FarmasinewStokreal::select('id')->where('kdruang', $koderuangan)
                    ->where('kdobat', $kdobat)
                    ->pluck('id');
                $penyesuaianRinci = PenyesuaianStok::select('stokreal_id', 'nopenerimaan', DB::raw('sum(penyesuaian) as jumlah'))
                    ->whereIn('stokreal_id', $stokid)
                    ->whereBetween('tgl_penyesuaian', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                    ->groupBy('stokreal_id', 'nopenerimaan')
                    ->get();
                $penyesuaian = collect($penyesuaianRinci)->sum('jumlah');
                $penerimaanRinci = PenerimaanRinci::select(
                    'penerimaan_r.kdobat',
                    'penerimaan_r.nopenerimaan',
                    DB::raw('sum(jml_terima_k) as jumlah')
                )
                    ->join('penerimaan_h', 'penerimaan_r.nopenerimaan', '=', 'penerimaan_h.nopenerimaan')
                    ->whereBetween('penerimaan_h.tglpenerimaan', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                    ->where('penerimaan_h.gudang', $koderuangan)
                    ->where('penerimaan_h.kunci', '1')
                    ->where('penerimaan_r.kdobat', $kdobat)
                    ->groupBy('penerimaan_r.nopenerimaan', 'penerimaan_r.kdobat')
                    ->get();
                $penerimaan = collect($penerimaanRinci)->sum('jumlah');

                $mutasiMasukRinci = Mutasigudangkedepo::select(
                    'mutasi_gudangdepo.kd_obat as kdobat',
                    'mutasi_gudangdepo.nopenerimaan',
                    DB::raw('sum(mutasi_gudangdepo.jml) as jumlah')
                )
                    ->join('permintaan_h', 'permintaan_h.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                    ->whereBetween('permintaan_h.tgl_terima_depo', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                    ->where('permintaan_h.dari', $koderuangan)
                    ->where('mutasi_gudangdepo.kd_obat', $kdobat)
                    ->groupBy('mutasi_gudangdepo.nopenerimaan', 'mutasi_gudangdepo.kd_obat')
                    ->get();
                $mutasiMasuk = collect($mutasiMasukRinci)->sum('jumlah');

                $mutasiKeluarRinci = Mutasigudangkedepo::select(
                    'mutasi_gudangdepo.kd_obat as kdobat',
                    'mutasi_gudangdepo.nopenerimaan',
                    DB::raw('sum(mutasi_gudangdepo.jml) as jumlah')
                )
                    ->join('permintaan_h', 'permintaan_h.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                    ->whereBetween('permintaan_h.tgl_kirim_depo', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                    ->where('permintaan_h.tujuan', $koderuangan)
                    ->where('mutasi_gudangdepo.kd_obat', $kdobat)
                    ->groupBy('mutasi_gudangdepo.nopenerimaan', 'mutasi_gudangdepo.kd_obat')
                    ->get();
                $mutasiKeluar = collect($mutasiKeluarRinci)->sum('jumlah');

                $rusakRinci = BarangRusak::select(
                    'kd_obat',
                    'nopenerimaan',
                    DB::raw('sum(jumlah) as jumlah')
                )
                    ->whereBetween('tgl_kunci', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                    ->where('kd_obat', $kdobat)
                    ->where('kunci', '1')
                    ->groupBy('kd_obat', 'nopenerimaan')
                    ->get();
                $rusak = collect($rusakRinci)->sum('jumlah');

                $returGudangRinci = ReturGudangDetail::select(
                    'retur_gudang_details.kd_obat',
                    'retur_gudang_details.nopenerimaan',
                    DB::raw('sum(retur_gudang_details.jumlah_retur) as jumlah')
                )
                    ->leftJoin('retur_gudangs', 'retur_gudangs.no_retur', '=', 'retur_gudang_details.no_retur')
                    ->where('retur_gudangs.gudang', $koderuangan)
                    ->whereBetween('retur_gudangs.tgl_retur', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                    ->where('retur_gudang_details.kd_obat', $kdobat)
                    ->where('retur_gudangs.kunci', '1')
                    ->groupBy('retur_gudang_details.nopenerimaan', 'retur_gudang_details.kd_obat', 'retur_gudangs.gudang')
                    ->get();
                $returGudang = collect($returGudangRinci)->sum('jumlah');

                $returPbfRinci = Returpbfrinci::select(
                    'retur_penyedia_r.kd_obat',
                    'retur_penyedia_r.nopenerimaan_default as nopenerimaan',
                    DB::raw('sum(retur_penyedia_r.jumlah_retur) as jumlah')
                )
                    ->leftJoin('retur_penyedia_h', 'retur_penyedia_h.no_retur', '=', 'retur_penyedia_r.no_retur')
                    ->where('retur_penyedia_h.gudang', $koderuangan)
                    ->where('retur_penyedia_h.tgl_kunci', 'LIKE', '%' . $x . '%')
                    ->where('retur_penyedia_r.kd_obat', $kdobat)
                    ->where('retur_penyedia_h.kunci', '1')
                    ->groupBy('retur_penyedia_r.nopenerimaan', 'retur_penyedia_r.kd_obat', 'retur_penyedia_h.gudang')
                    ->get();
                $returPbf = collect($returPbfRinci)->sum('jumlah');

                $pengembalianPinjamanRinci = PengembalianRinciFifo::select(
                    'pengembalian_rinci_fifos.kdobat',
                    'pengembalian_rinci_fifos.nopenerimaan',
                    DB::raw('sum(pengembalian_rinci_fifos.jml_dikembalikan) as jumlah')
                )
                    ->leftJoin('pengembalians', 'pengembalians.nopengembalian', '=', 'pengembalian_rinci_fifos.nopengembalian')
                    ->where('pengembalians.kdruang', $koderuangan)
                    ->where('pengembalians.tgl_kunci', 'LIKE', '%' . $x . '%')
                    ->where('pengembalian_rinci_fifos.kdobat', $kdobat)
                    ->where('pengembalians.flag', '1')
                    ->groupBy('pengembalian_rinci_fifos.nopenerimaan', 'pengembalian_rinci_fifos.kdobat', 'pengembalians.kdruang')
                    ->get();
                $pengembalianPinj = collect($pengembalianPinjamanRinci)->sum('jumlah');

                $totalStok = FarmasinewStokreal::select('kdobat', DB::raw('sum(jumlah) as jumlah'))->where('kdobat', $kdobat)
                    ->where('kdruang', $koderuangan)->first();

                $tts = $totalStok->jumlah ?? 0;
                $sal = $saldoAwal ?? 0;
                $peny = $penyesuaian ?? 0;
                $trm = $penerimaan ?? 0;
                $mutma = $mutasiMasuk ?? 0;
                $mutkel = $mutasiKeluar ?? 0;
                $rus = $rusak ?? 0;
                $retG = $returGudang ?? 0;
                $retPbf = $returPbf ?? 0;
                $pengPinj = $pengembalianPinj ?? 0;
                $masuk = (float)$sal + (float)$peny + (float)$trm + (float)$mutma + (float)$retG;
                $keluar = (float)$mutkel + (float)$rus + (float)$retPbf + (float)$pengPinj;
                $sisa = (float)$masuk - (float)$keluar;

                // cek rincian
                $stok = FarmasinewStokreal::lockForUpdate()
                    ->where('kdobat', $kdobat)
                    ->where('kdruang', $koderuangan)
                    ->orderBy('tglpenerimaan', 'DESC')
                    ->get();
                $nopeSt = [];
                foreach ($saldoAwalRinci as $key) {
                    $nopeSt[] = $key->nopenerimaan;
                }
                foreach ($penerimaanRinci as $key) {
                    $nopeSt[] = $key->nopenerimaan;
                }
                foreach ($mutasiMasukRinci as $key) {
                    $nopeSt[] = $key->nopenerimaan;
                }
                foreach ($mutasiKeluarRinci as $key) {
                    $nopeSt[] = $key->nopenerimaan;
                }
                foreach ($rusakRinci as $key) {
                    $nopeSt[] = $key->nopenerimaan;
                }
                foreach ($returGudangRinci as $key) {
                    $nopeSt[] = $key->nopenerimaan;
                }
                foreach ($penerimaanRinci as $key) {
                    $nopeSt[] = $key->nopenerimaan;
                }
                foreach ($returPbfRinci as $key) {
                    $nopeSt[] = $key->nopenerimaan;
                }
                foreach ($pengembalianPinjamanRinci as $key) {
                    $nopeSt[] = $key->nopenerimaan;
                }
                $uniNopeSt = array_unique($nopeSt);

                // return [
                //     'stok' => $stok,
                //     'nope' => $nope,
                //     'uniNope' => $uniNope,
                //     'penerimaanRinci' => $penerimaanRinci,
                //     'mutasiKeluarRinci' => $mutasiKeluarRinci,
                //     'saldoAwalRinci' => $saldoAwalRinci,
                //     'tts' => $tts,
                //     'sisa' => $sisa,
                // ];
                $err = [];
                if ((float)$sisa != (float)$tts) {
                    $ada = $sisa;
                    $index = 0;
                    $tolalIndex = count($stok) - 1;
                    // // nolkan semua stok
                    foreach ($stok as $st) {
                        $st->update([
                            'jumlah' => 0
                        ]);
                    }
                    $returNya = [];
                    if ($ada > 0) {
                        foreach ($uniNopeSt as $key) {
                            $salAwal =  collect($saldoAwalRinci)->firstWhere('nopenerimaan', $key)->total ?? 0;
                            $mutMas =  collect($mutasiMasukRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;
                            $trm =  collect($penerimaanRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;
                            $retGu =  collect($returGudangRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;
                            $peny =  collect($penyesuaianRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;
                            // keluar
                            $mutKel =  collect($mutasiKeluarRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;
                            $rus =  collect($rusakRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;
                            $retPbfx =  collect($returPbfRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;
                            $pengPinjx =  collect($pengembalianPinjamanRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;

                            $maSuk = (float) $salAwal + (float) $mutMas + (float) $peny + (float) $retGu + (float)$trm;
                            $keLuar = (float)$mutKel + (float)$rus + (float)$retPbfx + (float)$pengPinjx;
                            $sisanya = $maSuk - $keLuar;

                            if ($sisanya > 0) {
                                // $temp = $anuaad + $sisanya;
                                // $anuaad = $temp;
                                $stokNya = collect($stok)->firstWhere('nopenerimaan', $key);
                                if ($stokNya) {
                                    if ((float)$sisanya >= (float)$ada) {
                                        $sisaJumlah = 0;
                                        $stokNya->update(['jumlah' => $ada]);
                                    } else if ((float)$ada > 0) {
                                        $sisaJumlah = (float)$ada - (float) $sisanya;
                                        $stokNya->update(['jumlah' => $sisanya]);
                                    }
                                    $ada = $sisaJumlah;
                                } else {
                                    $err[] = [
                                        'data' => [
                                            'message' => 'stok dengan nomor penerimaan ' . $key . ' tidak ditemukan'
                                        ],
                                        'status' => 410
                                    ];
                                    $message = 'stok dengan nomor penerimaan ' . $key . ' tidak ditemukan';
                                }
                            }
                            // $tmpmas = $anumas + $maSuk;
                            // $anumas = $tmpmas;
                            // $tmpkel = $anukel + $keLuar;
                            // $anumas = $tmpkel;

                        }
                        $message = 'Cek Stok Gudang selesai, Stok sudah di update';
                    }


                    if ($sisa == 0) {
                        foreach ($stok as $st) {
                            $st->update([
                                'jumlah' => $sisa
                            ]);
                        }
                        $message = 'Cek Stok Gudang selesai, Stok Habis';
                    }
                    if ($sisa < 0) {
                        foreach ($stok as $st) {
                            $st->update([
                                'jumlah' => 0
                            ]);
                        }
                        $message = 'Sisa Stok kurang dari 0, Stok Tidak diganti silahkan cek transaksi';
                    }
                }


                $data = [
                    'saldoAwal' => $saldoAwal ?? [],
                    'stokid' => $stokid,
                    'penyesuaian' => $penyesuaian,
                    'penerimaan' => $penerimaan,
                    'mutasiMasuk' => $mutasiMasuk,
                    'mutasiKeluar' => $mutasiKeluar,
                    'totalStok' => $totalStok,
                    'masuk' => $masuk,
                    'keluar' => $keluar,
                    'err' => $err,

                    'stok' => $stok ?? [],
                    'ret' => $ret ?? [],

                    'uniNope' => $uniNopeSt,
                    'penerimaanRinci' => $penerimaanRinci,
                    'mutasiKeluarRinci' => $mutasiKeluarRinci,
                    'mutasiMasukRinci' => $mutasiMasukRinci,
                    'saldoAwalRinci' => $saldoAwalRinci,
                    'returGudangRinci' => $returGudangRinci,
                    'returPbfRinci' => $returPbfRinci,

                    'tts' => $tts,
                    'sisa' => $sisa,
                    'sal' => $sal,
                    'peny' => $peny,
                    'trm' => $trm,
                    'mutma' => $mutma,
                    'mutkel' => $mutkel,
                    'rus' => $rus,
                    'retG' => $retG,
                    // 'stok' => $stok ?? [],
                    'message' => $message
                ];
            } else {
                /*
             * harus memetakan mutasi masuk dan mutasi keluar berdasarkan
             * kode obat, momor penerimaan, dan kalo bisa nomor batch, tgl exp dan harga
             */

                $saldoAwalDepoRinci = StokStokopname::select(
                    'tglopname',
                    'nopenerimaan',
                    'kdobat',
                    'nobatch',
                    'harga',
                    DB::raw('sum(jumlah) as total')
                )
                    // ->whereBetween('tglopname', [$blnLaluAwal . ' 00:00:00', $blnLaluAkhir . ' 23:59:59'])
                    ->where('tglopname', 'LIKE', $blnLaluAwal . '%')
                    ->where('kdruang', $koderuangan)
                    ->where('kdobat', $kdobat)
                    ->groupBy('nopenerimaan', 'tglopname', 'kdruang', 'kdobat')
                    ->get();

                $saldoAwal = collect($saldoAwalDepoRinci)->sum('total');

                $stokid = FarmasinewStokreal::select('id')->where('kdruang', $koderuangan)
                    ->where('kdobat', $kdobat)
                    ->pluck('id');
                $penyesuaian = PenyesuaianStok::select('stokreal_id', DB::raw('sum(penyesuaian) as jumlah'))
                    ->whereIn('stokreal_id', $stokid)
                    // ->whereNull('flag') // local only
                    ->whereBetween('tgl_penyesuaian', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                    ->groupBy('stokreal_id')
                    ->first();

                $mutasiMasukDepoRinci = Mutasigudangkedepo::select(
                    'mutasi_gudangdepo.kd_obat as kdobat',
                    'mutasi_gudangdepo.nopenerimaan',
                    'mutasi_gudangdepo.nobatch',
                    'mutasi_gudangdepo.tglexp',
                    'mutasi_gudangdepo.no_permintaan',
                    'permintaan_h.tgl_terima_depo',
                    DB::raw('sum(mutasi_gudangdepo.jml) as jumlah')
                )
                    ->join('permintaan_h', 'permintaan_h.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                    ->whereBetween('permintaan_h.tgl_terima_depo', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                    ->where('permintaan_h.dari', $koderuangan)
                    ->where('mutasi_gudangdepo.kd_obat', $kdobat)
                    ->groupBy(
                        'mutasi_gudangdepo.kd_obat',
                        'mutasi_gudangdepo.nopenerimaan',
                    )
                    ->orderby('permintaan_h.tgl_terima_depo', 'DESC')
                    ->get();
                $mutasiMasuk = collect($mutasiMasukDepoRinci)->sum('jumlah');

                $mutasiKeluarDepoRinci = Mutasigudangkedepo::select(
                    'mutasi_gudangdepo.kd_obat as kdobat',
                    'mutasi_gudangdepo.nopenerimaan',
                    'mutasi_gudangdepo.nobatch',
                    'mutasi_gudangdepo.tglexp',
                    'mutasi_gudangdepo.no_permintaan',
                    'permintaan_h.tgl_kirim_depo',
                    DB::raw('sum(mutasi_gudangdepo.jml) as jumlah')
                )
                    ->join('permintaan_h', 'permintaan_h.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                    ->whereBetween('permintaan_h.tgl_kirim_depo', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                    ->where('permintaan_h.tujuan', $koderuangan)
                    ->where('mutasi_gudangdepo.kd_obat', $kdobat)
                    ->groupBy(
                        'mutasi_gudangdepo.kd_obat',
                        'mutasi_gudangdepo.nopenerimaan',
                    )
                    ->get();
                $mutasiKeluar = collect($mutasiKeluarDepoRinci)->sum('jumlah');
                // // jika bukan depo ok
                if ($koderuangan !== 'Gd-04010103') {

                    //     // $noresep = Resepkeluarrinci::select(
                    //     //     'resep_keluar_r.noresep',
                    //     // )
                    //     //     ->join('resep_keluar_h', 'resep_keluar_h.noresep', '=', 'resep_keluar_r.noresep')
                    //     //     ->whereBetween('resep_keluar_h.tgl_permintaan', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                    //     //     ->where('resep_keluar_h.depo', $koderuangan)
                    //     //     ->where('resep_keluar_r.kdobat', $kdobat)
                    //     //     ->pluck('resep_keluar_r.noresep');

                    $resepKeluarRinci = Resepkeluarrinci::select(
                        'resep_keluar_r.kdobat',
                        'resep_keluar_r.nopenerimaan',
                        DB::raw('sum(resep_keluar_r.jumlah) as jumlah')
                    )
                        ->join('resep_keluar_h', 'resep_keluar_h.noresep', '=', 'resep_keluar_r.noresep')
                        ->whereBetween('resep_keluar_h.tgl_selesai', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('resep_keluar_h.depo', $koderuangan)
                        ->where('resep_keluar_r.kdobat', $kdobat)
                        ->where('resep_keluar_r.jumlah', '>', 0)
                        ->groupBy('resep_keluar_r.kdobat', 'resep_keluar_r.nopenerimaan')
                        ->get();
                    $resepKeluar = collect($resepKeluarRinci)->sum('jumlah');
                    $returRinci = Returpenjualan_r::select(
                        'retur_penjualan_r.kdobat',
                        'retur_penjualan_r.nopenerimaan',
                        DB::raw('sum(retur_penjualan_r.jumlah_retur) as jumlah')
                    )
                        ->join('retur_penjualan_h', 'retur_penjualan_r.noretur', '=', 'retur_penjualan_h.noretur')
                        ->join('resep_keluar_h', 'retur_penjualan_r.noresep', '=', 'resep_keluar_h.noresep')
                        ->whereBetween('retur_penjualan_h.tgl_retur', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('resep_keluar_h.depo', $koderuangan)
                        // ->whereIn('noresep', $noresep)
                        ->where('retur_penjualan_r.kdobat', $kdobat)
                        ->groupBy('retur_penjualan_r.kdobat', 'retur_penjualan_r.nopenerimaan')
                        ->get();
                    $retur = collect($returRinci)->sum('jumlah');

                    $resepKeluarRacikanRinci = Resepkeluarrinciracikan::select(
                        'resep_keluar_racikan_r.kdobat',
                        'resep_keluar_racikan_r.nopenerimaan',
                        DB::raw('sum(resep_keluar_racikan_r.jumlah) as jumlah')
                    )
                        ->join('resep_keluar_h', 'resep_keluar_racikan_r.noresep', '=', 'resep_keluar_h.noresep')
                        ->whereBetween('resep_keluar_h.tgl_selesai', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('resep_keluar_h.depo', $koderuangan)
                        ->where('resep_keluar_racikan_r.kdobat', $kdobat)
                        ->groupBy('resep_keluar_racikan_r.kdobat', 'resep_keluar_racikan_r.nopenerimaan')
                        ->get();
                    $resepKeluarRacikan = collect($resepKeluarRacikanRinci)->sum('jumlah');
                } else {
                    $noresep = PersiapanOperasiRinci::select(
                        'persiapan_operasi_rincis.noresep',
                    )->join('persiapan_operasis', 'persiapan_operasi_rincis.nopermintaan', '=', 'persiapan_operasis.nopermintaan')
                        ->where(function ($w) use ($tglAwal, $tglAkhir) {
                            $w->whereBetween('persiapan_operasis.tgl_permintaan', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                                ->orWhereBetween('persiapan_operasis.tgl_retur', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59']);
                        })
                        ->where('persiapan_operasi_rincis.kd_obat', $kdobat)
                        ->groupBy('persiapan_operasi_rincis.noresep')
                        ->pluck('persiapan_operasi_rincis.noresep');

                    $resepKeluarRinci = Resepkeluarrinci::select(
                        'resep_keluar_r.kdobat',
                        'resep_keluar_r.nopenerimaan',
                        DB::raw('sum(resep_keluar_r.jumlah) as jumlah')
                    )
                        ->join('resep_keluar_h', 'resep_keluar_h.noresep', '=', 'resep_keluar_r.noresep')
                        ->whereBetween('resep_keluar_h.tgl_permintaan', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('resep_keluar_h.depo', $koderuangan)
                        ->where('resep_keluar_r.kdobat', $kdobat)
                        ->whereNotIn('resep_keluar_h.noresep', $noresep)
                        ->groupBy('resep_keluar_r.kdobat', 'resep_keluar_r.nopenerimaan')
                        ->get();
                    $resepKeluar = collect($resepKeluarRinci)->sum('jumlah');


                    $returRinci = Returpenjualan_r::select(
                        'retur_penjualan_r.kdobat',
                        'retur_penjualan_r.nopenerimaan',
                        DB::raw('sum(retur_penjualan_r.jumlah_retur) as jumlah')
                    )
                        ->join('retur_penjualan_h', 'retur_penjualan_r.noretur', '=', 'retur_penjualan_h.noretur')
                        ->join('resep_keluar_h', 'retur_penjualan_r.noresep', '=', 'resep_keluar_h.noresep')
                        ->whereBetween('retur_penjualan_h.tgl_retur', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('resep_keluar_h.depo', $koderuangan)

                        ->where('retur_penjualan_r.kdobat', $kdobat)
                        ->groupBy('retur_penjualan_r.kdobat', 'retur_penjualan_r.nopenerimaan')
                        ->get();
                    $retur = collect($returRinci)->sum('jumlah');

                    $resepKeluarRacikanRinci = Resepkeluarrinciracikan::select(
                        'resep_keluar_racikan_r.kdobat',
                        'resep_keluar_racikan_r.nopenerimaan',
                        DB::raw('sum(resep_keluar_racikan_r.jumlah) as jumlah')
                    )
                        ->join('resep_keluar_h', 'resep_keluar_racikan_r.noresep', '=', 'resep_keluar_h.noresep')
                        ->whereBetween('resep_keluar_h.tgl_permintaan', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('resep_keluar_h.depo', $koderuangan)
                        ->where('resep_keluar_racikan_r.kdobat', $kdobat)
                        ->whereNotIn('resep_keluar_h.noresep', $noresep)
                        ->groupBy('resep_keluar_racikan_r.kdobat', 'resep_keluar_racikan_r.nopenerimaan')
                        ->get();
                    $resepKeluarRacikan = collect($resepKeluarRacikanRinci)->sum('jumlah');

                    //     // $persiapanOperasi = PersiapanOperasiRinci::select(
                    //     //     'persiapan_operasi_rincis.kd_obat',
                    //     //     DB::raw('sum(persiapan_operasi_rincis.jumlah_minta) as minta'),
                    //     //     DB::raw('sum(persiapan_operasi_rincis.jumlah_distribusi) as distribusi'),
                    //     //     DB::raw('sum(persiapan_operasi_rincis.jumlah_kembali) as kembali'),
                    //     //     DB::raw('sum(persiapan_operasi_rincis.jumlah_resep) as resep'),
                    //     // )->join('persiapan_operasis', 'persiapan_operasi_rincis.nopermintaan', '=', 'persiapan_operasis.nopermintaan')
                    //     //     ->whereBetween('persiapan_operasis.tgl_permintaan', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                    //     //     ->where('persiapan_operasi_rincis.kd_obat', $kdobat)
                    //     //     ->first();

                    $persiapanOperasiDistribusiRinci = PersiapanOperasiDistribusi::select(
                        'persiapan_operasi_distribusis.kd_obat',
                        'persiapan_operasi_distribusis.nopenerimaan',
                        DB::raw('sum(persiapan_operasi_distribusis.jumlah) as distribusi'),
                    )
                        ->join('persiapan_operasis', 'persiapan_operasi_distribusis.nopermintaan', '=', 'persiapan_operasis.nopermintaan')
                        ->whereBetween('persiapan_operasis.tgl_permintaan', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('persiapan_operasi_distribusis.kd_obat', $kdobat)
                        ->whereIn('persiapan_operasis.flag', ['2', '3', '4'])
                        ->groupBy('persiapan_operasi_distribusis.kd_obat', 'persiapan_operasi_distribusis.nopenerimaan')
                        ->get();
                    $distribusiOk = collect($persiapanOperasiDistribusiRinci)->sum('distribusi');
                    $persiapanOperasiKmbaliRinci = PersiapanOperasiDistribusi::select(
                        'persiapan_operasi_distribusis.kd_obat',
                        'persiapan_operasi_distribusis.nopenerimaan',
                        DB::raw('sum(persiapan_operasi_distribusis.jumlah_retur) as kembali'),
                    )
                        ->join('persiapan_operasis', 'persiapan_operasi_distribusis.nopermintaan', '=', 'persiapan_operasis.nopermintaan')
                        ->whereBetween('persiapan_operasis.tgl_retur', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('persiapan_operasi_distribusis.kd_obat', $kdobat)
                        ->whereIn('persiapan_operasis.flag', ['2', '3', '4'])
                        ->groupBy('persiapan_operasi_distribusis.kd_obat', 'persiapan_operasi_distribusis.nopenerimaan')
                        ->get();

                    $kembaliOk = collect($persiapanOperasiKmbaliRinci)->sum('kembali');

                    foreach ($persiapanOperasiDistribusiRinci as $key) {
                        $rawNoper[] = $key->nopenerimaan;
                    }
                    foreach ($persiapanOperasiKmbaliRinci as $key) {
                        $rawNoper[] = $key->nopenerimaan;
                    }
                }

                // retur gudang

                $returGudangRinci = ReturGudangDetail::select(
                    'retur_gudang_details.kd_obat',
                    'retur_gudang_details.nopenerimaan',
                    DB::raw('sum(retur_gudang_details.jumlah_retur) as jumlah')
                )
                    ->leftJoin('retur_gudangs', 'retur_gudangs.no_retur', '=', 'retur_gudang_details.no_retur')
                    ->where('retur_gudangs.depo', $koderuangan)
                    ->whereBetween('retur_gudangs.tgl_retur', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                    ->where('retur_gudang_details.kd_obat', $kdobat)
                    ->where('retur_gudangs.kunci', '1')
                    ->groupBy('retur_gudang_details.kd_obat', 'retur_gudangs.depo', 'retur_gudang_details.nopenerimaan')
                    ->get();
                $returGudang = collect($returGudangRinci)->sum('jumlah');

                foreach ($saldoAwalDepoRinci as $key) {
                    $rawNoper[] = $key->nopenerimaan;
                }
                foreach ($mutasiMasukDepoRinci as $key) {
                    $rawNoper[] = $key->nopenerimaan;
                }
                foreach ($mutasiKeluarDepoRinci as $key) {
                    $rawNoper[] = $key->nopenerimaan;
                }
                foreach ($resepKeluarRinci as $key) {
                    $rawNoper[] = $key->nopenerimaan;
                }
                foreach ($returRinci as $key) {
                    $rawNoper[] = $key->nopenerimaan;
                }
                foreach ($resepKeluarRacikanRinci as $key) {
                    $rawNoper[] = $key->nopenerimaan;
                }
                // sudut pandang foreach
                $noper = array_unique($rawNoper);

                $totalStok = FarmasinewStokreal::select('kdobat', DB::raw('sum(jumlah) as jumlah'))->where('kdobat', $kdobat)
                    ->where('kdruang', $koderuangan)->first();

                $tts = $totalStok->jumlah ?? 0;
                $sal = $saldoAwal ?? 0;
                $peny = $penyesuaian->jumlah ?? 0;
                $mutma = $mutasiMasuk ?? 0;
                $ret = $retur ?? 0;
                $kem = $kembaliOk ?? 0;
                //keluar
                $dist = $distribusiOk ?? 0;
                $mutkel = $mutasiKeluar ?? 0;
                $reskel = $resepKeluar ?? 0;
                $reskelrac = $resepKeluarRacikan ?? 0;
                $retG = $returGudang ?? 0;



                // return [
                //     'saldoAwalDepoRinci' => $saldoAwalDepoRinci,
                //     'mutasiMasukDepoRinci' => $mutasiMasukDepoRinci,
                //     'mutasiKeluarDepoRinci' => $mutasiKeluarDepoRinci,
                //     'resepKeluarRinci' => $resepKeluarRinci,
                //     'returRinci' => $returRinci,
                //     'resepKeluarRacikanRinci' => $resepKeluarRacikanRinci,
                //     'persiapanOperasiDistribusiRinci' => $persiapanOperasiDistribusiRinci ?? [],
                //     'returGudangRinci' => $returGudangRinci,
                //     'saldoAwal' => $saldoAwal,
                //     'mutasiMasuk' => $mutasiMasuk,
                //     'mutasiKeluar' => $mutasiKeluar,
                //     'resepKeluar' => $resepKeluar,
                //     'retur' => $retur,
                //     'resepKeluarRacikan' => $resepKeluarRacikan,
                //     'distribusiOk' => $distribusiOk ?? null,
                //     'kembaliOk' => $kembaliOk ?? null,
                //     'returGudang' => $returGudang,
                // ];

                $stok = FarmasinewStokreal::lockForUpdate()
                    ->where('kdobat', $kdobat)
                    ->where('kdruang', $koderuangan)
                    ->orderBy('tglpenerimaan', 'DESC')
                    ->orderBy('nodistribusi', 'DESC')
                    ->get();
                $err = [];
                $hasil = [];
                $anuaad = 0;
                $anumas = 0;
                $anukel = 0;
                if ($koderuangan === 'Gd-04010103') {
                    $masuk = (float)$sal + (float)$peny + (float)$mutma + (float)$kem + (float) $ret;
                    $keluar = (float)$mutkel + (float)$dist + (float)$reskel + (float)$reskelrac + (float)$retG;
                    $sisa = (float)$masuk - (float)$keluar;
                    if ((float)$sisa != (float)$tts) {
                        //     // cek ketorolac
                        $ada = $sisa;
                        if ($sisa > 0) {
                            // nol kan semua
                            foreach ($stok as $st) {
                                $st->update([
                                    'jumlah' => 0
                                ]);
                            }
                            foreach ($noper as $key) {
                                // masuk
                                $salAwal =  collect($saldoAwalDepoRinci)->firstWhere('nopenerimaan', $key)->total ?? 0;
                                $mutMas =  collect($mutasiMasukDepoRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;
                                $retDep =  collect($returRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;
                                $kemB =  collect($persiapanOperasiKmbaliRinci)->firstWhere('nopenerimaan', $key)->kembali ?? 0;
                                // keluar
                                $disT =  collect($persiapanOperasiDistribusiRinci)->firstWhere('nopenerimaan', $key)->distribusi ?? 0;
                                $mutKel =  collect($mutasiKeluarDepoRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;
                                $resKel =  collect($resepKeluarRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;
                                $resKelRac =  collect($resepKeluarRacikanRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;
                                $retGud =  collect($returGudangRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;

                                $maSuk = (float) $salAwal + (float) $mutMas + (float) $kemB + (float) $retDep;
                                $keLuar = (float)$mutKel + (float)$resKel + (float)$resKelRac + (float)$retGud + (float)$disT;
                                $sisanya = $maSuk - $keLuar;

                                $tmpmas = $anumas + $maSuk;
                                $anumas = $tmpmas;
                                $tmpkel = $anukel + $keLuar;
                                $anumas = $tmpkel;

                                if ($sisanya > 0) {
                                    $temp = $anuaad + $sisanya;
                                    $anuaad = $temp;
                                    $stokNya = collect($stok)->firstWhere('nopenerimaan', $key);
                                    if ($stokNya) {
                                        if ((float)$sisanya >= (float)$ada) {
                                            $sisaJumlah = 0;
                                            $stokNya->update(['jumlah' => $ada]);
                                        } else if ((float)$ada > 0) {
                                            $sisaJumlah = (float)$ada - (float) $sisanya;
                                            $stokNya->update(['jumlah' => $sisanya]);
                                        }
                                        $ada = $sisaJumlah;
                                    } else {
                                        $err[] = [
                                            'data' => [
                                                'message' => 'stok dengan nomor penerimaan ' . $key . ' tidak ditemukan'
                                            ],
                                            'status' => 410
                                        ];

                                        $message = 'stok dengan nomor penerimaan ' . $key . ' tidak ditemukan';
                                    }
                                }

                                $hasil[] = [
                                    'nopenerimaan' => $key,
                                    'maSuk' => $maSuk,
                                    'keLuar' => $keLuar,
                                    'sisanya' => $sisanya,
                                    'salAwal' => $salAwal,
                                    'mutMas' => $mutMas,
                                    'retDep' => $retDep,
                                    'mutKel' => $mutKel,
                                    'resKel' => $resKel,
                                    'resKelRac' => $resKelRac,
                                    'retGud' => $retGud,
                                ];
                            }
                            $message = 'Cek Stok Depo selesai, Stok sudah di update';
                        }
                        if ($sisa == 0) {
                            foreach ($stok as $st) {
                                $st->update([
                                    'jumlah' => $sisa
                                ]);
                            }
                            $message = 'Cek Stok Depo Ok selesai, Stok Habis';
                        }
                        if ($sisa < 0) {
                            foreach ($stok as $st) {
                                $st->update([
                                    'jumlah' => 0
                                ]);
                            }
                            $message = 'Sisa Stok kurang dari 0, Stok Tidak diganti silahkan cek transaksi';
                        }
                    }
                } else {
                    $masuk = (float)$sal + (float)$peny + (float)$mutma + (float) $ret;
                    $keluar = (float)$mutkel + (float)$reskel + (float)$reskelrac + (float)$retG;
                    $sisa = (float)$masuk - (float)$keluar;

                    if ((float)$sisa != (float)$tts) {
                        //     // cek ketorolac
                        $ada = $sisa;
                        if ($sisa > 0) {
                            // nol kan semua
                            foreach ($stok as $st) {
                                $st->update([
                                    'jumlah' => 0
                                ]);
                            }
                            foreach ($noper as $key) {
                                // masuk
                                $salAwal =  collect($saldoAwalDepoRinci)->firstWhere('nopenerimaan', $key)->total ?? 0;
                                $mutMas =  collect($mutasiMasukDepoRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;
                                $retDep =  collect($returRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;
                                // keluar
                                $mutKel =  collect($mutasiKeluarDepoRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;
                                $resKel =  collect($resepKeluarRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;
                                $resKelRac =  collect($resepKeluarRacikanRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;
                                $retGud =  collect($returGudangRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;
                                $maSuk = (float) $salAwal + (float) $mutMas + (float) $retDep;
                                $keLuar = (float)$mutKel + (float)$resKel + (float)$resKelRac + (float)$retGud;
                                $sisanya = $maSuk - $keLuar;

                                $tmpmas = $anumas + $maSuk;
                                $anumas = $tmpmas;
                                $tmpkel = $anukel + $keLuar;
                                $anumas = $tmpkel;

                                if ($sisanya > 0) {
                                    $temp = $anuaad + $sisanya;
                                    $anuaad = $temp;
                                    $stokNya = collect($stok)->firstWhere('nopenerimaan', $key);
                                    if ($stokNya) {
                                        if ((float)$sisanya >= (float)$ada) {
                                            $sisaJumlah = 0;
                                            $stokNya->update(['jumlah' => $ada]);
                                        } else if ((float)$ada > 0) {
                                            $sisaJumlah = (float)$ada - (float) $sisanya;
                                            $stokNya->update(['jumlah' => $sisanya]);
                                        }

                                        $ada = $sisaJumlah;
                                    } else {
                                        $err[] = [
                                            'data' => [
                                                'message' => 'stok dengan nomor penerimaan ' . $key . ' tidak ditemukan'
                                            ],
                                            'status' => 410
                                        ];
                                        $message = 'stok dengan nomor penerimaan ' . $key . ' tidak ditemukan';
                                    }
                                }

                                $hasil[] = [
                                    'nopenerimaan' => $key,
                                    'maSuk' => $maSuk,
                                    'keLuar' => $keLuar,
                                    'sisanya' => $sisanya,
                                    'salAwal' => $salAwal,
                                    'mutMas' => $mutMas,
                                    'retDep' => $retDep,
                                    'mutKel' => $mutKel,
                                    'resKel' => $resKel,
                                    'resKelRac' => $resKelRac,
                                    'retGud' => $retGud,
                                ];
                            }
                            $message = 'Cek Stok Depo selesai, Stok sudah di update';
                        }
                        if ($sisa == 0) {
                            foreach ($stok as $st) {
                                $st->update([
                                    'jumlah' => $sisa
                                ]);
                            }
                            $message = 'Cek Stok Depo selesai, Stok Habis';
                        }
                        if ($sisa < 0) {
                            foreach ($stok as $st) {
                                $st->update([
                                    'jumlah' => 0
                                ]);
                            }
                            $message = 'Sisa Stok kurang dari 0, Stok Tidak diganti silahkan cek transaksi';
                        }
                    }
                }



                $data = [
                    'anuaad' => $anuaad,
                    'anumas' => $anumas,
                    'anukel' => $anukel,
                    'hasil' => $hasil,
                    'saldoAwal' => $saldoAwal,
                    'stokid' => $stokid,
                    'penyesuaian' => $penyesuaian,
                    'err' => $err,

                    'saldoAwalDepoRinci' => $saldoAwalDepoRinci,
                    'mutasiMasukDepoRinci' => $mutasiMasukDepoRinci,
                    'mutasiKeluarDepoRinci' => $mutasiKeluarDepoRinci,
                    'resepKeluarRinci' => $resepKeluarRinci,
                    'returRinci' => $returRinci,
                    'resepKeluarRacikanRinci' => $resepKeluarRacikanRinci,
                    'persiapanOperasiDistribusiRinci' => $persiapanOperasiDistribusiRinci ?? [],
                    'returGudangRinci' => $returGudangRinci,
                    'mutasiMasuk' => $mutasiMasuk,
                    'mutasiKeluar' => $mutasiKeluar,
                    'noresep' => $noresep ?? [],
                    'resepKeluar' => $resepKeluar,
                    'retur' => $retur,
                    'resepKeluarRacikan' => $resepKeluarRacikan,
                    'persiapanOperasiDistribusi' => $persiapanOperasiDistribusi ?? null,

                    'rawNoper' => $rawNoper,
                    'noper' => $noper,

                    'tts' => $tts,
                    'sal' => $sal,
                    'peny' => $peny,
                    'mutma' => $mutma,
                    'ret' => $ret,
                    'mutkel' => $mutkel,
                    'reskel' => $reskel,
                    'reskelrac' => $reskelrac,
                    'kem' => $kem,
                    'dist' => $dist,
                    'retG' => $retG,
                    'masuk' => $masuk,
                    'keluar' => $keluar,
                    'sisa' => $sisa,
                    'message' => $message
                ];
            }
            DB::connection('farmasi')->commit();
            return [
                'data' => $data,
                'status' => 200,
            ];
        } catch (\Exception $e) {
            DB::connection('farmasi')->rollBack();
            return [
                'result' => '' . $e,
                'err' =>  $e,
                'data' => $data,
                'status' => 410
            ];
        }
    }
    public function perbaikanStok(Request $request)
    {
        // $mapingGudang = [
        //     ['nama' => 'Gudang Farmasi ( Kamar Obat )', 'kode' => 'Gd-05010100', 'lama' => 'GU0001'],
        //     ['nama' => 'Gudang Farmasi (Floor Stok)', 'kode' => 'Gd-03010100', 'lama' => 'GU0002'],
        //     ['nama' => 'Floor Stock 1 (AKHP)', 'kode' => 'Gd-03010101', 'lama' => 'RC0001'],
        //     ['nama' => 'Depo Rawat inap', 'kode' => 'Gd-04010102', 'lama' => 'AP0002'],
        //     ['nama' => 'Depo OK', 'kode' => 'Gd-04010103', 'lama' => 'AP0005'],
        //     ['nama' => 'Depo Rawat Jalan', 'kode' => 'Gd-05010101', 'lama' => 'AP0001'],
        //     ['nama' => 'Depo IGD', 'kode' => 'Gd-02010104', 'lama' => 'AP0007']
        // ];
        // return new JsonResponse([
        //     // 'data' => $data,
        //     'message' => 'Cek Stok Untuk penenyesuaian sudan ditutup'
        // ], 410);
        $depo = $request->kdruang;
        // $forbid = ['Gd-05010100', 'Gd-03010100', 'Gd-04010103'];
        // if (in_array($depo, $forbid)) {

        //     return new JsonResponse([
        //         // 'data' => $data,
        //         'message' => 'Cek Stok Tidak Untuk Gudang dan atau Depo OK'
        //     ], 410);
        // }
        $obat = $request->kdobat;

        // CARI PENYESUAIAN
        $caristok = FarmasinewStokreal::where('kdobat', $obat)->where('kdruang', $depo)->get();
        $idtok = collect($caristok)->map(function ($st) {
            return $st->id;
        });
        $penye = PenyesuaianStok::whereIn('stokreal_id', $idtok)->sum('penyesuaian');
        $data['penyesuaian'] = $penye;

        $raw = self::kertuStok($depo, $obat);
        $col = collect($raw);

        $data['dataAll'] = $col;
        $data['awal'] = $col[0]->saldoawal[0]->jumlah ?? 0;
        // $data['stok_id'] = $col->map(function ($st) {
        //     return $st->stok->map(function ($an) {
        //         return $an->id;
        //     });
        // });
        $data['stok'] = $col->sum(function ($sa) {
            return $sa->stok->sum('jumlah');
        });
        $data['masuk'] = $col->sum(function ($sa) {
            return $sa->mutasimasuk->sum('jml');
        });
        $data['keluar'] = $col->sum(function ($sa) {
            return $sa->mutasikeluar->sum('jml');
        });
        $data['resep'] = $col->sum(function ($sa) {
            return $sa->resepkeluar->sum('jumlah');
        });
        $data['returres'] = $col->sum(function ($sa) {
            return $sa->resepkeluar->sum(function ($he) {
                return $he->retur->sum(function ($ri) {
                    return $ri->rinci->sum('jumlah_retur');
                });
            });
        });
        $data['racikan'] = $col->sum(function ($sa) {
            return $sa->resepkeluarracikan->sum('jumlah');
        });
        $data['returrrac'] = $col->sum(function ($sa) {
            return $sa->resepkeluarracikan->sum(function ($he) {
                return $he->retur->sum(function ($ri) {
                    return $ri->rinci->sum('jumlah_retur');
                });
            });
        });
        $data['operasidist'] = $col->sum(function ($sa) {
            return $sa->persiapanoperasiretur->sum('jumlah_distribusi');
        });
        $data['operasiret'] = $col->sum(function ($sa) {
            return $sa->persiapanoperasiretur->sum('jumlah_kembali');
        });
        $data['allmasuk'] = (int)$data['masuk'] + (int)$data['returres'] + (int)$data['returrrac'];
        $data['allkeluar'] = (int)$data['keluar'] + (int)$data['resep'] + (int)$data['racikan'];
        $data['op'] = (int)$data['operasidist'] - (int)$data['operasiret'];
        $data['awalandmas'] = (int)$data['awal'] + (int)$data['allmasuk'] + (int)$penye;
        if ($depo === 'Gd-04010103') {
            $data['akhir'] = (int)$data['awalandmas'] - (int)+(int)$data['allkeluar'] - (int)$data['op'];
        } else {
            $data['akhir'] = (int)$data['awalandmas'] - (int)+(int)$data['allkeluar'];
        }
        if ((int)$data['stok'] === (int)$data['akhir']) {
            return new JsonResponse(['message' => 'Data Sudah sesuai, tidak perlu penyesuaian']);
        }
        $stok = FarmasinewStokreal::where('kdobat', $obat)
            ->where('kdruang', $depo)->orderBy('tglexp', 'DESC')
            ->orderBy('nodistribusi', 'DESC')->get();
        $data['mutasiantar'] = [];

        $sisa = $data['akhir'];
        if (count($stok) > 0) {
            foreach ($stok as $st) {
                $distribusi = Mutasigudangkedepo::where('kd_obat', $st['kdobat'])->where('no_permintaan', $st['nodistribusi'])->first();
                $jmldist = $distribusi->jml ?? 0;
                if ($sisa > 0) {
                    if ($jmldist < $sisa) {
                        $st['jumlah'] =  $jmldist;
                        $st->save();
                        $sisa -=  $jmldist;
                    } else {
                        $st['jumlah'] = $sisa;
                        $st->save();
                        $sisa = 0;
                    }
                } else {
                    $st['jumlah'] = 0;
                    $st->save();
                }
                $data['mutasiantar'][] = $distribusi;
                // $data['mutasiantar'][] = $st['nodistribusi'];
            }
            $data['getStok'] = $stok;
        }

        return new JsonResponse([
            'data' => $data,
            'message' => 'Data Sudah disesuaikan'
        ]);
    }

    public static function kertuStok($koderuangan, $kdobat)
    {
        $koderuangan = $koderuangan;
        $bulan = date('m');
        $tahun = date('Y');
        // $bulan = request('bulan');
        // $tahun = request('tahun');
        $x = $tahun . '-' . $bulan;
        $tglAwal = $x . '-01';
        $tglAkhir = $x . '-31';
        $dateAwal = Carbon::parse($tglAwal);
        $dateAkhir = Carbon::parse($tglAkhir);
        $blnLaluAwal = $dateAwal->subMonth()->format('Y-m-d');
        $blnLaluAkhir = $dateAkhir->subMonth()->format('Y-m-d');


        $list = Mobatnew::query()
            ->select('kd_obat', 'nama_obat', 'satuan_k', 'satuan_b', 'id', 'flag', 'merk', 'kandungan')
            ->with([
                'saldoawal' => function ($saldo) use ($blnLaluAwal, $blnLaluAkhir, $koderuangan) {
                    $saldo->whereBetween('tglopname', [$blnLaluAwal, $blnLaluAkhir])
                        ->where('kdruang', $koderuangan)->select('tglopname', 'jumlah', 'kdobat');
                },
                'stok' => function ($st) use ($koderuangan) {
                    $st->select(
                        'id',
                        'kdobat',
                        'nopenerimaan',
                        'jumlah',
                        'kdruang',
                        'nodistribusi',
                    )
                        ->where('kdruang', $koderuangan);
                    // ->with('ssw:stokreal_id,penyesuaian');
                },
                // hanya ada jika koderuang itu adalah gudang
                'penerimaanrinci' => function ($q) use ($tglAwal, $tglAkhir, $koderuangan) {
                    $q->select(
                        'penerimaan_r.kdobat as kdobat',
                        'penerimaan_r.jml_all_penerimaan as jml_all_penerimaan',
                        'penerimaan_r.jml_terima_b as jml_terima_b',
                        'penerimaan_r.jml_terima_k as jml_terima_k',
                        'penerimaan_h.nopenerimaan as nopenerimaan',
                        'penerimaan_h.tglpenerimaan as tglpenerimaan',
                        'penerimaan_h.gudang as gudang',
                        'penerimaan_h.jenissurat as jenissurat',
                        'penerimaan_h.jenis_penerimaan as jenis_penerimaan',
                        'penerimaan_h.kunci as kunci',

                    )
                        ->join('penerimaan_h', 'penerimaan_r.nopenerimaan', '=', 'penerimaan_h.nopenerimaan')
                        // ->join('sigarang.gudangs as gudangs', 'penerimaan_h.gudang', '=', 'gudangs.kode')
                        ->whereBetween('penerimaan_h.tglpenerimaan', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('penerimaan_h.gudang', $koderuangan);
                },


                // mutasi masuk baik dari gudang, ataupun depo termasuk didalamnya mutasi antar depo dan antar gudang÷
                'mutasimasuk' => function ($q) use ($tglAwal, $tglAkhir, $koderuangan) {

                    $q->select(
                        'mutasi_gudangdepo.kd_obat as kd_obat',
                        'mutasi_gudangdepo.jml as jml',
                        'mutasi_gudangdepo.no_permintaan as no_permintaan',
                        'permintaan_h.tgl_permintaan as tgl_permintaan',
                        'permintaan_h.tujuan as tujuan',
                        'permintaan_h.dari as dari',
                    )
                        ->join('permintaan_h', 'permintaan_h.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                        ->whereBetween('permintaan_h.tgl_terima_depo', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('dari', $koderuangan);
                },


                // mutasi keluar baik ke gudang(mutasi antar gudang), ataupun ke depo dan juga ke ruangan
                'mutasikeluar' => function ($q) use ($tglAwal, $tglAkhir, $koderuangan) {

                    $q->select(
                        'mutasi_gudangdepo.kd_obat as kd_obat',
                        'mutasi_gudangdepo.jml as jml',
                        'mutasi_gudangdepo.no_permintaan as no_permintaan',
                        'permintaan_h.tgl_permintaan as tgl_permintaan',
                        'permintaan_h.tujuan as tujuan',
                        'permintaan_h.dari as dari',
                    )
                        ->join('permintaan_h', 'permintaan_h.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                        ->whereBetween('permintaan_h.tgl_kirim_depo', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('tujuan', $koderuangan);
                },

                'resepkeluar' => function ($q) use ($tglAwal, $tglAkhir, $koderuangan, $kdobat) {
                    $q->select(
                        'resep_keluar_h.depo',
                        'resep_keluar_r.noresep',
                        'resep_keluar_r.kdobat',
                        'resep_keluar_r.nopenerimaan',
                        'resep_keluar_r.jumlah',
                    )
                        ->join('resep_keluar_h', 'resep_keluar_h.noresep', '=', 'resep_keluar_r.noresep')
                        ->whereBetween('resep_keluar_h.tgl_permintaan', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('resep_keluar_h.depo', $koderuangan)

                        ->with([
                            'retur' => function ($ret) use ($kdobat) {
                                $ret->select(
                                    'noretur',
                                    'noresep',
                                )
                                    ->with([
                                        'rinci' => function ($ri) use ($kdobat) {
                                            $ri->select(
                                                'noretur',
                                                'kdobat',
                                                'jumlah_retur',
                                            )
                                                ->where('kdobat', $kdobat);
                                        }
                                    ]);
                            }
                        ]);
                },

                'resepkeluarracikan' => function ($q) use ($tglAwal, $tglAkhir, $koderuangan, $kdobat) {
                    $q->select(
                        'resep_keluar_h.depo',
                        'resep_keluar_racikan_r.noresep',
                        'resep_keluar_racikan_r.kdobat',
                        'resep_keluar_racikan_r.nopenerimaan',
                        'resep_keluar_racikan_r.jumlah',
                    )
                        ->join('resep_keluar_h', 'resep_keluar_racikan_r.noresep', '=', 'resep_keluar_h.noresep')
                        ->whereBetween('resep_keluar_h.tgl_permintaan', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59'])
                        ->where('resep_keluar_h.depo', $koderuangan)

                        ->with([
                            'retur' => function ($ret) use ($kdobat) {
                                $ret->select(
                                    'noretur',
                                    'noresep',
                                )
                                    ->with([
                                        'rinci' => function ($ri) use ($kdobat) {
                                            $ri->select(
                                                'noretur',
                                                'kdobat',
                                                'jumlah_retur',
                                            )
                                                ->where('kdobat', $kdobat);
                                        }
                                    ]);
                            }
                        ]);
                },

                // ini jika $koderuangan = Gd-04010103 (Depo OK) ini nanti di front end
                'persiapanoperasiretur' => function ($q) use ($tglAwal, $tglAkhir, $koderuangan) {
                    $q->select(
                        'persiapan_operasi_rincis.kd_obat',
                        'persiapan_operasi_rincis.jumlah_distribusi',
                        'persiapan_operasi_rincis.jumlah_kembali'
                    )->join('persiapan_operasis', 'persiapan_operasi_rincis.nopermintaan', '=', 'persiapan_operasis.nopermintaan')
                        ->whereBetween('persiapan_operasis.tgl_permintaan', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59']);
                },
                // ini jika $koderuangan = Gd-04010103 (Depo OK)
                // ini keluarnya nanti jumlah_distribusi harus dikurangi jumlah_resep karena resep nanti akan di ambil juga
                // 'persiapanoperasikeluar' => function ($q) use ($tglAwal, $tglAkhir, $koderuangan) {
                //     $q->select(
                //         'persiapan_operasi_rincis.kd_obat',
                //         'persiapan_operasi_rincis.jumlah_kembali',
                //     )->join('persiapan_operasis', 'persiapan_operasi_rincis.nopermintaan', '=', 'persiapan_operasis.nopermintaan')
                //         ->whereBetween('persiapan_operasis.tgl_distribusi', [$tglAwal . ' 00:00:00', $tglAkhir . ' 23:59:59']);
                // },
                // 'returpenjualan'

            ])

            ->orderBy('id', 'asc')
            ->where('flag', '')
            ->where('kd_obat', $kdobat)
            ->get();

        return $list;
    }
    public function perbaikanData()
    {
        $depo = request('kdruang');
        $obat = request('kdobat');
        $month = request('month');
        $year = request('year');
        $head = [
            'depo' => $depo,
            'obat' => $obat,
            'month' => $month,
            'year' => $year,
        ];
        // $data = self::getDataToFix($head);
        $data = self::getDataToFixByTrans($head);
        return new JsonResponse($data['data'] ?? $data, $data['status'] ?? 200);
    }
    public function frontPerbaikanData()
    {
        $depo = request('kdruang');
        $obat = request('kdobat');
        $month = request('bulan');
        $year = request('tahun');
        $perbaiki = request('perbaiki') === 'ya';
        $tipe = request('tipe') ?? 'default';
        $head = [
            'depo' => $depo,
            'obat' => $obat,
            'month' => $month,
            'year' => $year,
            'perbaiki' => $perbaiki,
            'tipe' => $tipe,
        ];
        // $data = self::getDataToFix($head);
        $data = self::getDataToFixByTrans($head);
        return new JsonResponse($data['data'] ?? $data, $data['status'] ?? 200);
    }
    public function frontPerbaikanDataPerDepo(Request $request)
    {
        $depo = request('kdruang');
        $month = request('bulan');
        $year = request('tahun');
        $perbaiki = request('perbaiki') === 'ya';
        $limit = request('per_page');
        $offset = (request('page') - 1) * $limit;
        $tipe = request('tipe') ?? 'default';

        $total = Mobatnew::count();
        $kdobat = Mobatnew::select('kd_obat', 'nama_obat')
            ->when($limit, function ($q) use ($limit, $offset) {
                $q->limit($limit)->offset($offset);
            })
            ->when(request('q'), function ($q) {
                $q->where(function ($query) {
                    $query->where('kd_obat', 'like', '%' . request('q') . '%')
                        ->orWhere('nama_obat', 'like', '%' . request('q') . '%');
                });
            })
            ->get();
        $anu = [];
        $mbuh = [];
        foreach ($kdobat as $obat) {
            $head = [
                'depo' => $depo,
                'obat' => $obat['kd_obat'],
                'month' => $month,
                'year' => $year,
                'perbaiki' => $perbaiki,
                'tipe' => $tipe,
            ];
            $data = self::getDataToFixByTrans($head);
            $obat['data'] = $data;
            $temp = $data['data'] ?? $data;
            // $anu[] = $temp;
            $ada = $temp['penKur'] ?? false;
            $gaktm = $temp['gaKtm'] ?? false;
            if ($ada) if (sizeof($temp['penKur']) > 0 || $gaktm) {
                $anu[] = $temp;
                $obat['perbaikan'] = $temp;
            } else $mbuh[] = $temp;
        }

        return new JsonResponse([
            'count data' => sizeof($anu),
            'kdobat' => $kdobat,
            'data' => $anu,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'page' => request('page'),
            'mbuh' => $mbuh,
            'req' => $request->all(),
        ]);
    }

    public function frontPerbaikanDataOpname(Request $request)
    {
        $data = [];
        try {
            DB::connection('farmasi')->beginTransaction();
            foreach ($request->all() as $key) {
                if ($key['nobatch'] == null) $key['nobatch'] = '';
                $temp = StokStokopname::updateOrCreate(
                    [
                        'id' => $key['id'],
                    ],
                    $key

                );
                $data[] = $temp;
            }
            DB::connection('farmasi')->commit();
            return new JsonResponse([
                'req' => $request->all(),
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            DB::connection('farmasi')->rollBack();
            return new JsonResponse([
                'req' => $request->all(),
                'message' => $e->getMessage(),
                'line' => '' . $e->getLine(),
                'file' =>  $e->getFile(),
            ], 410);
        }
    }

    public function frontDataMutasi(Request $request)
    {
        $head = Permintaandepoheder::select('no_permintaan')
            ->where('tujuan', $request->kdruang)
            ->where('tgl_kirim_depo', 'LIKE', '%' . $request->tahun . '-' . $request->bulan . '%')
            ->pluck('no_permintaan');
        $data = Mutasigudangkedepo::with('header:no_permintaan,tgl_kirim_depo,dari,tujuan', 'header.asal:kode,nama')
            ->whereIn('no_permintaan', $head)
            ->where('kd_obat', $request->kdobat)
            ->get();
        return new JsonResponse([
            'data' => $data,
            'head' => $head,
            'req' => $request->all(),
        ]);
    }
    public function frontDataResep(Request $request)
    {
        $head = Permintaandepoheder::select('no_permintaan')
            ->where('tujuan', $request->kdruang)
            ->where('dari', 'NOT LIKE', '%R-%')
            ->where('tgl_kirim_depo', 'LIKE', '%' . $request->tahun . '-' . $request->bulan . '%')
            ->pluck('no_permintaan');
        $data['mutasi'] = Mutasigudangkedepo::with('header:no_permintaan,tgl_kirim_depo,dari,tujuan', 'header.asal:kode,nama')
            ->whereIn('no_permintaan', $head)
            ->where('kd_obat', $request->kdobat)
            ->orderBy('no_permintaan', 'ASC')
            ->get();

        $headRuang = Permintaandepoheder::select('no_permintaan')
            ->where('tujuan', $request->kdruang)
            ->where('dari', 'LIKE', '%R-%')
            ->where('tgl_kirim_depo', 'LIKE', '%' . $request->tahun . '-' . $request->bulan . '%')
            ->pluck('no_permintaan');
        $data['mutasiruangan'] = Mutasigudangkedepo::with('header:no_permintaan,tgl_kirim_depo,dari,tujuan', 'header.asal:kode,nama', 'header.ruangan:kode,uraian')
            ->whereIn('no_permintaan', $headRuang)
            ->where('kd_obat', $request->kdobat)
            ->orderBy('no_permintaan', 'ASC')
            ->get();
        $headResep = Resepkeluarheder::select('noresep')
            ->where('depo', $request->kdruang)
            ->whereIn('flag', ['3', '4'])
            ->where('tgl_selesai', 'LIKE', '%' . $request->tahun . '-' . $request->bulan . '%')
            ->pluck('noresep');
        $data['resep'] = Resepkeluarrinci::with(
            'heder:noresep,tgl_selesai,ruangan,depo',
            'heder.poli:rs1,rs2',
            'heder.ruanganranap:rs1,rs2',
        )
            ->whereIn('noresep', $headResep)
            ->where('kdobat', $request->kdobat)
            ->where('jumlah', '>', 0)
            ->orderBy('noresep', 'ASC')
            ->get();
        $data['resepracikan'] = Resepkeluarrinciracikan::with(
            'header:noresep,tgl_selesai,ruangan,depo',
            'header.poli:rs1,rs2',
            'header.ruanganranap:rs1,rs2',
        )
            ->whereIn('noresep', $headResep)
            ->where('kdobat', $request->kdobat)
            ->where('jumlah', '>', 0)
            ->orderBy('noresep', 'ASC')
            ->get();

        $headRetur = Returpenjualan_h::select('retur_penjualan_h.noretur')
            ->leftJoin('resep_keluar_h', 'resep_keluar_h.noresep', '=', 'retur_penjualan_h.noresep')
            ->where('resep_keluar_h.depo', $request->kdruang)
            ->where('retur_penjualan_h.tgl_retur', 'LIKE', '%' . $request->tahun . '-' . $request->bulan . '%')
            ->pluck('retur_penjualan_h.noretur');
        $data['retur'] = Returpenjualan_r::whereIn('noretur', $headRetur)
            ->with(
                'heder:noretur,tgl_retur',
                'header:noresep,ruangan',
                'header.ruanganranap:rs1,rs2',
                'header.poli:rs1,rs2',
            )
            ->where('kdobat', $request->kdobat)
            ->get();

        $headPersiapan = PersiapanOperasi::select('nopermintaan')
            ->where('tgl_distribusi', 'LIKE', '%' . $request->tahun . '-' . $request->bulan . '%')
            ->pluck('nopermintaan');
        $data['persiapanop'] = PersiapanOperasiDistribusi::selectRaw('*,kd_obat as kdobat')->whereIn('nopermintaan', $headPersiapan)
            ->with(
                'persiapan',
                'persiapan.list:rs1,rs4,rs5',
                'persiapan.list.kunjunganranap:rs1,rs5',
                'persiapan.list.kunjunganranap.relmasterruangranap:rs1,rs2',
                'persiapan.list.kunjunganrajal:rs1,rs2,rs8',
                'persiapan.list.kunjunganrajal.relmpoli:rs1,rs2',

            )
            ->where('kd_obat', $request->kdobat)
            ->get();
        $data['persiapanrinci'] = PersiapanOperasiRinci::selectRaw('*,kd_obat as kdobat')->whereIn('nopermintaan', $headPersiapan)
            ->where('kd_obat', $request->kdobat)
            ->get();
        return new JsonResponse([
            'data' => $data,
            'req' => $request->all(),
        ]);
    }
    public function PerbaikanDataPerDepo(Request $request)
    {
        $depo = request('kdruang');
        $month = request('month');
        $year = request('year');
        $limit = request('limit');
        $offset = (request('page') - 1) * $limit;


        $kdobat = Mobatnew::select('kd_obat')
            ->when($limit, function ($q) use ($limit, $offset) {
                $q->limit($limit)->offset($offset);
            })
            ->pluck('kd_obat');
        $anu = [];
        $mbuh = [];
        foreach ($kdobat as $obat) {
            $head = [
                'depo' => $depo,
                'obat' => $obat,
                'month' => $month,
                'year' => $year,
            ];
            $data = self::getDataToFixByTrans($head);
            $temp = $data['data'] ?? $data;
            // $anu[] = $temp;
            $ada = $temp['penKur'] ?? false;
            $gaktm = $temp['gaKtm'] ?? false;
            if ($ada) if (sizeof($temp['penKur']) > 0 || $gaktm) $anu[] = $temp;
            else $mbuh[] = $temp;
        }

        return new JsonResponse([
            'count data' => sizeof($anu),
            'data' => $anu,
            'limit' => $limit,
            'offset' => $offset,
            'page' => request('page'),
            'mbuh' => $mbuh
        ]);
    }
    public static function getDataToFix($data)
    {

        // $index = 0;
        // $anu = [];
        // for ($i = 0; $i <= 5; $i++) {

        //     $anu[] = $index;
        //     $index += 1;
        // }
        // return  $anu;
        /// metode keluar fifo di ganti dari tgl exp ke mana yang masuk duluan jadi semua transi sudut pandangnya berubah..
        // pada fitur ini paramaeter yang digunakan hanya 3, yaitu, kode obat, nomor penerimaan dan harga. karena untuk laporan FIFO, hanya 3 parameter itu yang berpengaruh
        try {
            DB::connection('farmasi')->beginTransaction();
            $gudangs = ['Gd-05010100', 'Gd-03010100'];
            $depos = ['Gd-03010101', 'Gd-04010102', 'Gd-04010103', 'Gd-05010101', 'Gd-02010104'];
            $rumahSakit = ['Gd-05010100', 'Gd-03010100', 'Gd-03010101', 'Gd-04010102', 'Gd-04010103', 'Gd-05010101', 'Gd-02010104'];
            $kdruang = $data['depo'];
            $bulan = $data['month'];
            $tahun = $data['year'];
            $now = $tahun . '-' . $bulan;
            $tglAwal = $now . '-01';
            $tglAkhir = $now . date('-t', strtotime($now . '-01'));
            $dateAwal = Carbon::parse($tglAwal);
            $blnLaluAwal = $dateAwal->subMonth()->format('Y-m');


            // ***************  test  ********************


            // $bedaSaldo = [];
            // $sementara = StokopnameSementara::select(
            //     'kdobat',
            //     'tglopname',
            //     'kdruang',
            //     DB::raw('sum(jumlah) as jumlah'),
            // )
            //     ->where('tglopname', 'like', '%' . $tglAkhir . '%')
            //     ->whereIn('kdruang', $gudangs)
            //     ->groupBy('kdobat', 'tglopname', 'kdruang')
            //     ->get();
            // $opname = StokStokopname::select(
            //     'kdobat',
            //     'tglopname',
            //     'kdruang',
            //     DB::raw('sum(jumlah) as jumlah'),
            // )
            //     ->where('tglopname', 'like', '%' . $tglAkhir . '%')
            //     ->whereIn('kdruang', $gudangs)
            //     ->groupBy('kdobat', 'tglopname', 'kdruang')
            //     ->get();
            // $colSem = collect($sementara);
            // $colOp = collect($opname);
            // $kdSem = $colSem->map(function ($fu) {
            //     return $fu->kdobat;
            // })->toArray();
            // $kdOp = $colOp->map(function ($fu) {
            //     return $fu->kdobat;
            // })->toArray();
            // $rwkd = array_merge($kdSem, $kdOp);
            // $kdOb = array_unique($rwkd);
            // foreach ($kdOb as $kode) {
            //     foreach ($rumahSakit as $ruang) {
            //         $sm = $colSem->where('kdobat', $kode)->where('kdruang', $ruang)->first();
            //         $op = $colOp->where('kdobat', $kode)->where('kdruang', $ruang)->first();
            //         $jmlSm = 0;
            //         $jmlOp = 0;
            //         if ($sm) {
            //             $jmlSm = (float)$sm->jumlah;
            //         }
            //         if ($op) {
            //             $jmlOp = (float)$op->jumlah;
            //         }
            //         if (($jmlSm != $jmlOp)) {
            //             $bedaSaldo[] = [
            //                 'sm' => $sm,
            //                 'op' => $op,
            //                 'jmlSm' => $jmlSm,
            //                 'jmlOp' => $jmlOp,
            //                 'kode' => $kode,
            //                 'ruang' => $ruang,
            //             ];
            //         }
            //     }
            // }

            // return $bedaSaldo;



            // **************************  test end ********************

            // ambil data tanggal transaksi
            // 1. saldo awal
            $saldoAwal = StokStokopname::where('tglopname', 'LIKE', '%' . $blnLaluAwal . '%')->get();
            // cek di stok sementara dan update jika tidak sesuai
            $stnya = [];
            $stopnya = [];
            // ini di awal saja
            if ($data['month'] == '06' && $data['year'] == '2024') {
                StokrealSementara::truncate();
                StokopnameSementara::truncate();
                foreach ($saldoAwal as $sal) {
                    // cek stok sementara
                    $adaSt = StokrealSementara::where('kdobat', $sal->kdobat)
                        ->where('kdruang', $sal->kdruang)
                        ->where('nopenerimaan', $sal->nopenerimaan)
                        ->where('harga', $sal->harga)
                        ->first();
                    if ($adaSt) {
                        $sub = (float)$sal->jumlah + (float)$adaSt->jumlah;
                        $adaSt->update(['jumlah' => $sub]);
                    } else {
                        $tempSt = StokrealSementara::create(
                            [
                                'nopenerimaan' => $sal->nopenerimaan,
                                'kdobat' => $sal->kdobat,
                                'kdruang' => $sal->kdruang,
                                'nobatch' => $sal->nobatch,
                                'harga' => $sal->harga ?? 0,
                                'tglexp' => $sal->tglexp,
                                'tglpenerimaan' => $sal->tglpenerimaan,
                                'jumlah' => $sal->jumlah,


                            ]
                        );
                        $stnya[] = $tempSt;
                    }
                    $adaOp = StokopnameSementara::where('kdobat', $sal->kdobat)
                        ->where('kdruang', $sal->kdruang)
                        ->where('nopenerimaan', $sal->nopenerimaan)
                        ->where('harga', $sal->harga)
                        ->where('tglopname', $sal->tglopname)
                        ->first();
                    if ($adaOp) {
                        $sub = (float)$sal->jumlah + (float)$adaOp->jumlah;
                        $adaOp->update(['jumlah' => $sub]);
                    } else {
                        $tempOp = StokopnameSementara::create(
                            [
                                'nopenerimaan' => $sal->nopenerimaan,
                                'kdobat' => $sal->kdobat,
                                'kdruang' => $sal->kdruang,
                                'nobatch' => $sal->nobatch,
                                'harga' => $sal->harga ?? 0,
                                'tglexp' => $sal->tglexp,
                                'tglpenerimaan' => $sal->tglpenerimaan,
                                'jumlah' => $sal->jumlah,
                                'tglopname' => $sal->tglopname,


                            ]
                        );
                        $stopnya[] = $tempOp;
                    }
                }
            }
            // return [
            //     'stnya' => $stnya,
            //     'stopnya' => $stopnya,
            // ];
            // 2. array tanggal transaksi, dari tanggal 1 sampai akhir bulan
            $permintaanRinci = Mutasigudangkedepo::select(
                'permintaan_r.id',
                'permintaan_r.kdobat',
                'permintaan_h.no_permintaan',
                DB::raw('sum(mutasi_gudangdepo.jml) as jumlah'),
            )
                ->join('permintaan_h', 'permintaan_h.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                ->join('permintaan_r', function ($jo) {
                    $jo->on('mutasi_gudangdepo.no_permintaan', '=', 'permintaan_r.no_permintaan')
                        ->on('mutasi_gudangdepo.kd_obat', '=', 'permintaan_r.kdobat');
                })
                ->where('permintaan_h.tgl_permintaan', 'LIKE', '%' . $now . '%')
                ->groupBy('permintaan_h.no_permintaan', 'permintaan_r.id')
                ->get();
            // // return $permintaanRinci;
            // // update verif
            foreach ($permintaanRinci as $perm) {
                $rinci = Permintaandeporinci::find($perm->id);
                if ($rinci) {
                    if ($rinci->jumlah_diverif !== $perm->jumlah) {
                        $rinci->update(['jumlah_diverif' => $perm->jumlah]);
                    }
                }
            }

            // penyesuaian ambil langsung 1 bulan
            $penyesuaian = PenyesuaianStok::select(
                'penyesuaian_stoks.kdobat',
                'penyesuaian_stoks.nopenerimaan',
                'stokreal.kdruang',
                'stokreal.tglpenerimaan',
                'stokreal.harga',
                'stokreal.tglexp',
                'stokreal.nobatch',
                DB::raw('sum(penyesuaian_stoks.penyesuaian) as jumlah'),
            )
                ->join('stokreal', 'stokreal.id', '=', 'penyesuaian_stoks.stokreal_id')
                ->where('penyesuaian_stoks.tgl_penyesuaian', 'LIKE', '%' . $now . '%')
                ->groupBy('penyesuaian_stoks.kdobat', 'penyesuaian_stoks.nopenerimaan', 'stokreal.kdruang')
                ->get();

            // // eksekusi
            if (count($penyesuaian) > 0) {
                foreach ($penyesuaian as $key) {
                    $stok = StokrealSementara::where('kdobat', $key->kdobat)
                        ->where('kdruang', $key->kdruang)
                        ->where('nopenerimaan', $key->nopenerimaan)
                        ->first();
                    if ($stok) {
                        $tot = (float)$stok->jumlah + (float)$key->jumlah;
                        $stok->update(['jumlah' => $tot]);
                        // $ada[] = ['stok' => $stok];
                    } else {
                        StokrealSementara::create(
                            [
                                'nopenerimaan' => $key->nopenerimaan,
                                'kdobat' => $key->kdobat,
                                'kdruang' => $key->kdruang,
                                'nobatch' => $key->nobatch,
                                'harga' => $key->harga,
                                'tglexp' => $key->tglexp,
                                'tglpenerimaan' => $key->tglpenerimaan,
                                'jumlah' => $key->jumlah,

                            ]
                        );
                    }
                }
            }
            $akhir = (int)date('t', strtotime($now . '-01'));
            $arrayTgl = [];
            for ($i = 1; $i <= $akhir; $i++) {
                // for ($i = 1; $i <= $akhir; $i++) {
                $t = $i <= 9 ? '0' . $i : $i;
                $temp = $now . '-' . $t;
                $arrayTgl[] = $temp;
            }
            // 3. ambil semua data tanggal transaksi berdasarkan tanggal
            $trx = [];
            // $tgl = $now;
            foreach ($arrayTgl as $tgl) {
                $ada = [];
                $ok = [];
                $bedaHarga = [];
                // pada mutasi update jumlah di verif pada rinci, sesuikan dengan yang di distribusikan bulan ini

                // di tiap tanggal urutkam masuk nya dulu baru keluar


                /**
                 * gudang start *****************
                 *
                 * masuk start *****************
                 * # penerimaan
                 * # penyesuaian
                 */
                $headerPenerimaan = PenerimaanHeder::select('nopenerimaan')
                    ->where('tglpenerimaan', 'LIKE', '%' . $tgl . '%')
                    ->where('penerimaan_h.kunci', '=', '1')
                    ->orderBy('tglpenerimaan', 'ASC')
                    ->get();
                $penerimaan = PenerimaanRinci::select(
                    'nopenerimaan',
                    'no_batch', // no batch dibutuhkan untuk retur
                    'kdobat',
                    'tgl_exp',
                    'harga_netto_kecil as harga',
                    'jml_terima_k as jumlah',
                )
                    ->whereIn('nopenerimaan', $headerPenerimaan)
                    ->with('header:nopenerimaan,gudang,tglpenerimaan')
                    ->get();
                // eksekusi
                if (count($penerimaan) > 0) {
                    foreach ($penerimaan as $key) {
                        $gudang = $key->header->gudang;
                        // return $gudang;
                        $stok = StokrealSementara::where('kdobat', $key->kdobat)
                            ->where('kdruang', $gudang)
                            ->where('nopenerimaan', $key->nopenerimaan)
                            ->where('nobatch', $key->no_batch)
                            ->first();
                        if ($stok) {
                            $tot = (float)$stok->jumlah + (float)$key->jumlah;
                            $stok->update(['jumlah' => $tot]);
                            // $ada[] = $stok;
                        } else {
                            $stok = StokrealSementara::create(
                                [
                                    'nopenerimaan' => $key->nopenerimaan,
                                    'kdobat' => $key->kdobat,
                                    'kdruang' => $gudang,
                                    'nobatch' => $key->no_batch,
                                    'harga' => $key->harga,
                                    'tglexp' => $key->tgl_exp,
                                    'tglpenerimaan' => $key->header->tglpenerimaan,
                                    'jumlah' => $key->jumlah,

                                ]
                            );
                            // $ada[] = $stok;
                        }
                    }
                }

                /**
                 * masuk end *****************
                 */
                /**
                 * keluar start *****************
                 * # mutasi ke depo
                 * # barang rusak
                 */

                $headerPermintaanKeluarGudang = Permintaandepoheder::select('no_permintaan')->where('tgl_kirim_depo', 'LIKE', '%' . $tgl . '%')->whereIn('tujuan', $gudangs)->get();
                $rincian = Permintaandeporinci::select('no_permintaan', 'kdobat', 'jumlah_diverif')->whereIn('no_permintaan', $headerPermintaanKeluarGudang)->get();
                $mutasiKeluar = Mutasigudangkedepo::select(
                    'id',
                    'kd_obat as kdobat',
                    'nopenerimaan',
                    'no_permintaan',
                    'jml as jumlah'
                )
                    ->with('header:no_permintaan,tujuan')
                    ->whereIn('no_permintaan', $headerPermintaanKeluarGudang)
                    ->get();
                if (count($mutasiKeluar) > 0) {
                    $mutasiKelGud = collect($mutasiKeluar);
                    $rincinya = collect($rincian);
                    $noperRw = collect($mutasiKeluar)->map(function ($it) {
                        return $it->no_permintaan;
                    })->toArray();
                    $nopermintaan = array_unique($noperRw);
                    $kod = collect($mutasiKeluar)->map(function ($it) {
                        return $it->kdobat;
                    })->toArray();
                    $kdObFSt = array_unique($kod);
                    $stoknya = StokrealSementara::whereIn('kdobat', $kdObFSt)->where('jumlah', '>', 0)->orderBy('tglpenerimaan', 'ASC')->get();
                    $colStok = collect($stoknya);
                    foreach ($nopermintaan as $nomor) {
                        $kdob = $mutasiKelGud->where('no_permintaan', $nomor);
                        $kd = $kdob->map(function ($it) {
                            return $it->kdobat;
                        })->toArray();
                        $kode = array_unique($kd);

                        foreach ($kode as $obat) {
                            $tempMut = $mutasiKelGud->where('no_permintaan', $nomor)->where('kdobat', $obat);
                            $rinc = $rincinya->where('no_permintaan', $nomor)->where('kdobat', $obat)->first();
                            $tuj = $tempMut->first();
                            $tujuan = $tuj->header->tujuan;
                            $jum = (float)$tempMut->sum('jumlah');

                            $diverif = (float)$rinc->jumlah_diverif;

                            $keluar = (float)$jum;
                            if ($diverif > 0 && $jum != $diverif) $keluar = $diverif;

                            $maxIndex = (int)sizeof($tempMut);
                            $index = 0;
                            $tmpBisa = [];
                            foreach ($tempMut as $key => $value) {
                                $tmpBisa[] = $value;
                            }


                            while ($keluar > 0 || $index < $maxIndex) {


                                $sisaStok = 0;
                                $jumlah = 0;
                                // kondisi 1 : keluar > 0 dan index masih ada

                                if ($keluar > 0 && $index < $maxIndex) {
                                    $mut = $tmpBisa[$index];
                                    $kdobat = $mut->kdobat ?? null;
                                    $st = $colStok->where('kdobat', $kdobat)
                                        ->where('kdruang', $tujuan)
                                        ->where('jumlah', '>', 0)
                                        ->first();
                                    if ($st) {
                                        $sisaStok = (float)$st->jumlah ?? 0;
                                        $jumlah = (float)$mut->jumlah ?? 0;
                                        if ($sisaStok <= $jumlah) {
                                            $sisa = (float)$jumlah - (float)$sisaStok;
                                            $st->update(['jumlah' => 0]);
                                            $mut->update(['jml' => $sisaStok]);
                                            if ($mut->nopenerimaan != $st->nopenerimaan) $mut->update(['nopenerimaan' => $st->nopenerimaan]);
                                            if ($mut->tglpenerimaan != $st->tglpenerimaan) $mut->update(['tglpenerimaan' => $st->tglpenerimaan]);
                                            if ($mut->harga != $st->harga) $mut->update(['harga' => $st->harga]);
                                            if ($mut->tglexp != $st->tglexp) $mut->update(['tglexp' => $st->tglexp]);
                                            if ($mut->nobatch != $st->nobatch) $mut->update(['nobatch' => $st->nobatch]);
                                            $temp = (float)$keluar - (float)$jumlah + (float)$sisa;
                                            $keluar = (float)$temp;
                                            $index += 1;
                                        } else {
                                            $sisa =  (float)$sisaStok - (float)$jumlah;
                                            $st->update(['jumlah' => $sisa]);
                                            if ($mut->nopenerimaan != $st->nopenerimaan) $mut->update(['nopenerimaan' => $st->nopenerimaan]);
                                            if ($mut->tglpenerimaan != $st->tglpenerimaan) $mut->update(['tglpenerimaan' => $st->tglpenerimaan]);
                                            if ($mut->harga != $st->harga) $mut->update(['harga' => $st->harga]);
                                            if ($mut->tglexp != $st->tglexp) $mut->update(['tglexp' => $st->tglexp]);
                                            if ($mut->nobatch != $st->nobatch) $mut->update(['nobatch' => $st->nobatch]);
                                            $temp = (float)$keluar - (float)$jumlah;
                                            $keluar = (float)$temp;
                                            $index += 1;
                                        }
                                    } else {
                                        $ada[] = [
                                            'cond' => '$keluar > 0 && $index < $maxIndex',
                                            'cond 2' => '! $st',
                                            'maxIndex' => $maxIndex,
                                            'index' => $index,
                                            'keluar' => $keluar,
                                            'obat' => $obat,
                                            'kdobat' => $kdobat,
                                            'nomor' => $nomor,
                                            'tujuan' => $tujuan,
                                            'st' => [
                                                'kdobat' => $st->kdobat ?? null,
                                                'nopenerimaan' => $st->nopenerimaan ?? null,
                                                'nopenerimaan mut' => $mut->nopenerimaan ?? null,
                                                'jumlah' => $st->jumlah ?? null,
                                                'kdruang' => $st->kdruang ?? null,
                                            ],
                                            'sisaStok' => $sisaStok ?? null,
                                            'jumlah' => $jumlah ?? null,
                                        ];
                                        $keluar = 0;
                                        $index += 1;
                                    }

                                    // $keluar = 0;
                                    // $index += 1;
                                }
                                // kondisi 2 : keluar > 0 dan index habis
                                else if ($keluar > 0 && $index >= $maxIndex) {
                                    $mut = $tmpBisa[$index - 1];
                                    $kdobat = $mut->kdobat ?? null;
                                    $st = $colStok->where('kdobat', $kdobat)
                                        ->where('kdruang', $tujuan)
                                        ->where('jumlah', '>', 0)
                                        ->first();
                                    if ($st) {
                                        $sisaStok = (float)$st->jumlah ?? 0;
                                        $jumlah = (float)$keluar ?? 0;
                                        if ($sisaStok <= $jumlah) {
                                            $sisa =   (float)$keluar - (float)$sisaStok;
                                            $mutasi = Mutasigudangkedepo::create(
                                                [
                                                    'no_permintaan' => $nomor,
                                                    'nopenerimaan' => $st->nopenerimaan,
                                                    'kd_obat' => $st->kdobat,
                                                    'nobatch' => $st->nobatch,
                                                    'jml' => $sisaStok,
                                                    'tglpenerimaan' => $st->tglpenerimaan,
                                                    'harga' => $st->harga ?? 0,
                                                    'tglexp' => $st->tglexp,
                                                ]
                                            );
                                            $st->update(['jumlah' => 0]);

                                            $keluar = (float)$sisa;
                                        } else {
                                            $sisa =  (float)$sisaStok - (float)$keluar;
                                            $mutasi = Mutasigudangkedepo::create(
                                                [
                                                    'no_permintaan' => $nomor,
                                                    'nopenerimaan' => $st->nopenerimaan,
                                                    'kd_obat' => $st->kdobat,
                                                    'nobatch' => $st->nobatch,
                                                    'jml' => $keluar,
                                                    'tglpenerimaan' => $st->tglpenerimaan,
                                                    'harga' => $st->harga ?? 0,
                                                    'tglexp' => $st->tglexp,
                                                ]
                                            );
                                            $newMut[] = $mutasi;
                                            $st->update(['jumlah' => $sisa]);
                                            $keluar = 0;
                                        }
                                    } else {
                                        $ada[] = [
                                            'cond' => '$keluar > 0 && $index >= $maxIndex',
                                            'cond 2' => '! $st',
                                            'maxIndex' => $maxIndex,
                                            'index' => $index,
                                            'keluar' => $keluar,
                                            'obat' => $obat,
                                            'nomor' => $nomor,
                                            'kdobat' => $kdobat,
                                            'tujuan' => $tujuan,
                                            'st' => [
                                                'kdobat' => $st->kdobat ?? null,
                                                'nopenerimaan' => $st->nopenerimaan ?? null,
                                                'nopenerimaan mut' => $mut->nopenerimaan ?? null,
                                                'jumlah' => $st->jumlah ?? null,
                                                'kdruang' => $st->kdruang ?? null,
                                            ],
                                            'sisaStok' => $sisaStok ?? null,
                                            'jumlah' => $jumlah ?? null,
                                        ];
                                        $keluar = 0;
                                    }
                                    // $keluar = 0;
                                }
                                // kondisi 3 : keluar = 0 dan index ada
                                else if ($keluar <= 0 && $index > $maxIndex) {
                                    $mut = $tmpBisa[$index];
                                    $mut->update([
                                        'nopenerimaan' => '',
                                        'tglpenerimaan' => null,
                                        'harga' => 0,
                                        'tglexp' => null,
                                        'nobatch' => '',
                                        'jml' => 0,
                                    ]);
                                    $ada[] = [
                                        'cond' => '$keluar <= 0 && $index > $maxIndex',
                                        'maxIndex' => $maxIndex,
                                        'index' => $index,
                                        'keluar' => $keluar,
                                        'st' => '$st',
                                        'sisaStok' => $sisaStok ?? null,
                                        'jumlah' => $jumlah ?? null,
                                    ];
                                    $index += 1;
                                } else {
                                    $ada[] = [
                                        'cond' => 'else',
                                        'maxIndex' => $maxIndex,
                                        'index' => $index,
                                        'keluar' => $keluar,
                                        'tujuan' => $tujuan,
                                        'st' => '$st',
                                        'sisaStok' => $sisaStok ?? null,
                                        'jumlah' => $jumlah ?? null,
                                    ];
                                    $keluar = 0;
                                    $index += 1;
                                }
                            }

                            // end of while
                        } //sampe sini
                    }
                }

                // ****  untuk rusak masik belum ***
                // $rusak = BarangRusak::select(
                //     'id',
                //     'kd_obat',
                //     'nopenerimaan',
                //     'jumlah',
                //     'harga_net',
                //     'nobatch',
                //     // DB::raw('sum(jumlah) as jumlah')
                // )
                //     ->where('tgl_rusak',  'LIKE', '%' . $tgl . '%')
                //     ->where('kunci', '1')
                //     ->get();

                /**
                 * keluar end *****************
                 *
                 * gudang end ***********************************************
                 */

                /**
                 * depo start *****************
                 *
                 * masuk start *****************
                 * # mutasi dari gudang
                 * # antar depo keluar dulu baru masuk
                 * # retur resep
                 * ## khusus depo ok
                 * ## retur dari distribusi persiapan ok
                 */

                // code
                // **** mutasi Start***
                // //mutasi masuk
                // $headerPermintaanMasukDepoDariGudang = Permintaandepoheder::select('no_permintaan')->where('tgl_terima_depo', 'LIKE', '%' . $tgl . '%')->whereIn('dari', $depos)->whereIn('tujuan', $gudangs)->get();

                // $mutasiMasukDepo = Mutasigudangkedepo::select(
                //     'id',
                //     'kd_obat as kdobat',
                //     'nopenerimaan',
                //     'nobatch',
                //     'tglpenerimaan',
                //     'harga',
                //     'tglexp',
                //     'no_permintaan',
                //     'jml as jumlah'
                // )
                //     ->with('header:no_permintaan,tujuan,dari')
                //     ->whereIn('no_permintaan', $headerPermintaanMasukDepoDariGudang)
                //     ->get();
                // if (sizeof($mutasiMasukDepo) > 0) {
                //     foreach ($mutasiMasukDepo as $mutMasDep) {
                //         $dari = $mutMasDep->header->dari;
                //         // cek stok
                //         $adaStok = StokrealSementara::where('kdobat', $mutMasDep->kdobat)
                //             ->where('nopenerimaan', $mutMasDep->nopenerimaan)
                //             ->where('nobatch', $mutMasDep->nobatch)
                //             ->where('harga', $mutMasDep->harga)
                //             ->where('kdruang', $dari)
                //             ->first();
                //         if ($adaStok) {
                //             $tot = (float)$mutMasDep->jumlah + (float)$adaStok->jumlah;
                //             $adaStok->update(['jumlah' => $tot]);
                //         } else {
                //             StokrealSementara::create([
                //                 'nopenerimaan' => $mutMasDep->nopenerimaan,
                //                 'kdobat' => $mutMasDep->kdobat,
                //                 'kdruang' => $dari,
                //                 'nobatch' => $mutMasDep->nobatch,
                //                 'harga' => $mutMasDep->harga,
                //                 'tglexp' => $mutMasDep->tglexp,
                //                 'tglpenerimaan' => $mutMasDep->tglpenerimaan,
                //                 'jumlah' => $mutMasDep->jumlah,
                //             ]);
                //         }
                //     }
                // }
                // **** mutasi end***

                // // retur
                // $returHeader = Returpenjualan_h::select('noretur')->where('tgl_retur', 'LIKE', '%' . $tgl . '%')->get();
                // $returRinci = Returpenjualan_r::select(
                //     'id',
                //     'kdobat',
                //     'noresep',
                //     'nopenerimaan',
                //     'jumlah_retur'
                // )
                //     ->with('header:noresep,depo')
                //     ->whereIn('noretur', $returHeader)
                //     ->get();
                // if (sizeof($returRinci) > 0) {
                //     foreach ($returRinci as $ret) {
                //         $dari = $ret->header->depo;
                //         $adaStok = StokrealSementara::where('kdobat', $ret->kdobat)
                //             ->where('nopenerimaan', $ret->nopenerimaan)
                //             // ->where('nobatch', $ret->nobatch)
                //             // ->where('harga', $ret->harga)
                //             ->where('kdruang', $dari)
                //             ->first();
                //         if ($adaStok) {
                //             $tot = (float)$ret->jumlah_retur + (float)$adaStok->jumlah;
                //             $adaStok->update(['jumlah' => $tot]);
                //             if ($ret->harga != $adaStok->harga) {
                //                 $hargaAwal = $ret->harga;
                //                 $ret->update(['harga' => $adaStok->harga]);
                //                 $bedaHarga[] = [
                //                     'hargaAwal' => $hargaAwal,
                //                     'ret' => $ret,
                //                 ];
                //             }
                //         } else {
                //             $ada[] = [
                //                 'function' => 'Retur Resep',
                //                 'cond' => 'retur rinci !$adastok',
                //                 'ret' => $ret,
                //             ];
                //         }
                //     }
                // }



                /**
                 * masuk end *****************
                 */
                /**
                 * keluar start *****************
                 * # mutasi antar depo
                 * # mutasi ke ruangan
                 * # eresep
                 * ## khusus depo ok
                 * ## distribusi persiapan ok
                 */
                //code
                // $headerPermintaanKeluarkDepo = Permintaandepoheder::select('no_permintaan')->where('tgl_kirim_depo', 'LIKE', '%' . $tgl . '%')->whereIn('tujuan', $depos)->get();

                // $mutasiKeluarDepo = Mutasigudangkedepo::select(
                //     'id',
                //     'kd_obat as kdobat',
                //     'nopenerimaan',
                //     'nobatch',
                //     'tglexp',
                //     'no_permintaan',
                //     'jml as jumlah'
                // )

                //     ->with('header:no_permintaan,tujuan,dari')
                //     ->whereIn('no_permintaan', $headerPermintaanKeluarkDepo)
                //     ->get();

                // // if (count($mutasiKeluarDepo) > 0) {
                // //     $mutasiKelDep = collect($mutasiKeluarDepo);
                // //     $noperRw = collect($mutasiKeluarDepo)->map(function ($it) {
                // //         return $it->no_permintaan;
                // //     })->toArray();
                // //     $nopermintaan = array_unique($noperRw);
                // //     $kod = collect($mutasiKeluarDepo)->map(function ($it) {
                // //         return $it->kdobat;
                // //     })->toArray();
                // //     $kdObFSt = array_unique($kod);
                // //     $stoknya = StokrealSementara::whereIn('kdobat', $kdObFSt)->where('jumlah', '>', 0)->orderBy('id', 'ASC')->get();
                // //     $colStok = collect($stoknya);
                // //     foreach ($nopermintaan as $nomor) {
                // //         $kdob = $mutasiKelDep->where('no_permintaan', $nomor);
                // //         $kd = $kdob->map(function ($it) {
                // //             return $it->kdobat;
                // //         })->toArray();
                // //         $kode = array_unique($kd);

                // //         foreach ($kode as $obat) {
                // //             $tempMut = $mutasiKelDep->where('no_permintaan', $nomor)->where('kdobat', $obat);
                // //             $tuj = $tempMut->first();
                // //             $tujuan = $tuj->header->tujuan;
                // //             $jum = (float)$tempMut->sum('jumlah');
                // //             $keluar = (float)$jum;

                // //             foreach ($tempMut as $mut) {
                // //                 $st = $colStok->where('kdobat', $mut->kdobat)
                // //                     ->where('kdruang', $tujuan)
                // //                     ->where('jumlah', '>', 0)
                // //                     ->first();
                // //                 if ($st) {
                // //                     $sisaStok = (float)$st->jumlah ?? 0;
                // //                     $jumlah = (float)$mut->jumlah ?? 0;

                // //                     // jika sisa stok > jumlah, kurangi sisa stok saja
                // //                     if ($sisaStok <= $jumlah && $keluar > 0) {
                // //                         $sisa = (float)$jumlah - (float)$sisaStok;
                // //                         $st->update(['jumlah' => 0]);
                // //                         if ($mut->nopenerimaan != $st->nopenerimaan) $mut->update(['nopenerimaan' => $st->nopenerimaan]);
                // //                         if ($mut->tglpenerimaan != $st->tglpenerimaan) $mut->update(['tglpenerimaan' => $st->tglpenerimaan]);
                // //                         if ($mut->harga != $st->harga) $mut->update(['harga' => $st->harga]);
                // //                         if ($mut->tglexp != $st->tglexp) $mut->update(['tglexp' => $st->tglexp]);
                // //                         if ($mut->nobatch != $st->nobatch) $mut->update(['nobatch' => $st->nobatch]);
                // //                         $temp = (float)$keluar - (float)$jumlah + (float)$sisa;
                // //                         $keluar = (float)$temp;
                // //                     } else if ($keluar > 0) {
                // //                         $sisa =  (float)$sisaStok - (float)$jumlah;
                // //                         $st->update(['jumlah' => $sisa]);
                // //                         if ($mut->nopenerimaan != $st->nopenerimaan) $mut->update(['nopenerimaan' => $st->nopenerimaan]);
                // //                         if ($mut->tglpenerimaan != $st->tglpenerimaan) $mut->update(['tglpenerimaan' => $st->tglpenerimaan]);
                // //                         if ($mut->harga != $st->harga) $mut->update(['harga' => $st->harga]);
                // //                         if ($mut->tglexp != $st->tglexp) $mut->update(['tglexp' => $st->tglexp]);
                // //                         if ($mut->nobatch != $st->nobatch) $mut->update(['nobatch' => $st->nobatch]);
                // //                         $temp = (float)$keluar - (float)$jumlah;
                // //                         $keluar = (float)$temp;
                // //                     } else {
                // //                         $mut->update([
                // //                             'nopenerimaan' => '',
                // //                             'tglpenerimaan' => null,
                // //                             'harga' => 0,
                // //                             'tglexp' => null,
                // //                             'nobatch' => '',
                // //                             'jml' => 0,
                // //                         ]);

                // //                         $ada[] = [
                // //                             'kondisi 1' => 'else',
                // //                             'kondisi 2' => 'else',
                // //                             'jum' => $jum,
                // //                             'keluar' => $keluar,
                // //                             'nomor' => $nomor,
                // //                             'obat' => $obat,
                // //                             // 'sisa' => $sisa,
                // //                             'mutasi' => [
                // //                                 'kdobat' => $mut->kdobat,
                // //                                 'nopenerimaan' => $mut->nopenerimaan,
                // //                                 'jumlah' => $mut->jumlah,
                // //                             ],
                // //                             'st' =>  [
                // //                                 'kdobat' => $st->kdobat,
                // //                                 'nopenerimaan' => $st->nopenerimaan,
                // //                                 'jumlah' => $st->jumlah,
                // //                                 'kdruang' => $st->kdruang,
                // //                             ],
                // //                         ];
                // //                     }
                // //                 }
                // //             }
                // //             // }
                // //             // }
                // //         } //sampe sini
                // //     }
                // // }
                // // depo ok
                // $headerPermintaanDistOk = PersiapanOperasi::select('nopermintaan')->where('tgl_permintaan',  'LIKE', '%' . $tgl . '%')
                //     ->whereIn('persiapan_operasis.flag', ['2', '3', '4'])
                //     ->get();
                // $persiapanOperasiDistribusi = PersiapanOperasiDistribusi::select(
                //     'id',
                //     'kd_obat',
                //     'nopenerimaan',
                //     'jumlah',
                //     'jumlah_retur',
                // )
                //     ->whereIn('nopermintaan', $headerPermintaanDistOk)
                //     ->get();
                // $persiapanOperasiRinci = PersiapanOperasiRinci::select(
                //     'id',
                //     'kd_obat',
                //     'noresep',
                //     'jumlah_distribusi',
                //     'jumlah_kembali',
                // )
                //     ->whereIn('nopermintaan', $headerPermintaanDistOk)
                //     ->get();
                // //semmua resepe yang keluar yang tidak ada nomor resepnya di persiapan operasi ya itu yang di ambil..
                // // $headerResep=Resepkeluarheder::select('noresep')->where('tgl_selesai',  'LIKE', '%' . $tgl . '%')
                // $resOprw = collect($persiapanOperasiRinci)->map(function ($it) {
                //     return $it->noresep;
                // })->toArray();
                // $resOp = array_unique($resOprw);
                // $headerResep = Resepkeluarheder::select('noresep')->where('tgl_selesai', 'LIKE', '%' . $tgl . '%')->whereIn('flag', ['3', '4'])->distinct('noresep')->get();
                // $resepKeluar = Resepkeluarrinci::whereIn('noresep', $headerResep)->whereNotIn('noresep', $resOp)->get();
                // $resepKeluarRacikan = Resepkeluarrinciracikan::whereIn('noresep', $headerResep)->whereNotIn('noresep', $resOp)->get();



                /*
             * depo end *****************
             */
                /*
             * retur dari depo start *****************
             * # retur dari depo, keluar depo
             * # retur ke gudang, masuk gudang
             */

                // $headerReturGudang = ReturGudang::select('no_retur')
                //     ->where('tgl_retur', 'LIKE', '%' . $tgl . '%')
                //     ->where('kunci', '1')
                //     ->get();
                // $returGudang = ReturGudangDetail::select(
                //     'id',
                //     'no_retur',
                //     'kd_obat',
                //     'nopenerimaan',
                //     'tgl_exp',
                //     'harga',
                //     'no_batch',
                //     // 'retur_gudangs.gudang',
                //     'jumlah_retur as jumlah'
                // )
                //     ->with('header:no_retur,gudang')
                //     ->whereIn('no_retur', $headerReturGudang)
                //     ->get();
                // // eksekusi
                // if (count($returGudang) > 0) {
                //     foreach ($returGudang as $key) {
                //         $gudang = $key->header->gudang;
                //         // return $gudang;
                //         $stok = StokrealSementara::where('kdobat', $key->kd_obat)
                //             ->where('kdruang', $gudang)
                //             ->where('nopenerimaan', $key->nopenerimaan)
                //             ->where('nobatch', $key->no_batch)
                //             ->first();
                //         if ($stok) {
                //             $tot = (float)$stok->jumlah + (float)$key->jumlah;
                //             $stok->update(['jumlah' => $tot]);
                //             $ada[] = $stok;
                //         } else {
                //             $stok = StokrealSementara::create(
                //                 [
                //                     'nopenerimaan' => $key->nopenerimaan,
                //                     'kdobat' => $key->kd_obat,
                //                     'kdruang' => $gudang,
                //                     'nobatch' => $key->no_batch,
                //                     'harga' => $key->harga,
                //                     'tglexp' => $key->tgl_exp,
                //                     'tglpenerimaan' => $key->header->tglpenerimaan ?? null,
                //                     'jumlah' => $key->jumlah,

                //                 ]
                //             );
                //             $ada[] = $stok;
                //         }
                //     }
                // }

                /*
             * retur dari depo end *****************
             */

                $trx[] = [
                    'tgl' => $tgl,
                    'ada' => $ada,
                    'ok' => $ok,
                    'bedaHarga' => $bedaHarga,
                    // 'mutasiKeluar' => $mutasiKeluar,
                    // 'rusak' => $rusak,
                    // 'returGudang' => $returGudang,
                    // 'penyesuaian' => $penyesuaian,
                    // 'penerimaan' => $penerimaan,
                    // 'resepH' => $resepH,
                    // 'persiapanOperasiRinci' => $persiapanOperasiRinci,
                ];
            }

            // $recent = StokrealSementara::where('jumlah', '>', 0)
            //     ->get();
            // $get = $tglAkhir . date(' H:i:s');
            // $tanggal = $tglAkhir . ' 23:59:58';
            // $newOpname = [];
            // foreach ($recent as $key) {
            //     $item = [
            //         'nopenerimaan' => $key->nopenerimaan,
            //         'tglpenerimaan' => $key->tglpenerimaan,
            //         'kdobat' => $key->kdobat,
            //         'jumlah' => $key->jumlah,
            //         'kdruang' => $key->kdruang,
            //         'harga' => $key->harga,
            //         'flag' => $key->flag,
            //         'tglexp' => $key->tglexp,
            //         'nobatch' => $key->nobatch,
            //         'nodistribusi' => $key->nodistribusi,
            //         'tglopname' => $tanggal,
            //         'created_at' => $get,
            //         'updated_at' => date('Y-m-d H:i:s'),
            //     ];
            //     $newOpname[] = $item;
            // }
            // if (count($newOpname) > 0) {
            //     $stoktgl = StokopnameSementara::where('tglopname', $tanggal)->delete();
            //     foreach (array_chunk($newOpname, 100) as $t) {
            //         $data = StokopnameSementara::insert($t);
            //     }
            // }
            DB::connection('farmasi')->commit();

            $bedaSaldo = [];
            $sementara = StokopnameSementara::select(
                'kdobat',
                'tglopname',
                'kdruang',
                DB::raw('sum(jumlah) as jumlah'),
            )
                ->where('tglopname', 'like', '%' . $tglAkhir . '%')
                ->whereIn('kdruang', $gudangs)
                ->groupBy('kdobat', 'tglopname', 'kdruang')
                ->get();
            $opname = StokStokopname::select(
                'kdobat',
                'tglopname',
                'kdruang',
                DB::raw('sum(jumlah) as jumlah'),
            )
                ->where('tglopname', 'like', '%' . $tglAkhir . '%')
                ->whereIn('kdruang', $gudangs)
                ->groupBy('kdobat', 'tglopname', 'kdruang')
                ->get();
            $colSem = collect($sementara);
            $colOp = collect($opname);
            $kdSem = $colSem->map(function ($fu) {
                return $fu->kdobat;
            })->toArray();
            $kdOp = $colOp->map(function ($fu) {
                return $fu->kdobat;
            })->toArray();
            $rwkd = array_merge($kdSem, $kdOp);
            $kdOb = array_unique($rwkd);
            foreach ($kdOb as $kode) {
                foreach ($rumahSakit as $ruang) {
                    $sm = $colSem->where('kdobat', $kode)->where('kdruang', $ruang)->first();
                    $op = $colOp->where('kdobat', $kode)->where('kdruang', $ruang)->first();
                    $jmlSm = 0;
                    $jmlOp = 0;
                    if ($sm) {
                        $jmlSm = (float)$sm->jumlah;
                    }
                    if ($op) {
                        $jmlOp = (float)$op->jumlah;
                    }
                    if (($jmlSm != $jmlOp)) {
                        $bedaSaldo[] = [
                            'sm' => $sm,
                            'op' => $op,
                            'jmlSm' => $jmlSm,
                            'jmlOp' => $jmlOp,
                            'kode' => $kode,
                            'ruang' => $ruang,
                        ];
                    }
                }
            }
            return [
                'data' => [
                    // 'stok' => $master['stok'],
                    // 'master' => $master,
                    // 'saldoAlawal' => $saldoAlawal,
                    'arrayTgl' => $arrayTgl,
                    'trx' => $trx,
                    'data' => $data,
                    'bedaSaldo' => $bedaSaldo,
                    'now' => $now,
                    'dateAwal' => $dateAwal,
                    'penyesuaian' => $penyesuaian,
                    'blnLaluAwal' => $blnLaluAwal,
                    'tglAkhir' => $tglAkhir,
                    'akhir' => $akhir,
                ],
                'status' => 200

            ];
        } catch (\Exception $e) {
            DB::connection('farmasi')->rollBack();
            return ['data' => [
                'message' => 'ada kesalahan',
                'error' => $e,
                'error str' => ' ' . $e
            ], 'status' => 410];
        }
    }
    public static function getDataToFixByTrans($head)
    {
        // return $head;
        // $mapingGudang = [
        //     ['nama' => 'Gudang Farmasi ( Kamar Obat )', 'kode' => 'Gd-05010100', 'lama' => 'GU0001'],
        //     ['nama' => 'Gudang Farmasi (Floor Stok)', 'kode' => 'Gd-03010100', 'lama' => 'GU0002'],
        //     ['nama' => 'Floor Stock 1 (AKHP)', 'kode' => 'Gd-03010101', 'lama' => 'RC0001'],
        //     ['nama' => 'Depo Rawat inap', 'kode' => 'Gd-04010102', 'lama' => 'AP0002'],
        //     ['nama' => 'Depo OK', 'kode' => 'Gd-04010103', 'lama' => 'AP0005'],
        //     ['nama' => 'Depo Rawat Jalan', 'kode' => 'Gd-05010101', 'lama' => 'AP0001'],
        //     ['nama' => 'Depo IGD', 'kode' => 'Gd-02010104', 'lama' => 'AP0007']
        // ];

        // stok sekrang diganti stok akhir di opname
        // cek masing2 transaksi, apakah jumlah keluar sudah pas apa bekum dengan nomor penerimaan nya.
        // jika tidak cocok, maka cocok kan. metodanya:
        // 1. berapa yang lebih, di nomor penerimaan yang mana.
        // 2. berapa yang kurang, di nomor penerimaan yang mana.
        // 3. kurangi yang lebih, masukkan ke yang kurang.
        // ---- permasalahan:
        // 1. jika jumlah di yang lebih pada transaksi kurang dari jumlah yang di transaksinya. dan sebaliknya.

        try {
            DB::connection('farmasi')->beginTransaction();
            $data = [];
            $gudangs = ['Gd-05010100', 'Gd-03010100'];
            $depos = ['Gd-03010101', 'Gd-04010102', 'Gd-04010103', 'Gd-05010101', 'Gd-02010104'];
            $koderuangan = $head['depo'];
            $kdobat = $head['obat'];
            $bulan = $head['month'];
            $tahun = $head['year'];

            $sekarang = date('Y-m');
            $x = $tahun . '-' . $bulan;
            $tglAwal = $x . '-01';
            $tglAkhir = $x . date('-t', strtotime($x . '-01'));
            $dateAwal = Carbon::parse($tglAwal);
            $dateAkhir = Carbon::parse($tglAkhir);
            $blnLaluAwal = $dateAwal->subMonth()->format('Y-m');
            $blnLaluAkhir = $dateAkhir->subMonth()->format('Y-m-t');
            $parameter = [
                'kdobat' => $kdobat,
                'koderuangan' => $koderuangan,
                'now' => $x,
                'blnLalu' => $blnLaluAwal,
                'perbaiki' => $head['perbaiki'],
                'tipe' => $head['tipe'],
            ];

            // if ($sekarang == $x) return 'Fitur ini tidak dibuat untuk stok bulan ini';
            // if ($sekarang == $x) throw new Exception('Fitur ini tidak dibuat untuk stok bulan ini');

            $message = 'Stok sudah Sesuai tidak ada yang perlu di update';
            if (in_array($koderuangan, $gudangs)) {
                $saldoAwalRinci = StokStokopname::select('tglopname', 'nopenerimaan', 'harga', 'tglexp', 'nobatch', 'tglpenerimaan', 'kdobat', DB::raw('sum(jumlah) as total'))
                    ->where('tglopname', 'LIKE', $blnLaluAwal . '%')
                    ->where('kdruang', $koderuangan)
                    ->where('kdobat', $kdobat)
                    ->groupBy('nopenerimaan', 'tglopname', 'kdruang', 'kdobat')
                    ->orderBy('tglpenerimaan', 'DESC')
                    ->get();
                $saldoAwal = collect($saldoAwalRinci)->sum('total');
                $stokid = FarmasinewStokreal::select('id')->where('kdruang', $koderuangan)
                    ->where('kdobat', $kdobat)
                    ->pluck('id');
                $penyesuaianRinci = PenyesuaianStok::select('stokreal_id', 'nopenerimaan', DB::raw('sum(penyesuaian) as jumlah'))
                    ->whereIn('stokreal_id', $stokid)
                    ->where('tgl_penyesuaian', 'LIKE', '%' . $x . '%')
                    ->groupBy('stokreal_id', 'nopenerimaan')
                    ->get();
                $penyesuaian = collect($penyesuaianRinci)->sum('jumlah');
                $penerimaanRinci = PenerimaanRinci::select(
                    'penerimaan_r.kdobat',
                    'penerimaan_r.nopenerimaan',
                    DB::raw('sum(jml_terima_k) as jumlah')
                )
                    ->join('penerimaan_h', 'penerimaan_r.nopenerimaan', '=', 'penerimaan_h.nopenerimaan')
                    ->where('penerimaan_h.tglpenerimaan', 'LIKE', '%' . $x . '%')
                    ->where('penerimaan_h.gudang', $koderuangan)
                    ->where('penerimaan_h.kunci', '1')
                    ->where('penerimaan_r.kdobat', $kdobat)
                    ->groupBy('penerimaan_r.nopenerimaan', 'penerimaan_r.kdobat')
                    ->get();
                $penerimaan = collect($penerimaanRinci)->sum('jumlah');

                $mutasiMasukRinci = Mutasigudangkedepo::select(
                    'mutasi_gudangdepo.kd_obat as kdobat',
                    'mutasi_gudangdepo.nopenerimaan',
                    DB::raw('sum(mutasi_gudangdepo.jml) as jumlah')
                )
                    ->join('permintaan_h', 'permintaan_h.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                    ->where('permintaan_h.tgl_terima_depo',  'LIKE', '%' . $x . '%')
                    ->where('permintaan_h.dari', $koderuangan)
                    ->where('mutasi_gudangdepo.kd_obat', $kdobat)
                    ->groupBy('mutasi_gudangdepo.nopenerimaan', 'mutasi_gudangdepo.kd_obat')
                    ->get();
                $mutasiMasuk = collect($mutasiMasukRinci)->sum('jumlah');

                $mutasiKeluarRinci = Mutasigudangkedepo::select(
                    'mutasi_gudangdepo.kd_obat as kdobat',
                    'mutasi_gudangdepo.nopenerimaan',
                    DB::raw('sum(mutasi_gudangdepo.jml) as jumlah')
                )
                    ->join('permintaan_h', 'permintaan_h.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                    ->where('permintaan_h.tgl_kirim_depo', 'LIKE', '%' . $x . '%')
                    ->where('permintaan_h.tujuan', $koderuangan)
                    ->where('mutasi_gudangdepo.kd_obat', $kdobat)
                    ->groupBy('mutasi_gudangdepo.nopenerimaan', 'mutasi_gudangdepo.kd_obat')
                    ->get();
                $mutasiKeluar = collect($mutasiKeluarRinci)->sum('jumlah');

                $rusakRinci = BarangRusak::select(
                    'kd_obat',
                    'nopenerimaan',
                    DB::raw('sum(jumlah) as jumlah')
                )
                    ->where('tgl_kunci', 'LIKE', '%' . $x . '%')
                    ->where('kd_obat', $kdobat)
                    ->where('kunci', '1')
                    ->where('gudang', $koderuangan)
                    ->groupBy('kd_obat', 'nopenerimaan')
                    ->get();
                $rusak = collect($rusakRinci)->sum('jumlah');

                $returGudangRinci = ReturGudangDetail::select(
                    'retur_gudang_details.kd_obat',
                    'retur_gudang_details.nopenerimaan',
                    DB::raw('sum(retur_gudang_details.jumlah_retur) as jumlah')
                )
                    ->leftJoin('retur_gudangs', 'retur_gudangs.no_retur', '=', 'retur_gudang_details.no_retur')
                    ->where('retur_gudangs.gudang', $koderuangan)
                    ->where('retur_gudangs.tgl_retur', 'LIKE', '%' . $x . '%')
                    ->where('retur_gudang_details.kd_obat', $kdobat)
                    ->where('retur_gudangs.kunci', '1')
                    ->groupBy('retur_gudang_details.nopenerimaan', 'retur_gudang_details.kd_obat', 'retur_gudangs.gudang')
                    ->get();
                $returGudang = collect($returGudangRinci)->sum('jumlah');

                $returPbfRinci = Returpbfrinci::select(
                    'retur_penyedia_r.kd_obat',
                    'retur_penyedia_r.nopenerimaan_default as nopenerimaan',
                    DB::raw('sum(retur_penyedia_r.jumlah_retur) as jumlah')
                )
                    ->leftJoin('retur_penyedia_h', 'retur_penyedia_h.no_retur', '=', 'retur_penyedia_r.no_retur')
                    ->where('retur_penyedia_h.gudang', $koderuangan)
                    ->where('retur_penyedia_h.tgl_kunci', 'LIKE', '%' . $x . '%')
                    ->where('retur_penyedia_r.kd_obat', $kdobat)
                    ->where('retur_penyedia_h.kunci', '1')
                    ->groupBy('retur_penyedia_r.nopenerimaan', 'retur_penyedia_r.kd_obat', 'retur_penyedia_h.gudang')
                    ->get();
                $returPbf = collect($returPbfRinci)->sum('jumlah');

                $pengembalianPinjamanRinci = PengembalianRinciFifo::select(
                    'pengembalian_rinci_fifos.kdobat',
                    'pengembalian_rinci_fifos.nopenerimaan',
                    DB::raw('sum(pengembalian_rinci_fifos.jml_dikembalikan) as jumlah')
                )
                    ->leftJoin('pengembalians', 'pengembalians.nopengembalian', '=', 'pengembalian_rinci_fifos.nopengembalian')
                    ->where('pengembalians.kdruang', $koderuangan)
                    ->where('pengembalians.tgl_kunci', 'LIKE', '%' . $x . '%')
                    ->where('pengembalian_rinci_fifos.kdobat', $kdobat)
                    ->where('pengembalians.flag', '1')
                    ->groupBy('pengembalian_rinci_fifos.nopenerimaan', 'pengembalian_rinci_fifos.kdobat', 'pengembalians.kdruang')
                    ->get();
                $pengembalianPinj = collect($pengembalianPinjamanRinci)->sum('jumlah');


                if ($x == $sekarang) {
                    $totalStok = FarmasinewStokreal::select('kdobat', DB::raw('sum(jumlah) as jumlah'))->where('kdobat', $kdobat)
                        ->where('kdruang', $koderuangan)->first();
                } else {
                    $totalStok = StokStokopname::select('kdobat', DB::raw('sum(jumlah) as jumlah'))->where('kdobat', $kdobat)
                        ->where('kdruang', $koderuangan)->where('tglopname', 'LIKE', $x . '%')->first();
                }
                $tts = round($totalStok->jumlah, 2) ?? 0;
                $sal = round($saldoAwal, 2) ?? 0;
                $peny = round($penyesuaian, 2) ?? 0;
                $trm = round($penerimaan, 2) ?? 0;
                $mutma = round($mutasiMasuk, 2) ?? 0;
                $mutkel = round($mutasiKeluar, 2) ?? 0;
                $rus = round($rusak, 2) ?? 0;
                $retG = round($returGudang, 2) ?? 0;
                $retPbf = round($returPbf, 2) ?? 0;
                $pengPinj = $pengembalianPinj ?? 0;

                $masuk = (float)$sal + (float)$peny + (float)$trm + (float)$mutma + (float)$retG;
                $keluar = (float)$mutkel + (float)$rus + (float)$retPbf + (float)$pengPinj;
                $sisa = (float)$masuk - (float)$keluar;

                $nopeSt = [];
                foreach ($saldoAwalRinci as $key) {
                    $nopeSt[] = $key->nopenerimaan;
                }
                foreach ($penyesuaianRinci as $key) {
                    $nopeSt[] = $key->nopenerimaan;
                }
                foreach ($penerimaanRinci as $key) {
                    $nopeSt[] = $key->nopenerimaan;
                }
                foreach ($mutasiMasukRinci as $key) {
                    $nopeSt[] = $key->nopenerimaan;
                }
                foreach ($mutasiKeluarRinci as $key) {
                    $nopeSt[] = $key->nopenerimaan;
                }
                foreach ($rusakRinci as $key) {
                    $nopeSt[] = $key->nopenerimaan;
                }
                foreach ($returGudangRinci as $key) {
                    $nopeSt[] = $key->nopenerimaan;
                }
                foreach ($penerimaanRinci as $key) {
                    $nopeSt[] = $key->nopenerimaan;
                }
                foreach ($returPbfRinci as $key) {
                    $nopeSt[] = $key->nopenerimaan;
                }
                $uniNopeSt = array_unique($nopeSt);

                // pembetulan stok
                $ada = $sisa;
                $index = 0;


                $penKur = [];
                $penLeb = [];
                $penPas = [];
                $penAll = [];
                // pembetulan nomor penerimaan
                foreach ($uniNopeSt as $key) {
                    if ($x == $sekarang) {
                        $stOP = FarmasinewStokreal::select('kdobat', DB::raw('sum(jumlah) as jumlah'))->where('kdobat', $kdobat)
                            ->where('kdruang', $koderuangan)->where('nopenerimaan', $key)->first();
                    } else {
                        $stOP = StokStokopname::select('kdobat', DB::raw('sum(jumlah) as jumlah'))->where('kdobat', $kdobat)
                            ->where('kdruang', $koderuangan)->where('nopenerimaan', $key)->where('tglopname', 'LIKE', $x . '%')->first();
                    }
                    $stOpnya = round($stOP->jumlah, 2) ?? 0;
                    $salAwal =  collect($saldoAwalRinci)->firstWhere('nopenerimaan', $key)->total ?? 0;
                    $mutMas =  collect($mutasiMasukRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;
                    $trm =  collect($penerimaanRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;
                    $retGu =  collect($returGudangRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;
                    $peny =  collect($penyesuaianRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;
                    // keluar
                    $mutKel =  collect($mutasiKeluarRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;
                    $rus =  collect($rusakRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;
                    $retPbf =  collect($returPbfRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;
                    $pengPinjx =  collect($pengembalianPinjamanRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;

                    $maSuk = round(((float)round($salAwal, 2) + (float)round($mutMas, 2) + (float)round($trm, 2) + (float)round($retGu, 2) + (float)round($peny, 2)), 2);
                    $keLuar = round(((float)round($mutKel, 2) + (float)round($rus, 2) + (float)round($retPbf, 2)), 2) + (float)round($pengPinjx, 2);
                    $sisanya = round(($maSuk - $keLuar), 2);
                    $sts = round(($sisanya - $stOpnya), 2);

                    if ($sisanya == $stOpnya) {
                        $penPas[] = [
                            'noper' => $key,
                            'sisanya' => $sisanya,
                            'sts' => $sts,
                            'stOpnya' => $stOpnya,
                            'maSuk' => $maSuk,
                            'keLuar' => $keLuar,
                            'peny' => $peny,
                        ];
                    } else if ($sisanya < 0) {
                        $penKur[] = [
                            'noper' => $key,
                            'sisanya' => $sisanya,
                            'sts' => $sts,
                            'stOpnya' => $stOpnya,
                            'maSuk' => $maSuk,
                            'keLuar' => $keLuar,
                            'peny' => $peny,
                        ];
                    } else if ($sisanya < $stOpnya) {
                        $penKur[] = [
                            'noper' => $key,
                            'sisanyaPeng' => $sisanya,
                            'sisanya' => $sts,
                            'sts' => $sts,
                            'stOpnya' => $stOpnya,
                            'maSuk' => $maSuk,
                            'keLuar' => $keLuar,
                            'peny' => $peny,
                        ];
                    } else {
                        $penLeb[] = [
                            'noper' => $key,
                            'sisanya' => $sisanya,
                            'sts' => $sts,
                            'stOpnya' => $stOpnya,
                            'maSuk' => $maSuk,
                            'keLuar' => $keLuar,
                            'peny' => $peny,
                        ];
                    }
                    $penAll[] = [
                        'noper' => $key,
                        'sisanya' => $sisanya,
                        'maSuk' => $maSuk,
                        'keLuar' => $keLuar,
                        'peny' => $peny,
                    ];
                }
                $opNya = [];

                $parameter['nopenerimaan'] = $uniNopeSt;
                $parameter['penPas'] = $penPas;
                $parameter['penKur'] = $penKur;
                $parameter['penLeb'] = $penLeb;
                $parameter['penAll'] = $penAll;
                $parameter['tts'] = $tts;
                $parameter['sisa'] = $sisa;
                $parameter['keluar'] = $keluar;

                $eksekusi = self::nopenerimaanGudang($parameter);
                $cekOpname = self::opnemeGudang($parameter);
                $gaKtm = $eksekusi['gaKtm'] ?? false;
                // }


                $data = [
                    // 'saldoAwal' => $saldoAwal ?? [],
                    // 'stokid' => $stokid,
                    'kdobat' => $kdobat,
                    'gaKtm' => $gaKtm,
                    'eksekusi' => $eksekusi ?? [],
                    'cekOpname' => $cekOpname ?? [],
                    'penyesuaian' => $penyesuaian,
                    'penerimaan' => $penerimaan,
                    'mutasiMasuk' => $mutasiMasuk,
                    'mutasiKeluar' => $mutasiKeluar,
                    // 'totalStok' => $totalStok,
                    'masuk' => $masuk,
                    'keluar' => $keluar,

                    'penLeb' => $penLeb,
                    'penKur' => $penKur,
                    'penPas' => $penPas,
                    // 'ksekPenKur' => $ksekPenKur,
                    // 'gaAdaLeb' => $gaAdaLeb,
                    // 'hasilOpname' => $hasilOpname,
                    'opNya' => $opNya,

                    'stok' => $stok ?? [],
                    'ret' => $ret ?? [],
                    'adaPerbaiaknStok' => $adaPerbaiaknStok ?? [],

                    'uniNope' => $uniNopeSt,
                    'penerimaanRinci' => $penerimaanRinci,
                    'mutasiKeluarRinci' => $mutasiKeluarRinci,
                    'mutasiMasukRinci' => $mutasiMasukRinci,
                    'saldoAwalRinci' => $saldoAwalRinci,
                    'returGudangRinci' => $returGudangRinci,

                    'tts' => $tts,
                    'sisa' => $sisa,
                    'sal' => $sal,
                    'peny' => $peny,
                    'trm' => $trm,
                    'mutma' => $mutma,
                    'mutkel' => $mutkel,
                    'rus' => $rus,
                    'retG' => $retG,
                    'retPbf' => $retPbf,
                    'ada' => $ada,
                    // 'stok' => $stok ?? [],
                    'message' => $message
                ];
            } else {
                /*
             * harus memetakan mutasi masuk dan mutasi keluar berdasarkan
             * kode obat, momor penerimaan, dan kalo bisa nomor batch, tgl exp dan harga
             */

                $saldoAwalDepoRinci = StokStokopname::select(
                    'tglopname',
                    'tglpenerimaan',
                    'nopenerimaan',
                    'kdobat',
                    'nobatch',
                    'tglexp',
                    'harga',
                    DB::raw('sum(jumlah) as total')
                )
                    // ->whereBetween('tglopname', [$blnLaluAwal . ' 00:00:00', $blnLaluAkhir . ' 23:59:59'])
                    ->where('tglopname', 'LIKE', $blnLaluAwal . '%')
                    ->where('kdruang', $koderuangan)
                    ->where('kdobat', $kdobat)
                    ->groupBy('nopenerimaan', 'tglopname', 'kdruang', 'kdobat')
                    ->get();

                $saldoAwal = collect($saldoAwalDepoRinci)->sum('total');

                $stokid = FarmasinewStokreal::select('id')->where('kdruang', $koderuangan)
                    ->where('kdobat', $kdobat)
                    ->pluck('id');
                $penyesuaianDepoRinci = PenyesuaianStok::select('stokreal_id', 'nopenerimaan', DB::raw('sum(penyesuaian) as jumlah'))
                    ->whereIn('stokreal_id', $stokid)
                    ->where('tgl_penyesuaian', 'LIKE', '%' . $x . '%')
                    ->groupBy('stokreal_id', 'nopenerimaan')
                    ->get();
                $penyesuaian = collect($penyesuaianDepoRinci)->sum('jumlah');

                $headMutasiMas = Permintaandepoheder::select('no_permintaan')
                    ->where('dari', $koderuangan)
                    ->where('tgl_terima_depo', 'LIKE', '%' . $x . '%')
                    ->pluck('no_permintaan');
                $mutasiMasukDepoRinci = Mutasigudangkedepo::select(
                    'mutasi_gudangdepo.kd_obat as kdobat',
                    'mutasi_gudangdepo.nopenerimaan',
                    'mutasi_gudangdepo.nobatch',
                    'mutasi_gudangdepo.tglexp',
                    'mutasi_gudangdepo.tglpenerimaan',
                    'mutasi_gudangdepo.no_permintaan',
                    'mutasi_gudangdepo.harga',
                    DB::raw('sum(mutasi_gudangdepo.jml) as jumlah')
                )
                    ->whereIn('no_permintaan', $headMutasiMas)
                    ->where('mutasi_gudangdepo.kd_obat', $kdobat)
                    ->groupBy(
                        'mutasi_gudangdepo.kd_obat',
                        'mutasi_gudangdepo.nopenerimaan',
                    )
                    ->orderby('no_permintaan', 'DESC')
                    ->get();
                $mutasiMasuk = collect($mutasiMasukDepoRinci)->sum('jumlah');
                $headMutasiKel = Permintaandepoheder::select('no_permintaan')
                    ->where('tujuan', $koderuangan)
                    ->where('tgl_kirim_depo', 'LIKE', '%' . $x . '%')
                    ->pluck('no_permintaan');
                $mutasiKeluarDepoRinci = Mutasigudangkedepo::select(
                    'mutasi_gudangdepo.kd_obat as kdobat',
                    'mutasi_gudangdepo.nopenerimaan',
                    'mutasi_gudangdepo.nobatch',
                    'mutasi_gudangdepo.tglexp',
                    'mutasi_gudangdepo.no_permintaan',
                    // 'permintaan_h.tgl_kirim_depo',
                    DB::raw('sum(mutasi_gudangdepo.jml) as jumlah')
                )

                    ->whereIn('no_permintaan', $headMutasiKel)
                    ->where('mutasi_gudangdepo.kd_obat', $kdobat)
                    ->groupBy(
                        'mutasi_gudangdepo.kd_obat',
                        'mutasi_gudangdepo.nopenerimaan',
                    )
                    ->get();
                $mutasiKeluar = collect($mutasiKeluarDepoRinci)->sum('jumlah');

                $returRinci = Returpenjualan_r::select(
                    'retur_penjualan_r.kdobat',
                    'retur_penjualan_r.nopenerimaan',
                    DB::raw('sum(retur_penjualan_r.jumlah_retur) as jumlah')
                )
                    ->join('retur_penjualan_h', 'retur_penjualan_r.noretur', '=', 'retur_penjualan_h.noretur')
                    ->join('resep_keluar_h', 'retur_penjualan_r.noresep', '=', 'resep_keluar_h.noresep')
                    ->where('retur_penjualan_h.tgl_retur', 'LIKE', '%' . $x . '%')
                    ->where('resep_keluar_h.depo', $koderuangan)
                    // ->whereIn('retur_penjualan_r.noresep', $headerResep)
                    ->where('retur_penjualan_r.kdobat', $kdobat)
                    ->groupBy('retur_penjualan_r.kdobat', 'retur_penjualan_r.nopenerimaan')
                    ->get();
                $retur = collect($returRinci)->sum('jumlah');


                $headerResep = Resepkeluarheder::select('noresep')->where('tgl_selesai', 'LIKE', '%' . $x . '%')
                    ->where('depo', $koderuangan)
                    ->when($koderuangan == 'Gd-04010103', function ($q) use ($x, $kdobat) {
                        // ambil noresep rinci di persiapan operasi rinci
                        $perRin = PersiapanOperasiRinci::select('noresep')
                            ->join('persiapan_operasis', 'persiapan_operasi_rincis.nopermintaan', '=', 'persiapan_operasis.nopermintaan')
                            ->where('tgl_distribusi', 'LIKE', '%' . $x . '%')
                            ->where('kd_obat', $kdobat)
                            ->groupBy('kd_obat')
                            ->pluck('noresep');
                        $q->whereNotIn('noresep', $perRin);
                    })
                    ->distinct()->pluck('noresep');
                $resepKeluarRacikanRinci = Resepkeluarrinciracikan::select(
                    'resep_keluar_racikan_r.kdobat',
                    'resep_keluar_racikan_r.nopenerimaan',
                    DB::raw('sum(resep_keluar_racikan_r.jumlah) as jumlah')
                )
                    ->whereIn('noresep', $headerResep)
                    ->where('resep_keluar_racikan_r.kdobat', $kdobat)
                    ->groupBy('resep_keluar_racikan_r.kdobat', 'resep_keluar_racikan_r.nopenerimaan')
                    ->get();
                $resepKeluarRacikan = collect($resepKeluarRacikanRinci)->sum('jumlah');

                $resepKeluarRinci = Resepkeluarrinci::select(
                    'resep_keluar_r.kdobat',
                    'resep_keluar_r.nopenerimaan',
                    DB::raw('sum(resep_keluar_r.jumlah) as jumlah')
                )
                    ->when($koderuangan == 'Gd-04010103', function ($anu) use ($x, $kdobat) {
                        $anu->leftJoin('persiapan_operasi_rincis', function ($q) {
                            $q->on('persiapan_operasi_rincis.noresep', '=', 'resep_keluar_r.noresep')
                                ->on('persiapan_operasi_rincis.kd_obat', '=', 'resep_keluar_r.kdobat');
                        })
                            ->whereNull('persiapan_operasi_rincis.noresep');
                    })
                    ->whereIn('resep_keluar_r.noresep', $headerResep)
                    ->where('resep_keluar_r.kdobat', $kdobat)
                    ->where('resep_keluar_r.jumlah', '>', 0)
                    ->groupBy('resep_keluar_r.kdobat', 'resep_keluar_r.nopenerimaan')
                    ->get();

                $resepKeluar = collect($resepKeluarRinci)->sum('jumlah');
                $persiapanOperasiDistribusiRinci = [];
                $persiapanOperasiDistribusiRetur = [];
                if ($koderuangan == 'Gd-04010103') {
                    $persiapanOperasiDistribusiRinci = PersiapanOperasiDistribusi::select(
                        'persiapan_operasi_distribusis.kd_obat',
                        'persiapan_operasi_distribusis.nopenerimaan',
                        // 'persiapan_operasi_distribusis.nopermintaan',
                        DB::raw('sum(persiapan_operasi_distribusis.jumlah) as jumlah'),
                    )
                        ->join('persiapan_operasis', 'persiapan_operasi_distribusis.nopermintaan', '=', 'persiapan_operasis.nopermintaan')
                        ->leftJoin('persiapan_operasi_rincis', function ($join) {
                            $join->on('persiapan_operasi_rincis.nopermintaan', '=', 'persiapan_operasi_distribusis.nopermintaan')
                                ->on('persiapan_operasi_rincis.kd_obat', '=', 'persiapan_operasi_distribusis.kd_obat');
                        })
                        ->where('persiapan_operasis.tgl_distribusi', 'LIKE', '%' . $x . '%')
                        ->where('persiapan_operasi_distribusis.kd_obat', $kdobat)
                        ->whereIn('persiapan_operasis.flag', ['2', '3', '4'])
                        ->groupBy('persiapan_operasi_distribusis.kd_obat', 'persiapan_operasi_distribusis.nopenerimaan')
                        // ->groupBy('persiapan_operasi_distribusis.kd_obat', 'persiapan_operasi_distribusis.nopermintaan')
                        ->orderBy('persiapan_operasis.tgl_distribusi')
                        ->get();
                    $persiapanOperasiDistribusiRetur = PersiapanOperasiDistribusi::select(
                        'persiapan_operasi_distribusis.kd_obat',
                        'persiapan_operasi_distribusis.nopenerimaan',
                        // 'persiapan_operasi_distribusis.nopermintaan',
                        DB::raw('sum(persiapan_operasi_distribusis.jumlah_retur) as jumlah'),
                    )
                        ->join('persiapan_operasis', 'persiapan_operasi_distribusis.nopermintaan', '=', 'persiapan_operasis.nopermintaan')
                        ->leftJoin('persiapan_operasi_rincis', function ($join) {
                            $join->on('persiapan_operasi_rincis.nopermintaan', '=', 'persiapan_operasi_distribusis.nopermintaan')
                                ->on('persiapan_operasi_rincis.kd_obat', '=', 'persiapan_operasi_distribusis.kd_obat');
                        })
                        ->where('persiapan_operasis.tgl_retur', 'LIKE', '%' . $x . '%')
                        ->where('persiapan_operasi_distribusis.kd_obat', $kdobat)
                        ->where('persiapan_operasi_distribusis.jumlah_retur', '>', 0)
                        ->whereIn('persiapan_operasis.flag', ['2', '3', '4'])
                        ->groupBy('persiapan_operasi_distribusis.kd_obat', 'persiapan_operasi_distribusis.nopenerimaan')
                        // ->groupBy('persiapan_operasi_distribusis.kd_obat', 'persiapan_operasi_distribusis.nopenerimaan', 'persiapan_operasi_distribusis.nopermintaan')
                        // ->orderBy('persiapan_operasis.tgl_retur')
                        ->get();
                }


                // retur gudang

                $returGudangRinci = ReturGudangDetail::select(
                    'retur_gudang_details.kd_obat',
                    'retur_gudang_details.nopenerimaan',
                    DB::raw('sum(retur_gudang_details.jumlah_retur) as jumlah')
                )
                    ->leftJoin('retur_gudangs', 'retur_gudangs.no_retur', '=', 'retur_gudang_details.no_retur')
                    ->where('retur_gudangs.depo', $koderuangan)
                    ->where('retur_gudangs.tgl_retur', 'LIKE', '%' . $x . '%')
                    ->where('retur_gudang_details.kd_obat', $kdobat)
                    ->where('retur_gudangs.kunci', '1')
                    ->groupBy('retur_gudang_details.kd_obat', 'retur_gudangs.depo', 'retur_gudang_details.nopenerimaan')
                    ->get();
                $returGudang = collect($returGudangRinci)->sum('jumlah');
                $rawNoper = [];
                foreach ($saldoAwalDepoRinci as $key) {
                    $rawNoper[] = $key->nopenerimaan;
                }
                foreach ($penyesuaianDepoRinci as $key) {
                    $rawNoper[] = $key->nopenerimaan;
                }
                foreach ($mutasiMasukDepoRinci as $key) {
                    $rawNoper[] = $key->nopenerimaan;
                }
                foreach ($mutasiKeluarDepoRinci as $key) {
                    $rawNoper[] = $key->nopenerimaan;
                }
                foreach ($resepKeluarRinci as $key) {
                    $rawNoper[] = $key->nopenerimaan;
                }
                foreach ($returRinci as $key) {
                    $rawNoper[] = $key->nopenerimaan;
                }
                foreach ($resepKeluarRacikanRinci as $key) {
                    $rawNoper[] = $key->nopenerimaan;
                }
                foreach ($persiapanOperasiDistribusiRinci as $key) {
                    $rawNoper[] = $key->nopenerimaan;
                }
                foreach ($persiapanOperasiDistribusiRetur as $key) {
                    $rawNoper[] = $key->nopenerimaan;
                }
                // sudut pandang foreach
                $noper = array_unique($rawNoper);

                $hasil = [];

                $penKur = [];
                $penLeb = [];
                $penPas = [];

                $anuaad = 0;
                $anumas = 0;
                $anukel = 0;
                $stOPAll = StokStokopname::select('kdobat', DB::raw('sum(jumlah) as jumlah'))->where('kdobat', $kdobat)
                    ->where('kdruang', $koderuangan)->where('tglopname', 'LIKE', $x . '%')->first();
                $tts = round($stOPAll->jumlah, 2) ?? 0;
                $sisa = 0;
                $masukMu = 0;
                $keluarMu = 0;
                // pembetulan nomor penerimaan
                foreach ($noper as $key) {
                    if ($x == $sekarang) {
                        $stOP = FarmasinewStokreal::select('kdobat', DB::raw('sum(jumlah) as jumlah'))->where('kdobat', $kdobat)
                            ->where('kdruang', $koderuangan)->where('nopenerimaan', $key)->first();
                    } else {
                        $stOP = StokStokopname::select('kdobat', DB::raw('sum(jumlah) as jumlah'))->where('kdobat', $kdobat)
                            ->where('kdruang', $koderuangan)->where('nopenerimaan', $key)->where('tglopname', 'LIKE', $x . '%')->first();
                    }
                    $salAwal =  collect($saldoAwalDepoRinci)->firstWhere('nopenerimaan', $key)->total ?? 0;
                    $mutMas =  collect($mutasiMasukDepoRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;
                    $peny =  collect($penyesuaianDepoRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;
                    $retPenj =  collect($returRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;
                    $retPersi =  collect($persiapanOperasiDistribusiRetur)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;
                    // keluar
                    $mutKel =  collect($mutasiKeluarDepoRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;
                    $resepNRac =  collect($resepKeluarRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;
                    $resepRac =  collect($resepKeluarRacikanRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;
                    $retGud =  collect($returGudangRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;
                    $distOp =  collect($persiapanOperasiDistribusiRinci)->firstWhere('nopenerimaan', $key)->jumlah ?? 0;

                    $maSuk = round(((float)round($salAwal, 2) + (float)round($mutMas, 2) +  (float)round($retPenj, 2) +  (float)round($retPersi, 2) + (float)round($peny, 2)), 2);
                    $keLuar = round(((float)round($mutKel, 2) + (float)round($retGud, 2) + (float)round($resepNRac, 2) + (float)round($resepRac, 2) + (float)round($distOp, 2)), 2);
                    $sisanya = round(($maSuk - $keLuar), 2);
                    $stOpnya = round($stOP->jumlah, 2) ?? 0;
                    $sts = round(($sisanya - $stOpnya), 2);
                    // $tts += $stOpnya;
                    $sisa += $sisanya;
                    $masukMu += $maSuk;
                    $keluarMu += $keLuar;

                    if ($sisanya == $stOpnya) {
                        $penPas[] = [
                            'noper' => $key,
                            'sisanya' => $sisanya,
                            'sts' => $sts,
                            'stOpnya' => $stOpnya,
                            'maSuk' => $maSuk,
                            'keLuar' => $keLuar,
                            'masuknya' => [
                                'salAwal' => $salAwal,
                                'mutMas' => $mutMas,
                                'retPenj' => $retPenj,
                                'peny' => $peny,
                            ],
                            'keluarnya' => [
                                'mutKel' => $mutKel,
                                'resepNRac' => $resepNRac,
                                'resepRac' => $resepRac,
                                'retGud' => $retGud,
                                'distOp' => $distOp,
                            ],
                        ];
                    } else if ($sisanya < 0) {
                        $penKur[] = [
                            'noper' => $key,
                            'sisanya' => $sisanya,
                            'sts' => $sts,
                            'stOpnya' => $stOpnya,
                            'maSuk' => $maSuk,
                            'keLuar' => $keLuar,
                            'masuknya' => [
                                'salAwal' => $salAwal,
                                'mutMas' => $mutMas,
                                'retPenj' => $retPenj,
                                'peny' => $peny,
                            ],
                            'keluarnya' => [
                                'mutKel' => $mutKel,
                                'resepNRac' => $resepNRac,
                                'resepRac' => $resepRac,
                                'retGud' => $retGud,
                                'distOp' => $distOp,
                            ],
                        ];
                    } else if ($sisanya < $stOpnya) {
                        $penKur[] = [
                            'noper' => $key,
                            'sisanyaPeng' => $sisanya,
                            'sisanya' => $sts,
                            'sts' => $sts,
                            'stOpnya' => $stOpnya,
                            'maSuk' => $maSuk,
                            'keLuar' => $keLuar,
                            'masuknya' => [
                                'salAwal' => $salAwal,
                                'mutMas' => $mutMas,
                                'retPenj' => $retPenj,
                                'peny' => $peny,
                            ],
                            'keluarnya' => [
                                'mutKel' => $mutKel,
                                'resepNRac' => $resepNRac,
                                'resepRac' => $resepRac,
                                'retGud' => $retGud,
                                'distOp' => $distOp,
                            ],
                        ];
                    } else {
                        $penLeb[] = [
                            'noper' => $key,
                            'sisanya' => $sisanya,
                            'sts' => $sts,
                            'stOpnya' => $stOpnya,
                            'maSuk' => $maSuk,
                            'keLuar' => $keLuar,
                            'masuknya' => [
                                'salAwal' => $salAwal,
                                'mutMas' => $mutMas,
                                'retPenj' => $retPenj,
                                'peny' => $peny,
                            ],
                            'keluarnya' => [
                                'mutKel' => $mutKel,
                                'resepNRac' => $resepNRac,
                                'resepRac' => $resepRac,
                                'retGud' => $retGud,
                                'distOp' => $distOp,
                            ],
                        ];
                    }
                }
                // }


                $parameter['nopenerimaan'] = $noper;
                $parameter['penPas'] = $penPas;
                $parameter['penKur'] = $penKur;
                $parameter['penLeb'] = $penLeb;
                $parameter['tts'] = round($tts, 2);
                $parameter['sisa'] = round($sisa, 2);

                $eksekusi = self::nopenerimaanDepo($parameter);
                $cekOpname = self::opnemeDepo($parameter);
                $gaKtm = $eksekusi['gaKtm'] ?? false;

                $data = [
                    'kdobat' => $kdobat,
                    'gaKtm' => $gaKtm,
                    'eksekusi' => $eksekusi ?? [],
                    'penKur' => $penKur,
                    'penLeb' => $penLeb,
                    'penPas' => $penPas,
                    'cekOpname' => $cekOpname ?? [],


                    'tts' => round($tts, 2),
                    'sisa' => round($sisa, 2),
                    'masuk' => $masukMu,
                    'keluar' => $keluarMu,
                    // 'anuaad' => $anuaad,
                    // 'anumas' => $anumas,
                    // 'anukel' => $anukel,
                    // 'hasil' => $hasil,
                    'saldoAwal' => $saldoAwal,
                    // 'stokid' => $stokid,
                    'penyesuaian' => $penyesuaian,

                    'saldoAwalRinci' => $saldoAwalDepoRinci,
                    // 'mutasiMasukDepoRinci' => $mutasiMasukDepoRinci,
                    // 'mutasiKeluarDepoRinci' => $mutasiKeluarDepoRinci,
                    'resepKeluarRinci' => $resepKeluarRinci,
                    // 'returRinci' => $returRinci,
                    // 'resepKeluarRacikanRinci' => $resepKeluarRacikanRinci,
                    'persiapanOperasiDistribusiRinci' => $persiapanOperasiDistribusiRinci ?? [],
                    'persiapanOperasiDistribusiRetur' => $persiapanOperasiDistribusiRetur ?? [],
                    // 'returGudangRinci' => $returGudangRinci,
                    // 'mutasiMasuk' => $mutasiMasuk,
                    // 'mutasiKeluar' => $mutasiKeluar,
                    // 'noresep' => $noresep ?? [],
                    // 'resepKeluar' => $resepKeluar,
                    // 'retur' => $retur,
                    // 'resepKeluarRacikan' => $resepKeluarRacikan,
                    // 'persiapanOperasiDistribusi' => $persiapanOperasiDistribusi ?? null,

                    // 'rawNoper' => $rawNoper,
                    // 'noper' => $noper,

                    'message' => $message
                ];
            }
            DB::connection('farmasi')->commit();
            return [
                'data' => $data,
                'status' => 200,
            ];
        } catch (\Exception $e) {
            DB::connection('farmasi')->rollBack();
            return [
                'data' => [
                    'data' => $data,
                    'result' => '' . $e,
                    'err' =>  $e,
                    'message' =>  $e->getMessage(),
                    'line' => '' . $e->getLine(),
                    'file' =>  $e->getFile(),
                ],
                'status' => 410
            ];
        }
    }

    /**
     * langkah perbaikan
     * 1. pastikan nomor penerimaan gudang sudah ada di stokopname terakhir adalah sisa dari pernerimaan / mutasi terakhir
     * 2. jika gudang, maka perbaiki mutasi keluar saja
     * 3. jika depo paka prioitaskan resep keluar dengan status 3, kemudian racikan dengan status 3, kemudian resep keluar dan racikan dengan status 4,
     * dan terakhir jika masih belum bisa juga maka perbaiki yang mutasi
     * 4. jika depo ok maka perbaiki dulu yang di distribusi persiapan operasi, kemudian cari noresepnya berdasarkan rinci persiapan baru kemudian berdasarkan itu cari resep keluar,
     * dan jika masih belum teratasi maka perbaiki yang mutasi.
     * 5, jika sisa dan stok opname tidak sama maka, munculkan tanpa perlu diperbaiki
     */
    public static function dateCompare($element, $element2)
    {
        return strtotime($element2['tglpenerimaan']) - strtotime($element['tglpenerimaan']);
    }
    public static function opnemeGudang($head)
    {
        $sekarang = date('Y-m');
        $stokid = FarmasinewStokreal::select('id')->where('kdruang', $head['koderuangan'])
            ->where('kdobat', $head['kdobat'])
            ->pluck('id');
        $penyesuaianDepoRinci = PenyesuaianStok::select('stokreal_id', 'nopenerimaan', DB::raw('sum(penyesuaian) as jumlah'))
            ->whereIn('stokreal_id', $stokid)
            ->where('tgl_penyesuaian', 'LIKE', '%' . $head['now'] . '%')
            ->groupBy('stokreal_id', 'nopenerimaan')
            ->first();
        if ($head['now'] == $sekarang) {
            $opname = FarmasinewStokreal::select('id', 'kdobat', 'jumlah', 'nobatch', 'nopenerimaan', 'tglexp', 'tglpenerimaan',  'harga')->where('kdobat', $head['kdobat'])
                ->where('kdruang', $head['koderuangan'])
                ->where('jumlah', '!=', 0)
                ->orderBy('tglpenerimaan', 'DESC')
                ->get();
        } else {
            $opname = StokStokopname::select('id', 'kdobat', 'jumlah', 'nobatch', 'nopenerimaan', 'tglexp', 'tglpenerimaan', 'tglopname', 'harga')->where('kdobat', $head['kdobat'])
                ->where('kdruang', $head['koderuangan'])
                ->where('tglopname', 'LIKE', '%' . $head['now'] . '%')
                ->orderBy('tglpenerimaan', 'DESC')
                ->get();
        }

        $headerPenerimaan = PenerimaanHeder::select('nopenerimaan')->where('tglpenerimaan', 'LIKE', '%' . $head['now'] . '%')
            ->where('gudang', $head['koderuangan'])->pluck('nopenerimaan');
        $penerimaanRw = PenerimaanRinci::select('nopenerimaan', DB::raw('sum(jml_terima_k) as jml_terima_k'), 'harga_netto_kecil', 'no_batch', 'tgl_exp')
            ->with('header:nopenerimaan,tglpenerimaan')
            ->whereIn('nopenerimaan', $headerPenerimaan)
            ->where('kdobat', $head['kdobat'])
            ->groupBy('nopenerimaan', 'harga_netto_kecil')
            ->orderBy('nopenerimaan', 'DESC')
            ->get();
        $penerimaan = [];
        foreach ($penerimaanRw as $key) {
            $key->tglpenerimaan = $key->header->tglpenerimaan;
            $penerimaan[] = $key;
        }

        if ($penyesuaianDepoRinci) {
            $opnameAwal = StokStokopname::select('id', 'kdobat', 'jumlah', 'nobatch', 'nopenerimaan', 'tglexp', 'tglpenerimaan', 'tglopname', 'harga')
                ->where('kdobat', $head['kdobat'])
                ->where('kdruang', $head['koderuangan'])
                ->where('nopenerimaan', $penyesuaianDepoRinci->nopenerimaan)
                ->where('tglopname', 'LIKE', '%2024-05%')
                ->orderBy('tglpenerimaan', 'DESC')
                ->first();
            array_push($penerimaan, [
                'harga_netto_kecil' => $opnameAwal->harga,
                'jml_terima_k' => $penyesuaianDepoRinci->jumlah,
                'kd_obat' => $opnameAwal->kdobat,
                'no_batch' => $opnameAwal->nobatch,
                'nopenerimaan' => $opnameAwal->nopenerimaan,
                'tglpenerimaan' => $opnameAwal->tglpenerimaan,
                'tgl_exp' => $opnameAwal->tglexp,
                'koreksi' => true,
            ]);
        }
        // pertanyaan 1 : apakah jumlah stok opname sesuai?
        // jawab :
        $jmlOp = round(collect($opname)->sum('jumlah'), 2);
        $jmlSesuai = $jmlOp == $head['tts'] ? true : false;
        // pertanyaan 2 : apakah nomor peberimaan yang tertera sudah sesuai?
        // jawab :
        $noperTidak = [];
        $tts = $head['tts'];

        $sPen = usort($penerimaan, array('App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Stok\SetNewStokController', 'dateCompare'));

        foreach ($penerimaan as $key) {
            $opnya = collect($opname)->where('nopenerimaan', $key['nopenerimaan']);
            $jml = round($opnya->sum('jumlah'), 2);
            $jmlPen = round($key['jml_terima_k'], 2);
            if ($tts >= $jmlPen) $sisa = $tts - $jmlPen;
            else $sisa = 0;
            $tts = $sisa;
            // sama itu sisa masih lebih dari 0 dan jml===jml pen atau jika sisanya 0 maka jmlPen >= $jml
            if (!(($sisa > 0 && $jml == $jmlPen) || ($sisa == 0 && $jmlPen >= $jml))) {
                $noperTidak[] = [
                    'key' => $key,
                    'opnya' => $opnya,
                    'jml' => $jml,
                    'jmlPen' => $jmlPen,
                    'sisa' => $sisa,
                ];
            }
        }
        $noperSesuai = sizeof($noperTidak) == 0 ? true : false;
        return [
            'opname' => $opname,
            'penerimaan' => $penerimaan,
            'jmlOp' => $jmlOp,
            'jmlSesuai' => $jmlSesuai,
            'noperTidak' => $noperTidak,
            'noperSesuai' => $noperSesuai,
            'stokid' => $stokid,
            'sPen' => $sPen,
        ];
    }
    public static function opnemeDepo($head)
    {

        $stokid = FarmasinewStokreal::select('id')->where('kdruang', $head['koderuangan'])
            ->where('kdobat', $head['kdobat'])
            ->pluck('id');
        $penyesuaianDepoRinci = PenyesuaianStok::select('stokreal_id', 'nopenerimaan', DB::raw('sum(penyesuaian) as jumlah'))
            ->whereIn('stokreal_id', $stokid)
            ->where('tgl_penyesuaian', 'LIKE', '%' . $head['now'] . '%')
            ->groupBy('stokreal_id', 'nopenerimaan')
            ->first();


        $opname = StokStokopname::select('id', 'kdobat', 'jumlah', 'nobatch', 'nopenerimaan', 'tglexp', 'tglpenerimaan', 'tglopname', 'harga')->where('kdobat', $head['kdobat'])
            ->where('kdruang', $head['koderuangan'])
            ->where('tglopname', 'LIKE', '%' . $head['now'] . '%')
            ->orderBy('nopenerimaan', 'DESC')
            ->get();
        $headMut = Permintaandepoheder::select('no_permintaan')
            ->where('dari', $head['koderuangan'])
            ->where('tgl_kirim_depo', 'LIKE', '%' . $head['now'] . '%')
            ->pluck('no_permintaan');
        $penerimaan = Mutasigudangkedepo::select('no_permintaan', 'nopenerimaan', DB::raw('sum(jml) as jml_terima_k'), 'harga as harga_netto_kecil', 'nobatch as no_batch', 'tglexp as tgl_exp', 'kd_obat', 'tglpenerimaan')
            ->with('header:no_permintaan,tgl_kirim_depo')
            ->whereIn('nopenerimaan', $head['nopenerimaan'])
            ->whereIn('no_permintaan', $headMut)
            ->where('kd_obat', $head['kdobat'])
            ->groupBy('nopenerimaan', 'harga')
            ->orderBy('nopenerimaan', 'DESC')
            ->get()->toArray();
        if ($penyesuaianDepoRinci) {
            $opnameAwal = StokStokopname::select('id', 'kdobat', 'jumlah', 'nobatch', 'nopenerimaan', 'tglexp', 'tglpenerimaan', 'tglopname', 'harga')
                ->where('kdobat', $head['kdobat'])
                ->where('kdruang', $head['koderuangan'])
                ->where('nopenerimaan', $penyesuaianDepoRinci->nopenerimaan)
                ->where('tglopname', 'LIKE', '%2024-05%')
                ->orderBy('nopenerimaan', 'DESC')
                ->first();
            if ($opnameAwal) {
                array_push($penerimaan, [
                    'harga_netto_kecil' => $opnameAwal->harga,
                    'jml_terima_k' => $penyesuaianDepoRinci->jumlah,
                    'kd_obat' => $opnameAwal->kdobat,
                    'no_batch' => $opnameAwal->nobatch,
                    'nopenerimaan' => $opnameAwal->nopenerimaan,
                    'tglpenerimaan' => $opnameAwal->tglpenerimaan,
                    'tgl_exp' => $opnameAwal->tglexp,
                    'koreksi' => true,
                ]);
            }
        }
        // pertanyaan 1 : apakah jumlah stok opname sesuai?
        // jawab :
        $jmlOp = round(collect($opname)->sum('jumlah'), 2);
        $jmlSesuai = $jmlOp == $head['tts'] ? true : false;
        // pertanyaan 2 : apakah nomor peberimaan yang tertera sudah sesuai?
        // jawab :
        $noperTidak = [];
        $tts = $head['tts'];

        foreach ($penerimaan as $key) {
            $opnya = collect($opname)->where('nopenerimaan', $key['nopenerimaan']);
            $jml = round($opnya->sum('jumlah'), 2);
            $jmlPen = round($key['jml_terima_k'], 2);
            if ((float)$tts >= (float)$jmlPen) {
                $sisa = $tts - $jmlPen;
            } else {
                $sisa = 0;
            }
            $tts = $sisa;
            // sama itu sisa masih lebih dari 0 dan jml===jml pen atau jika sisanya 0 maka jmlPen >= $jml
            if (!(($sisa > 0 && $jml == $jmlPen) || ($sisa == 0 && $jmlPen >= $jml) || ($sisa == $tts))) {
                $noperTidak[] = [
                    'key' => $key,
                    'opnya' => $opnya,
                    'jml' => $jml,
                    'jmlPen' => $jmlPen,
                    'sisa' => $sisa,
                    'tts' => $tts,
                    'cond awl' => ((float)$tts >= (float)$jmlPen),
                    'cond 1' => ($sisa > 0 && $jml == $jmlPen),
                    'cond 2' => ($sisa == 0 && $jmlPen >= $jml),
                    'cond 3' => !(($sisa > 0 && $jml == $jmlPen) || ($sisa == 0 && $jmlPen >= $jml)),
                    'cond 4' => ($sisa == $tts),
                    'cond 5' => !(($sisa > 0 && $jml == $jmlPen) || ($sisa == 0 && $jmlPen >= $jml) || ($sisa == $tts)),
                ];
            }
        }
        $noperSesuai = sizeof($noperTidak) == 0 ? true : false;
        return [
            'opname' => $opname,
            'penerimaan' => $penerimaan,
            'penyesuaianDepoRinci' => $penyesuaianDepoRinci,
            'jmlOp' => $jmlOp,
            'jmlSesuai' => $jmlSesuai,
            'noperTidak' => $noperTidak,
            'noperSesuai' => $noperSesuai,
            'opnameAwal' => $opnameAwal ?? null,
        ];
    }
    public static function nopenerimaanGudang($head)
    {
        $opname = StokStokopname::where('kdobat', $head['kdobat'])
            ->where('kdruang', $head['koderuangan'])
            ->where('tglopname', 'LIKE', '%' . $head['now'] . '%')
            ->get();
        // $penrimaanR = PenerimaanRinci::select('penerimaan_r.nopenerimaan', 'penerimaan_r.kdobat', 'penerimaan_r.harga_netto_kecil as harga', 'penerimaan_r.no_batch as nobatch', 'penerimaan_r.jml_terima_k as jumlah', 'penerimaan_r.tgl_exp as tglexp', 'penerimaan_h.tglpenerimaan')
        //     ->leftJoin('penerimaan_h', 'penerimaan_h.nopenerimaan', '=', 'penerimaan_r.nopenerimaan')
        //     ->whereIn('penerimaan_r.nopenerimaan', $head['nopenerimaan'])->where('penerimaan_r.kdobat', $head['kdobat'])
        //     ->orderBy('penerimaan_h.tglpenerimaan', 'DESC')->get();

        // return [
        //     'opname' => $opname,
        //     'penrimaanR' => $penrimaanR,
        //     'tts' => $head['tts'],
        //     'head' => $head,

        // ];
        $mutasiKeluarRinci = Mutasigudangkedepo::select(
            'mutasi_gudangdepo.*'
        )
            ->join('permintaan_h', 'permintaan_h.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
            ->where('permintaan_h.tgl_kirim_depo', 'LIKE', '%' . $head['now'] . '%')
            ->where('permintaan_h.tujuan', $head['koderuangan'])
            ->where('mutasi_gudangdepo.kd_obat', $head['kdobat'])
            ->orderBy('mutasi_gudangdepo.jml', 'DESC')
            ->get();
        $mutasi = collect($mutasiKeluarRinci);
        $retMutasi = [];
        $opnaNya = collect($opname);
        $gaKtm = false;
        $pelengkaps = [];
        $targetJums = [];
        $targets = [];
        if ($head['sisa'] == 0) {
            foreach ($head['penLeb'] as $key) {
                $targets[] = [
                    'boleh' => $key['sisanya'],
                    'sisa' => $key['sisanya'],
                    'noper' => $key['noper'],
                ];
            }
        } else if ($head['sisa'] > 0) {
            // untuk yang lalu ga usah mikir mana yang keluar dulaun, yang penting ga ada nomor penerimaan yang mutasinya minus
            // dan hasil stok opnamenya sesuai

            foreach ($head['penLeb'] as $key) {
                $targets[] = [
                    'boleh' => $key['sisanya'] - $opnaNya->where('nopenerimaan', $key['noper'])->sum('jumlah')  ?? 0,
                    'sisa' => $key['sisanya'],
                    'noper' => $key['noper'],
                ];
            }
        }
        usort($targets, fn($a, $b) => $a['boleh'] <=> $b['boleh']);
        // return $targets;
        if (sizeof($targets) > 0) {
            foreach ($head['nopenerimaan'] as $key) {
                if (str_contains($key, 'awal')) {
                    $temp = StokStokopname::where('kdobat', $head['kdobat'])
                        ->where('kdruang', $head['koderuangan'])
                        ->where('nopenerimaan', $key)
                        ->first();
                    $pelengkaps[] = $temp;
                } else {
                    $temp = PenerimaanRinci::select('nopenerimaan', 'kdobat', 'harga_netto_kecil as harga', 'no_batch as nobatch', 'tgl_exp as tglexp')->with('header:nopenerimaan,tglpenerimaan')->where('nopenerimaan', $key)->where('kdobat', $head['kdobat'])->first();
                    if ($temp) {
                        $temp->tglpenerimaan = $temp->header->tglpenerimaan;
                        $pelengkaps[] = $temp;
                    }
                }
            }
            foreach ($targets as $target) {
                // return $target;
                $boleh = $target['boleh'] ?? 0;
                if ($boleh > 0) {
                    foreach ($head['penKur'] as $key) {
                        $adaKurang = -$key['sisanya'] ?? 0;
                        $targetJumlah = $adaKurang - $boleh == 0 ? $boleh : ($adaKurang - $boleh < 0 ? $adaKurang : $adaKurang - $boleh);
                        $dataBolehDiganti = [];
                        $accJumlah = 0;
                        $entries = $mutasi->where('nopenerimaan', $key['noper'])->all();
                        foreach ($entries as $entry) {
                            if ($entry['jml'] == $targetJumlah) {
                                $dataBolehDiganti = [];
                                $accJumlah = $entry['jml'];
                                $dataBolehDiganti[] = $entry;
                            } else if ($entry['jml'] <= ($targetJumlah - $accJumlah)) {
                                $accJumlah += $entry['jml'];
                                $dataBolehDiganti[] = $entry;
                            }
                            if ($accJumlah == $targetJumlah) {
                                break;
                            }
                        }
                        if ($head['perbaiki']) {
                            if ($accJumlah == $targetJumlah) {
                                foreach ($dataBolehDiganti as $entry) {
                                    $pelengkap = collect($pelengkaps)->firstWhere('nopenerimaan', $target['noper']);

                                    $entry->update(['nopenerimaan' => $target['noper']]);
                                    if ($entry->harga != $pelengkap->harga) $entry->update(['harga' => $pelengkap->harga]);
                                    if ($entry->nobatch != $pelengkap->nobatch) $entry->update(['nobatch' => $pelengkap->nobatch]);
                                    if ($entry->tglexp != $pelengkap->tglexp) $entry->update(['tglexp' => $pelengkap->tglexp]);
                                }
                            } else if ($accJumlah <= $targetJumlah) {
                                foreach ($dataBolehDiganti as $entry) {
                                    $pelengkap = collect($pelengkaps)->firstWhere('nopenerimaan', $target['noper']);

                                    $entry->update(['nopenerimaan' => $target['noper']]);
                                    if ($entry->harga != $pelengkap->harga) $entry->update(['harga' => $pelengkap->harga]);
                                    if ($entry->nobatch != $pelengkap->nobatch) $entry->update(['nobatch' => $pelengkap->nobatch]);
                                    if ($entry->tglexp != $pelengkap->tglexp) $entry->update(['tglexp' => $pelengkap->tglexp]);
                                }
                                $gaKtm = ['else' => $dataBolehDiganti];
                            } else {
                                $gaKtm = $dataBolehDiganti;
                            }
                        }
                    }
                }
            }
            $retMutasi[] = [
                'targetJums' => $targetJums ?? null,
                'targetJumlah' => $targetJumlah ?? null,
                'accJumlah' => $accJumlah ?? null,
                'targets' => $targets,
                'opname' => $opname,
                'pelengkaps' => $pelengkaps,
                'pelengkap' => $pelengkap ?? null,
                'penerimaan' => $penerimaan ?? null,
                'dataBolehDiganti' => $dataBolehDiganti ?? null,
                // 'penerimaan tgl' => $penerimaan->tglpenerimaan ?? null,
            ];
        }
        if (sizeof($head['penKur']) > 0 && sizeof($targets) == 0)  $gaKtm = $head['penKur'];
        return [
            'count' => count($mutasi),
            'retMutasi' => $retMutasi,
            'head' => $head,
            'gaKtm' => $gaKtm,
            // 'opname' => $opname,
            'mutasiKeluarRinci' => $mutasiKeluarRinci,
        ];
    }
    public static function nopenerimaanDepo($head)
    {
        // return $head;
        $opname = StokStokopname::where('kdobat', $head['kdobat'])
            ->where('kdruang', $head['koderuangan'])
            ->where('tglopname', 'LIKE', '%' . $head['now'] . '%')
            ->get();
        $opnaNya = collect($opname);
        if ($head['tipe'] === 'default') {
            $mutasiKeluarRinci = Mutasigudangkedepo::select(
                'mutasi_gudangdepo.*',
                'mutasi_gudangdepo.jml as jumlah'
            )
                ->join('permintaan_h', 'permintaan_h.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                ->where('permintaan_h.tgl_kirim_depo', 'LIKE', '%' . $head['now'] . '%')
                ->where('permintaan_h.tujuan', $head['koderuangan'])
                ->where('permintaan_h.dari', 'LIKE', '%R-%')
                ->where('mutasi_gudangdepo.kd_obat', $head['kdobat'])
                ->orderBy('mutasi_gudangdepo.jml', 'DESC')
                ->get();
            $resepKeluarRinci = Resepkeluarrinci::select(
                'resep_keluar_r.id',
                'resep_keluar_r.noresep',
                'resep_keluar_r.nopenerimaan',
                'resep_keluar_r.kdobat',
                'resep_keluar_r.harga_beli',
                'resep_keluar_r.jumlah',
            )
                ->join('resep_keluar_h', 'resep_keluar_h.noresep', '=', 'resep_keluar_r.noresep')
                ->whereIn('resep_keluar_h.flag', ['3', '4'])
                ->where('resep_keluar_h.tgl_selesai', 'LIKE', '%' . $head['now'] . '%')
                ->where('resep_keluar_h.depo', $head['koderuangan'])
                ->where('resep_keluar_r.kdobat', $head['kdobat'])
                ->where('resep_keluar_r.jumlah', '>', 0)
                ->orderBy('resep_keluar_h.flag', 'ASC')
                ->orderBy('resep_keluar_r.jumlah', 'DESC')
                ->get();
            if ($head['koderuangan'] == 'Gd-04010103') {
                $persiapanOperasiDistribusi = PersiapanOperasiDistribusi::select(
                    'persiapan_operasi_distribusis.id',
                    'persiapan_operasi_distribusis.kd_obat',
                    'persiapan_operasi_distribusis.nopenerimaan',
                    'persiapan_operasi_distribusis.jumlah as jumlah',
                    DB::raw('persiapan_operasi_distribusis.jumlah - persiapan_operasi_distribusis.jumlah_retur as sisa'),
                )
                    ->join('persiapan_operasis', 'persiapan_operasi_distribusis.nopermintaan', '=', 'persiapan_operasis.nopermintaan')
                    ->where('persiapan_operasis.tgl_distribusi', 'LIKE', '%' . $head['now'] . '%')
                    ->where('persiapan_operasi_distribusis.kd_obat', $head['kdobat'])
                    ->whereIn('persiapan_operasis.flag', ['2', '3', '4'])
                    ->havingRaw('sisa > 0')
                    ->get();
            }

            $mutasi = $head['koderuangan'] == 'Gd-03010101' ? collect($mutasiKeluarRinci) : ($head['koderuangan'] == 'Gd-04010103' ? collect($persiapanOperasiDistribusi) : collect($resepKeluarRinci));
        }
        if ($head['tipe'] === 'racikan') {
            // racikan
            $headRes = Resepkeluarheder::select('noresep')
                ->whereIn('flag', ['3', '4'])
                ->where('tgl_selesai', 'LIKE', '%' . $head['now'] . '%')
                ->where('depo', $head['koderuangan'])

                ->pluck('noresep');
            $rinciracikan = Resepkeluarrinciracikan::select('id', 'noresep', 'nopenerimaan', 'kdobat', 'harga_beli', 'jumlah')
                ->whereIn('noresep', $headRes)
                ->where('kdobat', $head['kdobat'])
                ->get();
            $mutasi =  collect($rinciracikan);
        }
        // mutasi antar
        if ($head['tipe'] === 'antar') {
            $headMut = Permintaandepoheder::select('no_permintaan')
                ->where('tujuan', $head['koderuangan'])
                ->where('dari', 'NOT LIKE', '%R-%')
                ->where('tgl_kirim_depo', 'LIKE', '%' . $head['now'] . '%')
                ->pluck('no_permintaan');
            $mutanu = Mutasigudangkedepo::select('*', 'jml as jumlah')->with('header:no_permintaan,tgl_kirim_depo,dari,tujuan', 'header.asal:kode,nama')
                ->whereIn('no_permintaan', $headMut)
                ->where('kd_obat', $head['kdobat'])
                ->get();
            $mutasi =  collect($mutanu);
        }

        $retResep = [];
        $targets = [];
        $gaKtm = false;
        // usort($$head['penLeb'], fn($a, $b) => $a['sisanya'] <=> $b['sisanya']);
        if ($head['sisa'] == 0) {
            foreach ($head['penLeb'] as $key) {
                $targets[] = [
                    'boleh' => $key['sisanya'],
                    'sisa' => $key['sisanya'],
                    'noper' => $key['noper'],
                ];
            }
            // if (sizeof($head['penLeb']) > 0) {
            //     $targets[] = [
            //         'boleh' => $head['penLeb'][0]['sisanya'],
            //         'sisa' => $head['penLeb'][0]['sisanya'],
            //         'noper' => $head['penLeb'][0]['noper'],
            //     ];
            // }
        } else if ($head['sisa'] > 0) {
            // untuk yang lalu ga usah mikir mana yang keluar dulaun, yang penting ga ada nomor penerimaan yang mutasinya minus
            // dan hasil stok opnamenya sesuai
            foreach ($head['penLeb'] as $key) {
                $anu = $key['sisanya'] - $opnaNya->where('nopenerimaan', $key['noper'])->sum('jumlah');
                $targets[] = [
                    'boleh' =>  round($anu, 2) ?? 0,
                    'sisa' => $key['sisanya'],
                    'noper' => $key['noper'],
                ];
            }
            // if (sizeof($head['penLeb']) > 0) {
            //     $anu = $head['penLeb'][0]['sisanya'] - $opnaNya->where('nopenerimaan', $head['penLeb'][0]['noper'])->sum('jumlah');
            //     $targets[] = [
            //         'boleh' =>  round($anu, 2) ?? 0,
            //         'sisa' => $head['penLeb'][0]['sisanya'],
            //         'noper' => $head['penLeb'][0]['noper'],
            //     ];
            // }
        }

        usort($targets, fn($a, $b) => $a['boleh'] <=> $b['boleh']);
        if (sizeof($targets) > 0) {
            foreach ($head['nopenerimaan'] as $key) {
                if (str_contains($key, 'awal')) {
                    $temp = StokStokopname::select('nopenerimaan', 'kdobat', 'harga')->where('kdobat', $head['kdobat'])
                        // ->where('kdruang', $head['koderuangan'])
                        ->where('nopenerimaan', $key)
                        ->first();
                } else {
                    $temp = PenerimaanRinci::select('nopenerimaan', 'kdobat', 'harga_netto_kecil as harga')->where('nopenerimaan', $key)->where('kdobat', $head['kdobat'])->first();
                }
                $pelengkaps[] = $temp ?? $key;
            }
            foreach ($targets as $target) {
                // return $target;
                $boleh = $target['boleh'] ?? 0;
                if ($boleh > 0) {
                    foreach ($head['penKur'] as $key) {
                        $adaKurang = -$key['sisanya'] ?? 0;
                        $targetJumlah = $adaKurang - $boleh == 0 ? $boleh : ($adaKurang - $boleh < 0 ? $adaKurang : $adaKurang - $boleh);
                        $dataBolehDiganti = [];
                        $accJumlah = 0;
                        $entries = $mutasi->where('nopenerimaan', $key['noper'])->all();
                        foreach ($entries as $entry) {
                            if ($entry['jumlah'] == $targetJumlah) {
                                $dataBolehDiganti = [];
                                $accJumlah = $entry['jumlah'];
                                $dataBolehDiganti[] = $entry;
                                // return 'asu' . $entry;
                            } else if ($entry['jumlah'] <= ($targetJumlah - $accJumlah)) {
                                $accJumlah += $entry['jumlah'];
                                $dataBolehDiganti[] = $entry;
                            }
                            if ($accJumlah == $targetJumlah) {
                                break;
                            }
                        }
                        if ($head['perbaiki']) {
                            if ($accJumlah == $targetJumlah) {
                                foreach ($dataBolehDiganti as $entry) {
                                    $pelengkap = collect($pelengkaps)->firstWhere('nopenerimaan', $target['noper']);

                                    $entry->update(['nopenerimaan' => $target['noper']]);
                                    if ($head['koderuangan'] == 'Gd-03010101') {
                                        if ($entry->harga != $pelengkap->harga) $entry->update(['harga' => $pelengkap->harga]);
                                        if ($entry->nobatch != $pelengkap->nobatch) $entry->update(['nobatch' => $pelengkap->nobatch]);
                                        if ($entry->tglexp != $pelengkap->tglexp) $entry->update(['tglexp' => $pelengkap->tglexp]);
                                    } else {
                                        if ($entry->harga_beli != $pelengkap->harga) $entry->update(['harga_beli' => $pelengkap->harga]);
                                    }
                                }
                            } else if ($accJumlah < $targetJumlah) {
                                foreach ($dataBolehDiganti as $entry) {
                                    $pelengkap = collect($pelengkaps)->firstWhere('nopenerimaan', $target['noper']);

                                    $entry->update(['nopenerimaan' => $target['noper']]);
                                    if ($head['koderuangan'] == 'Gd-03010101') {
                                        if ($entry->harga != $pelengkap->harga) $entry->update(['harga' => $pelengkap->harga]);
                                        if ($entry->nobatch != $pelengkap->nobatch) $entry->update(['nobatch' => $pelengkap->nobatch]);
                                        if ($entry->tglexp != $pelengkap->tglexp) $entry->update(['tglexp' => $pelengkap->tglexp]);
                                    } else {
                                        if ($entry->harga_beli != $pelengkap->harga) $entry->update(['harga_beli' => $pelengkap->harga]);
                                    }
                                }
                                $gaKtm = ['else' => $dataBolehDiganti];
                            } else {
                                $gaKtm = $dataBolehDiganti;
                            }
                        }
                    }
                }
            }
            $retResep[] = [
                'targetJumlah' => $targetJumlah ?? null,
                'accJumlah' => $accJumlah ?? null,
                'targets' => $targets,
                'opname' => $opname,
                'pelengkaps' => $pelengkaps,
                'pelengkap' => $pelengkap ?? null,
                'penerimaan' => $penerimaan ?? null,
                'dataBolehDiganti' => $dataBolehDiganti ?? null,
                'adaKurang' => $adaKurang ?? null,
                // 'penerimaan tgl' => $penerimaan->tglpenerimaan ?? null,
            ];
        }
        if (sizeof($head['penKur']) > 0 && sizeof($targets) == 0)  $gaKtm = $head['penKur'];
        return [
            'count' => count($mutasi),
            'retResep' => $retResep,
            'head' => $head,
            'gaKtm' => $gaKtm,
            'opname' => $opname,
            'boleh' => $boleh ?? null,
            // 'resepKeluarRinci' => $resepKeluarRinci,
        ];
    }

    /*************  ✨ Codeium Command ⭐  *************/
    /**
     * @return array
     */
    /*
    - perbaikan Harga Keluar Ok
    - mencari data resep yang tidak ada nopenerimaan
    - mencari data penerimaan yang sesuai dengan resep
    - jika data penerimaan ditemukan maka akan diupdate harga beli dan nopenerimaan di resep
    - jika tidak ditemukan maka akan diupdate nopenerimaan saja
    */
    /******  2b93805c-f2b4-4b97-88dd-37cc1753a589  *******/
    public function perbaikanHargaKeluarOk()
    {
        $tanpaNoper = Resepkeluarrinci::select('noresep')->where('nopenerimaan', '')->distinct()->pluck('noresep');
        $perSiRinc = PersiapanOperasiRinci::select(
            'persiapan_operasi_rincis.nopermintaan',
            'persiapan_operasi_rincis.noresep',
            'persiapan_operasi_distribusis.kd_obat',
            'persiapan_operasi_distribusis.nopenerimaan',
            'persiapan_operasi_distribusis.jumlah',
            'persiapan_operasi_distribusis.jumlah_retur',
            DB::raw('persiapan_operasi_distribusis.jumlah - persiapan_operasi_distribusis.jumlah_retur as dipakai'),
        )
            ->join('persiapan_operasi_distribusis', 'persiapan_operasi_distribusis.nopermintaan', '=', 'persiapan_operasi_rincis.nopermintaan')
            ->whereIn('noresep', $tanpaNoper)
            ->havingRaw('dipakai > 0')
            ->get();
        // $distPer = PersiapanOperasiDistribusi::whereIn('nopermintaan', $perSiRinc)->get();
        $ketemu = [];
        $gaketemu = [];
        foreach ($perSiRinc as $key) {
            $trm = PenerimaanRinci::select('kdobat', 'nopenerimaan', 'harga_netto_kecil as harga')
                ->where('nopenerimaan', $key->nopenerimaan)
                ->where('kdobat', $key->kd_obat)
                ->first();
            $dftr = DaftarHarga::where('nopenerimaan', $key->nopenerimaan)
                ->where('kd_obat', $key->kd_obat)
                ->first();
            $opnme = StokStokopname::where('nopenerimaan', $key->nopenerimaan)
                ->where('kdobat', $key->kd_obat)
                ->first();
            $reseps = Resepkeluarrinci::select('id', 'noresep', 'kdobat', 'nopenerimaan', 'harga_beli', 'jumlah')
                ->where('noresep', $key->noresep)
                ->where('kdobat', $key->kd_obat)
                ->where('nopenerimaan', '')
                ->get();
            if (sizeof($reseps)) {
                foreach ($reseps as $resep) {
                    $res = Resepkeluarrinci::find($resep->id);
                    if ($trm) {
                        $res->update(['nopenerimaan' => $key->nopenerimaan]);
                        if ($res->harga_beli != $trm->harga) $res->update(['harga_beli' => $trm->harga]);
                    } else if ($dftr) {
                        $res->update(['nopenerimaan' => $key->nopenerimaan]);
                        if ($res->harga_beli != $dftr->harga) $res->update(['harga_beli' => $dftr->harga]);
                    } else if ($opnme) {
                        $res->update(['nopenerimaan' => $key->nopenerimaan]);
                        if ($res->harga_beli != $opnme->harga) $res->update(['harga_beli' => $opnme->harga]);
                    } else {
                        $res->update(['nopenerimaan' => $key->nopenerimaan]);
                    }

                    $ketemu[] = [
                        'resep' => $resep,
                        'trm' => $trm,
                        'dftr' => $dftr,
                        'opnme' => $opnme,
                    ];
                }
            } else {
                $gaketemu[] = [
                    'key' => $key,
                    'reseps' => $reseps,
                ];
            }
            // if (sizeof($reseps) > 0) {
            //     return [
            //         'key' => $key,
            //         'resep' => $reseps,
            //         'ketemu' => $ketemu,
            //         'gaketemu' => $gaketemu,
            //         'trm' => $trm,
            //         'dftr' => $dftr,
            //         'opnme' => $opnme,
            //     ];
            // }
        }
        return [
            // 'tanpaNoper' => $tanpaNoper,
            // 'perSiRinc' => $perSiRinc,
            'ketemu' => $ketemu,
            'gaketemu' => $gaketemu,
        ];
    }
    public function perbaikanHargaKeluar()
    {
        /**
         * yang di cek
         * 1. mutasi
         * 2. resep keluar
         * 3. resep keluar racikan
         * 4. retur resep
         **/
        // mutasi
        $mutasi = self::perbaikanHargaDiMutasi();
        //resep
        $resep = self::perbaikanHargaDiResep();
        //resep racikan
        $resepRacikan = self::perbaikanHargaDiResepRacikan();
        //retur racikan
        $returResep = self::perbaikanHargaDiReturResep();


        return [
            'mutasi' => $mutasi,
            'resep' => $resep,
            'resepRacikan' => $resepRacikan,
            'returResep' => $returResep,
        ];
    }

    public static function perbaikanHargaDiReturResep()
    {

        $data = [];
        $resPenSource = Returpenjualan_r::select(
            'retur_penjualan_r.nopenerimaan',
            'retur_penjualan_r.kdobat',
            'penerimaan_r.harga_netto_kecil'
        )->leftJoin('penerimaan_r', function ($jo) {
            $jo->on('penerimaan_r.nopenerimaan', '=', 'retur_penjualan_r.nopenerimaan')
                ->on('penerimaan_r.kdobat', '=', 'retur_penjualan_r.kdobat')
                ->on('penerimaan_r.harga_netto_kecil', '=', 'retur_penjualan_r.harga_beli');
        })
            ->where('retur_penjualan_r.nopenerimaan', 'NOT LIKE', '%awal%')
            ->whereNull('penerimaan_r.harga_netto_kecil')
            // ->limit(10) /////////////////////////// ini nanti di coment
            ->get();
        $nopeMutPen = collect($resPenSource)->map(function ($it) {
            return $it->nopenerimaan;
        })->toArray();
        $nopeMutPenUni = array_unique($nopeMutPen);
        $kdobatMutPen = collect($resPenSource)->map(function ($it) {
            return $it->kdobat;
        })->toArray();
        $kdobatMutPenUni = array_unique($kdobatMutPen);
        $mutasiPenToTr = Returpenjualan_r::whereIn('nopenerimaan', $nopeMutPenUni)->whereIn('kdobat', $kdobatMutPenUni)->where('jumlah_retur', '>', 0)->get();
        $penernya = PenerimaanRinci::select('nopenerimaan', 'kdobat', 'no_batch', 'tgl_exp', 'harga_netto_kecil')->with('header:nopenerimaan,tglpenerimaan')->whereIn('nopenerimaan', $nopeMutPenUni)->whereIn('kdobat', $kdobatMutPenUni)->get();
        foreach ($mutasiPenToTr as $mut) {
            $trm = collect($penernya)->where('nopenerimaan', $mut->nopenerimaan)->where('kdobat', $mut->kdobat)->first();
            if ($trm) {
                if ($mut->harga_beli != $trm->harga_netto_kecil) $mut->update(['harga_beli' => $trm->harga_netto_kecil]);
            }
            // return [
            //     'mut' => $mut,
            //     'trm' => $trm,
            // ];

            $data[] = [
                'mut' => $mut,
                'trm' => $trm,
            ];
        }


        $resStokSource = Returpenjualan_r::select(
            'retur_penjualan_r.nopenerimaan',
            'retur_penjualan_r.kdobat',
            'stokopname.harga'
        )->leftJoin('stokopname', function ($jo) {
            $jo->on('stokopname.nopenerimaan', '=', 'retur_penjualan_r.nopenerimaan')
                ->on('stokopname.kdobat', '=', 'retur_penjualan_r.kdobat')
                ->on('stokopname.harga', '=', 'retur_penjualan_r.harga_beli');
        })
            ->where('retur_penjualan_r.nopenerimaan', 'LIKE', '%awal%')
            ->whereNull('stokopname.harga')
            // ->limit(10) /////////////////////////// ini nanti di coment
            ->get();

        $nopeMutSt = collect($resStokSource)->map(function ($it) {
            return $it->nopenerimaan;
        })->toArray();
        $nopeMutStUni = array_unique($nopeMutSt);
        $kdobatMutSt = collect($resStokSource)->map(function ($it) {
            return $it->kdobat;
        })->toArray();
        $kdobatMutStUni = array_unique($kdobatMutSt);
        $mutasiStToTr = Returpenjualan_r::whereIn('nopenerimaan', $nopeMutStUni)->whereIn('kdobat', $kdobatMutStUni)->where('jumlah_retur', '>', 0)->get();
        $saldonya = StokStokopname::whereIn('nopenerimaan', $nopeMutStUni)->whereIn('kdobat', $kdobatMutStUni)->get();
        foreach ($mutasiStToTr as $mut) {
            $trm = collect($saldonya)->where('nopenerimaan', $mut->nopenerimaan)->where('kdobat', $mut->kdobat)->first();
            if ($trm) {
                if ($mut->harga_beli != $trm->harga) $mut->update(['harga_beli' => $trm->harga]);
            }
            // return  [
            //     'mut' => $mut,
            //     'trm' => $trm,
            // ];
            $data[] =  [
                'mut' => $mut,
                'trm' => $trm,
            ];
        }

        return $data;
    }
    public static function perbaikanHargaDiResepRacikan()
    {

        $data = [];
        $resPenSource = Resepkeluarrinciracikan::select(
            'resep_keluar_racikan_r.nopenerimaan',
            'resep_keluar_racikan_r.kdobat',
            'penerimaan_r.harga_netto_kecil'
        )->leftJoin('penerimaan_r', function ($jo) {
            $jo->on('penerimaan_r.nopenerimaan', '=', 'resep_keluar_racikan_r.nopenerimaan')
                ->on('penerimaan_r.kdobat', '=', 'resep_keluar_racikan_r.kdobat')
                ->on('penerimaan_r.harga_netto_kecil', '=', 'resep_keluar_racikan_r.harga_beli');
        })
            ->where('resep_keluar_racikan_r.nopenerimaan', 'NOT LIKE', '%awal%')
            ->where('resep_keluar_racikan_r.jumlah', '>', 0)
            ->whereNull('penerimaan_r.harga_netto_kecil')
            // ->limit(10) /////////////////////////// ini nanti di coment
            ->get();
        $nopeMutPen = collect($resPenSource)->map(function ($it) {
            return $it->nopenerimaan;
        })->toArray();
        $nopeMutPenUni = array_unique($nopeMutPen);
        $kdobatMutPen = collect($resPenSource)->map(function ($it) {
            return $it->kdobat;
        })->toArray();
        $kdobatMutPenUni = array_unique($kdobatMutPen);
        $mutasiPenToTr = Resepkeluarrinciracikan::whereIn('nopenerimaan', $nopeMutPenUni)->whereIn('kdobat', $kdobatMutPenUni)->where('jumlah', '>', 0)->get();
        $penernya = PenerimaanRinci::select('nopenerimaan', 'kdobat', 'no_batch', 'tgl_exp', 'harga_netto_kecil')->with('header:nopenerimaan,tglpenerimaan')->whereIn('nopenerimaan', $nopeMutPenUni)->whereIn('kdobat', $kdobatMutPenUni)->get();
        foreach ($mutasiPenToTr as $mut) {
            $trm = collect($penernya)->where('nopenerimaan', $mut->nopenerimaan)->where('kdobat', $mut->kdobat)->first();
            if ($trm) {
                if ($mut->harga_beli != $trm->harga_netto_kecil) $mut->update(['harga_beli' => $trm->harga_netto_kecil]);
            }
            // return [
            //     'mut' => $mut,
            //     'trm' => $trm,
            // ];

            $data[] = [
                'mut' => $mut,
                'trm' => $trm,
            ];
        }


        $resStokSource = Resepkeluarrinciracikan::select(
            'resep_keluar_racikan_r.nopenerimaan',
            'resep_keluar_racikan_r.kdobat',
            'stokopname.harga'
        )->leftJoin('stokopname', function ($jo) {
            $jo->on('stokopname.nopenerimaan', '=', 'resep_keluar_racikan_r.nopenerimaan')
                ->on('stokopname.kdobat', '=', 'resep_keluar_racikan_r.kdobat')
                ->on('stokopname.harga', '=', 'resep_keluar_racikan_r.harga_beli');
        })
            ->where('resep_keluar_racikan_r.nopenerimaan', 'LIKE', '%awal%')
            ->where('resep_keluar_racikan_r.jumlah', '>', 0)
            ->whereNull('stokopname.harga')
            // ->limit(10) /////////////////////////// ini nanti di coment
            ->get();

        $nopeMutSt = collect($resStokSource)->map(function ($it) {
            return $it->nopenerimaan;
        })->toArray();
        $nopeMutStUni = array_unique($nopeMutSt);
        $kdobatMutSt = collect($resStokSource)->map(function ($it) {
            return $it->kdobat;
        })->toArray();
        $kdobatMutStUni = array_unique($kdobatMutSt);
        $mutasiStToTr = Resepkeluarrinciracikan::whereIn('nopenerimaan', $nopeMutStUni)->whereIn('kdobat', $kdobatMutStUni)->where('jumlah', '>', 0)->get();
        $saldonya = StokStokopname::whereIn('nopenerimaan', $nopeMutStUni)->whereIn('kdobat', $kdobatMutStUni)->get();
        foreach ($mutasiStToTr as $mut) {
            $trm = collect($saldonya)->where('nopenerimaan', $mut->nopenerimaan)->where('kdobat', $mut->kdobat)->first();
            if ($trm) {
                if ($mut->harga_beli != $trm->harga) $mut->update(['harga_beli' => $trm->harga]);
            }
            // return  [
            //     'mut' => $mut,
            //     'trm' => $trm,
            // ];
            $data[] =  [
                'mut' => $mut,
                'trm' => $trm,
            ];
        }

        return $data;
    }
    public static function perbaikanHargaDiResep()
    {

        $data = [];
        $resPenSource = Resepkeluarrinci::select(
            'resep_keluar_r.nopenerimaan',
            'resep_keluar_r.kdobat',
            'penerimaan_r.harga_netto_kecil'
        )->leftJoin('penerimaan_r', function ($jo) {
            $jo->on('penerimaan_r.nopenerimaan', '=', 'resep_keluar_r.nopenerimaan')
                ->on('penerimaan_r.kdobat', '=', 'resep_keluar_r.kdobat')
                ->on('penerimaan_r.harga_netto_kecil', '=', 'resep_keluar_r.harga_beli');
        })
            ->where('resep_keluar_r.nopenerimaan', 'NOT LIKE', '%awal%')
            ->where('resep_keluar_r.jumlah', '>', 0)
            ->whereNull('penerimaan_r.harga_netto_kecil')
            // ->limit(10) /////////////////////////// ini nanti di coment
            ->get();
        $nopeMutPen = collect($resPenSource)->map(function ($it) {
            return $it->nopenerimaan;
        })->toArray();
        $nopeMutPenUni = array_unique($nopeMutPen);
        $kdobatMutPen = collect($resPenSource)->map(function ($it) {
            return $it->kdobat;
        })->toArray();
        $kdobatMutPenUni = array_unique($kdobatMutPen);
        $mutasiPenToTr = Resepkeluarrinci::whereIn('nopenerimaan', $nopeMutPenUni)->whereIn('kdobat', $kdobatMutPenUni)->where('jumlah', '>', 0)->get();
        $penernya = PenerimaanRinci::select('nopenerimaan', 'kdobat', 'no_batch', 'tgl_exp', 'harga_netto_kecil')->with('header:nopenerimaan,tglpenerimaan')->whereIn('nopenerimaan', $nopeMutPenUni)->whereIn('kdobat', $kdobatMutPenUni)->get();
        foreach ($mutasiPenToTr as $mut) {
            $trm = collect($penernya)->where('nopenerimaan', $mut->nopenerimaan)->where('kdobat', $mut->kdobat)->first();
            if ($trm) {
                if ($mut->harga_beli != $trm->harga_netto_kecil) $mut->update(['harga_beli' => $trm->harga_netto_kecil]);
            }

            // return [
            //     'mut' => $mut,
            //     'trm' => $trm,
            // ];
            $data[] = [
                'mut' => $mut,
                'trm' => $trm,
            ];
        }


        $resStokSource = Resepkeluarrinci::select(
            'resep_keluar_r.nopenerimaan',
            'resep_keluar_r.kdobat',
            'stokopname.harga'
        )->leftJoin('stokopname', function ($jo) {
            $jo->on('stokopname.nopenerimaan', '=', 'resep_keluar_r.nopenerimaan')
                ->on('stokopname.kdobat', '=', 'resep_keluar_r.kdobat')
                ->on('stokopname.harga', '=', 'resep_keluar_r.harga_beli');
        })
            ->where('resep_keluar_r.nopenerimaan', 'LIKE', '%awal%')
            ->where('resep_keluar_r.jumlah', '>', 0)
            ->whereNull('stokopname.harga')
            // ->limit(10) /////////////////////////// ini nanti di coment
            ->get();

        $nopeMutSt = collect($resStokSource)->map(function ($it) {
            return $it->nopenerimaan;
        })->toArray();
        $nopeMutStUni = array_unique($nopeMutSt);
        $kdobatMutSt = collect($resStokSource)->map(function ($it) {
            return $it->kdobat;
        })->toArray();
        $kdobatMutStUni = array_unique($kdobatMutSt);
        $mutasiStToTr = Resepkeluarrinci::whereIn('nopenerimaan', $nopeMutStUni)->whereIn('kdobat', $kdobatMutStUni)->where('jumlah', '>', 0)->get();
        $saldonya = StokStokopname::whereIn('nopenerimaan', $nopeMutStUni)->whereIn('kdobat', $kdobatMutStUni)->get();
        foreach ($mutasiStToTr as $mut) {
            $trm = collect($saldonya)->where('nopenerimaan', $mut->nopenerimaan)->where('kdobat', $mut->kdobat)->first();
            if ($trm) {
                if ($mut->harga_beli != $trm->harga) $mut->update(['harga_beli' => $trm->harga]);
            }
            // return  [
            //     'mut' => $mut,
            //     'trm' => $trm,
            // ];
            $data[] =  [
                'mut' => $mut,
                'trm' => $trm,
            ];
        }

        return $data;
    }
    public static function perbaikanHargaDiMutasi()
    {

        $data = [];
        // penerimaan
        $mutasiPen = Mutasigudangkedepo::select(
            'mutasi_gudangdepo.nopenerimaan',
            'mutasi_gudangdepo.kd_obat',
            'penerimaan_r.harga_netto_kecil'
        )
            ->leftJoin('penerimaan_r', function ($jo) {
                $jo->on('penerimaan_r.nopenerimaan', '=', 'mutasi_gudangdepo.nopenerimaan')
                    ->on('penerimaan_r.kdobat', '=', 'mutasi_gudangdepo.kd_obat')
                    ->on('penerimaan_r.harga_netto_kecil', '=', 'mutasi_gudangdepo.harga');
            })
            ->where('mutasi_gudangdepo.nopenerimaan', 'NOT LIKE', '%awal%')
            ->whereNull('penerimaan_r.harga_netto_kecil')
            ->get();

        $nopeMutPen = collect($mutasiPen)->map(function ($it) {
            return $it->nopenerimaan;
        })->toArray();
        $nopeMutPenUni = array_unique($nopeMutPen);
        $kdobatMutPen = collect($mutasiPen)->map(function ($it) {
            return $it->kd_obat;
        })->toArray();
        $kdobatMutPenUni = array_unique($kdobatMutPen);
        $mutasiPenToTr = Mutasigudangkedepo::whereIn('nopenerimaan', $nopeMutPenUni)->whereIn('kd_obat', $kdobatMutPenUni)->get();
        $penernya = PenerimaanRinci::select('nopenerimaan', 'kdobat', 'no_batch', 'tgl_exp', 'harga_netto_kecil')->with('header:nopenerimaan,tglpenerimaan')->whereIn('nopenerimaan', $nopeMutPenUni)->whereIn('kdobat', $kdobatMutPenUni)->get();
        foreach ($mutasiPenToTr as $mut) {
            $trm = collect($penernya)->where('nopenerimaan', $mut->nopenerimaan)->where('kdobat', $mut->kd_obat)->first();
            if ($trm) {
                if ($mut->harga != $trm->harga_netto_kecil) $mut->update(['harga' => $trm->harga_netto_kecil]);
                if ($mut->tglpenerimaan != $trm->header->tglpenerimaan) $mut->update(['tglpenerimaan' => $trm->header->tglpenerimaan]);
                if ($mut->tglexp != $trm->tgl_exp) $mut->update(['tglexp' => $trm->tgl_exp]);
                if ($mut->nobatch != $trm->no_batch) $mut->update(['nobatch' => $trm->no_batch]);
            }

            $data[] = [
                'mut' => $mut,
                'trm' => $trm,
            ];
        }

        // awal
        $mutasiSt = Mutasigudangkedepo::select(
            'mutasi_gudangdepo.nopenerimaan',
            'mutasi_gudangdepo.kd_obat',
            'stokopname.harga'
        )
            ->leftJoin('stokopname', function ($jo) {
                $jo->on('stokopname.nopenerimaan', '=', 'mutasi_gudangdepo.nopenerimaan')
                    ->on('stokopname.kdobat', '=', 'mutasi_gudangdepo.kd_obat')
                    ->on('stokopname.harga', '=', 'mutasi_gudangdepo.harga');
            })
            ->where('mutasi_gudangdepo.nopenerimaan', 'LIKE', '%awal%')
            ->whereNull('stokopname.harga')
            ->get();
        $nopeMutSt = collect($mutasiSt)->map(function ($it) {
            return $it->nopenerimaan;
        })->toArray();
        $nopeMutStUni = array_unique($nopeMutSt);
        $kdobatMutSt = collect($mutasiSt)->map(function ($it) {
            return $it->kd_obat;
        })->toArray();
        $kdobatMutStUni = array_unique($kdobatMutSt);
        $mutasiStToTr = Mutasigudangkedepo::whereIn('nopenerimaan', $nopeMutStUni)->whereIn('kd_obat', $kdobatMutStUni)->get();
        $saldonya = StokStokopname::whereIn('nopenerimaan', $nopeMutStUni)->whereIn('kdobat', $kdobatMutStUni)->get();
        foreach ($mutasiStToTr as $mut) {
            $trm = collect($saldonya)->where('nopenerimaan', $mut->nopenerimaan)->where('kdobat', $mut->kd_obat)->first();
            if ($trm) {
                if ($mut->harga != $trm->harga) $mut->update(['harga' => $trm->harga]);
                if ($mut->tglpenerimaan != $trm->tglpenerimaan) $mut->update(['tglpenerimaan' => $trm->tglpenerimaan]);
                if ($mut->tglexp != $trm->tglexp) $mut->update(['tglexp' => $trm->tglexp]);
                if ($mut->nobatch != $trm->nobatch) $mut->update(['nobatch' => $trm->nobatch]);
            }
            $data[] =  [
                'mut' => $mut,
                'trm' => $trm,
            ];
        }
        return $data;
    }
}
