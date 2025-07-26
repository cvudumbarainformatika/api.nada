<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\BarangRS;
use App\Models\Sigarang\MapingBarangDepo;
use App\Models\Sigarang\MinMaxDepo;
use App\Models\Sigarang\MonthlyStokUpdate;
use App\Models\Sigarang\Pegawai;
use App\Models\Sigarang\PenggunaRuang;
use App\Models\Sigarang\RecentStokUpdate;
use App\Models\Sigarang\Transaksi\Permintaanruangan\DetailPermintaanruangan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    // stok user
    // stok alokasi depo
    // stok alokasi user
    // maks stok maksimal depo
    // maks stok maksimal user
    //  user ? ruangan?
    /*
    * get stok min max depo
    */
    public function stokMinMaxDepo(Request $request)
    {
        $depo = $request->kode_depo;
        $data = MinMaxDepo::where('kode_depo', '=', $depo)->get();
        return new JsonResponse($data, 200);
    }

    public function stokSekarang()
    {
        $perpage = request('per_page') ? request('per_page') : 10;
        // $raw = RecentStokUpdate::with('depo', 'ruang', 'barang.barang108')

        $user = auth()->user()->pegawai_id;
        $pegawai = Pegawai::find($user);

        // $before = RecentStokUpdate::selectRaw('* , sum(sisa_stok) as totalStok');
        // $before = RecentStokUpdate::select([
        //     'barang_r_s.kode',
        //     'barang_r_s.nama',
        //     'recent_stok_updates.kode_rs',
        //     'recent_stok_updates.sisa_stok',
        //     'recent_stok_updates.kode_ruang',
        //     DB::raw('sum(recent_stok_updates.sisa_stok) as totalStok')
        // ])
        $before = RecentStokUpdate::selectRaw('* , sum(sisa_stok) as totalStok')
            // ->leftJoin('barang_r_s', 'barang_r_s.kode', '=', 'recent_stok_updates.kode_rs')
            // ->when(request('q'), function ($anu) {
            //     $anu->where('barang_r_s.nama', 'LIKE', '%' . request('q') . '%')
            //         ->orWhere('barang_r_s.kode', 'LIKE', '%' . request('q') . '%');
            // })
            ->when($pegawai->role_id === 5, function ($q) use ($pegawai) {
                $pengguna = PenggunaRuang::where('kode_ruang', $pegawai->kode_ruang)->first();
                $ruang = PenggunaRuang::select('kode_ruang')->where('kode_pengguna', $pengguna->kode_pengguna)->get();
                $raw = collect($ruang);
                $only = $raw->map(function ($y) {
                    return $y->kode_ruang;
                });

                $q->whereIn('kode_ruang', $only);
            })
            ->when($pegawai->role_id === 4, function ($q) use ($pegawai) {
                $q->where('kode_ruang', $pegawai->kode_ruang)
                    ->where('kode_ruang', '<>', 'Gd-02010100')
                    ->orWhere('kode_ruang', 'like', '%R-%');
            })
            ->where('sisa_stok', '>', 0)
            // ->orderBy('barang_r_s.nama', 'ASC')
            ->with('depo', 'ruang', 'barang.barang108', 'barang.satuan')
            ->groupBy('kode_ruang', 'kode_rs')
            // ->where('recent_stok_updates.sisa_stok', '>', 0)
            // ->orderBy('barang_r_s.nama', 'ASC')
            // ->with('depo', 'ruang', 'barang.barang108', 'barang.satuan')
            // ->groupBy('recent_stok_updates.kode_rs', 'recent_stok_updates.kode_ruang')

            ->filter(request(['q']))
            // ->simplePaginate($perpage);
            ->paginate($perpage);

        // if ($pegawai->role_id === 5) {

        //     $pengguna = PenggunaRuang::where('kode_ruang', $pegawai->kode_ruang)->first();

        //     $ruang = PenggunaRuang::where('kode_pengguna', $pengguna->kode_pengguna)->get();
        //     $raw = collect($ruang);
        //     $only = $raw->map(function ($y) {
        //         return $y->kode_ruang;
        //     });

        //     $before->whereIn('kode_ruang', $only);
        // }
        // if ($pegawai->role_id === 4) {
        //     $before->where('kode_ruang', $pegawai->kode_ruang)
        //         ->where('kode_ruang', '<>', 'Gd-02010100')
        //         ->orWhere('kode_ruang', 'like', '%R-%');
        // }
        // $raw = $before->orderBy('barang_r_s.nama', 'ASC')
        //     ->with('depo', 'ruang', 'barang.barang108', 'barang.satuan')
        //     ->where('recent_stok_updates.sisa_stok', '>', 0)
        //     ->groupBy('recent_stok_updates.kode_rs', 'recent_stok_updates.kode_ruang')
        //     // ->filter(request(['q']))
        //     ->paginate($perpage);

        $raw = $before;

        $col = collect($raw);
        $meta = $col->except('data');
        $meta->all();

        $data = $col->only('data');
        $data['meta'] = $meta;
        return new JsonResponse($data);
    }

    public function stokRuanganByBarang()
    {
        $kode_rs = request('kode_rs');
        $kode_ruangan = request('kode_ruangan');

        // ambil data barang
        $barang = BarangRS::where('kode', $kode_rs)->first();

        // cari barang ini masuk depo mana
        $depo = MapingBarangDepo::where('kode_rs', $kode_rs)->first();

        // ambil stok ruangan
        $stokRuangan = RecentStokUpdate::where('kode_rs', $kode_rs)
            ->where('kode_ruang', $kode_ruangan)->get();
        $totalStokRuangan = collect($stokRuangan)->sum('sisa_stok');

        // cari stok di depo
        $stok = RecentStokUpdate::where('kode_rs', $kode_rs)
            ->where('kode_ruang', $barang->kode_depo)->get();
        $totalStok = collect($stok)->sum('sisa_stok');

        // ambil alokasi barang
        $data = DetailPermintaanruangan::whereHas('permintaanruangan', function ($q) {
            $q->where('status', '>=', 4)
                ->where('status', '<', 7);
        })->where('kode_rs', $kode_rs)->get();
        $col = collect($data);
        $gr = $col->map(function ($item) {
            $jumsem = $item->jumlah_disetujui ? $item->jumlah_disetujui : $item->jumlah;
            $item->alokasi = $jumsem;
            return $item;
        });
        $sum = $gr ? $gr->sum('alokasi') : 0;
        $alokasi = 0;
        // hitung alokasi
        if ($totalStok >= $sum) {
            $alokasi =  $totalStok - $sum;
        } else {
            $alokasi = 0;
        }

        $barang->alokasi = $alokasi;
        $barang->stok = $totalStok;
        $barang->stokRuangan = $totalStokRuangan;
        return new JsonResponse($barang);
    }
    public static function getDetailsStok($kode_rs, $kode_ruangan)
    {
        // $kode_rs = request('kode_rs');
        // $kode_ruangan = request('kode_ruangan');

        // ambil data barang
        $barang = BarangRS::where('kode', $kode_rs)->first();

        // cari barang ini masuk depo mana
        // $depo = MapingBarangDepo::where('kode_rs', $kode_rs)->first();

        // ambil stok ruangan
        // $stokRuangan = RecentStokUpdate::where('kode_rs', $kode_rs)
        //     ->where('kode_ruang', $kode_ruangan)->get();
        // $totalStokRuangan = collect($stokRuangan)->sum('sisa_stok');

        // ambil stok ruangan cara baru
        $totalStokRuangan = RecentStokUpdate::where('kode_rs', $kode_rs)
            ->where('kode_ruang', $kode_ruangan)->sum('sisa_stok');
        // $totalStokRuangan = collect($stokRuangan)->sum('sisa_stok');

        // cari stok di depo
        // $stok = RecentStokUpdate::where('kode_rs', $kode_rs)
        //     ->where('kode_ruang', $barang->kode_depo)->get();
        // $totalStok = collect($stok)->sum('sisa_stok');

        // cari stok di depo cara baru
        $totalStok = RecentStokUpdate::where('kode_rs', $kode_rs)
            ->where('kode_ruang', $barang->kode_depo)->sum('sisa_stok');
        // $totalStok = collect($stok)->sum('sisa_stok');

        // ambil alokasi barang
        // $data = DetailPermintaanruangan::whereHas('permintaanruangan', function ($q) {
        //     $q->where('status', '>=', 4)
        //         ->where('status', '<', 7);
        // })->where('kode_rs', $kode_rs)->get();

        $data = DB::connection('sigarang')->table('detail_permintaanruangans')
            ->select(
                'detail_permintaanruangans.permintaanruangan_id',
                'detail_permintaanruangans.kode_rs',
                'detail_permintaanruangans.jumlah_disetujui',
                'detail_permintaanruangans.jumlah',
                'permintaanruangans.id',
                'permintaanruangans.status',
            )
            ->join('permintaanruangans', function ($minta) {
                $minta->on(
                    'detail_permintaanruangans.permintaanruangan_id',
                    '=',
                    'permintaanruangans.id'
                )
                    ->whereIn('status', [4, 5, 6]);
            })->where('kode_rs', $kode_rs)->get();

        $col = collect($data);
        $gr = $col->map(function ($item) {
            $jumsem = $item->jumlah_disetujui ? $item->jumlah_disetujui : $item->jumlah;
            $item->alokasi = $jumsem;
            return $item;
        });
        $sum = $gr->sum('alokasi');
        $alokasi = 0;
        // hitung alokasi
        if ($totalStok >= $sum) {
            $alokasi =  $totalStok - $sum;
        } else {
            $alokasi = 0;
        }

        // $barang->depo = $depo;
        // $barang->sum = $sum;
        // $barang->stok = $stok;
        // $barang->stokRuangan = $stokRuangan;
        $barang->alokasi = $alokasi;
        $barang->stok = $totalStok;
        $barang->stokRuangan = $totalStokRuangan;
        return $barang;
        // return new JsonResponse($barang);
    }

    public function currentStok()
    {
        // $data = RecentStokUpdate::get();
        $data = RecentStokUpdate::selectRaw('* , sum(sisa_stok) as stok')
            // ->where('kode_ruang', '<>', 'Gd-02010100')
            ->groupBy('kode_rs', 'kode_ruang')
            ->with('barang.barang108', 'barang.satuan', 'depo', 'barang.mapingdepo.gudang')
            ->get();
        // ->paginate(10);
        $collection = collect($data)->unique('kode_rs');
        $collection->values()->all();

        // return new JsonResponse($data);
        return new JsonResponse($collection);
    }
    public function stokDepo()
    {
        // $data = RecentStokUpdate::get();
        $data = RecentStokUpdate::selectRaw('* , sum(sisa_stok) as stok')
            ->where('kode_ruang', '<>', 'Gd-02010100')
            ->groupBy('kode_rs', 'kode_ruang')
            ->with('barang.barang108', 'barang.satuan', 'depo', 'barang.depo')
            ->get();
        // ->paginate(10);
        $collection = collect($data)->unique('kode_rs');
        $collection->values()->all();

        // return new JsonResponse($data);
        return new JsonResponse($collection);
    }

    // ruang yang punya stok
    public function ruangHasStok()
    {
        $user = auth()->user();
        $pegawai = Pegawai::find($user->pegawai_id);
        $before = RecentStokUpdate::selectRaw('* , sum(sisa_stok) as stok')
            ->where('sisa_stok', '>', 0);
        if ($pegawai->role_id === 5) {

            $pengguna = PenggunaRuang::where('kode_ruang', $pegawai->kode_ruang)->first();
            $ruang = PenggunaRuang::where('kode_pengguna', $pengguna->kode_pengguna)->get();
            $raw = collect($ruang);
            $only = $raw->map(function ($y) {
                return $y->kode_ruang;
            });

            $before->whereIn('kode_ruang', $only)->where('kode_ruang', '<>', 'Gd-02010100');
        }
        if ($pegawai->role_id === 4) {
            $before->where('kode_ruang', $pegawai->kode_ruang)
                ->where('kode_ruang', '<>', 'Gd-02010100')
                ->orWhere('kode_ruang', 'like', '%R-%');
        }
        $raw = $before->with('barang.barang108', 'barang.satuan', 'depo', 'barang.mapingdepo.gudang', 'ruang')
            // ->whereIn('kode_ruang', $only)
            ->groupBy('kode_ruang')
            ->get();

        $data = collect($raw)->unique('kode_ruang');
        $data->all();
        return new JsonResponse($data);
    }
    // get data by depo
    public function getDataStokByDepo()
    {

        $raw = RecentStokUpdate::selectRaw('* , sum(sisa_stok) as totalStok')
            ->where('kode_ruang', '=', request('search'))
            ->where('sisa_stok', '>', 0)
            ->orderBy(request('order_by'), request('sort'))
            ->groupBy('kode_rs', 'kode_ruang')
            ->filter(request(['q']))
            // ->filter(request(['search']))
            ->with('ruang', 'barang.barang108', 'barang.satuan', 'depo')
            ->paginate(request('per_page'));
        $col = collect($raw);
        $meta = $col->except('data');
        $meta->all();

        $data = $col->only('data');
        $data['meta'] = $meta;
        return new JsonResponse($data);
    }
    public function currentHasStok()
    {
        // $data = RecentStokUpdate::get();
        $data = RecentStokUpdate::selectRaw('* , sum(sisa_stok) as stok')
            ->where('sisa_stok', '>', 0)
            ->groupBy('kode_rs', 'kode_ruang')
            ->get();
        $collection = collect($data)->unique('kode_rs');
        $collection->values()->all();

        // return new JsonResponse($data);
        return new JsonResponse($collection);
    }

    //ambil stok tiap-tiap ruangan
    public function stokRuangan()
    {
        $data = RecentStokUpdate::selectRaw('* , sum(sisa_stok) as stok')
            ->where('kode_ruang', 'LIKE', 'R-' . '%')
            ->where('sisa_stok', '>', 0)
            ->groupBy('kode_rs', 'kode_ruang')
            ->get();
        $collection = collect($data)->unique('kode_rs');
        $collection->values()->all();

        // return new JsonResponse($data);
        return new JsonResponse($collection);
    }

    //ambil stok tiap-tiap gudang
    public function stokNonRuangan()
    {
        $data = RecentStokUpdate::selectRaw('* , sum(sisa_stok) as stok')
            ->where('kode_ruang', 'LIKE', 'Gd-' . '%')
            ->where('sisa_stok', '>', 0)
            ->groupBy('kode_rs', 'kode_ruang')
            ->get();
        $collection = collect($data)->unique('kode_rs');
        $collection->values()->all();

        // return new JsonResponse($data);
        return new JsonResponse($collection);
    }
    //ambil stok berdasarkan ruangan
    public function stokByRuangan()
    {
        $data = RecentStokUpdate::selectRaw('* , sum(sisa_stok) as stok')
            ->where('kode_ruang', request('kode_ruang'))
            ->where('sisa_stok', '>', 0)
            ->with('barang')
            ->groupBy('kode_rs', 'kode_ruang')
            ->get();
        $collection = collect($data)->unique('kode_rs');
        $collection->values()->all();

        // return new JsonResponse($data);
        return new JsonResponse($collection);
    }
    // ambil data stok yang masih ada di gudang.
    // data ini berarti data yang belum di distribusikan
    public function currentStokGudang()
    {
        // $data = RecentStokUpdate::get();
        $data = RecentStokUpdate::selectRaw('* , sum(sisa_stok) as stok')
            ->where('kode_ruang', 'Gd-02010100')
            ->groupBy('kode_rs', 'kode_ruang')
            ->with('maping', 'barang')
            ->get();
        $collection = collect($data)->unique('kode_rs');
        $collection->values()->all();

        // return new JsonResponse($data);
        return new JsonResponse($collection);
    }

    public function currentStokByRuangan(Request $request)
    {
        $ruang = $request->ruang;
        $data = RecentStokUpdate::where('kode_ruang', $ruang)
            ->get();
        return new JsonResponse($data);
    }

    public function currentStokByPermintaan(Request $request)
    {
        $permintaan = $request->permintaan;
        $data = RecentStokUpdate::where('no_permintaan', $permintaan)
            ->get();
        return new JsonResponse($data);
    }

    public function currentStokByBarang(Request $request)
    {
        $barang = $request->barang;
        $data = RecentStokUpdate::where('kode_rs', $barang)
            ->get();
        return new JsonResponse($data);
    }
    public function currentStokByGudang()
    {
        $data = RecentStokUpdate::where('kode_ruang', 'Gd-02010100')
            ->get();
        return new JsonResponse($data);
    }

    // untuk kartu stok recent stok update
    public function getDataKartuStok()
    {
        $from = request('from');
        $to = request('to');
        $fromWt = request('from') . ' 00:00:00';
        $toWt = request('to') . ' 23:59:59';

        $lastDate = date('Y-m-t', strtotime($to));
        // $m = date('m', strtotime($to));
        // $y = date('Y', strtotime($to));
        $prevFromWt = date('Y-m-d', strtotime(request('from') . '- 1 month')) . ' 00:00:00';
        $prevToWt = date('Y-m-t', strtotime(request('to') . '- 1 month')) . ' 23:59:59';
        $request = request()->all();
        $data = BarangRS::where('kode', request('kode_rs'))
            ->with([
                'recent.depo',
                'monthly' => function ($x) use ($prevFromWt, $prevToWt) {
                    $x->where('kode_ruang', request('kode_ruang'))
                        ->whereBetween('tanggal', [$prevFromWt, $prevToWt]);
                },
                'detailPemesanan.pemesanan' => function ($x) use ($from, $to) {
                    $x->whereBetween('tanggal', [$from, $to]);
                },
                'detailPenerimaan.penerimaan' => function ($x) use ($from, $to) {
                    $x->whereBetween('tanggal', [$from, $to]);
                },
                'detailTransaksiGudang.transaction' => function ($x) use ($from, $to) {
                    $x->whereBetween('tanggal', [$from, $to]);
                },
                'detailDistribusiDepo.distribusi' => function ($x) use ($fromWt, $toWt) {
                    $x->whereBetween('tanggal', [$fromWt, $toWt]);
                },
                'detailDistribusiLangsung.distribusi' => function ($x) use ($fromWt, $toWt) {
                    $x->whereBetween('tanggal', [$fromWt, $toWt]);
                },
                'detailPermintaanruangan.permintaanruangan' => function ($x) use ($fromWt, $toWt) {
                    $x->whereBetween('tanggal', [$fromWt, $toWt]);
                },
                'detailPemakaianruangan.pemakaianruangan' => function ($x) use ($fromWt, $toWt) {
                    $x->whereBetween('tanggal', [$fromWt, $toWt]);
                },
            ])
            ->first();

        return new JsonResponse([
            $data,
            'bulan' => $prevFromWt,
            'tahun' => $prevToWt,
            'request' => $request
        ]);
    }

    // update harga stok
    public function updateHarga($data)
    {
        // return $data;
        $recent = RecentStokUpdate::where('kode_rs', $data['kode'])
            ->where('no_penerimaan', $data['no_penerimaan'])
            ->get();
        $month = MonthlyStokUpdate::where('kode_rs', $data['kode'])
            ->where('no_penerimaan', $data['no_penerimaan'])
            ->get();
        if (count($recent)) {
            foreach ($recent as $key) {
                if ($key['harga'] <= 0) {
                    $key['harga'] = (float)$data['harga'];
                    $key->save();
                }
            }
        }
        if (count($month)) {
            foreach ($month as $key) {
                if ($key['harga'] <= 0) {
                    $key['harga'] = (float)$data['harga'];
                    $key->save();
                }
            }
        }
        $balik = [
            'a-req' => $data,
            'recent' => $recent,
            'month' => $month,
        ];
        return $balik;
    }
}
