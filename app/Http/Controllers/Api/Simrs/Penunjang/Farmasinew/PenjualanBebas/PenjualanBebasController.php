<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\PenjualanBebas;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Master\Mpasien;
use App\Models\Simrs\Master\Mpihakketiga;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarrinci;
use App\Models\Simrs\Penunjang\Farmasinew\Harga\DaftarHarga;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Penjualan\KunjunganPenjualan;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokrel;
use App\Models\Simrs\Penunjang\Farmasinew\Stokreal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PenjualanBebasController extends Controller
{
    public function listKunjungan()
    {
        $list = KunjunganPenjualan::select('kunjungan_penjualans.*')
            ->where('nama', 'LIKE', '%' . request('q') . '%')
            ->with([
                // 'rincian:noreg,noresep,kdobat,aturan,harga_jual,jumlah',
                // 'rincian.mobat:kd_obat,nama_obat,satuan_k',
                // 'rincian.heder:noresep,flag_pembayaran,nama_pejabat'
                'rincian' => function ($ri) {
                    $ri->select(
                        'resep_keluar_r.noreg',
                        'resep_keluar_r.noresep',
                        'resep_keluar_r.kdobat',
                        'resep_keluar_r.aturan',
                        'resep_keluar_r.harga_jual',
                        'resep_keluar_r.jumlah',
                        'resep_keluar_h.flag_pembayaran',
                        'resep_keluar_h.nama_pejabat'
                    )
                        ->with(['mobat:kd_obat,nama_obat,satuan_k'])
                        ->leftJoin('resep_keluar_h', 'resep_keluar_h.noresep', '=', 'resep_keluar_r.noresep');
                }
            ])
            ->leftJoin('resep_keluar_h', 'resep_keluar_h.noreg', '=', 'kunjungan_penjualans.noreg')
            ->whereBetween('kunjungan_penjualans.tgl_kunjungan', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
            ->where('depo', request('kdruang'))
            ->groupBy('kunjungan_penjualans.tgl_kunjungan', 'kunjungan_penjualans.kode_identitas')
            ->paginate(request('per_page'));

        $data = [
            'data' => collect($list)['data'],
            'meta' => collect($list)->except('data'),
            'req' => request()->all()
        ];
        return new JsonResponse($data);
    }
    public function getPasien()
    {
        $data = Mpasien::select(
            'rs1 as norm',
            'rs2 as nama',
            'rs49 as nik',
        )
            ->where(function ($q) {
                $q->where('rs1', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs2', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs49', 'LIKE', '%' . request('q') . '%');
            })
            ->where('rs2', '!=', '')
            ->orderBy('rs2', 'ASC')
            ->limit(15)
            ->get();

        return new JsonResponse($data);
    }
    public function getDaftarKunjungan()
    {
        $data = KunjunganPenjualan::where('kode_identitas', 'NOT LIKE', 'PK%')
            ->where(function ($q) {
                $q->where('kode_identitas', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('nama', 'LIKE', '%' . request('q') . '%');
            })
            ->groupBy('kode_identitas')
            ->orderBy('nama', 'ASC')
            ->limit(15)
            ->get();

        return new JsonResponse($data);
    }
    public function getKaryawan()
    {
        $data = Petugas::select('nama', 'nik')
            ->where('nama', 'LIKE', '%' . request('q') . '%')
            ->where('aktif', 'AKTIF')
            ->where('nik', '!=', '')
            ->limit(30)
            ->get();

        return new JsonResponse($data);
    }
    public function getPihakTiga()
    {
        $data = Mpihakketiga::select('nama', 'kode')
            ->where('nama', 'LIKE', '%' . request('q') . '%')
            ->where('hidden', '')
            ->limit(30)
            ->get();
        return new JsonResponse($data);
    }
    public function pencarianObat()
    {
        $sistembayar = ['SEMUA', 'UMUM'];
        $limitHargaTertinggi = 5;

        $listobat = Mobatnew::query()
            ->select(
                'new_masterobat.kd_obat',
                'new_masterobat.nama_obat as namaobat',
                'new_masterobat.kandungan as kandungan',
                // 'new_masterobat.bentuk_sediaan as bentuk_sediaan',
                'new_masterobat.satuan_k as satuankecil',
                // 'new_masterobat.status_fornas as fornas',
                // 'new_masterobat.status_forkid as forkit',
                // 'new_masterobat.status_generik as generik',
                // 'new_masterobat.status_kronis as kronis',
                // 'new_masterobat.status_prb as prb',
                // 'new_masterobat.kode108',
                // 'new_masterobat.uraian108',
                // 'new_masterobat.kode50',
                // 'new_masterobat.uraian50',
                // 'new_masterobat.kekuatan_dosis as kekuatandosis',
                // 'new_masterobat.volumesediaan as volumesediaan',
                // 'new_masterobat.kelompok_psikotropika as psikotropika',
                'new_masterobat.obat_program',
                'new_masterobat.satuan_k',
                'stokreal.kdobat as kdobat',
                'stokreal.jumlah as jumlah',
                DB::raw('SUM(
                    CASE When stokreal.kdruang="' . request('kdruang') . '" AND stokreal.kdobat = new_masterobat.kd_obat Then stokreal.jumlah Else 0 End )
                     as total'),
            )
            ->leftjoin('stokreal', 'new_masterobat.kd_obat', '=', 'stokreal.kdobat')
            ->where('new_masterobat.status_konsinyasi', '')
            ->where('new_masterobat.obat_program', '')
            ->whereIn('new_masterobat.sistembayar', $sistembayar)

            ->where(function ($query) {
                $query->where('new_masterobat.nama_obat', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('new_masterobat.kandungan', 'LIKE', '%' . request('q') . '%');
            })
            ->with('permintaandeporinci', function ($q) {
                $q->select(
                    'permintaan_r.no_permintaan',
                    'permintaan_r.kdobat',
                    'permintaan_h.tujuan as kdruang',
                    DB::raw('sum(permintaan_r.jumlah_minta) as jumlah_minta')
                )
                    ->leftJoin('permintaan_h', 'permintaan_h.no_permintaan', '=', 'permintaan_r.no_permintaan')
                    ->leftJoin('mutasi_gudangdepo', function ($anu) {
                        $anu->on('permintaan_r.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                            ->on('permintaan_r.kdobat', '=', 'mutasi_gudangdepo.kd_obat');
                    })
                    ->where('permintaan_h.tujuan', request('kdruang'))
                    ->whereIn('permintaan_h.flag', ['', '1', '2'])
                    ->groupBy('permintaan_r.kdobat');
            })

            ->with('transracikan', function ($q) {
                $q->select(
                    'resep_permintaan_keluar_racikan.kdobat as kdobat',
                    'resep_keluar_h.depo as kdruang',
                    DB::raw('sum(resep_permintaan_keluar_racikan.jumlah) as jumlah')
                )
                    ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', '=', 'resep_permintaan_keluar_racikan.noresep')
                    ->where('resep_keluar_h.depo', request('kdruang'))
                    ->whereIn('resep_keluar_h.flag', ['', '1', '2']);
            })

            ->with('transnonracikan', function ($q) {
                $q->select(
                    'resep_permintaan_keluar.kdobat as kdobat',
                    'resep_keluar_h.depo as kdruang',
                    DB::raw('sum(resep_permintaan_keluar.jumlah) as jumlah')
                )
                    ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_permintaan_keluar.noresep')
                    ->where('resep_keluar_h.depo', request('kdruang'))
                    ->whereIn('resep_keluar_h.flag', ['', '1', '2'])
                    ->groupBy('resep_permintaan_keluar.kdobat');
            })
            ->addSelect([
                'harga_tertinggi_ids' => DaftarHarga::query()
                    ->selectRaw("SUBSTRING_INDEX(GROUP_CONCAT(daftar_hargas.id order by tgl_mulai_berlaku desc, ','), ',', {$limitHargaTertinggi})")
                    ->whereColumn('daftar_hargas.kd_obat', '=', 'stokreal.kdobat')
                    ->limit($limitHargaTertinggi)
            ])
            ->addSelect([
                'harga_beli' => DaftarHarga::query()
                    ->selectRaw("MAX(harga)")
                    ->whereColumn('daftar_hargas.kd_obat', '=', 'stokreal.kdobat')
                    ->orderBy('tgl_mulai_berlaku', 'desc')
                    ->limit($limitHargaTertinggi)
            ])


            ->groupBy('new_masterobat.kd_obat')
            ->orderBy('total', 'DESC')
            ->limit(20)
            ->get();


        $hrgTertinggiIds = $listobat->pluck('harga_tertinggi_ids')
            ->map(function ($daftarHargaKodes) {
                return $daftarHargaKodes !== null ? explode(',', $daftarHargaKodes) : [];
            })
            ->flatten();


        $hargaTertinggi = DaftarHarga::select('id', 'kd_obat', 'tgl_mulai_berlaku', 'harga')
            ->whereIn('id', $hrgTertinggiIds)
            ->orderBy('tgl_mulai_berlaku', 'desc')
            ->get();
        // return new JsonResponse(
        //     [
        //         'dataobat' => $hargaTertinggi
        //     ]
        // );
        // $ht = collect($hargaTertinggi);

        foreach ($listobat as $stok) {
            // menjadikan array dari string $stok->harga_tertinggi_ids
            $ids = explode(',', $stok->harga_tertinggi_ids);

            $stokHargaTertinggi = $hargaTertinggi
                ->whereIn('id', $ids)
                ->sortBy(fn(DaftarHarga $daftarHarga) => array_flip($ids)[$daftarHarga->id])
                ->values();

            // masukkan ke object harga_teringgi_kodes
            $stok->setRelation('harga_tertinggi_ids', $stokHargaTertinggi)->toArray();
        }

        $wew = collect($listobat)->map(function ($x, $y) {
            $total = $x->total ?? 0;
            $jumlahtrans = $x['transnonracikan'][0]->jumlah ?? 0;
            $jumlahtransx = $x['transracikan'][0]->jumlah ?? 0;
            $permintaanobatrinci = $x['permintaandeporinci'][0]->jumlah_minta ?? 0; // mutasi antar depo
            $x->alokasi = (float) $total - (float)$jumlahtrans - (float)$jumlahtransx - (float)$permintaanobatrinci;
            return $x;
        });

        return new JsonResponse(
            [
                'dataobat' => $wew
            ]
        );
    }

    public static function setNomor($n, $kode)
    {
        $has = null;
        $lbr = strlen($n);
        for ($i = 1; $i <= 6 - $lbr; $i++) {
            $has = $has . "0";
        }
        return $has . $n . "/" . date("d") . "/" . date("m") . "/" . date("Y") . "/" . $kode;
    }
    public function  simpan(Request $request)
    {

        $jumRi = sizeof($request->details);
        if ($jumRi <= 0) {
            return new JsonResponse([
                'message' => 'Tidak Ada Rincian Obat Untuk Disimpan'
            ], 410);
        }
        // cek alokasi

        $cekObat = self::setAlokasi($request);
        $obatDiminta = [];
        foreach ($request->details as $key) {
            $data = collect($cekObat)->firstWhere('kdobat', $key['kodeobat']);
            $key['isError'] = $data['isError'];
            $key['errors'] = $data['errors'];
            $key['harga'] = $data['harga'];
            $key['hargajual'] = $data['hargajual'];
            $key['jumlah_diminta'] = $data['jumlah_diminta'];
            $key['item'] = $data['data'];
            $key['alokasi'] = $data['data']['alokasi'];
            $key['sistembayar'] = 'UMUM';
            // $key['signa'] = $key['signa'];
            // $key['keterangan'] = $key['keterangan'];
            $obatDiminta[] = $key;
        }

        $a = count($obatDiminta) > 0 ? collect($obatDiminta)->pluck('isError')->toArray() : [];
        $msg = 'ok';
        $isError = false;
        if (in_array(true, $a)) {
            $isError = true;
            $msg = 'Gagal Alokasi Kurang';
        } else {
            $isError = false;
        }

        //  test start
        // $data = [
        //     'message' => $msg,
        //     'isError' => '$isError',
        //     'items' => $obatDiminta,
        //     'req' => $request->all(),
        //     // 'cekobat'=> $cekObat
        // ];

        // return new JsonResponse(
        //     $data,
        //     410
        // );
        // end test
        // JIKA ADA YG ERROR
        if ($isError) {
            $data = [
                'message' => $msg,
                'isError' => $isError,
                'items' => $obatDiminta,
                // 'cekobat'=> $cekObat
            ];

            return new JsonResponse(
                $data,
                $isError ? 410 : 200
            );
        } else {
            $user = auth()->user()->pegawai_id;
            $request->request->add(['pegawai_id' => $user]);
            $request->request->add(['orders' => $obatDiminta]);


            // return new JsonResponse($nomor);

            // simpan kunjungan dimana? satu hari satu kunjungan?

            return self::sendOrder($request, $obatDiminta);
        }
        return new JsonResponse([
            'obatdiminta' => $obatDiminta,
            'obat' => $cekObat,
            'req' => $request->all()
        ]);
    }
    public static function cekAlokasi($request)
    {

        $limitHargaTertinggi = 5;

        $listobat = Mobatnew::query()
            ->select(
                'new_masterobat.kd_obat',
                'new_masterobat.kandungan',
                'new_masterobat.status_fornas as fornas',
                'new_masterobat.status_forkid as forkit',
                'new_masterobat.status_generik as generik',
                'new_masterobat.kode108',
                'new_masterobat.uraian108',
                'new_masterobat.kode50',
                'new_masterobat.uraian50',
                'stokreal.kdobat as kdobat',
                'stokreal.jumlah as jumlah',
                DB::raw('SUM(
                    CASE When stokreal.kdruang="' . $request->depo . '" AND stokreal.kdobat = new_masterobat.kd_obat Then stokreal.jumlah Else 0 End )
                     as total'),
            )
            ->leftjoin('stokreal', 'new_masterobat.kd_obat', '=', 'stokreal.kdobat')
            ->where('new_masterobat.status_konsinyasi', '')
            ->whereIn('new_masterobat.kd_obat', $request->kode)

            ->with('permintaandeporinci', function ($q) use ($request) {
                $q->select(
                    'permintaan_r.no_permintaan',
                    'permintaan_r.kdobat',
                    'permintaan_h.tujuan as kdruang',
                    DB::raw('sum(permintaan_r.jumlah_minta) as jumlah_minta')
                )
                    ->leftJoin('permintaan_h', 'permintaan_h.no_permintaan', '=', 'permintaan_r.no_permintaan')
                    ->leftJoin('mutasi_gudangdepo', function ($anu) {
                        $anu->on('permintaan_r.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                            ->on('permintaan_r.kdobat', '=', 'mutasi_gudangdepo.kd_obat');
                    })
                    ->where('permintaan_h.tujuan', $request->depo)
                    ->whereIn('permintaan_h.flag', ['', '1', '2'])
                    ->groupBy('permintaan_r.kdobat');
            })

            ->with('transracikan', function ($q) use ($request) {
                $q->select(
                    'resep_permintaan_keluar_racikan.kdobat as kdobat',
                    'resep_keluar_h.depo as kdruang',
                    DB::raw('sum(resep_permintaan_keluar_racikan.jumlah) as jumlah')
                )
                    ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', '=', 'resep_permintaan_keluar_racikan.noresep')
                    ->where('resep_keluar_h.depo', $request->depo)
                    ->whereIn('resep_keluar_h.flag', ['', '1', '2']);
            })

            ->with('transnonracikan', function ($q) use ($request) {
                $q->select(
                    'resep_permintaan_keluar.kdobat as kdobat',
                    'resep_keluar_h.depo as kdruang',
                    DB::raw('sum(resep_permintaan_keluar.jumlah) as jumlah')
                )
                    ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_permintaan_keluar.noresep')
                    ->where('resep_keluar_h.depo', $request->depo)
                    ->whereIn('resep_keluar_h.flag', ['', '1', '2'])
                    ->groupBy('resep_permintaan_keluar.kdobat');
            })
            ->addSelect([
                'harga_tertinggi_ids' => DaftarHarga::query()
                    ->selectRaw("SUBSTRING_INDEX(GROUP_CONCAT(daftar_hargas.id order by tgl_mulai_berlaku desc, ','), ',', {$limitHargaTertinggi})")
                    ->whereColumn('daftar_hargas.kd_obat', '=', 'stokreal.kdobat')
                    ->limit($limitHargaTertinggi)
            ])
            ->addSelect([
                'harga_tertinggi' => DaftarHarga::query()
                    ->selectRaw("MAX(harga)")
                    ->whereColumn('daftar_hargas.kd_obat', '=', 'stokreal.kdobat')
                    ->orderBy('tgl_mulai_berlaku', 'desc')
                    ->limit($limitHargaTertinggi)
            ])


            ->groupBy('new_masterobat.kd_obat')
            ->get();

        $hrgTertinggiIds = $listobat->pluck('harga_tertinggi_ids')
            ->map(function (string $daftarHargaKodes) {
                return explode(',', $daftarHargaKodes);
            })
            ->flatten();

        $hargaTertinggi = DaftarHarga::select('id', 'kd_obat', 'tgl_mulai_berlaku', 'harga')
            ->whereIn('id', $hrgTertinggiIds)
            ->orderBy('tgl_mulai_berlaku', 'desc')
            ->get();

        // $ht = collect($hargaTertinggi);

        foreach ($listobat as $stok) {
            // menjadikan array dari string $stok->harga_tertinggi_ids
            $ids = explode(',', $stok->harga_tertinggi_ids);

            $stokHargaTertinggi = $hargaTertinggi
                ->whereIn('id', $ids)
                ->sortBy(fn(DaftarHarga $daftarHarga) => array_flip($ids)[$daftarHarga->id])
                ->values();

            // masukkan ke object harga_teringgi_kodes
            $stok->setRelation('harga_tertinggi_ids', $stokHargaTertinggi)->toArray();
        }
        $wew = collect($listobat)->map(function ($x, $y) {
            $total = $x->total ?? 0;
            $jumlahtrans = $x['transnonracikan'][0]->jumlah ?? 0;
            $jumlahtransx = $x['transracikan'][0]->jumlah ?? 0;
            $permintaanobatrinci = $x['permintaandeporinci'][0]->jumlah_minta ?? 0; // mutasi antar depo
            $x->alokasi = (float) $total - (float)$jumlahtrans - (float)$jumlahtransx - (float)$permintaanobatrinci;
            return $x;
        });
        return $wew->toArray();
    }
    public static function setAlokasi($request)
    {
        $adaRaw = self::cekAlokasi($request);
        $item = collect($request->details);
        return self::outputRaw($adaRaw, $item, $request);
    }
    public static function outputRaw($adaraw, $item, $request)
    {
        $diminta = [];
        foreach ($item as $key) {
            $data = collect($adaraw)->firstWhere('kdobat', $key['kodeobat']);
            $diminta[] = self::mappingObat($data, $key, (float)$request->margin);
        }
        return $diminta;
    }
    public static function mappingObat($data, $key, $margin)
    {
        $isError = $data ? false : true;
        $obat['isError'] = $isError;
        $obat['errors'] = $isError ? 'Stok Obat Tidak Tersedia' : null;
        $obat['kdobat'] = $key['kodeobat'];
        $obat['margin'] = $margin;
        $obat['data'] = $data;
        $obat['key'] = $key;

        $alokasi = $isError ? false : (int)$data['alokasi'];
        $jumlahDiminta = $isError ? false : (int)$key['jumlah'];
        $jumlahstok = $isError ? false : $data['total'];
        $obat['jumlahstok'] = $jumlahstok;
        $obat['alokasi'] = $alokasi;
        $obat['jumlah_diminta'] = $jumlahDiminta;

        if ($alokasi) {
            if ($jumlahDiminta > $alokasi) {
                $obat['isError'] = true;
                $obat['errors'] = 'Jumlah diminta melebihi Alokasi yang tersedia';
            }
        }
        $harga = $isError ? false : $data['harga_tertinggi'];
        $hargajual = $isError ? false : ((float)$harga * ((float)$margin) / 100) + (float)$harga;
        $obat['hargajual'] = $hargajual;
        if ($hargajual === 0 && $data['obat_program'] !== '1') {
            $obat['isError'] = true;
            $obat['errors'] = 'Obat ini tidak mempunyai harga';
        }
        if ($hargajual === false) {
            $obat['isError'] = true;
            $obat['errors'] = 'Obat ini tidak mempunyai harga';
        }
        $obat['harga'] = $harga;
        return $obat;
    }
    public static function sendOrder($request, $obatyangsudahdicek)
    {
        // return new JsonResponse([
        //     'req' => $request->all(),
        //     'obat' => $obatyangsudahdicek,
        //     'noreg' => $noreg,
        // ], 410);
        // mulai insert
        //  $obatdiminta = $request->items;
        //  return [
        //   'obatdiminta' => $obatdiminta,
        //   'obatyangsudahdicek' => $obatyangsudahdicek];

        // $adaAlokasiRacikan = array_filter($obatyangsudahdicek, function($obat) {
        //   return $obat['racikan'] !== false;
        // });

        // return $adaAlokasiRacikan;
        $request->validate([
            'kode_identitas' => 'required',
            'nama' => 'required',
        ]);

        try {
            DB::connection('farmasi')->beginTransaction();

            // ini  nanti ngisi di resep_keluar_h dan resep_keluar_r
            // bikin baru tabel registrasi penjualan bebas
            // tiap obat harus cek alokasi, jadi bikin seperti template resep untuk keluarnya.
            // noresp
            $tgl = date('Y-m-d');
            $adaKunjungan = KunjunganPenjualan::where('kode_identitas', $request->kode_identitas)
                ->where('tgl_kunjungan', 'LIKE', '%' . $tgl . '%')
                ->orderBy('tgl_kunjungan', 'DESC')
                ->first();
            if (!$adaKunjungan) {
                DB::connection('farmasi')->select('call registrasipenjumum(@nomor)');
                $x = DB::connection('farmasi')->table('conter')->select('regbebas')->first();
                $nom = $x->regbebas;
                $noreg = self::setNomor($nom, 'R-PJB');
                KunjunganPenjualan::create([
                    'noreg' => $noreg,
                    'kode_identitas' => $request->kode_identitas,
                    'nama' => $request->nama,
                    'keterangan' => $request->keterangan,
                    'tgl_kunjungan' => date('Y-m-d H:i:s'),
                ]);
            } else {
                $noreg = $adaKunjungan->noreg;
            }

            if ($request->depo === 'Gd-04010102') {
                $lebel = 'PD-RI';
            } elseif ($request->depo === 'Gd-04010103') {
                $lebel = 'PD-KO';
            } elseif ($request->depo === 'Gd-05010101') {
                $lebel = 'PD-RJ';
            } else if ($request->depo === 'Gd-02010104') {
                $lebel = 'PD-IR';
            } else {
                $lebel = 'D-UM';
            }

            DB::connection('farmasi')->select('call reseppenjumum(@nomor)');
            $x = DB::connection('farmasi')->table('conter')->select('respbebas')->first();
            $nom = $x->respbebas;
            $noresep = self::setNomor($nom, $lebel);
            // DB::connection('farmasi')->select('call ' . $procedure);
            // $x = DB::connection('farmasi')->table('conter')->select($colom)->get();
            // $wew = $x[0]->$colom;
            // $noresep = FormatingHelper::resep($wew, $lebel);


            $created = date('Y-m-d H:i:s');
            $user = FormatingHelper::session_user();

            $simpan = Resepkeluarheder::updateOrCreate(
                [
                    'noresep' => $noresep,
                    'noreg' => $noreg,
                ],
                [
                    'norm' => $request->kode_identitas,
                    'tgl_permintaan' => date('Y-m-d H:i:s'),
                    'tgl_kirim' => date('Y-m-d H:i:s'),
                    'tgl_selesai' => date('Y-m-d H:i:s'),
                    'tgl' => date('Y-m-d'),
                    'depo' => $request->depo,
                    'ruangan' => $request->depo,
                    'nama_pejabat' => $request->nama_pejabat,
                    // 'dokter' =>  $user['kodesimrs'],
                    'sistembayar' => 'UMUM',

                    // 'diagnosa' => $request->diagnosa ?? '',
                    // 'kodeincbg' => $request->kodeincbg ?? '',
                    // 'uraianinacbg' => $request->uraianinacbg ?? '',
                    // 'tarifina' => $request->tarifina ?? '',
                    'tiperesep' => 'penjualan',
                    'flag' => '3',
                    'flag_dari' => '5',
                    'user' => $user['kodesimrs'],
                    // 'iter_expired' => $iter_expired,
                    // 'iter_jml' => $iter_jml,
                    // 'iter_expired' => $request->iter_expired ?? '',
                    'tagihanrs' => $request->tagihanrs ?? 0,
                ]
            );

            if (!$simpan) {
                return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!'], 500);
            }

            // ini ranah detail
            $racikandtd = [];
            $racikannondtd = [];
            $rinciaja = [];
            $idnya = [];
            $stokToUp = [];

            $adaAlokasi = $obatyangsudahdicek;
            $stok = Stokreal::lockForUpdate()
                ->whereIn('kdobat', $request->kode)
                ->where('jumlah', '>', 0)
                ->where('kdruang', $request->depo)
                ->orderBy('tglpenerimaan', 'ASC')
                ->get();
            $collectStok = collect($stok);

            // jika ada racikan
            // $adaAlokasi = array_filter($obatyangsudahdicek, function ($obat) {
            //     return $obat['racikan'] === false;
            // });

            // return $adaAlokasi;

            // $adaAlokasiRacikan = array_filter($obatyangsudahdicek, function ($obat) {
            //     return $obat['racikan'] !== false;
            // });

            // return $adaAlokasiRacikan;


            if (count($adaAlokasi) > 0) {
                foreach ($adaAlokasi as $non) {
                    $diminta = (float) $non['jumlah_diminta'];
                    // cari stok nya berdasarkan kode
                    // rinci
                    while ($diminta > 0) {
                        $stokNya = $collectStok->where('kdobat', $non['kodeobat'])
                            ->whereNotIn('id',  $idnya)
                            ->where('jumlah', '>', 0)
                            ->first();
                        $sisa = (float) $stokNya->jumlah;
                        // return new JsonResponse([
                        //     'req' => $request->all(),
                        //     'obat' => $obatyangsudahdicek,
                        //     'noreg' => $noreg,
                        //     'rinciaja' => $rinciaja,
                        //     'stok' => $stok,
                        //     'stokNya' => $stokNya,
                        //     'sisa' => $sisa,
                        // ], 410);
                        if ($sisa < $diminta) {
                            $sisax =  $diminta - $sisa;
                            $simpanrinci =
                                [
                                    'noreg' => $noreg,
                                    'noresep' => $noresep,
                                    'kdobat' => $non['kodeobat'],
                                    'kandungan' => $non['item']['kandungan'] ?? '',
                                    'fornas' => $non['item']['fornas'] ?? '',
                                    'forkit' => $non['item']['forkit'] ?? '',
                                    'generik' => $non['item']['generik'] ?? '',
                                    'kode108' => $non['item']['kode108'],
                                    'uraian108' => $non['item']['uraian108'],
                                    'kode50' => $non['item']['kode50'],
                                    'uraian50' => $non['item']['uraian50'],
                                    // 'stokalokasi' => $non['item']['alokasi'],
                                    'nilai_r' => 0,
                                    'jumlah' => $sisa,
                                    'nopenerimaan' => $stokNya->nopenerimaan,
                                    'hpp' => $non['harga'],
                                    'harga_jual' => $non['harga_jual'],
                                    'harga_beli' => $non['harga_beli'],
                                    'aturan' => $non['aturan'],
                                    'konsumsi' => $non['konsumsi'],
                                    'keterangan' => $non['keterangan'] ?? '',
                                    'user' => $user['kodesimrs'],
                                    'created_at' => $created,
                                    'updated_at' => date('Y-m-d H:i:s'),
                                ];
                            $rinciaja[] = $simpanrinci;
                            $idnya[] = $stokNya->id;
                            $stokToUp[] = [
                                'jumlah' => 0,
                                'id' => $stokNya->id,
                                'sisa' => $sisa,
                                'sisax' => $sisax,
                                'diminta' => $diminta,
                            ];
                            $stokNya->update(['jumlah' => 0]);
                            $diminta = $sisax;
                        } else {
                            $sisax =  $sisa - $diminta;
                            $simpanrinci =
                                [
                                    'noreg' => $noreg,
                                    'noresep' => $noresep,
                                    'kdobat' => $non['kodeobat'],
                                    'kandungan' => $non['item']['kandungan'] ?? '',
                                    'fornas' => $non['item']['fornas'] ?? '',
                                    'forkit' => $non['item']['forkit'] ?? '',
                                    'generik' => $non['item']['generik'] ?? '',
                                    'kode108' => $non['item']['kode108'],
                                    'uraian108' => $non['item']['uraian108'],
                                    'kode50' => $non['item']['kode50'],
                                    'uraian50' => $non['item']['uraian50'],
                                    // 'stokalokasi' => $non['item']['alokasi'],
                                    'nilai_r' => 0,
                                    'jumlah' => $diminta,
                                    'nopenerimaan' => $stokNya->nopenerimaan,
                                    'hpp' => $non['harga'],
                                    'harga_jual' => $non['harga_jual'],
                                    'harga_beli' => $non['harga_beli'],
                                    'aturan' => $non['aturan'],
                                    'konsumsi' => $non['konsumsi'],
                                    'keterangan' => $non['keterangan'] ?? '',
                                    'user' => $user['kodesimrs'],
                                    'created_at' => $created,
                                    'updated_at' => date('Y-m-d H:i:s'),
                                ];
                            $rinciaja[] = $simpanrinci;

                            $idnya[] = $stokNya->id;
                            $stokToUp[] = [
                                'jumlah' => $sisax,
                                'id' => $stokNya->id,
                                'sisa' => $sisa,
                                'sisax' => $sisax,
                                'diminta' => $diminta,
                            ];
                            $stokNya->update(['jumlah' => $sisax]);
                            // $stokNya->update(['jumlah' => $sisax]);
                            $diminta = 0;
                        }
                    }
                }
            }



            // keluarkan by fifo
            // return new JsonResponse([
            //     'req' => $request->all(),
            //     'obat' => $obatyangsudahdicek,
            //     'noreg' => $noreg,
            //     'rinciaja' => $rinciaja,
            //     'stok' => $stok,
            //     'idnya' => $idnya,
            //     'stokToUp' => $stokToUp,
            //     'collectStok' => $collectStok,
            // ], 410);

            // racikan
            // if (count($adaAlokasiRacikan) > 0) {
            //   foreach ($adaAlokasiRacikan as $rac) {
            //     // $har = HargaHelper::getHarga($request->kodeobat, $request->groupsistembayar);
            //     // $res = $har['res'];
            //     // if ($res) {
            //     //     $hargajualx = $rac['hargajual'];
            //     //     $harga = $rac['hpp'];
            //     // } else {
            //     //     $hargajualx = $har['hargaJual'];
            //     //     $harga = $har['harga'];
            //     // }

            //     if ($rac['tiperacikan'] == 'DTD') {
            //         $simpandtd =
            //             [
            //                 'noreg' => $request->noreg,
            //                 // 'noresep' => $noresep,
            //                 'namaracikan' => $rac['namaracikan'],
            //                 'tiperacikan' => $rac['tiperacikan'],
            //                 'jumlahdibutuhkan' => $rac['jumlah_diminta'], // jumlah racikan
            //                 'aturan' => $rac['signa'],
            //                 'konsumsi' => $rac['konsumsi'] ?? 1,
            //                 'keterangan' => $rac['keterangan'],
            //                 'kdobat' => $rac['kodeobat'],
            //                 'kandungan' => $rac['item']['kandungan'] ?? '',
            //                 'fornas' => $rac['item']['fornas'] ?? '',
            //                 'forkit' => $rac['item']['forkit'] ?? '',
            //                 'generik' => $rac['item']['generik'] ?? '',
            //                 'r' => $request->groupsistembayar === '1' || $request->groupsistembayar === 1 ? 500 : 0,
            //                 'hpp' => $rac['harga'],
            //                 'harga_jual' => $rac['hargajual'],
            //                 'kode108' => $rac['item']['kode108'],
            //                 'uraian108' => $rac['item']['uraian108'],
            //                 'kode50' => $rac['item']['kode50'],
            //                 'uraian50' => $rac['item']['uraian50'],
            //                 'stokalokasi' => $rac['item']['alokasi'],
            //                 'dosisobat' => $rac['dosis'] ?? 1,
            //                 'dosismaksimum' => $rac['dosismaksimum'] ?? 1, // dosis resep
            //                 'jumlah' => $rac['jumlah_diminta'], // jumlah obat
            //                 'satuan_racik' => $rac['kemasan'], // jumlah obat
            //                 'keteranganx' => $rac['item']['keterangan'] ?? '', // keterangan obat
            //                 'created_at' => $created,
            //                 'updated_at' => date('Y-m-d H:i:s'),
            //             ];
            //         $racikandtd[] = $simpandtd;
            //         // if ($simpandtd) {
            //         //     $simpandtd->load('mobat:kd_obat,nama_obat');
            //         // }
            //     } else {
            //         $simpannondtd =
            //             [
            //                 'noreg' => $request->noreg,
            //                 // 'noresep' => $noresep,
            //                 'namaracikan' => $rac['namaracikan'],
            //                 'tiperacikan' => $rac['tiperacikan'],
            //                 'jumlahdibutuhkan' => $rac['jumlah_diminta'], // jumlah racikan
            //                 'aturan' => $rac['signa'],
            //                 'konsumsi' => $rac['konsumsi'] ?? 1,
            //                 'keterangan' => $rac['keterangan'],
            //                 'kdobat' => $rac['kodeobat'],
            //                 'kandungan' => $rac['item']['kandungan'] ?? '',
            //                 'fornas' => $rac['item']['fornas'] ?? '',
            //                 'forkit' => $rac['item']['forkit'] ?? '',
            //                 'generik' => $rac['item']['generik'] ?? '',
            //                 'r' => $request->groupsistembayar === '1' || $request->groupsistembayar === 1 ? 500 : 0,
            //                 'hpp' => $rac['harga'],
            //                 'harga_jual' => $rac['hargajual'],
            //                 'kode108' => $rac['item']['kode108'],
            //                 'uraian108' => $rac['item']['uraian108'],
            //                 'kode50' => $rac['item']['kode50'],
            //                 'uraian50' => $rac['item']['uraian50'],
            //                 'stokalokasi' => $rac['item']['alokasi'],
            //                 // 'dosisobat' => $rac['dosisobat'],
            //                 // 'dosismaksimum' => $rac['dosismaksimum'],
            //                 'jumlah' => $rac['jumlah_diminta'], // jumlah obat
            //                 'satuan_racik' => $rac['kemasan'], // jumlah obat
            //                 'keteranganx' => $rac['item']['keterangan'] ?? '', // keterangan obat
            //                 'created_at' => $created,
            //                 'updated_at' => date('Y-m-d H:i:s'),
            //             ];
            //         $racikannondtd[] = $simpannondtd;
            //         // if ($simpannondtd) {
            //         //     $simpannondtd->load('mobat:kd_obat,nama_obat');
            //         // }
            //     }
            //   }
            // }


            if (count($rinciaja) > 0) {
                Resepkeluarrinci::insert($rinciaja);
            }

            // if (count($racikandtd) > 0) {
            //     Permintaanresepracikan::insert($racikandtd);
            // }
            // if (count($racikannondtd) > 0) {
            //     Permintaanresepracikan::insert($racikannondtd);
            // }

            if (count($rinciaja) <= 0) {
                return new JsonResponse([
                    'message' => 'Tidak ada rincian Obat untuk disimpan'
                ], 410);
            }


            DB::connection('farmasi')->commit();
            // $simpan->load([
            //     'permintaanresep.mobat:kd_obat,nama_obat,satuan_k,status_kronis',
            //     'permintaanracikan.mobat:kd_obat,nama_obat,satuan_k,status_kronis',
            // ]);

            // $msg = [
            //     'data' => [
            //         'id' => $simpan->id,
            //         'noreg' => $simpan->noreg,
            //         'depo' => $simpan->depo,
            //         'noresep' => $simpan->noresep,
            //         'status' => '1',
            //     ]
            // ];
            // event(new NotifMessageEvent($msg, 'depo-farmasi', auth()->user()));
            // cek apakah pasien rawat jalan, dan ini nanti jadi pasien selesai layanan dan ambil antrian farmasi iki
            // $updatekunjungan = KunjunganPoli::where('rs1', $request->noreg)->where('rs17.rs8', '!=', 'POL014')->first();
            // if ($updatekunjungan) {
            //     self::kirimResepDanSelesaiLayanan($request);
            // }
            return new JsonResponse([
                'message' => 'Resep Berhasil dibuat',
                // 'adaraw' => $adaraw,
                // 'data' => $simpan,
                // 'racikandtd' => $racikandtd,
                // 'racikannondtd' => $racikannondtd,
                'rinci' => $rinciaja,
                'stok' => $stok,
                // 'adaAlokasi' => $adaAlokasi,
                // 'adaAlokasi' => $adaAlokasi,
                // 'tidakAdaAlokasi' => $tidakAdaAlokasi,
                // 'adaAlokasiRacikan' => $adaAlokasiRacikan,
                // 'tidakAdaAlokasiRacikan' => $tidakAdaAlokasiRacikan,
                // 'nonRacik' => $nonRacik,
                // 'racikan' => $racikan,
                // 'kdobat' => $kdobat,
                // 'uniqueObat' => $uniqueObat,
                // 'alokasinya' => $alokasinya,
                // 'cekjumlahstok' => $cekjumlahstok,
            ]);
        } catch (\Exception $e) {
            DB::connection('farmasi')->rollBack();
            return new JsonResponse([
                'racikandtd' => $racikandtd ?? [],
                'racikannondtd' => $racikannondtd ?? [],
                'rinci' => $rinciaja ?? [],
                // 'adaAlokasi' => $adaAlokasi,
                // 'tidakAdaAlokasi' => $tidakAdaAlokasi,
                // 'adaAlokasiRacikan' => $adaAlokasiRacikan,
                // 'tidakAdaAlokasiRacikan' => $tidakAdaAlokasiRacikan,
                'error' => ' ' . $e,
                'message' => 'rolled back ada kesalahan'
            ], 410);
        }
    }
    public function hapus(Request $request)
    {
        try {
            DB::connection('farmasi')->beginTransaction();
            $header = Resepkeluarheder::where('noresep', $request->noresep)
                ->whereNull('flag_pembayaran')
                ->first();
            if (!$header) {
                return new JsonResponse([
                    'message' => 'Data tidak ditemukan, apakah Sudah dibayar?'
                ], 410);
            }
            $rinci = Resepkeluarrinci::where('noresep', $request->noresep)
                ->get();
            $stok = [];
            // balikin by fifo
            foreach ($rinci as $ri) {
                $jumlah = $ri['jumlah'];
                $tmpstok = Stokrel::lockForUpdate()
                    ->where('kdobat', $ri['kdobat'])
                    ->where('nopenerimaan', $ri['nopenerimaan'])
                    ->where('kdruang', $header->depo)
                    ->orderBy('tglexp', 'DESC')
                    ->orderBy('id', 'DESC')
                    ->first();
                $jmlS = $tmpstok->jumlah;
                $tot = (float)$jumlah + (float)$jmlS;
                $tmpstok->update([
                    'jumlah' => $tot
                ]);
                $stok[] = $tmpstok;
                // return $jumlah;
                $ri->delete();
            }
            $header->delete();
            $data = [
                'header' => $header,
                'rinci' => $rinci,
                'stok' => $stok,
                'jumlah' => $jumlah,
                'jmlS' => $jmlS,
                'tot' => $tot,
                'req' => $request->all()
            ];

            DB::connection('farmasi')->commit();
            return new JsonResponse([
                'message' => 'Data Sudah dihapus',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            DB::connection('farmasi')->rollBack();
            return response()->json([
                'message' => 'ada kesalahan',
                'error' =>  $e,
                'error e' => '' . $e,
                'stok' => $dataStok ?? null
            ], 410);
        }
    }
}
