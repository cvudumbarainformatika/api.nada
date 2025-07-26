<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang\Transaksi;

use App\Helpers\StokHelper;
use App\Http\Controllers\Controller;
use App\Models\Sigarang\BarangRS;
use App\Models\Sigarang\Gudang;
use App\Models\Sigarang\MonthlyStokUpdate;
use App\Models\Sigarang\Pegawai;
use App\Models\Sigarang\RecentStokUpdate;
use App\Models\Sigarang\StokOpname;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StokOpnameController extends Controller
{
    // data gudang dan depo sigarang
    public function getDataGudangDepo()
    {
        $user = auth()->user();
        $pegawai = Pegawai::find($user->pegawai_id);
        $raw = Gudang::query();
        if ($pegawai->role_id === 4) {
            $raw->where('kode', $pegawai->kode_ruang);
        } else {
            $raw->where('gedung', 2)
                ->where('lantai', '>', 0)
                ->where('gudang', '>', 0)
                ->where('depo', '>', 0);
        }

        $data = $raw->get();
        return new JsonResponse($data);
    }
    // ambil data stok current ->
    // masukkan ke tabel stok opname bulanan ->
    // tampilkan ->
    // jika ada perbedaan tulis jumlah dan sisanya di tabel stok opname
    public function index(Request $request)
    {
        $request->validate(['gudang' => 'required']);
        $data = RecentStokUpdate::where('kode_ruang', $request->gudang)
            ->filter([$request->search])
            ->paginate(10);

        return new JsonResponse($data);
    }

    public function getDataTransaksi()
    {
        $header = (object)[];
        $bulan = request('bulan') ? '-' . request('bulan') : date('m');
        $tahun = request('tahun') ? request('tahun') : date('Y');
        $hari = '-31';
        $anu = (int)request('bulan') - 1;
        $prevTahun = request('bulan') === '01' ? strval((int)$tahun - 1) : $tahun;
        $prevbulan = request('bulan') === '01' ? '-12' : ($anu < 10 ? '-0' . $anu : '-' . $anu);

        $header->thisMonthFrom = $tahun . $bulan . '-01' . ' 00:00:00';
        $header->thisMonthTo = $tahun . $bulan . $hari . ' 23:59:59';
        $header->from = $tahun . $bulan . '-01';
        $header->to = $tahun . $bulan . $hari;
        $header->prevMonthFrom = $prevTahun . $prevbulan . '-01' . ' 00:00:00';
        $header->prevMonthTo = $prevTahun . $prevbulan . $hari . ' 23:59:59';

        $user = auth()->user();
        $pegawai = Pegawai::find($user->pegawai_id);
        $depo = Gudang::where('kode', $pegawai->kode_ruang)->first();
        $header->pegawai = $pegawai;

        $penerimaan = StokHelper::hitungTransaksiPenerimaan($header);
        $distribusi_depo = StokHelper::hitungTransaksiDistribusiDepo($header);
        $permintaan_ruangan = StokHelper::hitungTransaksiPermintaanRuangan($header);

        $awal = MonthlyStokUpdate::selectRaw('kode_rs,kode_ruang,sum(sisa_stok) as stok')
            ->whereBetween('tanggal', [$header->prevMonthFrom, $header->prevMonthTo])
            ->groupBy('kode_rs', 'kode_ruang')
            ->get();
        return [
            'penerimaan' => $penerimaan,
            'distribusi_depo' => $distribusi_depo,
            'permintaan_ruangan' => $permintaan_ruangan,
            'awal' => $awal,
            'header' => $header
        ];
    }

    public function getDataTransaksiByKodeRs($kode_rs)
    {
        $header = (object)[];
        $header->kode_rs = $kode_rs;
        $bulan = request('bulan') ? '-' . request('bulan') : date('m');
        $tahun = request('tahun') ? request('tahun') : date('Y');
        $hari = '-31';
        $anu = (int)request('bulan') - 1;
        $prevTahun = request('bulan') === '01' ? strval((int)$tahun - 1) : $tahun;
        $prevbulan = request('bulan') === '01' ? '-12' : ($anu < 10 ? '-0' . $anu : '-' . $anu);

        $header->thisMonthFrom = $tahun . $bulan . '-01' . ' 00:00:00';
        $header->thisMonthTo = $tahun . $bulan . $hari . ' 23:59:59';
        $header->from = $tahun . $bulan . '-01';
        $header->to = $tahun . $bulan . $hari;
        $header->prevMonthFrom = $prevTahun . $prevbulan . '-01' . ' 00:00:00';
        $header->prevMonthTo = $prevTahun . $prevbulan . $hari . ' 23:59:59';

        // $user = auth()->user();
        // $pegawai = Pegawai::find($user->pegawai_id);
        // $depo = Gudang::where('kode', $pegawai->kode_ruang)->first();
        // $header->pegawai = $pegawai;

        // transaksi awal yaitu 31 des 2022
        // $dataAwal = MonthlyStokUpdate::whereBetween('tanggal', ['2022-12-01 00:00:00', '2022-12-31 23:59:59'])
        //     ->with('barang')->get();
        /*

            * transaksi berpengaruh :
            * penerimaan,
            * distribusi depo,
            * permintaan ruangan,
            * distribusi langsung
        */

        $penerimaan = StokHelper::hitungTransaksiPenerimaanByKodeBarang($header);
        $distribusi_depo = StokHelper::hitungTransaksiDistribusiDepoByKodeBarang($header);
        $permintaan_ruangan = StokHelper::hitungTransaksiPermintaanRuanganByKodeBarang($header);
        $awal = MonthlyStokUpdate::selectRaw('kode_rs ,kode_ruang, sum(sisa_stok) as stok')
            ->whereBetween('tanggal', [$header->prevMonthFrom, $header->prevMonthTo])
            ->where('kode_rs', $header->kode_rs)
            ->groupBy('kode_rs', 'kode_ruang')
            ->get();
        return [
            'penerimaan' => $penerimaan,
            'distribusi_depo' => $distribusi_depo,
            'permintaan_ruangan' => $permintaan_ruangan,
            'awal' => $awal,
            'header' => $header
        ];
    }

    public function getDataStokOpname()
    {
        $bulan = request('bulan') ? request('bulan') : date('m');
        $tahun = request('tahun') ? request('tahun') : date('Y');

        $awal = $tahun . '-' . $bulan . '-01' . ' 00:00:00';
        $akhir = $tahun . '-' . $bulan . '-31' . ' 23:59:59';

        $tAwal = $tahun . '-' . $bulan . '-1';
        $tAkhir = $tahun . '-' . $bulan . '-31';

        $anu = (int)request('bulan') - 1;
        $prevTahun = request('bulan') === '01' ? strval((int)$tahun - 1) : $tahun;
        $prevbulan = request('bulan') === '01' ? '-12' : ($anu < 10 ? '-0' . $anu : '-' . $anu);

        $from = $prevTahun . '-' . $prevbulan . '-01' . ' 00:00:00';
        $to = $prevTahun . '-' . $prevbulan . '-31' . ' 23:59:59';

        $raw = MonthlyStokUpdate::selectRaw('*, sum(sisa_stok) as totalStok')
            ->whereBetween('tanggal', [$awal, $akhir])
            // ->where('tanggal', '<=', $tahun . '-' . $bulan . '-31')
            ->with([
                'penyesuaian',
                'barang.detailPenerimaan.penerimaan' => function ($wew) use ($tAwal, $tAkhir) {
                    $wew->whereBetween('tanggal', [$tAwal, $tAkhir])
                        ->where('status', '>=', 2);
                },
                'barang.detailPermintaanruangan.permintaanruangan' => function ($wew) use ($awal, $akhir) {
                    $wew->whereBetween('tanggal', [$awal, $akhir])
                        ->where('status', '>=', 7)
                        ->where('status', '<=', 8);
                },
                'barang.detailTransaksiGudang' => function ($x) {
                    $x->selectRaw('sum(qty) as jumlah');
                },
                //.transaction' => function ($wew) use ($tAwal, $tAkhir) {
                //     $wew->whereBetween('tanggal', [$tAwal, $tAkhir])
                //         ->where('status', '>=', 2);
                // },
                'barang.detailDistribusiDepo',
                //distribusi' => function ($wew) use ($awal, $akhir) {
                //     $wew->whereBetween('tanggal', [$awal, $akhir])
                //         ->where('status', '>=', 2);
                // },
                'barang.detailDistribusiLangsung.distribusi' => function ($wew) use ($awal, $akhir) {
                    $wew->whereBetween('tanggal', [$awal, $akhir])
                        ->where('status', '>=', 2);
                },
                'depo',
                'ruang',
                'barang.monthly' => function ($wew) use ($from, $to) {
                    $wew->whereBetween('tanggal', [$from, $to]);
                },
            ])
            ->where('kode_ruang', 'like', '%' . request('search') . '%')
            ->filter(request(['q']))
            ->groupBy('kode_rs', 'kode_ruang', 'no_penerimaan')
            ->paginate(request('per_page'));


        $col = collect($raw);
        $meta = $col->except('data');
        $meta->all();

        $data = $col->only('data');
        $data['meta'] = $meta;
        $count = collect($raw);
        $data['with count'] = $count->only('data');
        return new JsonResponse($data);
    }

    public function getDataStokOpnameBaru()
    {
        $head = (object)[];
        $head->bulan = request('bulan') ? request('bulan') : date('m');
        $head->tahun = request('tahun') ? request('tahun') : date('Y');

        $head->awal = $head->tahun . '-' . $head->bulan . '-01' . ' 00:00:00';
        $head->akhir = $head->tahun . '-' . $head->bulan . '-31' . ' 23:59:59';

        $head->nawal = $head->tahun . '-' . $head->bulan . '-01';
        $head->nakhir = $head->tahun . '-' . $head->bulan . '-31';

        $anu = (int)request('bulan') - 1;
        $head->prevTahun = request('bulan') === '01' ? strval((int)$head->tahun - 1) : $head->tahun;
        $head->prevbulan = request('bulan') === '01' ? '-12' : ($anu < 10 ? '-0' . $anu : '-' . $anu);

        $head->from = $head->prevTahun . '-' . $head->prevbulan . '-01' . ' 00:00:00';
        $head->to = $head->prevTahun . '-' . $head->prevbulan . '-31' . ' 23:59:59';

        $user = auth()->user();
        $pegawai = Pegawai::find($user->pegawai_id);
        $raw = Gudang::where('gedung', 2)
            ->where('lantai', '>', 0)
            ->where('gudang', '>', 0)
            ->where('depo', '>', 0)
            ->get();
        $wew = collect($raw);
        $depos = $wew->map(function ($anu) {
            return $anu->kode;
        });

        $data = BarangRS::with([
            'monthly' => function ($q) use ($head) {
                $q->whereBetween('tanggal', [$head->awal, $head->akhir]);
                // ->when(request('search'), function ($anu) {
                //     $anu->whereIn('kode_ruang', [request('search'), 'Gd-02010100']);
                // });
                // ->whereIn('kode_ruang', [request('search'), 'Gd-02010100']);
            },
            'stok_awal' => function ($q) use ($head) {
                // if($head->bulan==12){

                // }else{
                $q->whereBetween('tanggal', [$head->from, $head->to]);
                // ->when(request('search'), function ($anu) {
                //     $anu->whereIn('kode_ruang', [request('search'), 'Gd-02010100']);
                // });
                // }
            },
            'fisik' => function ($q) use ($head) {
                $q->whereBetween('tanggal', [$head->awal, $head->akhir]);
                // ->where('kode_depo', request('search'));
                // ->where('kode_ruang', request('search'));
            },
            'detailPermintaanruangan' => function ($detail) use ($head) {
                $detail->select(
                    'detail_permintaanruangans.kode_rs',
                    'detail_permintaanruangans.jumlah_distribusi',
                    'detail_permintaanruangans.alasan',
                    'detail_permintaanruangans.no_penerimaan',
                    'permintaanruangans.id',
                    'permintaanruangans.kode_ruang',
                    'permintaanruangans.dari',
                    'permintaanruangans.tanggal',
                    'ruangs.uraian',
                )
                    ->join('permintaanruangans', function ($minta) use ($head) {
                        $minta->on('detail_permintaanruangans.permintaanruangan_id', '=', 'permintaanruangans.id')
                            ->whereBetween('tanggal', [$head->awal, $head->akhir])
                            ->whereIn('status', [7, 8]);
                        $minta->join('ruangs', 'ruangs.kode', '=', 'permintaanruangans.kode_ruang');
                    })
                    ->where('jumlah_distribusi', '>', 0);
            },
            'detailPenerimaan' => function ($detail) use ($head) {
                $detail->select(
                    'detail_penerimaans.kode_rs',
                    'detail_penerimaans.qty',
                    'detail_penerimaans.merk',
                    'penerimaans.nomor',
                    'penerimaans.no_penerimaan',
                    'penerimaans.tanggal',
                )
                    ->join('penerimaans', function ($minta) use ($head) {
                        $minta->on('detail_penerimaans.penerimaan_id', '=', 'penerimaans.id')
                            ->whereBetween('tanggal', [$head->nawal, $head->nakhir])
                            ->where('status', 2);
                    });
            },
            'detailDistribusiDepo' => function ($detail) use ($head) {
                $detail->select(
                    'detail_distribusi_depos.kode_rs',
                    'detail_distribusi_depos.no_penerimaan',
                    'detail_distribusi_depos.jumlah',
                    'detail_distribusi_depos.merk',
                    'distribusi_depos.kode_depo',
                    'distribusi_depos.tanggal'
                )
                    ->join('distribusi_depos', function ($minta) use ($head) {
                        $minta->on('detail_distribusi_depos.distribusi_depo_id', '=', 'distribusi_depos.id')
                            ->whereBetween('tanggal', [$head->awal, $head->akhir])
                            ->where('status', 2);
                    });
            },
            'detailDistribusiLangsung' => function ($detail) use ($head) {
                $detail->select(
                    'detail_distribusi_langsungs.kode_rs',
                    'detail_distribusi_langsungs.jumlah',
                    // 'detail_distribusi_langsungs.merk',
                    'distribusi_langsungs.no_penerimaan',
                    'distribusi_langsungs.tanggal'
                )
                    ->join('distribusi_langsungs', function ($minta) use ($head) {
                        $minta->on('detail_distribusi_langsungs.distribusi_langsung_id', '=', 'distribusi_langsungs.id')
                            ->whereBetween('tanggal', [$head->awal, $head->akhir])
                            ->where('status', 2);
                    });
            },
            'detailTransaksiGudang' => function ($detail) use ($head) {
                $detail->select(
                    'detail_transaksi_gudangs.kode_rs',
                    'detail_transaksi_gudangs.no_penerimaan',
                    'detail_transaksi_gudangs.qty',
                    'detail_transaksi_gudangs.merk',
                    'transaksi_gudangs.tanggal',
                    'transaksi_gudangs.no_penerimaan',
                )
                    ->join('transaksi_gudangs', function ($minta) use ($head) {
                        $minta->on('detail_transaksi_gudangs.transaksi_gudang_id', '=', 'transaksi_gudangs.id')
                            ->whereBetween('tanggal', [$head->nawal, $head->nakhir])
                            ->where('status', 2);
                    });
            },
            'detailPemakaianruangan' => function ($detail) use ($head) {
                $detail->select(
                    'details_pemakaianruangans.kode_rs',
                    // 'details_pemakaianruangans.no_penerimaan',
                    'details_pemakaianruangans.jumlah',
                    'details_pemakaianruangans.merk',
                    'pemakaianruangans.no_penerimaan',
                    'pemakaianruangans.tanggal',
                )
                    ->join('pemakaianruangans', function ($minta) use ($head) {
                        $minta->on('details_pemakaianruangans.pemakaianruangan_id', '=', 'pemakaianruangans.id')
                            ->whereBetween('tanggal', [$head->awal, $head->akhir]);
                        // ->where('status', 2);
                    });
            }
        ])

            ->select('barang_r_s.*', 'satuans.nama as satuan', 'gudangs.nama as depo')
            // ->join('gudangs', function ($query) {
            //     // $user = auth()->user();
            //     // $pegawai = Pegawai::find($user->pegawai_id);
            //     // $raw = Gudang::where('gedung', 2)
            //     //     ->where('lantai', '>', 0)
            //     //     ->where('gudang', '>', 0)
            //     //     ->where('depo', '>', 0)
            //     //     ->get();
            //     // $wew = collect($raw);
            //     // $depos = $wew->map(function ($anu) {
            //     //     return $anu->kode;
            //     // });

            //     $query->on('gudangs.kode', '=', 'barang_r_s.kode_depo');
            //     // if (!request('search')) {
            //     //     if ($pegawai->role_id === 2 || $pegawai->role_id === 1) {
            //     //         $query->whereIn('gudangs.kode', [$depos]);
            //     //     } else {
            //     //         $query->whereIn('gudangs.kode', [$pegawai->kode_ruang]);
            //     //     }
            //     // } else {
            //     //     if (request('search') === 'Gd-02010100') {
            //     //         $query->whereIn('gudangs.kode', [$depos]);
            //     //     } else {
            //     //         $query->whereIn('gudangs.kode', [request('search')]);
            //     //     }
            //     // }


            // })
            ->join('gudangs', 'gudangs.kode', '=', 'barang_r_s.kode_depo')
            ->join('satuans', 'satuans.kode', '=', 'barang_r_s.kode_satuan')

            // ->when(request('search') ?? function ($anu) use ($pegawai, $depos) {
            //     if ($pegawai->role_id === 2 || $pegawai->role_id === 1) {
            //         $anu->whereIn('barang_r_s.kode_depo', [$depos]);
            //     } else {
            //         $anu->whereIn('barang_r_s.kode_depo', [$pegawai->kode_ruang]);
            //     }
            // }, function ($query) use ($depos) {
            //     if (request('search') === 'Gd-02010100') {
            //         $query->whereIn('barang_r_s.kode_depo', [$depos]);
            //     } else {
            //         $query->whereIn('barang_r_s.kode_depo', [request('search')]);
            //     }
            // })
            ->when(request('search'), function ($wew) {
                $wew->whereIn('barang_r_s.kode_depo', [request('search')]);
            })
            ->when(request('q'), function ($search) {
                $search->where('barang_r_s.nama', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('barang_r_s.kode', 'LIKE', '%' . request('q') . '%');
            })
            ->latest('id')
            ->paginate(request('per_page'));

        return new JsonResponse($data);
    }

    public function getDataStokOpnameByDepo()
    {
        $bulan = request('bulan') ? request('bulan') : date('m');
        $tahun = request('tahun') ? request('tahun') : date('Y');

        $awal = $tahun . '-' . $bulan . '-01' . ' 00:00:00';
        $akhir = $tahun . '-' . $bulan . '-31' . ' 23:59:59';

        $tAwal = $tahun . '-' . $bulan . '-1';
        $tAkhir = $tahun . '-' . $bulan . '-31';

        $anu = (int)request('bulan') - 1;
        $prevTahun = request('bulan') === '01' ? strval((int)$tahun - 1) : $tahun;
        $prevbulan = request('bulan') === '01' ? '-12' : ($anu < 10 ? '-0' . $anu : '-' . $anu);

        $from = $prevTahun . '-' . $prevbulan . '-01' . ' 00:00:00';
        $to = $prevTahun . '-' . $prevbulan . '-31' . ' 23:59:59';


        $raw = MonthlyStokUpdate::selectRaw('*, sum(sisa_stok) as totalStok')
            ->whereBetween('tanggal', [$awal, $akhir])
            ->where('kode_ruang', request('search'))
            ->groupBy('kode_rs', 'kode_ruang', 'no_penerimaan')
            ->filter(request(['q']))
            ->with([
                'penyesuaian',
                'barang.detailPenerimaan.penerimaan' => function ($wew) use ($tAwal, $tAkhir) {
                    $wew->whereBetween('tanggal', [$tAwal, $tAkhir]);
                },
                'barang.detailPermintaanruangan.permintaanruangan' => function ($wew) use ($awal, $akhir) {
                    $wew->whereBetween('tanggal', [$awal, $akhir])
                        ->where('status', '>=', 7)
                        ->where('status', '<=', 8);
                },
                'barang.detailTransaksiGudang.transaction' => function ($wew) use ($tAwal, $tAkhir) {
                    $wew->whereBetween('tanggal', [$tAwal, $tAkhir]);
                },
                'barang.detailDistribusiDepo.distribusi' => function ($wew) use ($awal, $akhir) {
                    $wew->whereBetween('tanggal', [$awal, $akhir]);
                },
                'barang.detailDistribusiLangsung.distribusi' => function ($wew) use ($awal, $akhir) {
                    $wew->whereBetween('tanggal', [$awal, $akhir]);
                },
                'depo',
                'ruang',
                'barang.monthly' => function ($wew) use ($from, $to) {
                    $wew->whereBetween('tanggal', [$from, $to]);
                },
            ])
            ->paginate(request('per_page'));
        $col = collect($raw);
        $meta = $col->except('data');
        $meta->all();

        $data = $col->only('data');
        $data['meta'] = $meta;
        $data['meta'] = $meta;
        $data['request'] = request()->all();
        return new JsonResponse($data);
    }


    public function storeMonthly()
    {

        $tanggal = request('tahun') . '-' . request('bulan') . '-' . date('d');
        $today = request('tahun') ? $tanggal : date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 days'));
        // $today = date('2023-06-30');
        // $lastDay = date('Y-m-t', strtotime($today));
        $lastDay = date('Y-m-01', strtotime($today));
        $dToday = date_create($today);
        $dLastDay = date_create($lastDay);
        $diff = date_diff($dToday, $dLastDay);

        // return new JsonResponse([
        //     'today' => $today,
        //     'last day' => $lastDay,
        //     'diff' => $diff,
        //     'request' => request()->all(),
        //     // 'recent' => $recent,
        //     // 'awal' => $dataAwal,
        // ], 410);

        if ($diff->d === 0 && $diff->m === 0) {
            // ambil data barang yang ada stoknya di tabel sekarang
            $recent = RecentStokUpdate::where('sisa_stok', '>', 0)
                // ->where('kode_ruang', 'like', '%Gd-%')
                ->with('barang')
                ->get();

            $total = [];
            $fisik = [];
            $tanggal = $yesterday . ' 23:59:58';
            foreach ($recent as $key) {
                // $data = MonthlyStokUpdate::updateOrCreate([
                $data = [
                    'tanggal' => $tanggal,
                    'kode_rs' => $key->kode_rs,
                    'kode_ruang' => $key->kode_ruang,
                    'no_penerimaan' => $key->no_penerimaan,
                    'sisa_stok' => $key->sisa_stok,
                    'harga' => $key->harga !== '' ? $key->harga : 0,

                    'satuan' => $key->satuan !== '' ? $key->satuan : 'Belum ada satuan',
                    'kode_satuan' => $key->kode_satuan !== '' ? ($key->barang ? $key->barang->kode_satuan : '71') : '71',
                ];
                $total[] = $data;
                // $anu = MonthlyStokUpdate::find($data->id);

                // if ($anu->stok_fisik == 0) {
                //     $anu->update([
                //         'stok_fisik' => $key->sisa_stok
                //     ]);
                //     array_push($fisik, $anu);
                // }
                // array_push($total, $data);
            }
            foreach (array_chunk($total, 100) as $t) {
                $result = MonthlyStokUpdate::insert($t);
            }
            if (count($recent) !== count($total)) {
                return new JsonResponse(['message' => 'ada kesalahan dalam penyimpanan data stok opname, hubungi tim IT'], 409);
            }
            // return new JsonResponse(['message' => 'data berhasil disimpan'], 201);
            return new JsonResponse([
                'message' => 'data berhasil disimpan',
                'recent' => count($recent),
                'total' => count($total),
            ], 201);

            //end if
        }

        return new JsonResponse([
            'message' => 'Stok opname dapat dilakukan di hari terakhir tiap bulan',
            'hari ini' => $yesterday
        ], 410);
        // return new JsonResponse(['message' => 'Anda tidak terdaftar sebagai petugas Depo'], 422);
    }
    // public function storeCoba()
    // {

    //     $tanggal = request('tahun') . '-' . request('bulan') . '-' . date('d');
    //     // $today = request('tahun') ? $tanggal : date('Y-m-d');
    //     $yesterday = date('Y-m-d', strtotime('-1 days'));
    //     $today = date('2023-09-01');
    //     // $lastDay = date('Y-m-t', strtotime($today));
    //     $lastDay = date('Y-m-01', strtotime($today));
    //     $dToday = date_create($today);
    //     $dLastDay = date_create($lastDay);
    //     $diff = date_diff($dToday, $dLastDay);

    //     // return new JsonResponse([
    //     //     'today' => $today,
    //     //     'last day' => $lastDay,
    //     //     'diff' => $diff,
    //     //     'request' => request()->all(),
    //     //     // 'recent' => $recent,
    //     //     // 'awal' => $dataAwal,
    //     // ], 410);

    //     if ($diff->d === 0 && $diff->m === 0) {
    //         // ambil data barang yang ada stoknya di tabel sekarang
    //         $recent = RecentStokUpdate::where('sisa_stok', '>', 0)
    //             // ->where('kode_ruang', 'like', '%Gd-%')
    //             ->with('barang')
    //             ->get();

    //         $total = [];
    //         $fisik = [];
    //         $tanggal = $yesterday . ' 23:59:59';
    //         foreach ($recent as $key) {
    //             $data = MonthlyStokUpdate::updateOrCreate([
    //                 'tanggal' => $tanggal,
    //                 'kode_rs' => $key->kode_rs,
    //                 'kode_ruang' => $key->kode_ruang,
    //                 'no_penerimaan' => $key->no_penerimaan,
    //                 'sisa_stok' => $key->sisa_stok,
    //                 'harga' => $key->harga !== '' ? $key->harga : 0,
    //             ], [
    //                 // 'tanggal' => $tanggal,
    //                 // 'kode_rs' => $key->kode_rs,
    //                 // 'kode_ruang' => $key->kode_ruang,
    //                 // 'no_penerimaan' => $key->no_penerimaan,
    //                 'satuan' => $key->satuan !== '' ? $key->satuan : 'Belum ada satuan',
    //                 'kode_satuan' => $key->kode_satuan !== '' ? ($key->barang ? $key->barang->kode_satuan : '71') : '71',
    //             ]);

    //             // $anu = MonthlyStokUpdate::find($data->id);

    //             // if ($anu->stok_fisik == 0) {
    //             //     $anu->update([
    //             //         'stok_fisik' => $key->sisa_stok
    //             //     ]);
    //             //     array_push($fisik, $anu);
    //             // }
    //             array_push($total, $data);
    //         }

    //         if (count($recent) !== count($total)) {
    //             return new JsonResponse(['message' => 'ada kesalahan dalam penyimpanan data stok opname, hubungi tim IT'], 409);
    //         }
    //         // return new JsonResponse(['message' => 'data berhasil disimpan'], 201);
    //         return new JsonResponse([
    //             'message' => 'data berhasil disimpan',
    //             'recent' => count($recent),
    //             'total' => count($total),
    //         ], 201);

    //         //end if
    //     }

    //     return new JsonResponse([
    //         'message' => 'Stok opname dapat dilakukan di hari terakhir tiap bulan',
    //         'hari ini' => $yesterday
    //     ], 410);
    //     // return new JsonResponse(['message' => 'Anda tidak terdaftar sebagai petugas Depo'], 422);
    // }

    public function autoFisik()
    {
    }

    public function storePenyesuaian(Request $request)
    {
        $monthlyStok = MonthlyStokUpdate::find($request->id);

        $recent = RecentStokUpdate::where('kode_rs', $monthlyStok->kode_rs)
            ->where('kode_ruang', $monthlyStok->kode_ruang)
            ->where('no_penerimaan', $monthlyStok->no_penerimaan)->first();

        // return new JsonResponse([
        //     'monthly' => $monthlyStok,
        //     'recent' => $recent,
        //     'request' => $request->all(),
        // ], 200);

        $penyesuaian = StokOpname::updateOrCreate(
            [
                'monthly_stok_update_id' => $monthlyStok->id,
            ],
            $request->all()
        );

        // $recent->update([
        //     'sisa_stok' => $request->jumlah
        // ]);

        if ($penyesuaian->wasRecentlyCreated) {
            return new JsonResponse(['message' => 'data berhasil disimpan'], 201);
        }
        if ($penyesuaian->wasChanged()) {
            return new JsonResponse(['message' => 'data berhasil disimpan'], 201);
        }

        return new JsonResponse(['message' => 'Tidak ada perubahan data'], 417);
    }
    public function updateStokFisik(Request $request)
    {

        $barang = BarangRS::where('kode', $request->kode_rs)->first();
        // $stok = MonthlyStokUpdate::selectRaw('kode_rs, kode_ruang, sisa_stok, sum(sisa_stok) as totalStok')
        //     ->where('tanggal', $request->tanggal)
        //     ->where('kode_rs', $barang->kode)
        //     ->groupBy('kode_rs', 'kode_ruang')
        //     ->get();
        // if (!count($stok)) {
        //     return new JsonResponse(['message' => 'Tidak ada data Stok Opname untuk barang ini'], 410);
        // }
        // return new JsonResponse([
        //     'requset' => $request->all(),
        //     'stok' => $stok,
        //     'barang' => $barang
        // ]);

        $data = StokOpname::updateOrCreate([
            'tanggal' => $request->tanggal,
            'kode_rs' => $barang->kode,
            'kode_depo' => $barang->kode_depo,
        ], [
            'stok_fisik' => $request->stok_fisik
        ]);
        // $data = MonthlyStokUpdate::find($request->id);
        // $data->update([
        //     'stok_fisik' => $request->stok_fisik
        // ]);

        if ($data->wasChanged()) {
            return new JsonResponse(['message' => 'data berhasil disimpan', 'data' => $data], 200);
        }
        if ($data->wasRecentlyCreated) {
            return new JsonResponse(['message' => 'data berhasil Dibuat', 'data' => $data], 201);
        }
        return new JsonResponse([$request->all(), 'message' => 'Data tidak berubah'], 410);
    }
}
