<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Stok;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaandeporinci;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaanresep;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaanresepracikan;
use App\Models\Simrs\Penunjang\Farmasinew\Harga\DaftarHarga;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Obatoperasi\PersiapanOperasi;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\PenyesuaianStok;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokopname;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\StokOpnameFisik;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokrel;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\TutupOpname;
use App\Models\Simrs\Penunjang\Farmasinew\Stokreal;
use App\Models\Simrs\Ranap\Mruangranap;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StokrealController extends Controller
{
    public static function stokreal($nopenerimaan, $request)
    {
        //return ($request->kdobat);
        $simpanstokreal = Stokrel::updateOrCreate(
            [
                'nopenerimaan' => $nopenerimaan,
                'kdobat' => $request->kdobat,
                'kdruang' => $request->kdruang,
                'nobatch' => $request->no_batch,
            ],
            [
                'tglexp' => $request->tgl_exp,
                'harga' => $request->harga_netto_kecil,
                'tglpenerimaan' => $request->tglpenerimaan,
                'jumlah' => $request->jml_terima_k,
                'flag' => 1

            ]
        );
        if (!$simpanstokreal) {
            return 500;
        }
        // $ada = Stokrel::where('nopenerimaan', $nopenerimaan)
        //     ->where('kdobat', $request->kdobat)
        //     ->where('kdruang', $request->kdruang)
        //     ->where('nobatch', $request->no_batch)
        //     ->where('harga', $request->harga_netto_kecil)
        //     ->where('flag', '1')
        //     ->first();
        // if ($ada) {
        //     $prev = (float)$ada->jumlah;
        //     $rec = (float)$request->jml_terima_k;
        //     $sub = $rec + $prev;
        //     $ada->update(['jumlah' => $sub]);
        // } else {
        //     $simpanstokreal = Stokrel::create(
        //         [
        //             'nopenerimaan' => $nopenerimaan,
        //             'kdobat' => $request->kdobat,
        //             'kdruang' => $request->kdruang,
        //             'nobatch' => $request->no_batch,
        //             'harga' => $request->harga_netto_kecil,
        //             'tglexp' => $request->tgl_exp,
        //             'tglpenerimaan' => $request->tglpenerimaan,
        //             'jumlah' => $request->jml_terima_k,
        //             'flag' => 1

        //         ]
        //     );
        //     if (!$simpanstokreal) {
        //         return 500;
        //     }
        // }
        return 200;
    }

    public static function updatestokgudangdandepo($request)
    {
        $jml_dikeluarkan = (int) $request->jumlah_diverif;
        $totalstok = Stokrel::select(DB::raw('sum(stokreal.jumlah) as totalstok'))
            ->where('kdobat', $request->kdobat)
            ->where('kdruang', $request->kdruang)
            ->where('jumlah', '!=', 0)
            ->groupBy('kdobat', 'kdruang')
            ->first();

        $totalstokx = (int) $totalstok->totalstok;
        if ($jml_dikeluarkan > $totalstokx) {
            return new JsonResponse(['message' => 'Maaf Stok Anda Tidak Mencukupi...!!!'], 500);
        }

        $caristokgudang = Stokrel::where('kdobat', $request->kdobat)
            ->where('kdruang', $request->kdruang)
            ->where('jumlah', '!=', 0)
            ->orderBy('tglexp')
            ->get();

        foreach ($caristokgudang as $val) {
            $jmlstoksatuan = $val['jumlah'];
            $nopenerimaan = $val['nopenerimaan'];
        }

        return [$nopenerimaan, $jmlstoksatuan];
    }

    public function insertsementara(Request $request)
    {
        $sementara = date('ymdhis');
        $ruang = $request->kdruang;
        if ($ruang === 'Gd-05010100') {
            $kdruang = 'GO';
        } elseif ($ruang === 'Gd-03010100') {
            $kdruang = 'FS';
        } elseif ($ruang === 'Gd-04010102') {
            $kdruang = 'DRI';
        } elseif ($ruang === 'Gd-05010101') {
            $kdruang = 'DRJ';
        } else {
            $kdruang = 'DKO';
        }
        $notrans = $sementara . '-' . $kdruang;

        // $simpanstok = Stokrel::create(
        //     [
        //         'nopenerimaan' => $request->notrans ?? $notrans,
        //         'tglpenerimaan' => $request->tglpenerimaan ?? date('Y-m-d H:i:s'),
        //         'kdobat' => $request->kdobat,
        //         'jumlah' => $request->jumlah,
        //         'kdruang' => $request->kdruang,
        //         'harga' => $request->harga ?? '',
        //         'tglexp' => $request->tglexp ?? '',
        //         'nobatch' => $request->nobatch ?? '',
        //     ]
        // );

        $simpanstokopname = Stokopname::create(
            [
                'nopenerimaan' => $request->notrans ?? $notrans,
                'tglpenerimaan' => $request->tglpenerimaan ?? date('Y-m-d H:i:s'),
                'kdobat' => $request->kdobat,
                'jumlah' => $request->jumlah,
                'kdruang' => $request->kdruang,
                'harga' => $request->harga ?? '',
                'tglexp' => $request->tglexp ?? '',
                'nobatch' => $request->nobatch ?? '',
                'tglopname' => '2023-12-31 23:59:59'
            ]
        );
        return new JsonResponse(
            [
                'datastok' => $simpanstokopname,
                'message' => 'Stok Berhasil Disimpan...!!!'
            ],
            200
        );
    }
    public function updatestoksementara(Request $request)
    {
        $cari = Stokopname::where('id', $request->id)->first();
        $cari->jumlah = $request->jumlah ?? '';
        $cari->harga = $request->harga ?? '';
        $cari->tglexp = $request->tglexp ?? '';
        $cari->nobatch = $request->nobatch ?? '';
        $cari->save();

        return new JsonResponse(['message' => 'Stok Berhasil Disimpan...!!!'], 200);
    }
    public function updatehargastok(Request $request)
    {
        $cari = Stokreal::where('id', $request->id)->first();
        $penyesuaian = PenyesuaianStok::create([
            'tgl_penyesuaian' => date('Y-m-d H:i:s'),
            'stokreal_id' => $request->id,
            'nopenerimaan' => $request->nopenerimaan,
            'kdobat' => $cari->kdobat,
            'awal' => $request->awal,
            'penyesuaian' => $request->penyesuaian,
            'akhir' => $request->akhir,

        ]);

        $cari->jumlah = $request->akhir ?? 0;
        $cari->harga = $request->harga ?? 0;
        $cari->tglexp = $request->tglexp ?? '';
        $cari->nobatch = $request->nobatch ?? '';
        $cari->save();

        return new JsonResponse(['message' => 'Stok Berhasil Disimpan...!!!'], 200);
    }

    public function liststokreal()
    {
        if (!request('from')) return [];
        $kdruang = request('kdruang');


        $stokreal = Mobatnew::select(
            'stokopname.*',
            'new_masterobat.kd_obat',
            'new_masterobat.nama_obat',
            'new_masterobat.satuan_k',
            'new_masterobat.status_fornas',
            'new_masterobat.status_forkid',
            'new_masterobat.status_generik',
            'new_masterobat.kelompok_psikotropika',
            'new_masterobat.status_kronis',
            'new_masterobat.status_konsinyasi',
            'new_masterobat.gudang',
            'stokopname.id as idx',
            DB::raw('sum(stokopname.jumlah) as total'),
            DB::raw('sum(stokopname.fisik) as totalFisik')
        )->where('stokopname.flag', '')
            ->leftjoin('stokopname', 'new_masterobat.kd_obat', 'stokopname.kdobat')
            ->where('stokopname.kdruang', $kdruang)
            ->where(function ($x) {
                $x->where('stokopname.nopenerimaan', 'like', '%' . request('q') . '%')
                    ->orwhere('stokopname.kdobat', 'like', '%' . request('q') . '%')
                    ->orwhere('new_masterobat.nama_obat', 'like', '%' . request('q') . '%');
            })
            ->when(request('from'), function ($q) {
                $q->whereBetween('tglopname', [request('from') . ' 23:00:00', request('to') . ' 23:59:59']);
            })
            // ->with('tutup')
            ->groupBy('stokopname.kdobat', 'stokopname.kdruang')
            ->orderBy('new_masterobat.nama_obat', 'ASC')
            ->paginate(request('per_page'));
        // }
        $raw = collect($stokreal);
        $data['data'] = $raw['data'];
        $data['meta'] = $raw->except('data');
        // $data['diff'] = $diff;
        // $data['stokreal'] = $stokreal;

        return new JsonResponse($data);
    }
    public function liststokopname()
    {
        if (!request('from')) return [];

        $now = Carbon::create(request('from'))->format('Y-m');
        $kdop = Stokopname::select('kdobat')->where('tglopname', 'LIKE', '%' . $now . '%')->distinct('kdobat')->pluck('kdobat');
        $fis = StokOpnameFisik::select('kdobat')->where('tglopname', 'LIKE', '%' . $now . '%')->distinct('kdobat')->pluck('kdobat');
        $sm = array_unique(array_merge($kdop->toArray(), $fis->toArray()));
        $stokreal = Mobatnew::select(
            'kd_obat',
            'nama_obat',
            'satuan_k',
            'status_fornas',
            'status_forkid',
            'status_generik',
            'kelompok_psikotropika',
            'status_kronis',
            'status_konsinyasi',
            'gudang',
        )->with([
            'onestok' => function ($q) {
                $q->select(
                    'kdobat',
                    'kdruang',
                    'harga',
                    'tglexp',
                    DB::raw('sum(jumlah) as total')
                )
                    ->where('kdruang', request('kdruang'))
                    ->groupBy('kdobat', 'kdruang');
            },
            'oneopname' => function ($q) {
                $q->select(
                    'kdobat',
                    'kdruang',
                    'harga',
                    'tglexp',
                    'tglopname',
                    DB::raw('sum(jumlah) as total')
                )->whereBetween('tglopname', [request('from') . ' 23:00:00', request('to') . ' 23:59:59'])
                    ->where('kdruang', request('kdruang'))
                    ->groupBy('kdobat', 'kdruang');
            },
            'onefisik' => function ($q) {
                $q->whereBetween('tglopname', [request('from') . ' 23:00:00', request('to') . ' 23:59:59'])
                    ->where('kdruang', request('kdruang'));
            }
            // 'oneopname'

        ])
            ->where('flag', '')
            ->where(function ($x) {
                $x->where('nama_obat', 'like', '%' . request('q') . '%')
                    ->orWhere('kd_obat', 'like', '%' . request('q') . '%');
            })
            ->when(count($sm) > 0 && $now != date('Y-m', strtotime(request('from'))), function ($q) use ($sm) {
                $q->whereIn('kd_obat', $sm);
            })
            ->orderBy('nama_obat', 'ASC')
            ->paginate(request('per_page'));
        // }
        $raw = collect($stokreal);
        $data['data'] = $raw['data'];
        $data['meta'] = $raw->except('data');
        $data['now'] = date('Y-m-d H:i:s');
        $data['nowx'] = $now;
        $data['sm'] = $sm;
        $data['cond'] = $now != date('Y-m', strtotime(request('from')));
        // $data['stokreal'] = $stokreal;

        return new JsonResponse($data);
    }
    public function listStokSekarang()
    {
        $kdruang = request('kdruang');
        $stokreal = Stokreal::select(
            'stokreal.id as idx',
            'stokreal.kdruang',
            'stokreal.jumlah',
            'stokreal.tglexp',
            'stokreal.kdobat',
            'new_masterobat.kd_obat',
            'new_masterobat.nama_obat',
            'new_masterobat.satuan_k',
            'new_masterobat.status_fornas',
            'new_masterobat.status_forkid',
            'new_masterobat.status_generik',
            'new_masterobat.kelompok_psikotropika',
            'new_masterobat.status_kronis',
            'new_masterobat.sistembayar',
            'new_masterobat.status_konsinyasi',
            'new_masterobat.gudang',
            DB::raw('sum(stokreal.jumlah) as total')
        )->where('stokreal.flag', '')
            ->leftjoin('new_masterobat', 'new_masterobat.kd_obat', 'stokreal.kdobat')
            ->where('stokreal.kdruang', $kdruang)
            // ->where('stokreal.jumlah', '>', 0)
            ->where(function ($x) {
                $x->orwhere('stokreal.kdobat', 'like', '%' . request('q') . '%')
                    ->orwhere('new_masterobat.nama_obat', 'like', '%' . request('q') . '%');
            })
            ->with([
                'onepermintaandeporinci' => function ($q) {
                    $q->select(
                        'permintaan_r.no_permintaan',
                        'permintaan_r.kdobat',
                        // 'permintaan_r.jumlah_minta',
                        'permintaan_h.tujuan as kdruang',
                        DB::raw('sum(permintaan_r.jumlah_minta) as jumlah_minta')
                    )
                        ->leftJoin('permintaan_h', 'permintaan_h.no_permintaan', '=', 'permintaan_r.no_permintaan')
                        ->leftJoin('mutasi_gudangdepo', function ($anu) {
                            $anu->on('permintaan_r.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                                ->on('permintaan_r.kdobat', '=', 'mutasi_gudangdepo.kd_obat');
                        })
                        ->where('permintaan_h.tujuan', request('kdruang'))
                        // ->whereNull('mutasi_gudangdepo.kd_obat')
                        ->whereIn('permintaan_h.flag', ['', '1', '2'])
                        ->groupBy('permintaan_r.kdobat');
                },
                'oneperracikan' => function ($q) {
                    $q->select(
                        // 'resep_keluar_racikan_r.kdobat as kdobat',
                        'resep_permintaan_keluar_racikan.kdobat as kdobat',
                        'resep_keluar_h.depo as kdruang',
                        DB::raw('sum(resep_permintaan_keluar_racikan.jumlah) as jumlah')
                    )
                        ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', '=', 'resep_permintaan_keluar_racikan.noresep')
                        ->where('resep_keluar_h.depo', request('kdruang'))
                        ->whereIn('resep_keluar_h.flag', ['', '1', '2']);
                },
                'onepermintaan' => function ($q) {
                    $q->select(
                        // 'resep_keluar_r.kdobat as kdobat',
                        'resep_permintaan_keluar.kdobat as kdobat',
                        'resep_keluar_h.depo as kdruang',
                        DB::raw('sum(resep_permintaan_keluar.jumlah) as jumlah')
                    )
                        ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_permintaan_keluar.noresep')
                        ->where('resep_keluar_h.depo', request('kdruang'))
                        ->whereIn('resep_keluar_h.flag', ['', '1', '2'])
                        ->groupBy('resep_permintaan_keluar.kdobat');
                },
                'oneobatkel' => function ($q) {
                    $q->select(
                        // 'resep_keluar_r.kdobat as kdobat',
                        'resep_keluar_r.kdobat as kdobat',
                        'resep_keluar_h.depo as kdruang',
                        DB::raw('sum(resep_keluar_r.jumlah) as jumlah')
                    )
                        ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_keluar_r.noresep')
                        ->where('resep_keluar_h.depo', request('kdruang'))
                        ->where('resep_keluar_h.flag', '2')
                        ->where('resep_keluar_r.jumlah', '>', 0)
                        ->groupBy('resep_keluar_r.kdobat');
                },
                'oneobatkelracikan' => function ($q) {
                    $q->select(
                        // 'resep_keluar_r.kdobat as kdobat',
                        'resep_keluar_racikan_r.kdobat as kdobat',
                        'resep_keluar_h.depo as kdruang',
                        DB::raw('sum(resep_keluar_racikan_r.jumlah) as jumlah')
                    )
                        ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_keluar_racikan_r.noresep')
                        ->where('resep_keluar_h.depo', request('kdruang'))
                        ->where('resep_keluar_h.flag', '2')
                        ->where('resep_keluar_racikan_r.jumlah', '>', 0)
                        ->groupBy('resep_keluar_racikan_r.kdobat');
                },
                'persiapanrinci' => function ($res) {
                    $res->select(
                        'persiapan_operasi_rincis.kd_obat',
                        DB::raw('sum(persiapan_operasi_rincis.jumlah_minta) as jumlah'),
                    )
                        ->leftJoin('persiapan_operasis', 'persiapan_operasis.nopermintaan', '=', 'persiapan_operasi_rincis.nopermintaan')
                        ->whereIn('persiapan_operasis.flag', ['', '1'])
                        ->groupBy('persiapan_operasi_rincis.kd_obat');
                },
            ])
            ->groupBy('stokreal.kdobat', 'stokreal.kdruang')
            ->orderBy('new_masterobat.nama_obat', 'ASC')
            ->orderBy('stokreal.tglexp', 'ASC')
            ->paginate(request('per_page'));
        $stokreal->append('harga');
        $datastok = $stokreal->map(function ($xxx) use ($kdruang) {
            $stolreal = $xxx->total;
            $jumlahper = $kdruang === 'Gd-04010103' ? $xxx['persiapanrinci'][0]->jumlah ?? 0 : 0;
            $jumlahtrans = $xxx['oneperracikan']->jumlah ?? 0;
            $jumlahtransx = $xxx['onepermintaan']->jumlah ?? 0;
            $keluar = $xxx['oneobatkel']->jumlah ?? 0;
            $keluarRa = $xxx['oneobatkelracikan']->jumlah ?? 0;
            $permintaanobatrinci = $xxx['onepermintaandeporinci']->jumlah_minta ?? 0; // mutasi antar depo
            $stokalokasi = (float) $stolreal - (float) $permintaanobatrinci - (float) $jumlahtrans - (float) $jumlahtransx - (int)$jumlahper + (float)$keluar + (float)$keluarRa;
            $xxx['stokalokasi'] = $stokalokasi;
            $xxx['permintaantotal'] = $permintaanobatrinci;
            $xxx['lain'] = [
                'jumlahper' => $jumlahper,
                'jumlahtrans' => $jumlahtrans,
                'jumlahtransx' => $jumlahtransx,
                'keluar' => $keluar,
                'keluarRa' => $keluarRa,
            ];
            return $xxx;
        });
        return new JsonResponse([
            'data' => $datastok,
            'meta' => collect($stokreal)->except('data'),
        ]);
    }
    public function listStokMinDepo()
    {
        $kdruang = request('kdruang');
        $depos = ['Gd-03010101', 'Gd-04010102', 'Gd-04010103', 'Gd-05010101', 'Gd-04010104'];
        // $stokreal = Mobatnew::select(
        //     'stokreal.id as idx',
        //     'stokreal.kdruang',
        //     'stokreal.jumlah',
        //     'stokreal.tglexp',
        //     'stokreal.kdobat',
        //     'new_masterobat.kd_obat',
        //     'new_masterobat.nama_obat',
        //     'new_masterobat.satuan_k',
        //     'new_masterobat.status_fornas',
        //     'new_masterobat.status_forkid',
        //     'new_masterobat.status_generik',
        //     'new_masterobat.gudang',
        //     'min_max_ruang.min as minvalue',
        //     DB::raw('sum(COALESCE(stokreal.jumlah,0)) as total'),
        //     DB::raw('((min_max_ruang.min - sum(COALESCE(stokreal.jumlah,0))) / min_max_ruang.min * 100) as persen')

        // )
        //     ->leftjoin('stokreal', function ($x) use ($kdruang) {
        //         $x->on('new_masterobat.kd_obat', '=', 'stokreal.kdobat')
        //             ->where('stokreal.flag', '=', '')
        //             // ->where('stokreal.kdruang', '=', $kdruang)
        //             ->where(function ($query) use ($kdruang) {
        //                 $query->where('stokreal.kdruang', '=', $kdruang)
        //                     ->orWhereNull('stokreal.kdruang'); // Pastikan stokreal tetap diikutkan meskipun NULL
        //             })
        //         ;
        //     })
        //     ->leftjoin('min_max_ruang', function ($anu) use ($kdruang) {
        //         $anu->on('min_max_ruang.kd_obat', '=', 'new_masterobat.kd_obat')
        //             ->where('min_max_ruang.kd_ruang', '=', $kdruang);
        //     })

        //     ->where(function ($x) {
        //         $x->orwhere('new_masterobat.kd_obat', 'like', '%' . request('q') . '%')
        //             ->orwhere('new_masterobat.nama_obat', 'like', '%' . request('q') . '%');
        //     })
        //     ->when(in_array($kdruang, $depos), function ($q) {
        //         $q->whereNotNull('stokreal.jumlah')
        //             ->whereNotNull('min_max_ruang.min')
        //             ->where('min_max_ruang.max', '>', 1) // permintaan depo rajal
        //             ->havingRaw('sum(stokreal.jumlah) < min_max_ruang.min');
        //     }, function ($q) {
        //         $q->whereNotNull('min_max_ruang.min')
        //             ->havingRaw('sum(COALESCE(stokreal.jumlah, 0)) < min_max_ruang.min');
        //     })

        //     ->with([
        //         'permintaanobatrinci' => function ($pr) use ($kdruang) {
        //             $pr->select(
        //                 'permintaan_r.kdobat',
        //                 'permintaan_r.jumlah_minta',
        //                 'permintaan_h.dari',
        //                 'permintaan_h.flag',
        //                 'permintaan_h.no_permintaan',
        //                 'mutasi_gudangdepo.jml',
        //             )
        //                 ->leftJoin('permintaan_h', 'permintaan_h.no_permintaan', 'permintaan_r.no_permintaan')
        //                 ->leftJoin('mutasi_gudangdepo', function ($anu) {
        //                     $anu->on('permintaan_r.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
        //                         ->on('permintaan_r.kdobat', '=', 'mutasi_gudangdepo.kd_obat');
        //                 })
        //                 ->where('permintaan_h.dari', $kdruang)
        //                 ->whereIn('permintaan_h.flag', ['', '1', '2', '3']);
        //         }
        //     ])
        //     ->groupBy(DB::raw('IFNULL(stokreal.kdobat, "00")'))
        //     ->groupBy(DB::raw('IFNULL(stokreal.kdruang, "' . addslashes($kdruang) . '")'))

        //     ->orderBy(DB::raw('(min_max_ruang.min - sum(COALESCE(stokreal.jumlah,0))) / min_max_ruang.min * 100'), 'DESC')
        //     ->orderBy(DB::raw('min_max_ruang.min'), 'DESC')
        //     ->paginate(request('per_page'));



        $stokreal = Mobatnew::select(
            'stokreal.id as idx',
            'stokreal.kdruang',
            'stokreal.jumlah',
            'stokreal.tglexp',
            'stokreal.kdobat',
            'new_masterobat.kd_obat',
            'new_masterobat.nama_obat',
            'new_masterobat.satuan_k',
            'new_masterobat.status_fornas',
            'new_masterobat.status_forkid',
            'new_masterobat.status_generik',
            'new_masterobat.gudang',
            'min_max_ruang.min as minvalue',
            DB::raw('SUM(COALESCE(stokreal.jumlah, 0)) as total'),
            DB::raw('((min_max_ruang.min - SUM(COALESCE(stokreal.jumlah, 0))) / min_max_ruang.min * 100) as persen')
        )
            ->leftJoin('stokreal', function ($x) use ($kdruang) {
                $x->on('new_masterobat.kd_obat', '=', 'stokreal.kdobat')
                    ->where('stokreal.flag', '=', '')
                    ->where(function ($query) use ($kdruang) {
                        $query->where('stokreal.kdruang', '=', $kdruang)
                            ->orWhereNull('stokreal.kdruang'); // Memastikan stokreal tetap masuk walau NULL
                    });
            })
            ->leftJoin('min_max_ruang', function ($anu) use ($kdruang) {
                $anu->on('min_max_ruang.kd_obat', '=', 'new_masterobat.kd_obat')
                    ->where('min_max_ruang.kd_ruang', '=', $kdruang);
            })
            ->where(function ($x) {
                $x->orWhere('new_masterobat.kd_obat', 'like', '%' . request('q') . '%')
                    ->orWhere('new_masterobat.nama_obat', 'like', '%' . request('q') . '%');
            })
            ->when(in_array($kdruang, $depos), function ($q) {
                $q->whereNotNull('stokreal.jumlah')
                    ->whereNotNull('min_max_ruang.min')
                    ->where('min_max_ruang.max', '>', 1)
                    ->havingRaw('SUM(COALESCE(stokreal.jumlah, 0)) < min_max_ruang.min');
            }, function ($q) use ($kdruang) {

                $q->whereRaw('(SELECT COALESCE(SUM(sr.jumlah), 0)
                FROM stokreal sr
                WHERE sr.kdobat = new_masterobat.kd_obat
                AND sr.kdruang = ?) < min_max_ruang.min', [$kdruang]);
            })
            ->with([
                'permintaanobatrinci' => function ($pr) use ($kdruang) {
                    $pr->select(
                        'permintaan_r.kdobat',
                        'permintaan_r.jumlah_minta',
                        'permintaan_h.dari',
                        'permintaan_h.flag',
                        'permintaan_h.no_permintaan',
                        'mutasi_gudangdepo.jml'
                    )
                        ->leftJoin('permintaan_h', 'permintaan_h.no_permintaan', 'permintaan_r.no_permintaan')
                        ->leftJoin('mutasi_gudangdepo', function ($anu) {
                            $anu->on('permintaan_r.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                                ->on('permintaan_r.kdobat', '=', 'mutasi_gudangdepo.kd_obat');
                        })
                        ->where('permintaan_h.dari', $kdruang)
                        ->whereIn('permintaan_h.flag', ['', '1', '2', '3']);
                }
            ])
            ->groupBy(DB::raw('COALESCE(stokreal.kdobat, new_masterobat.kd_obat)'))
            ->groupBy(DB::raw('COALESCE(stokreal.kdruang, "' . addslashes($kdruang) . '")'))
            ->orderBy(DB::raw('(min_max_ruang.min - SUM(COALESCE(stokreal.jumlah, 0))) / min_max_ruang.min * 100'), 'DESC')
            ->orderBy(DB::raw('min_max_ruang.min'), 'DESC')
            ->paginate(request('per_page'));



        $stokreal->append('harga');

        $nu = collect($stokreal);
        return new JsonResponse([
            'data' => $nu['data'],
            'meta' => collect($stokreal)->except('data'),
        ]);
    }

    public static function updatestokdepo($request)
    {
        $kembalikan = Stokreal::select(
            'stokreal.nopenerimaan as nopenerimaan',
            'stokreal.kdobat as kdobat',
            'stokreal.harga as harga',
            DB::raw('stokreal.jumlah + retur_penjualan_r.jumlah_retur as masuk')
        )
            ->leftjoin('retur_penjualan_r', function ($e) {
                $e->on('retur_penjualan_r.nopenerimaan', 'stokreal.nopenerimaan')
                    ->on('retur_penjualan_r.kdobat', 'stokreal.kdobat')
                    ->on('retur_penjualan_r.harga_beli', 'stokreal.harga');
            })
            ->where('retur_penjualan_r.kdobat', $request->kdobat)
            ->where('stokreal.kdruang', $request->koderuang)
            ->where('retur_penjualan_r.noresep', $request->noresep)
            ->get();
        foreach ($kembalikan as $e) {
            $updatestok = Stokreal::where('nopenerimaan', $e->nopenerimaan)
                ->where('kdobat', $e->kdobat)
                ->where('stokreal.kdruang', $request->koderuang)
                ->where('harga', $e->harga)->first();
            $updatestok->jumlah = $e->masuk;
            $updatestok->save();
        }
        return 200;
    }
    public static function newupdatestokdepo($request)
    {
        $kembalikan = Stokreal::select(
            'stokreal.nopenerimaan as nopenerimaan',
            'stokreal.kdobat as kdobat',
            'stokreal.harga as harga',
            // 'stokreal.jumlah as jumlah',
            DB::raw('(stokreal.jumlah + retur_penjualan_r.jumlah_retur) as masuk')
        )
            ->leftjoin('retur_penjualan_r', function ($e) {
                $e->on('retur_penjualan_r.nopenerimaan', 'stokreal.nopenerimaan')
                    ->on('retur_penjualan_r.kdobat', 'stokreal.kdobat')
                    ->on('retur_penjualan_r.harga_beli', 'stokreal.harga');
            })
            ->where('retur_penjualan_r.kdobat', $request->kdobat)
            ->where('stokreal.kdruang', $request->koderuang)
            ->where('retur_penjualan_r.noresep', $request->noresep)
            // ->where('stokreal.jumlah ', '>', 0)
            // ->latest('stokreal.id')
            ->first();
        // foreach ($kembalikan as $e) {
        $updatestok = Stokreal::where('nopenerimaan', $kembalikan->nopenerimaan)
            ->where('kdobat', $kembalikan->kdobat)
            ->where('kdruang', $request->koderuang)
            ->where('harga', $kembalikan->harga)
            ->first();
        $updatestok->jumlah = $kembalikan->masuk;
        $updatestok->save();
        // }
        return 200;
    }
    public function dataAlokasi()
    {
        $transNonRacikan = Permintaanresep::select(
            'resep_permintaan_keluar.noreg',
            'resep_permintaan_keluar.noresep',
            'resep_permintaan_keluar.kdobat as kdobat',
            'resep_keluar_h.depo as kdruang',
            'resep_keluar_h.ruangan as dari',
            'resep_keluar_h.tgl',
            'resep_keluar_h.tgl_permintaan',
            'resep_keluar_h.flag',
            'resep_permintaan_keluar.jumlah'
            // DB::raw('sum(resep_permintaan_keluar.jumlah) as jumlah')
        )
            ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_permintaan_keluar.noresep')
            ->where('resep_keluar_h.depo', request('kdruang'))
            ->where('resep_permintaan_keluar.kdobat', request('kdobat'))
            ->whereIn('resep_keluar_h.flag', ['', '1', '2'])
            // ->with(['head'])
            // ->with(['head' => function ($he) {
            //     $he->select('noresep', 'noreg');
            // }])
            // ->groupBy('resep_permintaan_keluar.kdobat')
            ->get();
        $transRacikan = Permintaanresepracikan::select(
            'resep_permintaan_keluar_racikan.noreg',
            'resep_permintaan_keluar_racikan.noresep',
            'resep_permintaan_keluar_racikan.kdobat as kdobat',
            'resep_keluar_h.depo as kdruang',
            'resep_keluar_h.ruangan as dari',
            'resep_keluar_h.tgl',
            'resep_keluar_h.tgl_permintaan',
            'resep_keluar_h.flag',
            'resep_permintaan_keluar_racikan.jumlah'
            // DB::raw('sum(resep_permintaan_keluar_racikan.jumlah) as jumlah')
        )
            ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_permintaan_keluar_racikan.noresep')
            ->where('resep_keluar_h.depo', request('kdruang'))
            ->where('resep_permintaan_keluar_racikan.kdobat', request('kdobat'))
            ->whereIn('resep_keluar_h.flag', ['', '1', '2'])
            // ->groupBy('resep_permintaan_keluar_racikan.kdobat')
            ->get();

        $permintaan = Permintaandeporinci::select(
            'permintaan_h.tgl_permintaan as tgl',
            'permintaan_h.no_permintaan',
            'permintaan_h.flag',
            'permintaan_h.dari',
            'permintaan_r.kdobat',
            'permintaan_r.jumlah_minta as jumlah'
            // DB::raw('sum(permintaan_r.jumlah_minta) as allpermintaan')
        )
            ->leftJoin('permintaan_h', 'permintaan_h.no_permintaan', '=', 'permintaan_r.no_permintaan')
            // biar yang ada di tabel mutasi ga ke hitung
            ->leftJoin('mutasi_gudangdepo', function ($anu) {
                $anu->on('permintaan_r.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                    ->on('permintaan_r.kdobat', '=', 'mutasi_gudangdepo.kd_obat');
            })
            ->whereNull('mutasi_gudangdepo.kd_obat')

            ->where('permintaan_h.tujuan', request('kdruang'))
            ->where('permintaan_r.kdobat', request('kdobat'))
            ->whereIn('permintaan_h.flag', ['', '1', '2'])
            // ->groupBy('permintaan_r.kdobat')
            ->get();
        if (request('kdruang') === 'Gd-04010103') {
            $operasi = PersiapanOperasi::select(
                'persiapan_operasis.noreg',
                'persiapan_operasis.nopermintaan',
                'persiapan_operasis.tgl_permintaan',
                'persiapan_operasis.flag',
                'persiapan_operasi_rincis.jumlah_minta as jumlah',
                'persiapan_operasi_rincis.kd_obat',
            )
                ->leftJoin('persiapan_operasi_rincis', 'persiapan_operasi_rincis.nopermintaan', 'persiapan_operasis.nopermintaan')
                ->whereIn('persiapan_operasis.flag', ['', '1'])
                ->where('persiapan_operasi_rincis.kd_obat', request('kdobat'))
                ->with(
                    'list:rs1,rs4,rs14',
                    'list.sistembayar:rs1,rs2,groups',
                    'list.kunjunganranap:rs1,rs5,rs6',
                    'list.kunjunganranap.relmasterruangranap:rs1,rs2',
                    'list.kunjunganrajal:rs1,rs8',
                    'list.kunjunganrajal.relmpoli:rs1,rs2'
                )
                ->get();
        }

        $data = [
            // 'req' => request()->all(),
            'transNonRacikan' => $transNonRacikan,
            'transRacikan' => $transRacikan,
            'permintaan' => $permintaan,
            'operasi' => $operasi ?? null,
        ];
        return new JsonResponse($data);
    }
    public function getRuangRanap()
    {
        $data = Mruangranap::select(
            'rs1 as kdruang',
            'rs2 as nama',
        )
            ->get();
        return new JsonResponse($data);
    }
    public function obatMauDisesuaikan()
    {
        $data = Stokrel::where('kdobat', request('kdobat'))
            ->where('kdruang', request('kdruang'))
            ->where('nopenerimaan', 'LIKE', '%awal%')
            ->get();
        // $data->append('harga');
        return new JsonResponse([
            'data' => $data,
            'req' => request()->all(),
        ]);
    }

    public function simpanFisik(Request $request)
    {

        $request->validate([
            'kd_obat' => 'required',
            'kdruang' => 'required',
        ]);

        $data = StokOpnameFisik::updateOrCreate(
            [
                'kdobat' => $request->kd_obat,
                'kdruang' => $request->kdruang,
                'tglopname' => $request->tglopname,
            ],
            [
                'jumlah' => $request->fisik,
                'keterangan' => $request->keterangan,

            ]
        );

        return new JsonResponse([
            'message' => 'Stok Fisik sudah disimpan',
            'data' => $data,
            'req' => $request->all()
        ]);
    }
    public function simpanKeterangan(Request $request)
    {
        // return $request->all();
        $request->validate([
            'idfisik' => 'required',
        ]);

        $data = StokOpnameFisik::find($request->idfisik);
        if (!$data) {
            return new JsonResponse([
                'message' => 'Stok Fisik tidak ditemukan',
            ]);
        }
        $data->update([
            'keterangan' => $request->keterangan
        ]);

        return new JsonResponse([
            'message' => 'Keterangan sudah disimpan',
            'data' => $data,
            'req' => $request->all()
        ]);
    }
    public function simpanBaru(Request $request)
    {
        $data = StokOpnameFisik::updateOrCreate(
            [
                'kdobat' => $request->kd_obat,
                'kdruang' => $request->kdruang,
                'tglopname' => $request->tglopname,
            ],
            [
                'jumlah' => $request->fisik,

            ]
        );


        return new JsonResponse([
            'message' => 'Stok Fisik sudah disimpan',
            'data' => $data,
            'req' => $request->all()
        ]);
    }

    public function tutupOpname(Request $request)
    {
        $data = TutupOpname::firstOrCreate(
            [
                'tglopname' => $request->tglopname
            ],
            [
                'status' => '1'
            ]
        );
        return new JsonResponse([
            'message' => 'Stok Opname Sudah Ditutup',
            'data' => $data,
            'req' => $request->all()
        ]);
    }
    public function listBlangko()
    {
        $from = request('from') . ' 00:00:00';
        $to = request('to') . ' 23:59:59';
        $data = Mobatnew::select('kd_obat', 'nama_obat', 'satuan_k')
            ->with([
                'onestok' => function ($q) {
                    $q->select(
                        'kdobat',
                        'kdruang',
                        DB::raw('sum(jumlah) as total')
                    )
                        ->where('kdruang', request('kdruang'))
                        ->groupBy('kdobat', 'kdruang');
                },
                'oneopname' => function ($q) {
                    $q->select(
                        'kdobat',
                        'kdruang',
                        'tglopname',
                        DB::raw('sum(jumlah) as total')
                    )->whereBetween('tglopname', [request('from') . ' 23:00:00', request('to') . ' 23:59:59'])
                        ->where('kdruang', request('kdruang'))
                        ->groupBy('kdobat', 'kdruang');
                }
                // 'oneopname'

            ])
            ->orderBy('nama_obat', 'ASC')
            ->get();
        return new JsonResponse([
            'data' => $data,
            'from' => $from,
            'to' => $to,

        ]);
    }
}
