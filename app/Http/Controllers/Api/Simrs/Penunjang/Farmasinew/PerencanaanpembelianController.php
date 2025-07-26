<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Mminmaxobat;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\RencanabeliH;
use App\Models\Simrs\Penunjang\Farmasinew\RencanabeliR;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokrel;
use App\Models\Simrs\Penunjang\Farmasinew\Stokreal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PerencanaanpembelianController extends Controller
{
    public function perencanaanpembelian()
    {
        $perencanaapembelianobat = Mminmaxobat::select(
            'min_max_ruang.kd_obat as kd_obat',
            'new_masterobat.nama_obat as namaobat',
            'new_masterobat.status_generik as status_generik',
            'new_masterobat.status_fornas as status_fornas',
            'new_masterobat.status_forkid as status_forkid',
            'new_masterobat.satuan_k as satuan_k',
            'new_masterobat.sistembayar as sistembayar',
            'new_masterobat.gudang as gudang',
            DB::raw('sum(min_max_ruang.min) as summin'),
            DB::raw('sum(min_max_ruang.max) as summax'),
        )->with(
            [
                'perencanaanrinci' => function ($perencanaanrinci) {
                    $perencanaanrinci->select(
                        'kdobat',
                        DB::raw(
                            'sum(jumlahdirencanakan) as jumlah'
                        )
                    )->where('flag', '')
                        ->groupBy('kdobat');
                },
                'stokreal' => function ($stokreal) {
                    $stokreal->select(
                        'kdobat',
                        DB::raw(
                            'sum(jumlah) as jumlah'
                        )
                    )->where('kdruang', 'like', '%Gd%')
                        ->groupBy('kdobat');
                }
            ]
        )
            ->leftjoin('new_masterobat', 'new_masterobat.kd_obat', 'min_max_ruang.kd_obat')
            //    ->havingRaw('stok <= summin')
            ->where('min_max_ruang.kd_ruang', 'like', '%Gd%')
            ->where('new_masterobat.nama_obat', 'like', '%' . request('namaobat') . '%')
            ->groupby('min_max_ruang.kd_obat')
            ->get();
        $x = collect($perencanaapembelianobat);
        // $y = $x->where('stokreal.jumlah', '<=', 'summin');
        $y = $x->filter(function ($item, $key) {
            //$wew = $item->summin . ' min | stok ' . $item->stokreal[0];
            //    ->where($item->stokreal, '<=',  $item->summin);

            $item->stok = 0;
            foreach ($item->stokreal as $stokKey) {
                $item->stok = $stokKey->jumlah;
            }
            // if ($item->stok <= $item->summin) {
            return $item->stok <= $item->summin;
            // }
        });
        return new JsonResponse($y);
        // $xxx = FormatingHelper::session_user();
        // if ($xxx['kdruang'] === '') {
        //     $ruangan = ['', 'Gd-05010100', 'Gd-03010100'];
        // } else {
        //     $ruangan = ['', $xxx['kdruang']];
        // }

        // $perencanaapembelianobat = Mobatnew::select(
        //     'kd_obat',
        //     'nama_obat',
        //     'status_generik',
        //     'status_fornas',
        //     'status_forkid',
        //     'satuan_k',
        //     'sistembayar',
        //     'gudang'
        // )->with(
        //     [
        //         'stokrealgudang' => function ($stokrealgudang) {
        //             $stokrealgudang->select(
        //                 'stokreal.kdobat',
        //                 DB::raw(
        //                     'sum(stokreal.jumlah) as jumlah'
        //                 )
        //             )
        //                 ->whereIn(
        //                     'stokreal.kdruang',
        //                     ['Gd-03010100', 'Gd-05010100']
        //                 )
        //                 ->groupBy('stokreal.kdobat');
        //         },
        //         'stokrealgudangfs' => function ($stokrealgudangfs) {
        //             $stokrealgudangfs->select(
        //                 'stokreal.kdobat',
        //                 DB::raw(
        //                     'sum(stokreal.jumlah) as jumlah'
        //                 )
        //             )
        //                 ->where(
        //                     'stokreal.kdruang',
        //                     'Gd-03010100'
        //                 )
        //                 ->groupBy('stokreal.kdobat');
        //         },
        //         'stokrealgudangko' => function ($stokrealgudangko) {
        //             $stokrealgudangko->select(
        //                 'stokreal.kdobat',
        //                 DB::raw(
        //                     'sum(stokreal.jumlah) as jumlah'
        //                 )
        //             )
        //                 ->where(
        //                     'stokreal.kdruang',
        //                     'Gd-05010100'
        //                 )
        //                 ->groupBy('stokreal.kdobat');
        //         },
        //         'stokrealallrs' => function ($stokrealallrs) {
        //             $stokrealallrs->select(
        //                 'stokreal.kdobat',
        //                 DB::raw(
        //                     'sum(stokreal.jumlah) as jumlah'
        //                 )
        //             )->groupBy('stokreal.kdobat');
        //         },
        //         'stokmaxrs' => function ($stokmaxrs) {
        //             $stokmaxrs->select(
        //                 'min_max_ruang.kd_obat',
        //                 DB::raw(
        //                     'sum(min_max_ruang.max) as jumlah'
        //                 )
        //             )->groupBy('min_max_ruang.kd_obat');
        //         },
        //         'perencanaanrinci' => function ($perencanaanrinci) {
        //             $perencanaanrinci->select(
        //                 'kdobat',
        //                 DB::raw(
        //                     'sum(jumlahdirencanakan) as jumlah'
        //                 )
        //             )->where('flag', '')
        //                 ->groupBy('kdobat');
        //         },
        //         'stokmaxpergudang' => function ($stokmaxpergudang) use ($ruangan) {
        //             $stokmaxpergudang->select(
        //                 'kd_ruang',
        //                 'min_max_ruang.kd_obat',
        //                 'min_max_ruang.max as jumlah'
        //             )->whereIn('kd_ruang', $ruangan);
        //         },
        //     ]
        // )
        //     ->where(function ($obat) {
        //         $obat->where('nama_obat', 'Like', '%' . request('q') . '%')
        //             ->orWhere('kd_obat', 'Like', '%' . request('q') . '%');
        //     })
        //     ->where('flag', '')
        //     ->whereIn('gudang', $ruangan)
        //     ->orderBy('kd_obat')
        //     ->paginate(request('per_page'));

        // return new JsonResponse($perencanaapembelianobat);
    }

    public function viewrinci()
    {
        $viewrinci = Stokreal::select(
            'stokreal.kdobat as kdobat',
            'stokreal.kdruang as kdruang',
            'new_masterobat.nama_obat as namaobat',
            DB::raw('sum(stokreal.jumlah) as jumlah'),

        )->with(
            [
                'gudangdepo:kode,nama',
                'ruang:kode,uraian',
                // 'gudanggudangdepo.minmax'
            ]
        )
            ->leftjoin('new_masterobat', 'stokreal.kdobat', 'new_masterobat.kd_obat')
            ->groupby('stokreal.kdobat', 'stokreal.kdruang')
            ->where('stokreal.kdobat', request('kdobat'))
            ->where('stokreal.flag', '')
            // ->where('stokreal.kdruang', 'like', '%GD%')
            ->get();

        $viewrinciminmax = Mminmaxobat::where('kd_obat', request('kdobat'))
            // ->where('kd_ruang', 'like', '%GD%')
            ->with('gudang:kode,nama', 'ruang:kode,uraian')
            ->get();
        return new JsonResponse([
            'viewrincistok' => $viewrinci,
            'viewrinciminmax' => $viewrinciminmax,
        ]);
    }

    public function simpanrencanabeliobat(Request $request)
    {
        //$cekflag = RencanabeliR::where('kdobat', $request->kdobat)->where('flag', '')->count();
        $xxx = FormatingHelper::session_user();
        // $cekflag = RencanabeliH::select(
        //     'perencana_pebelian_h.no_rencbeliobat as notrans',
        //     'perencana_pebelian_h.kd_ruang as gudang',
        //     'perencana_pebelian_r.no_rencbeliobat'
        // )
        //     ->leftjoin('perencana_pebelian_r', 'perencana_pebelian_h.no_rencbeliobat', 'perencana_pebelian_r.no_rencbeliobat')
        //     ->where('perencana_pebelian_h.kd_ruang', $request->kd_ruang)
        //     ->where('perencana_pebelian_r.kdobat', $request->kdobat)
        //     ->where('perencana_pebelian_r.flag', '')
        //     ->count();

        // if ($cekflag > 0) {
        //     return new JsonResponse(['message' => 'maaf obat ini masih dalam proses pemesanan...!!!'], 500);
        // }

        $cekminmax = Mminmaxobat::select(DB::raw('sum(max) as max'))
            ->where('kd_obat', $request->kdobat)
            // ->where('kd_ruang', 'like', '%GD%')
            ->groupby('kd_obat')->first();
        $maxobat = (int) $cekminmax['max'] ?? 0;

        $cekstok = Stokrel::select(DB::raw('sum(jumlah) as jumlah'))
            ->where('kdobat', $request->kdobat)
            // ->where('kdruang', 'like', '%GD%')
            ->groupby('kdobat')->first();
        $stok = $cekstok['jumlah'] ?? 0;
        $max = $maxobat - $stok;

        if ($request->jumlahdpesan > $max) {
            return new JsonResponse(['message' => 'Maaf Jumlah yang Dipesan melebihi jumlah yang boleh dipesan...!!!'], 500);
        }

        if ($request->norencanabeliobat === '' || $request->norencanabeliobat === null) {

            DB::connection('farmasi')->select('call rencana_beliobat(@nomor)');
            $x = DB::connection('farmasi')->table('conter')->select('rencblobat')->get();
            $wew = $x[0]->rencblobat;
            $norencanabeliobat = FormatingHelper::norencanabeliobat($wew, 'REN-BOBAT');
        } else {
            $norencanabeliobat = $request->norencanabeliobat;
        }
        $simpanheder = RencanabeliH::updateorcreate(
            [
                'no_rencbeliobat' => $norencanabeliobat
            ],
            [
                'tgl' => date('Y-m-d'),
                'user' => $xxx['kodesimrs'],
                'kd_ruang' => $request->kd_ruang
            ]
        );

        if (!$simpanheder) {
            return new JsonResponse(['message' => 'not ok'], 500);
        }

        $simpanrinci = RencanabeliR::updateorcreate(
            [
                'no_rencbeliobat' => $norencanabeliobat,
                'kdobat' => $request->kdobat,
                'jumlahdirencanakan'  => $request->jumlahdpesan
            ],
            [
                // 'stok_real_gudang' => $request->stok_real_gudang,
                'stok_real_rs'  => $request->stok_real_rs,
                'stok_max_rs'  => $request->stok_max_rs,
                'jumlah_bisa_dibeli'  => $request->jumlah_bisa_dibeli,
                'tgl_stok'  => $request->tgl_stok,
                'pabrikan'  => $request->pabrikan,
                'pbf'  => $request->pbf,
                'user'  => $xxx['kodesimrs']
            ]
        );

        if (!$simpanrinci) {
            return new JsonResponse(['message' => 'not ok'], 500);
        }

        return new JsonResponse(
            [
                'message' => 'ok',
                'notrans' => $norencanabeliobat,
                'heder' => $simpanheder,
                'rinci' => $simpanrinci
            ],
            200
        );
    }

    public function listrencanabeli()
    {
        if (request('kdruang') == '' || request('kdruang') == null) {
            $gudang = ['Gd-05010100', 'Gd-03010100'];
        } else {
            $gudang = request('kdruang');
        }
        $rencanabeli = RencanabeliH::with('rincian.mobat:kd_obat,nama_obat', 'rincian.stok')
            ->wherein('kd_ruang', $gudang)
            ->where('no_rencbeliobat', 'LIKE', '%' . request('no_rencbeliobat') . '%')
            ->when(request('flag'), function ($x) {
                $x->whereIn('flag', request('flag'));
            })
            ->orderBy('tgl', 'desc')
            ->paginate(request('per_page'));
        return new JsonResponse($rencanabeli);
    }
    public function listVerif()
    {
        if (request('kdruang') == '' || request('kdruang') == null) {
            $gudang = ['Gd-05010100', 'Gd-03010100'];
        } else {
            $gudang = request('kdruang');
        }
        $rencanabeli = RencanabeliH::with([
            'rincian' => function ($r) {
                $r->with([
                    'minmax',
                    'mobat',
                    'stok' => function ($s) {
                        $s->with('gudang:kode,nama', 'ruang:kode,uraian')
                            ->where('jumlah', '>', 0)
                            ->where('flag', '');
                    }
                ]);
            },
            'gudang'
        ])
            ->wherein('kd_ruang', $gudang)
            ->where('no_rencbeliobat', 'LIKE', '%' . request('no_rencbeliobat') . '%')
            ->when(request('flag'), function ($x) {
                $x->whereIn('flag', request('flag'));
            })
            ->orderBy('flag', 'asc')
            ->orderBy('tgl', 'desc')
            ->paginate(request('per_page'));
        return new JsonResponse($rencanabeli);
    }

    public function kuncirencana(Request $request)
    {
        $kunci = RencanabeliH::where('no_rencbeliobat', $request->no_rencbeliobat)
            ->update(['flag' => 1]);
        if (!$kunci) {
            return new JsonResponse(['message' => 'gagal mengupdate data'], 500);
        }
        return new JsonResponse(['message' => 'ok'], 200);
    }

    public function ambilRencana()
    {
        $data = Mobatnew::select(
            'kd_obat',
            'nama_obat',
            'status_generik',
            'status_fornas',
            'status_forkid',
            'satuan_k',
            'sistembayar',
            'gudang'
        )
            // ->where(function ($query) {
            //     $query
            //         ->orDoesntHave('stokrealallrs')
            //         ->orWhereHas('stokmaxrs', function ($q) {
            //             $q->select(
            //                 'min_max_ruang.kd_obat',
            //                 'min_max_ruang.min',
            //                 db::raw('sum(min_max_ruang.min) as balance'),
            //                 db::raw('sum(stokreal.jumlah) as stok')

            //             )
            //                 ->leftJoin('stokreal', 'min_max_ruang.kd_obat', '=', 'stokreal.kdobat')
            //                 ->havingRaw('balance >= stok');
            //         })
            //         ->orDoesntHave('stokmaxrs');
            // })

            ->where(function ($q) {
                $q->where('nama_obat', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('kd_obat', 'LIKE', '%' . request('q') . '%');
            })
            ->with([
                'stokmaxrs' => function ($mm) {
                    $mm->select(
                        'kd_obat',
                        'kd_ruang',
                        'min',
                        'max',
                        // db::raw('sum(min) as balance'),

                    );
                },
                'stokrealallrs' => function ($stokrealallrs) {
                    $stokrealallrs->select(
                        'kdobat',
                        'jumlah',
                        'kdruang',
                        // db::raw('sum(jumlah) as stok')

                    )
                        ->where('jumlah', '>', 0);
                },
                'perencanaanrinci' => function ($perencanaanrinci) {
                    $perencanaanrinci->select(
                        'kdobat',
                        DB::raw(
                            'sum(jumlahdirencanakan) as jumlah'
                        )
                    )->where('flag', '')
                        ->groupBy('kdobat');
                },
                'pemesananrinci' => function ($q) {
                    $q->select(
                        'kdobat',
                        DB::raw(
                            'sum(jumlahdpesan) as jumlah'
                        )
                    )->where('flag', '')
                        ->groupBy('kdobat');
                },

            ])

            ->orderBy('nama_obat')
            ->paginate(request('per_page'));

        return new JsonResponse($data);
    }

    public function updateRinci(Request $request)
    {
        $rinc = RencanabeliR::find($request->id);
        if (!$rinc) {
            return new JsonResponse([
                'message' => 'Data Tidak ditemukan'
            ], 410);
        }
        $rinc->jumlahdirencanakan = $request->jumlahdirencanakan;
        $rinc->save();
        return new JsonResponse([
            'message' => 'Data Berhasil Di Update'
        ]);
    }
    public function hapusHead(Request $request)
    {
        $head = RencanabeliH::where('no_rencbeliobat', $request->no_rencbeliobat)->first();
        $rinc = RencanabeliR::where('no_rencbeliobat', $request->no_rencbeliobat)->get();
        if (!$head) {
            return new JsonResponse([
                'message' => 'Data Tidak ditemukan'
            ], 410);
        }
        if (count($rinc) > 0) {
            foreach ($rinc as $key) {
                $key->delete();
            }
        }
        if ($head) {
            $head->delete();
        }
        return new JsonResponse([
            'message' => 'Data Berhasil dihapus'
        ]);
    }
    public function hapusRinci(Request $request)
    {
        $rinc = RencanabeliR::find($request->id);
        if (!$rinc) {
            return new JsonResponse([
                'message' => 'Data Tidak ditemukan'
            ], 410);
        }
        if ($rinc) {
            $rinc->delete();
        }
        $rincAll = RencanabeliR::where('no_rencbeliobat', $request->no_rencbeliobat)->get();
        if (count($rincAll) === 0) {
            $head = RencanabeliH::where('no_rencbeliobat', $request->no_rencbeliobat)->first();
            $head->delete();
        }
        return new JsonResponse([
            'message' => 'Data Berhasil dihapus'
        ]);
    }

    public function listrencanabeliBynomor()
    {
        $rencanabeli = RencanabeliH::with('rincian.mobat:kd_obat,nama_obat,satuan_k', 'rincian.stok', 'gudang')
            ->where('no_rencbeliobat', request('no_rencbeliobat'))
            ->when(request('flag'), function ($x) {
                $x->whereIn('flag', request('flag'));
            })
            ->get();
        return new JsonResponse($rencanabeli);
    }
}
