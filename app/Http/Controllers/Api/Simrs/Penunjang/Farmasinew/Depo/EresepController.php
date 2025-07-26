<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Depo;

use App\Events\NotifMessageEvent;
use App\Helpers\FormatingHelper;
use App\Helpers\HargaHelper;
use App\Http\Controllers\Api\Simrs\Antrian\AntrianController;
use App\Http\Controllers\Api\Simrs\Pendaftaran\Rajal\BridantrianbpjsController;
use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Master\Mpasien;
use App\Models\Simrs\Pendaftaran\Rajalumum\Bpjsrespontime;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaandeporinci;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaanresep;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaanresepracikan;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarrinci;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarrinciracikan;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Sistembayarlain;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Obat\RestriksiObat;
use App\Models\Simrs\Penunjang\Farmasinew\Obat\RestriksiObatKecualiRuangan;
use App\Models\Simrs\Penunjang\Farmasinew\PelayananInformasiObat;
use App\Models\Simrs\Penunjang\Farmasinew\Retur\Returpenjualan_r;
use App\Models\Simrs\Penunjang\Farmasinew\Stokreal;
use App\Models\Simrs\Penunjang\Farmasinew\TelaahResep;
use App\Models\Simrs\Penunjang\Farmasinew\Template\TemplateResepRacikan;
use App\Models\Simrs\Penunjang\Farmasinew\Template\TemplateResepRinci;
use App\Models\Simrs\Penunjang\Laborat\LaboratMeta;
use App\Models\Simrs\Penunjang\Laborat\Laboratpemeriksaan;
use App\Models\Simrs\Rajal\KunjunganPoli;
use App\Models\SistemBayar;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EresepController extends Controller
{

    public function getForPrint()
    {
        $noresep = request('noresep');
        $data = Resepkeluarheder::select('noresep', 'noresep_asal', 'flag')
            ->with([
                'permintaanresep.mobat:kd_obat,nama_obat,satuan_k,status_kronis',
                'permintaanracikan.mobat:kd_obat,nama_obat,satuan_k,status_kronis',
                'rincian' => function ($ri) {
                    $ri->select('*', DB::raw('sum(jumlah) as jumlah'),  'harga_jual as hargajual')
                        ->with(
                            'mobat:kd_obat,nama_obat,satuan_k,status_kronis',
                        )
                        ->where('jumlah', '>', 0)
                        ->groupBy('kdobat', 'noresep', 'noreg');
                },
                'rincianracik' => function ($ri) {
                    $ri->select('*', DB::raw('sum(jumlah) as jumlah'))
                        ->with(
                            'mobat:kd_obat,nama_obat,satuan_k,status_kronis',

                        )
                        ->where('jumlah', '>', 0)
                        ->groupBy('kdobat', 'noresep', 'noreg', 'namaracikan');
                },
                'asalpermintaanresep.mobat:kd_obat,nama_obat,satuan_k,status_kronis',
                'asalpermintaanracikan.mobat:kd_obat,nama_obat,satuan_k,status_kronis',

            ])
            ->where('noresep', $noresep)
            ->first();
        return new JsonResponse([
            'data' => $data,
            'req' => request()->all(),
        ]);
    }
    public function conterracikan()
    {
        $conter = Permintaanresepracikan::where('noresep', request('noresep'))
            ->groupby('noresep', 'namaracikan')
            // ->count(); kalo count ada 2 nama racikan terhitung 1
            ->get();

        /*  mencari nilai max racikan jika lebih dari satu, dan agar jika ada yang di hapus racikannya tapi tidak
            dihapus semua, maka nomornya bisa lanjut
        */
        $col = collect($conter)->map(function ($c) {
            $temp = explode(' ', $c->namaracikan);
            return  (int)$temp[1];
        })->max();
        $num = 0;
        if (count($conter)) {
            $num = $col;
        }
        // return new JsonResponse($num);
        $conterx =  $num + 1;
        $contery = 'Racikan ' . $conterx;
        return new JsonResponse($contery);
    }

    public function lihatstokobateresepBydokter()
    {
        // penccarian termasuk tiperesep
        $groupsistembayar = request('groups');
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
                            'resep_permintaan_keluar.kdobat as kdobat',
                            'resep_keluar_h.depo as kdruang',
                            DB::raw('sum(resep_permintaan_keluar.jumlah) as jumlah')
                        )
                            ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_permintaan_keluar.noresep')
                            ->where('resep_keluar_h.depo', request('kdruang'))
                            ->whereIn('resep_keluar_h.flag', ['', '1', '2'])
                            ->groupBy('resep_permintaan_keluar.kdobat');
                    },
                    'transracikan' => function ($transracikan) {
                        $transracikan->select(
                            // 'resep_keluar_racikan_r.kdobat as kdobat',
                            'resep_permintaan_keluar_racikan.kdobat as kdobat',
                            'resep_keluar_h.depo as kdruang',
                            DB::raw('sum(resep_permintaan_keluar_racikan.jumlah) as jumlah')
                        )
                            ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', '=', 'resep_permintaan_keluar_racikan.noresep')
                            ->where('resep_keluar_h.depo', request('kdruang'))
                            ->whereIn('resep_keluar_h.flag', ['', '1', '2'])
                            // ->groupBy('resep_permintaan_keluar_racikan.kdobat')
                        ;
                    },
                    // 'permintaanobatrinci' => function ($permintaanobatrinci) {
                    //     $permintaanobatrinci->select(
                    //         // 'permintaan_r.no_permintaan',
                    //         // 'permintaan_r.kdobat',
                    //         DB::raw('sum(permintaan_r.jumlah_minta) as allpermintaan')
                    //     )
                    //         ->leftJoin('permintaan_h', 'permintaan_h.no_permintaan', '=', 'permintaan_r.no_permintaan')
                    //         // biar yang ada di tabel mutasi ga ke hitung
                    //         ->leftJoin('mutasi_gudangdepo', function ($anu) {
                    //             $anu->on('permintaan_r.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                    //                 ->on('permintaan_r.kdobat', '=', 'mutasi_gudangdepo.kd_obat');
                    //         })
                    //         ->whereNull('mutasi_gudangdepo.kd_obat')

                    //         ->where('permintaan_h.tujuan', request('kdruang'))
                    //         ->whereIn('permintaan_h.flag', ['', '1', '2'])
                    //         ->groupBy('permintaan_r.kdobat');

                    // },
                    'permintaanobatrinci' => function ($permintaanobatrinci) {
                        $permintaanobatrinci->select(
                            'permintaan_r.no_permintaan',
                            'permintaan_r.kdobat',
                            'permintaan_r.jumlah_minta',
                            'permintaan_h.tujuan as kdruang',
                            DB::raw('sum(permintaan_r.jumlah_minta) as allpermintaan')
                        )
                            ->leftJoin('permintaan_h', 'permintaan_h.no_permintaan', '=', 'permintaan_r.no_permintaan')
                            ->leftJoin('mutasi_gudangdepo', function ($anu) {
                                $anu->on('permintaan_r.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                                    ->on('permintaan_r.kdobat', '=', 'mutasi_gudangdepo.kd_obat');
                            })
                            ->where('permintaan_h.tujuan', request('kdruang'))
                            // ->whereNull('mutasi_gudangdepo.kd_obat')
                            ->whereIn('permintaan_h.flag', ['', '1', '2'])
                            ->groupBy('permintaan_r.kdobat');;
                        // ->leftJoin('permintaan_h', 'permintaan_h.no_permintaan', '=', 'permintaan_r.no_permintaan')
                        // // biar yang ada di tabel mutasi ga ke hitung
                        // ->leftJoin('mutasi_gudangdepo', function ($anu) {
                        //     $anu->on('permintaan_r.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                        //         ->on('permintaan_r.kdobat', '=', 'mutasi_gudangdepo.kd_obat');
                        // })
                        // ->whereNull('mutasi_gudangdepo.kd_obat')

                        // ->where('permintaan_h.tujuan', request('kdruang'))
                        // ->whereIn('permintaan_h.flag', ['', '1', '2'])
                        // ->groupBy('permintaan_r.kdobat');

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
                ]
            )
            ->leftjoin('new_masterobat', 'new_masterobat.kd_obat', '=', 'stokreal.kdobat')
            ->where('stokreal.kdruang', request('kdruang'))
            ->where('stokreal.jumlah', '>', 0)
            ->whereIn('new_masterobat.sistembayar', $sistembayar)
            ->where('new_masterobat.status_konsinyasi', '')
            ->when(request('tiperesep') === 'prb', function ($q) {
                $q->where('new_masterobat.status_prb', '!=', '');
            })
            ->when(request('tiperesep') === 'iter', function ($q) {
                $q->where('new_masterobat.status_kronis', '!=', '');
            })
            ->where(function ($query) {
                $query->where('new_masterobat.nama_obat', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('new_masterobat.kandungan', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('stokreal.kdobat', 'LIKE', '%' . request('q') . '%');
            })
            ->groupBy('stokreal.kdobat')
            ->limit(10)
            ->get();
        $wew = collect($cariobat)->map(function ($x, $y) {
            $total = $x->total ?? 0;
            $jumlahper = request('kdruang') === 'Gd-04010103' ? $x['persiapanrinci'][0]->jumlah ?? 0 : 0;
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

    public function pencarianObatResep()
    {
        // penccarian termasuk tiperesep
        $groupsistembayar = request('groups');
        if ((int)$groupsistembayar === 1) {
            $sistembayar = ['SEMUA', 'BPJS'];
        } else {
            $sistembayar = ['SEMUA', 'UMUM'];
        }

        $listobat = Mobatnew::query()
            ->select(
                'new_masterobat.kd_obat',
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
                'new_masterobat.kelompok_psikotropika as psikotropika',
                'new_masterobat.jenis_perbekalan',
                'stokreal.kdobat as kdobat',
                'stokreal.jumlah as jumlah',
                // DB::raw('sum(stokreal.jumlah) as total')
                DB::raw('SUM(
                    CASE When stokreal.kdruang="' . request('kdruang') . '" AND stokreal.kdobat = new_masterobat.kd_obat Then stokreal.jumlah Else 0 End )
                     as total'),
            )
            ->leftjoin('stokreal', 'new_masterobat.kd_obat', '=', 'stokreal.kdobat')
            ->whereIn('new_masterobat.sistembayar', $sistembayar)
            ->when(request('tiperesep'), function ($q) {
                if (request('tiperesep') === 'prb') {
                    $q->where('new_masterobat.status_prb', '!=', '');
                } elseif (request('tiperesep') === 'iter') {
                    $q->where('new_masterobat.status_kronis', '!=', '');
                }
                // else {
                //     # code...
                // }
            })
            ->where(function ($query) {
                $query->where('new_masterobat.nama_obat', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('new_masterobat.kandungan', 'LIKE', '%' . request('q') . '%');
                // ->orWhere('stokreal.kdobat', 'LIKE', '%' . request('q') . '%');
            })
            ->with([
                'onepermintaandeporinci' => function ($q) {
                    $q->select(
                        'permintaan_r.no_permintaan',
                        'permintaan_r.kdobat',
                        'permintaan_h.tujuan as kdruang',
                        DB::raw('sum(permintaan_r.jumlah_minta) as jumlah_minta')
                    )
                        ->leftJoin('permintaan_h', 'permintaan_h.no_permintaan', '=', 'permintaan_r.no_permintaan')
                        ->where('permintaan_h.tujuan', request('kdruang'))
                        ->whereIn('permintaan_h.flag', ['', '1', '2'])
                        ->groupBy('permintaan_r.kdobat');
                },
                'oneperracikan' => function ($q) {
                    $q->select(
                        'resep_permintaan_keluar_racikan.kdobat as kdobat',
                        'resep_keluar_h.depo as kdruang',
                        DB::raw('sum(
                        Case
                        When resep_keluar_racikan_r.jumlah is null
                        Then resep_permintaan_keluar_racikan.jumlah
                        Else 0
                        End
                        ) as jumlah')
                    )
                        ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', '=', 'resep_permintaan_keluar_racikan.noresep')
                        ->leftJoin('resep_keluar_racikan_r', function ($anu) {
                            $anu->on('resep_keluar_racikan_r.noresep', '=', 'resep_permintaan_keluar_racikan.noresep')
                                ->on('resep_keluar_racikan_r.kdobat', '=', 'resep_permintaan_keluar_racikan.kdobat');
                        })
                        ->where('resep_keluar_h.depo', request('kdruang'))
                        ->whereIn('resep_keluar_h.flag', ['', '1', '2']);
                },
                'onepermintaan' => function ($q) {
                    $q->select(
                        'resep_permintaan_keluar.kdobat as kdobat',
                        'resep_keluar_h.depo as kdruang',
                        DB::raw('sum(
                        Case
                        When resep_keluar_r.jumlah is null
                        Then resep_permintaan_keluar.jumlah
                        Else 0
                        End
                        ) as jumlah')
                    )
                        ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_permintaan_keluar.noresep')
                        ->leftJoin('resep_keluar_r', function ($anu) {
                            $anu->on('resep_keluar_r.noresep', '=', 'resep_permintaan_keluar.noresep')
                                ->on('resep_keluar_r.kdobat', '=', 'resep_permintaan_keluar.kdobat');
                        })
                        ->where('resep_keluar_h.depo', request('kdruang'))
                        // ->whereNull('resep_keluar_r.kdobat')
                        ->whereIn('resep_keluar_h.flag', ['', '1', '2'])
                        ->groupBy('resep_permintaan_keluar.kdobat');
                },
                // 'oneobatkel' => function ($q) {
                //     $q->select(
                //         'resep_keluar_r.kdobat as kdobat',
                //         'resep_keluar_h.depo as kdruang',
                //         DB::raw('sum(resep_keluar_r.jumlah) as jumlah')
                //     )
                //         ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_keluar_r.noresep')
                //         ->where('resep_keluar_h.depo', request('kdruang'))
                //         ->where('resep_keluar_h.flag', '2')
                //         ->where('resep_keluar_r.jumlah', '>', 0)
                //         ->groupBy('resep_keluar_r.kdobat');
                // },
                // 'oneobatkelracikan' => function ($q) {
                //     $q->select(
                //         // 'resep_keluar_r.kdobat as kdobat',
                //         'resep_keluar_racikan_r.kdobat as kdobat',
                //         'resep_keluar_h.depo as kdruang',
                //         DB::raw('sum(resep_keluar_racikan_r.jumlah) as jumlah')
                //     )
                //         ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_keluar_racikan_r.noresep')
                //         ->where('resep_keluar_h.depo', request('kdruang'))
                //         ->where('resep_keluar_h.flag', '2')
                //         ->where('resep_keluar_racikan_r.jumlah', '>', 0)
                //         ->groupBy('resep_keluar_racikan_r.kdobat');
                // }
            ])



            ->groupBy('new_masterobat.kd_obat')
            ->orderBy('total', 'DESC')
            ->limit(20)
            ->get();

        $wew = collect($listobat)->map(function ($x, $y) {
            $total = $x->total ?? 0;
            // $jumlahper = request('kdruang') === 'Gd-04010103' ? $x['persiapanrinci'][0]->jumlah ?? 0 : 0;
            // $jumlahtrans = $x['transnonracikan'][0]->jumlah ?? 0;
            // $jumlahtransx = $x['transracikan'][0]->jumlah ?? 0;
            // $permintaanobatrinci = $x['permintaandeporinci'][0]->jumlah_minta ?? 0; // mutasi antar depo
            $jumlahtransx = $x['oneperracikan']->jumlah ?? 0;
            $jumlahtrans = $x['onepermintaan']->jumlah ?? 0;
            $permintaanobatrinci = $x['onepermintaandeporinci']->jumlah_minta ?? 0; // mutasi antar depo
            // $resep = $x['oneobatkel']->jumlah ?? 0; // keluar
            // $resepkeluarracikan = $x['oneobatkelracikan']->jumlah ?? 0; // keluar rac
            // $alokasi = (float) $total - (float)$jumlahtrans - (float)$jumlahtransx - (float)$permintaanobatrinci + (float)$resep + (float)$resepkeluarracikan;
            $alokasi = (float) $total - (float)$jumlahtrans - (float)$jumlahtransx - (float)$permintaanobatrinci;
            $x->alokasi = $alokasi <= 0 ? 0 : $alokasi;
            return $x;
        });

        return new JsonResponse(
            [
                'dataobat' => $wew
            ]
        );
    }

    public function pencarianObatResep2()
    {
        $kdruang = request('kdruang');
        $q = request('q');
        $groupsistembayar = request('groups');
        $tiperesep = request('tiperesep');

        // Cache sistembayar array
        $sistembayar = ((int)$groupsistembayar === 1) ? ['SEMUA', 'BPJS'] : ['SEMUA', 'UMUM'];

        // Optimize: First get matching obat IDs with index
        $matchingObatIds = Mobatnew::select('kd_obat')
            ->whereIn('sistembayar', $sistembayar)
            ->where(function ($query) use ($q) {
                $query->where('nama_obat', 'LIKE', "%$q%")
                    ->orWhere('kandungan', 'LIKE', "%$q%");
            });

        if ($tiperesep === 'prb') {
            $matchingObatIds->where('status_prb', '!=', '');
        } elseif ($tiperesep === 'iter') {
            $matchingObatIds->where('status_kronis', '!=', '');
        }

        $obatIds = $matchingObatIds->pluck('kd_obat');

        // Main query with optimized joins
        $listobat = Mobatnew::select([
            'new_masterobat.kd_obat',
            'new_masterobat.nama_obat as namaobat',
            'new_masterobat.kandungan',
            'new_masterobat.bentuk_sediaan',
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
            'new_masterobat.volumesediaan',
            'new_masterobat.kelompok_psikotropika as psikotropika',
            'new_masterobat.jenis_perbekalan',
            'stokreal.kdobat',
            DB::raw("COALESCE(SUM(CASE WHEN stokreal.kdruang = ? THEN stokreal.jumlah ELSE 0 END), 0) as total")
        ])
            ->addBinding($kdruang, 'select')
            ->whereIn('new_masterobat.kd_obat', $obatIds)
            ->leftJoin('stokreal', function ($join) use ($kdruang) {
                $join->on('new_masterobat.kd_obat', '=', 'stokreal.kdobat')
                    ->where('stokreal.kdruang', '=', $kdruang);
            });

        // Eager load with optimized subqueries
        $listobat = $listobat->with([
            'onepermintaandeporinci' => function ($q) use ($kdruang) {
                $q->select([
                    'permintaan_r.kdobat',
                    DB::raw('COALESCE(SUM(permintaan_r.jumlah_minta), 0) as jumlah_minta')
                ])
                    ->join('permintaan_h', 'permintaan_h.no_permintaan', '=', 'permintaan_r.no_permintaan')
                    ->where('permintaan_h.tujuan', $kdruang)
                    ->whereIn('permintaan_h.flag', ['', '1', '2'])
                    ->groupBy('permintaan_r.kdobat');
            },
            'oneperracikan' => function ($q) use ($kdruang) {
                $q->select([
                    'resep_permintaan_keluar_racikan.kdobat',
                    DB::raw('COALESCE(SUM(CASE WHEN resep_keluar_racikan_r.jumlah is null THEN resep_permintaan_keluar_racikan.jumlah ELSE 0 END), 0) as jumlah')
                ])
                    ->join('resep_keluar_h', 'resep_keluar_h.noresep', '=', 'resep_permintaan_keluar_racikan.noresep')
                    ->leftJoin('resep_keluar_racikan_r', function ($join) {
                        $join->on('resep_keluar_racikan_r.noresep', '=', 'resep_permintaan_keluar_racikan.noresep')
                            ->on('resep_keluar_racikan_r.kdobat', '=', 'resep_permintaan_keluar_racikan.kdobat');
                    })
                    ->where('resep_keluar_h.depo', $kdruang)
                    ->whereIn('resep_keluar_h.flag', ['', '1', '2'])
                    ->groupBy('resep_permintaan_keluar_racikan.kdobat');
            },
            'onepermintaan' => function ($q) use ($kdruang) {
                $q->select([
                    'resep_permintaan_keluar.kdobat',
                    DB::raw('COALESCE(SUM(CASE WHEN resep_keluar_r.jumlah is null THEN resep_permintaan_keluar.jumlah ELSE 0 END), 0) as jumlah')
                ])
                    ->join('resep_keluar_h', 'resep_keluar_h.noresep', '=', 'resep_permintaan_keluar.noresep')
                    ->leftJoin('resep_keluar_r', function ($join) {
                        $join->on('resep_keluar_r.noresep', '=', 'resep_permintaan_keluar.noresep')
                            ->on('resep_keluar_r.kdobat', '=', 'resep_permintaan_keluar.kdobat');
                    })
                    ->where('resep_keluar_h.depo', $kdruang)
                    ->whereIn('resep_keluar_h.flag', ['', '1', '2'])
                    ->groupBy('resep_permintaan_keluar.kdobat');
            }
        ])
            ->groupBy('new_masterobat.kd_obat')
            ->orderBy('total', 'DESC')
            ->limit(20)
            ->get();

        // Map results maintaining original response structure
        $wew = $listobat->map(function ($x) {
            $total = $x->total ?? 0;
            $jumlahtransx = $x['oneperracikan']->jumlah ?? 0;
            $jumlahtrans = $x['onepermintaan']->jumlah ?? 0;
            $permintaanobatrinci = $x['onepermintaandeporinci']->jumlah_minta ?? 0;

            $alokasi = (float)$total - (float)$jumlahtrans - (float)$jumlahtransx - (float)$permintaanobatrinci;
            $x->alokasi = $alokasi <= 0 ? 0 : $alokasi;
            return $x;
        });

        return new JsonResponse([
            'dataobat' => $wew
        ]);
    }

    public function pembuatanresep(Request $request)
    {
        // return new JsonResponse([
        //     'req' => $request->all(),
        // ]);
        $request->validate([
            'kodeobat' => 'required',
            // 'jumlah' => 'required',
            'kdruangan' => 'required',
            'aturan' => 'required',
        ]);
        $sudahAda = Resepkeluarheder::where('noresep', $request->noresep)->first();
        if ($sudahAda) {
            if ($sudahAda->noreg !== $request->noreg) $request['noresep'] = null;
        }
        /**
         * 'Gd-04010102' Ranap
         * 'Gd-05010101' Rajal
         */

        $depoLimit = ['Gd-04010102', 'Gd-05010101'];
        // return new JsonResponse([
        //     'siba'=>(int)$request->groupsistembayarlain
        // ],410);
        // pembatasan untuk pasien bpjs saja
        $total = 0;
        if (in_array($request->kodedepo, $depoLimit) && (int)$request->groupsistembayarlain === 1) {

            // pembatasan obat fornas ranap start --------
            /**
             * 1. cek apakah obat itu ada ppembatasan
             * 2. cek apakah ruangan termasuk yang dikecualikan
             * 3. hitung jumlah obat keluar, jika melebihi batasan, maka return error
             * 4. cek retur, jika diretur maka kurangi sejumlah yang di retur
             * 5. cek permintaan atas obat tersebut yang sudah dikirim ke depo, jika melebihi, return error
             *
             * depo = $request->kodedepo
             * kdobat = $request->kodeobat
             * kd_ruang = $request->kdruangan
             */
            if ($request->kodedepo === 'Gd-04010102') {
                $pembatasanFornas = RestriksiObat::where('depo', $request->kodedepo)
                    ->where('kd_obat', $request->kodeobat)
                    ->orderBy('tgl_mulai_berlaku', 'desc')
                    ->first();
                if ($pembatasanFornas) {
                    $jumlahPembatasan = (int) $pembatasanFornas->jumlah;
                    $kecualiRuang = RestriksiObatKecualiRuangan::where('depo', $request->kodedepo)
                        ->where('kd_obat', $request->kodeobat)
                        ->where('kd_ruang', $request->kdruangan)
                        ->first();
                    if (!$kecualiRuang) {
                        $jumlah = (int)$request->jumlah_diminta;
                        // jumlah obat keluar
                        $rincianKeluar = Resepkeluarrinci::where('resep_keluar_h.noreg', $request->noreg)
                            ->leftJoin('resep_keluar_h', 'resep_keluar_r.noresep', '=', 'resep_keluar_h.noresep')
                            ->where('kdobat', $request->kodeobat)
                            ->where('ruangan', '!=', 'POL014')
                            ->sum('jumlah');
                        // jumlah retur
                        $retur = Returpenjualan_r::where('retur_penjualan_r.noreg', $request->noreg)
                            ->leftJoin('retur_penjualan_h', 'retur_penjualan_r.noretur', '=', 'retur_penjualan_h.noretur')
                            ->where('kdobat', $request->kodeobat)
                            ->where('kdruangan', '!=', 'POL014')
                            ->sum('retur_penjualan_r.jumlah_retur');
                        $obatKeluar = (int)$rincianKeluar - (int)$retur;
                        // jumlah permintaan
                        $raw = Permintaanresep::query()
                            ->leftJoin('resep_keluar_h', 'resep_permintaan_keluar.noresep', '=', 'resep_keluar_h.noresep')
                            ->leftJoin('resep_keluar_r', function ($q) {
                                $q->on('resep_permintaan_keluar.noresep', '=', 'resep_keluar_r.noresep')
                                    ->on('resep_permintaan_keluar.kdobat', '=', 'resep_keluar_r.kdobat');
                            })
                            ->where('resep_keluar_h.noreg', $request->noreg)
                            ->where('resep_permintaan_keluar.kdobat', $request->kodeobat)
                            ->whereNull('resep_keluar_r.kdobat')
                            ->where('resep_keluar_h.ruangan', '!=', 'POL014');

                        $minta = $raw->whereIn('flag', ['1', '2'])->sum('resep_permintaan_keluar.jumlah');
                        $resep = $raw->whereIn('flag', ['1', '2'])->pluck('resep_permintaan_keluar.noresep')->toArray();

                        $obat = Mobatnew::select('nama_obat', 'kd_obat')->where('kd_obat', '=', $request->kodeobat)->first();
                        if ((int)$obatKeluar >= (int)$jumlahPembatasan) {
                            return new JsonResponse([
                                'message' => 'Jumlah ' . $obat->nama_obat . ' sudah diberikan sebanyak ' . $obatKeluar .
                                    ' batasan Restriksi fornas adalah ' . $jumlahPembatasan,
                            ], 410);
                        }
                        if ((int)$obatKeluar + (int)$minta > (int)$jumlahPembatasan) {
                            $reseps = implode(', ', $resep);
                            return new JsonResponse([
                                'message' => 'Jumlah ' . $obat->nama_obat . ' sudah diberikan sebanyak ' . $obatKeluar .
                                    ' ditambahkan dengan permintaan Resep nomor ' . $reseps . ' sebanyak ' . (int)$minta .
                                    ' melebihi batasan Restriksi fornas yaitu ' . $jumlahPembatasan . ' ( ' . (int)$obatKeluar + (int)$minta . ' >= ' . (int)$jumlahPembatasan . ' )',
                            ], 410);
                        }
                        if ((int)$obatKeluar + (int)$jumlah > (int)$jumlahPembatasan) {

                            return new JsonResponse([
                                'message' => 'Jumlah ' . $obat->nama_obat . ' sudah diberikan sebanyak ' . $obatKeluar .
                                    ' ditambahkan dengan jumlah permintaan sekarang sejumlah ' . (int)$jumlah .
                                    ' melebihi batasan Restriksi fornas yaitu ' . $jumlahPembatasan . ' ( ' . (int)$obatKeluar + (int)$jumlah . ' >= ' . (int)$jumlahPembatasan . ' )',
                            ], 410);
                        }
                        if ((int)$obatKeluar + (int)$minta + (int)$jumlah > (int)$jumlahPembatasan) {
                            $reseps = implode(', ', $resep);
                            return new JsonResponse([
                                'message' => 'Jumlah ' . $obat->nama_obat .
                                    ' sudah diberikan sebanyak ' . $obatKeluar .
                                    ' ditambahkan dengan permintaan Resep nomor ' . $reseps . ' sebanyak ' . (int)$minta .
                                    ' dan jumlah permintaan sekarang sejumlah ' . (int)$jumlah .
                                    ' melebihi batasan Restriksi fornas yaitu ' . $jumlahPembatasan . ' ( ' . (int)$obatKeluar + (int)$minta + (int)$jumlah . ' >= ' . (int)$jumlahPembatasan . ' )',
                            ], 410);
                        }
                    }
                }

                // return new JsonResponse([
                //     'message' => 'cek pembatasan',
                //     'pembatasanFornas' => $pembatasanFornas,
                //     'kecualiRuang' => $kecualiRuang ?? null,
                //     'jumlah' => $jumlah ?? null,
                //     'rincianKeluar' => $rincianKeluar ?? null,
                //     'retur' => $retur ?? null,
                //     'minta' => $minta ?? null,
                //     'jumlahPembatasan' => $jumlahPembatasan ?? null,
                // ], 410);
            }
            // pembatasan obat fornas ranap end --------
            // jumlah Racikan
            $racikan = Permintaanresepracikan::where('noresep', $request->noresep)->groupBy('namaracikan')->get()->count();
            // non racikan
            $nonracikan = Permintaanresep::select('resep_permintaan_keluar.kdobat')
                ->leftJoin('new_masterobat', 'new_masterobat.kd_obat', '=', 'resep_permintaan_keluar.kdobat')
                ->where('resep_permintaan_keluar.noresep', $request->noresep)
                ->where('new_masterobat.jenis_perbekalan', 'obat')
                ->get()
                ->count();

            $total = (int)$racikan + (int)$nonracikan;
            $batasRanap = $request->jenisresep == 'Racikan' ? $total > 7 : $total >= 7;
            $batasRajal = $request->jenisresep == 'Racikan' ? $total > 5 : $total >= 5;

            $obatMinta = Mobatnew::select('kd_obat')->where('jenis_perbekalan', 'obat')->where('kd_obat', $request->kodeobat)->first();
            if ($request->kodedepo === 'Gd-04010102' && $batasRanap && $obatMinta) {
                return new JsonResponse([
                    'message' => 'Jumlah Obat Dibatasi 7 saja',
                    'racikan' => $racikan,
                    'non racikan' => $nonracikan
                ], 410);
            }
            if ($request->kodedepo === 'Gd-05010101' && $batasRajal && $obatMinta) {
                return new JsonResponse([
                    'message' => 'Jumlah Obat Dibatasi 5 saja',
                    'racikan' => $racikan,
                    'non racikan' => $nonracikan
                ], 410);
            }
            // batasan obat yang sama
            if (!$request->keterangan_bypass) {
                $sekarang = date('Y-m-d');
                $head = Resepkeluarheder::when($request->kodedepo === 'Gd-04010102', function ($q) use ($request) {
                    $q->where('noreg', $request->noreg);
                })->when($request->kodedepo === 'Gd-05010101', function ($q) use ($request) {
                    $q->where('norm', $request->norm);
                })
                    ->where('tgl_kirim', 'LIKE', '%' . $sekarang . '%')->whereIn('flag', ['1', '2'])->where('depo', $request->kodedepo)->pluck('noresep');

                $adaObat = Permintaanresep::where('kdobat', $request->kodeobat)->whereIn('noresep', $head)->count();

                $bypass = $request->kodedepo === 'Gd-04010102' ? 1 : '0';

                if ($adaObat) {
                    $pesanA = 'Item Obat ';
                    $pesanT = '';
                    $pesanB = ' Sudah Pernah Diberikan Hari ini ';
                    $master = Mobatnew::select('nama_obat')->where('kd_obat', $request->kodeobat)->first();
                    if ($master) {
                        $pesanT = $master->nama_obat;
                    }
                    $msg = $pesanA . $pesanT . $pesanB;
                    return new JsonResponse([
                        'message' => $msg,
                        'bypass' => $bypass,
                    ], 410);
                }

                $head1 = Resepkeluarheder::when($request->kodedepo === 'Gd-04010102', function ($q) use ($request) {
                    $q->where('noreg', $request->noreg);
                })->when($request->kodedepo === 'Gd-05010101', function ($q) use ($request) {
                    $q->where('norm', $request->norm);
                })
                    ->where('tgl_kirim', 'LIKE', '%' . $sekarang . '%')->whereIn('flag', ['3', '4'])->where('depo', $request->kodedepo)->pluck('noresep');

                $adaObat1 = Resepkeluarrinci::where('kdobat', $request->kodeobat)->whereIn('noresep', $head1)->where('jumlah', '>', 0)->count();
                $adaRetur = Returpenjualan_r::where('kdobat', $request->kodeobat)->whereIn('noresep', $head1)->where('jumlah_retur', '>=', 'jumlah_keluar')->count();

                if ($adaObat1 > $adaRetur) {
                    $pesanA = 'Item Obat ';
                    $pesanT = '';
                    $pesanB = ' Sudah Pernah Diberikan Hari ini ';
                    $master = Mobatnew::select('nama_obat')->where('kd_obat', $request->kodeobat)->first();
                    if ($master) {
                        $pesanT = $master->nama_obat;
                    }
                    $msg = $pesanA . $pesanT . $pesanB;
                    return new JsonResponse([
                        'message' => $msg,
                        'bypass' => $bypass,
                        'adaObat1' => $adaObat1,
                        'adaRetur' => $adaRetur,
                    ], 410);
                }
            }

            // return new JsonResponse([
            //     'message' => 'Batasan',
            //     'racikan' => $racikan,
            //     'non racikan' => $nonracikan,
            //     'depo' => $request->kodedepo,
            //     'obat minta' => $obatMinta,
            // ], 410);
        }
        try {
            DB::connection('farmasi')->beginTransaction();
            $user = FormatingHelper::session_user();
            if ($user['kdgroupnakes'] != '1') {
                return new JsonResponse(['message' => 'Maaf Anda Bukan Dokter...!!!'], 500);
            }

            if ($request->kodedepo === 'Gd-05010101') {
                $tiperesep = $request->tiperesep ?? 'normal';
                $iter_expired = $request->iter_expired ?? null;
                $iter_jml = $request->iter_jml ?? null;
                if ($request->tiperesep === 'normal' || $request->tiperesep === 'prb') {
                    $iter_expired =  null;
                    $iter_jml =  null;
                }
            } else {
                $tiperesep =  'normal';
                $iter_expired =  null;
                $iter_jml =  null;
            }
            $cekjumlahstok = Stokreal::select('kdobat', DB::raw('sum(jumlah) as jumlahstok'))
                ->where('kdobat', $request->kodeobat)
                ->where('kdruang', $request->kodedepo)
                ->where('jumlah', '>', 0)
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
                    'persiapanrinci' => function ($res) use ($request) {
                        $res->select(
                            'persiapan_operasi_rincis.kd_obat',

                            DB::raw('sum(persiapan_operasi_rincis.jumlah_minta) as jumlah'),
                        )
                            ->leftJoin('persiapan_operasis', 'persiapan_operasis.nopermintaan', '=', 'persiapan_operasi_rincis.nopermintaan')
                            ->whereIn('persiapan_operasis.flag', ['', '1'])
                            ->groupBy('persiapan_operasi_rincis.kd_obat');
                    },
                ])
                ->orderBy('tglexp')
                ->groupBy('kdobat')
                ->get();
            $wew = collect($cekjumlahstok)->map(function ($x, $y) use ($request) {
                $total = $x->jumlahstok ?? 0;
                $jumlahper = $request->kodedepo === 'Gd-04010103' ? $x['persiapanrinci'][0]->jumlah ?? 0 : 0;
                $jumlahtrans = $x['oneperracikan']->jumlah ?? 0;
                $jumlahtransx = $x['onepermintaan']->jumlah ?? 0;
                $keluar = $x['oneobatkel']->jumlah ?? 0;
                $keluarRa = $x['oneobatkelracikan']->jumlah ?? 0;
                $permintaanobatrinci = $x['onepermintaandeporinci']->jumlah_minta ?? 0; // mutasi antar depo
                $x->alokasi = (float) $total - (float)$jumlahtrans - (float)$jumlahtransx - (float)$permintaanobatrinci - (float)$jumlahper + (float)$keluar + (float)$keluarRa;
                return $x;
            });
            // $jumlahstok = $cekjumlahstok[0]->jumlahstok;
            $alokasi = $wew[0]->alokasi ?? 0;
            // return new JsonResponse([
            //     'alokasi' => $alokasi,
            //     'wew' => $wew,
            // ]);
            if ($request->jenisresep == 'Racikan') {
                if ($request->jumlah > $alokasi) {
                    return new JsonResponse(['message' => 'Maaf Stok Alokasi Tidak Mencukupi...!!!', 'cek' => $cekjumlahstok], 500);
                }
            } else {

                if ($request->jumlah_diminta > $alokasi) {
                    return new JsonResponse(['message' => 'Maaf Stok Alokasi Tidak Mencukupi...!!!', 'cek' => $cekjumlahstok], 500);
                }
            }

            if ($request->kodedepo === 'Gd-04010102') {
                $procedure = 'resepkeluardeporanap(@nomor)';
                $colom = 'deporanap';
                $lebel = 'D-RI';
            } elseif ($request->kodedepo === 'Gd-04010103') {
                $procedure = 'resepkeluardepook(@nomor)';
                $colom = 'depook';
                $lebel = 'D-KO';
            } elseif ($request->kodedepo === 'Gd-05010101') {
                $lanjut = $request->lanjuTr ?? '';
                $cekpemberian = self::cekpemberianobat($request, $alokasi);
                if ($cekpemberian['status'] == 1 && $lanjut !== '1') {
                    return new JsonResponse(['message' => '', 'cek' => $cekpemberian], 202);
                }

                $procedure = 'resepkeluardeporajal(@nomor)';
                $colom = 'deporajal';
                $lebel = 'D-RJ';
            } else {
                $procedure = 'resepkeluardepoigd(@nomor)';
                $colom = 'depoigd';
                $lebel = 'D-IR';
            }

            if ($request->noresep === '' || $request->noresep === null) {
                DB::connection('farmasi')->select('call ' . $procedure);
                $x = DB::connection('farmasi')->table('conter')->select($colom)->get();
                $wew = $x[0]->$colom;
                $noresep = FormatingHelper::resep($wew, $lebel);
            } else {
                $noresep = $request->noresep;
            }



            // =======================================================================================================================================================================
            // WAN ..... Sistem Bayar  tak ganti kabeh yoooo ...... cek dibawah sing tak ganti tak komen
            // =======================================================================================================================================================================

            $simpan = Resepkeluarheder::updateOrCreate(
                [
                    'noresep' => $noresep,
                    'noreg' => $request->noreg,
                ],
                [
                    'norm' => $request->norm,
                    'tgl_permintaan' => date('Y-m-d H:i:s'),
                    'tgl' => date('Y-m-d'),
                    'depo' => $request->kodedepo,
                    'ruangan' => $request->kdruangan,
                    'dokter' =>  $user['kodesimrs'],
                    // 'sistembayar' => $request->sistembayar,
                    'sistembayar' => $request->sistembayarlain, // tak ganti iki wan
                    'diagnosa' => $request->diagnosa,
                    'kodeincbg' => $request->kodeincbg,
                    'uraianinacbg' => $request->uraianinacbg,
                    'tarifina' => $request->tarifina,
                    'tiperesep' => $tiperesep,
                    'iter_expired' => $iter_expired,
                    'iter_jml' => $iter_jml,
                    'flag_krs' => !$request->respkrs ? null : '1',
                    // 'iter_expired' => $request->iter_expired ?? '',
                    'tagihanrs' => $request->tagihanrs ?? 0,
                ]
            );

            if (!$simpan) {
                return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!'], 500);
            }

            // IKI TAMBAHANE WAN JIKA ADA SISTEM BAYAR LAIN (update or create berdasarkan noresep dan noreg)

            if ($request->groupsistembayarlain !== $request->groupsistembayar) {
                Sistembayarlain::updateOrCreate(
                    [
                        'noresep' => $noresep,
                        'noreg' => $request->noreg,
                    ],
                    [
                        'kodeawal' => $request->sistembayar,
                        'kodeganti' => $request->sistembayarlain,
                        'groupawal' => $request->groupsistembayar,
                        'groupganti' => $request->groupsistembayarlain,
                        'user' => $user['kodesimrs']
                    ]
                );
            }


            // $har = HargaHelper::getHarga($request->kodeobat, $request->groupsistembayar);
            $har = HargaHelper::getHarga($request->kodeobat, $request->groupsistembayarlain); // tak ganti iki wan
            $res = $har['res'];
            if ($res) {
                return new JsonResponse(['message' => $har['message'], 'data' => $har], 410);
            }
            $hargajualx = $har['hargaJual'];
            $harga = $har['harga'];

            if ($request->jenisresep == 'Racikan') {
                if ($request->tiperacikan == 'DTD') {
                    $simpandtd = Permintaanresepracikan::updateOrCreate(
                        [
                            'noreg' => $request->noreg,
                            'noresep' => $noresep,
                            'namaracikan' => $request->namaracikan,
                            'kdobat' => $request->kodeobat,
                        ],
                        [
                            'tiperacikan' => $request->tiperacikan,
                            'jumlahdibutuhkan' => $request->jumlahdibutuhkan, // jumlah racikan
                            'aturan' => $request->aturan,
                            'konsumsi' => $request->konsumsi,
                            'keterangan' => $request->keterangan,
                            'kandungan' => $request->kandungan ?? '',
                            'fornas' => $request->fornas ?? '',
                            'forkit' => $request->forkit ?? '',
                            'generik' => $request->generik ?? '',
                            // 'r' => $request->groupsistembayar === '1' || $request->groupsistembayar === 1 ? 500 : 0,
                            'r' => $request->groupsistembayarlain === '1' || $request->groupsistembayarlain === 1 ? 500 : 0, // tak ganti iki wan
                            'hpp' => $harga,
                            'harga_jual' => $hargajualx,
                            'kode108' => $request->kode108,
                            'uraian108' => $request->uraian108,
                            'kode50' => $request->kode50,
                            'uraian50' => $request->uraian50,
                            'stokalokasi' => $request->stokalokasi,
                            'dosisobat' => $request->dosisobat ?? 0,
                            'dosismaksimum' => $request->dosismaksimum ?? 0, // dosis resep
                            'jumlah' => $request->jumlah, // jumlah obat
                            'satuan_racik' => $request->satuan_racik, // jumlah obat
                            'keteranganx' => $request->keteranganx, // keterangan obat
                            'keterangan_bypass' => $request->keterangan_bypass, // keterangan bypass
                            'user' => $user['kodesimrs']
                        ]
                    );
                    // if ($simpandtd) {
                    //     $simpandtd->load('mobat:kd_obat,nama_obat');
                    // }
                } else {
                    $simpannondtd = Permintaanresepracikan::updateOrCreate(
                        [
                            'noreg' => $request->noreg,
                            'noresep' => $noresep,
                            'namaracikan' => $request->namaracikan,
                            'kdobat' => $request->kodeobat,
                        ],
                        [
                            'tiperacikan' => $request->tiperacikan,
                            'jumlahdibutuhkan' => $request->jumlahdibutuhkan,
                            'aturan' => $request->aturan,
                            'konsumsi' => $request->konsumsi,
                            'keterangan' => $request->keterangan,
                            'kandungan' => $request->kandungan ?? '',
                            'fornas' => $request->fornas ?? '',
                            'forkit' => $request->forkit ?? '',
                            'generik' => $request->generik ?? '',
                            // 'r' => $request->groupsistembayar === '1' || $request->groupsistembayar === 1 ? 500 : 0,
                            'r' => $request->groupsistembayarlain === '1' || $request->groupsistembayarlain === 1 ? 500 : 0,
                            'hpp' => $harga,
                            'harga_jual' => $hargajualx,
                            'kode108' => $request->kode108,
                            'uraian108' => $request->uraian108,
                            'kode50' => $request->kode50,
                            'uraian50' => $request->uraian50,
                            'stokalokasi' => $request->stokalokasi,
                            // 'dosisobat' => $request->dosisobat,
                            // 'dosismaksimum' => $request->dosismaksimum,
                            'jumlah' => $request->jumlah,
                            'satuan_racik' => $request->satuan_racik,
                            'keteranganx' => $request->keteranganx,
                            'keterangan_bypass' => $request->keterangan_bypass,
                            'user' => $user['kodesimrs']
                        ]
                    );
                    // if ($simpannondtd) {
                    //     $simpannondtd->load('mobat:kd_obat,nama_obat');
                    // }
                }
            } else {
                $simpanrinci = Permintaanresep::updateOrCreate(
                    [
                        'noreg' => $request->noreg,
                        'noresep' => $noresep,
                        'kdobat' => $request->kodeobat,
                    ],
                    [
                        'kandungan' => $request->kandungan ?? '',
                        'fornas' => $request->fornas ?? '',
                        'forkit' => $request->forkit ?? '',
                        'generik' => $request->generik ?? '',
                        'kode108' => $request->kode108,
                        'uraian108' => $request->uraian108,
                        'kode50' => $request->kode50,
                        'uraian50' => $request->uraian50,
                        'stokalokasi' => $request->stokalokasi,
                        // 'r' => $request->groupsistembayar === '1' || $request->groupsistembayar === 1 ? 300 : 0,
                        'r' => $request->groupsistembayarlain === '1' || $request->groupsistembayarlain === 1 ? 300 : 0,
                        'jumlah' => $request->jumlah_diminta,
                        'hpp' => $harga,
                        'hargajual' => $hargajualx,
                        'aturan' => $request->aturan,
                        'konsumsi' => $request->konsumsi,
                        'keterangan' => $request->keterangan ?? '',
                        'keterangan_bypass' => $request->keterangan_bypass,
                        'user' => $user['kodesimrs']
                    ]
                );
                // if ($simpanrinci) {
                //     $simpanrinci->load('mobat:kd_obat,nama_obat');
                // }
            }

            // $simpan->load(
            //     'permintaanresep.mobat:kd_obat,nama_obat',
            //     'permintaanracikan.mobat:kd_obat,nama_obat'
            // );
            $endas = Resepkeluarheder::where('noreg', $request->noreg)->with(
                'permintaanresep.mobat:kd_obat,nama_obat,kode_bpjs',
                'permintaanracikan.mobat:kd_obat,nama_obat,kode_bpjs'
            )->get();
            DB::connection('farmasi')->commit();
            return new JsonResponse([
                'newapotekrajal' => $endas,
                'heder' => $simpan,
                'rinci' => $simpanrinci ?? 0,
                'rincidtd' => $simpandtd ?? 0,
                'rincinondtd' => $simpannondtd ?? 0,
                'nota' => $noresep,
                'tot' => $total,
                'siba' => (int)$request->groupsistembayarlain === 1,
                'inaa' => in_array($request->kodedepo, $depoLimit),
                'all' => in_array($request->kodedepo, $depoLimit) && (int)$request->groupsistembayarlain === 1,
                'message' => 'Data Berhasil Disimpan...!!!'
            ], 200);
        } catch (\Exception $e) {
            DB::connection('farmasi')->rollBack();
            return new JsonResponse([
                'newapotekrajal' => $endas ?? false,
                'har' => $har ?? false,
                'heder' => $simpan ?? false,
                'rinci' => $simpanrinci ?? 0,
                'rincidtd' => $simpandtd ?? 0,
                'rincinondtd' => $simpannondtd ?? 0,
                'cekjumlahstok' => $cekjumlahstok ?? 0,
                'noresep' => $noresep ?? 0,
                'user' => $user['kodesimrs'] ?? 0,
                'tiperesep' => $tiperesep ?? 0,
                'iter_expired' => $iter_expired ?? 0,
                'iter_jml' => $iter_jml ?? 0,
                'error' => $e,
                'message' => 'ada kesalahan : ' . $e->getMessage()
            ], 410);
        }
    }

    public function listresepbydokter()
    {
        // return new JsonResponse(request()->all());
        // $data['c'] = date('m');
        // $data['c-3'] = (int)date('m') - 3;
        // $m = (int)date('m') - 3;
        // $data['all'] = [date('Y') . '-0' . ((int)date('m') - 3) . '-01 00:00:00', date('Y-m-31 23:59:59')];
        // return new JsonResponse($data);
        // if (request('flag') === 'semua') {
        //     $flag = ['1', '2', '3', '4', '5'];
        // } else {
        //     $flag = [request('flag')];
        // }

        //     $rm = [];
        //     if (request('q') !== null) {
        //         if (preg_match('~[0-9]+~', request('q'))) {
        //             $rm = [];
        //         } else {
        //             if (strlen(request('q')) >= 3) {
        //                 $data = Mpasien::select('rs1 as norm')->where('rs2', 'LIKE', '%' . request('q') . '%')->get();
        //                 $rm = collect($data)->map(function ($x) {
        //                     return $x->norm;
        //                 })->toArray();
        //             } else $rm = [];
        //         }
        //     }
        //     if (request('to') === '' || request('from') === null) {
        //         $tgl = Carbon::now()->format('Y-m-d 00:00:00');
        //         $tglx = Carbon::now()->format('Y-m-d 23:59:59');
        //     } else {
        //         $tgl = request('from') . ' 00:00:00';
        //         $tglx = request('to') . ' 23:59:59';
        //     }

        //     // Define database names
        //     $mysqlDb = config('database.connections.mysql.database');
        //     $farmasiDb = config('database.connections.farmasi.database');

        //     // Construct the base query
        //     $query = "
        //     SELECT resep_keluar_h.*
        //     FROM {$farmasiDb}.resep_keluar_h
        //     LEFT JOIN {$mysqlDb}.antrian_ambil ON antrian_ambil.noreg = resep_keluar_h.noreg
        //     WHERE (resep_keluar_h.noresep LIKE :noresep OR resep_keluar_h.norm LIKE :norm OR resep_keluar_h.noreg LIKE :noreg)
        //     AND resep_keluar_h.depo = :depo
        //     AND resep_keluar_h.tgl_permintaan BETWEEN :start_date AND :end_date
        // ";

        //     $bindings = [
        //         'noresep' => '%' . request('q') . '%',
        //         'norm' => '%' . request('q') . '%',
        //         'noreg' => '%' . request('q') . '%',
        //         'depo' => request('kddepo'),
        //         'start_date' => $tgl,
        //         'end_date' => $tglx,
        //     ];

        //     // Handle 'rm' array if it's not empty
        //     if (count($rm) > 0) {
        //         $query .= " AND resep_keluar_h.norm IN (" . implode(',', array_fill(0, count($rm), '?')) . ")";
        //         $bindings = array_merge($bindings, $rm);
        //     }

        //     // Handle 'tipe' parameter
        //     if (request('tipe')) {
        //         if (request('tipe') === 'iter' && request('kddepo') === 'Gd-05010101') {
        //             $query .= "
        //             AND resep_keluar_h.tiperesep = :tipe
        //             AND resep_keluar_h.noresep_asal = ''
        //             AND resep_keluar_h.iter_expired BETWEEN :iter_start AND :iter_end
        //         ";
        //             $bindings['tipe'] = request('tipe');
        //             $bindings['iter_start'] = date('Y-m-d 00:00:00');
        //             $bindings['iter_end'] = date('Y') . '-0' . ((int)date('m') + 3) . '-31 23:59:59';
        //         } else {
        //             $query .= " AND resep_keluar_h.tiperesep = :tipe";
        //             $bindings['tipe'] = request('tipe');
        //         }
        //     }

        //     // Handle 'flag' parameter
        //     if (request('flag')) {
        //         if (request('flag') === 'semua') {
        //             $query .= " AND resep_keluar_h.flag IN ('', '1', '2', '3', '4', '5')";
        //         } else {
        //             $query .= " AND resep_keluar_h.flag = :flag";
        //             $bindings['flag'] = request('flag');
        //         }
        //     } else {
        //         $query .= " AND resep_keluar_h.flag = ''";
        //     }

        //     // Add the ORDER BY clause
        //     $query .= " ORDER BY antrian_ambil.nomor ASC, resep_keluar_h.flag ASC, resep_keluar_h.tgl_permintaan ASC";

        //     // Get results with pagination
        //     $perPage = request('per_page', 15);
        //     $page = request('page', 1);
        //     $offset = ($page - 1) * $perPage;

        //     $paginatedQuery = $query . " LIMIT :limit OFFSET :offset";
        //     $bindings['limit'] = $perPage;
        //     $bindings['offset'] = $offset;

        //     // Execute the query
        //     $results = DB::connection('farmasi')->select($paginatedQuery, $bindings);

        //     // Count total records for pagination
        //     $countQuery = "SELECT COUNT(*) as total FROM ({$query}) as count_table";
        //     $totalBindings = $bindings;
        //     unset($totalBindings['limit'], $totalBindings['offset']);
        //     $total = DB::connection('farmasi')->select($countQuery, $totalBindings)[0]->total;

        //     // Create pagination response
        //     $paginatedResults = new \Illuminate\Pagination\LengthAwarePaginator($results, $total, $perPage, $page, [
        //         'path' => request()->url(),
        //         'query' => request()->query(),
        //     ]);

        //     return new JsonResponse($paginatedResults);
        $rm = [];
        if (request('q') !== null) {
            if (preg_match('~[0-9]+~', request('q'))) {
                $rm = [];
            } else {
                if (strlen(request('q')) >= 3) {
                    $data = Mpasien::select('rs1 as norm')->where('rs2', 'LIKE', '%' . request('q') . '%')->get();
                    $rm = collect($data)->map(function ($x) {
                        return $x->norm;
                    });
                } else $rm = [];
            }
        }
        if (request('to') === '' || request('from') === null) {
            $tgl = Carbon::now()->format('Y-m-d 00:00:00');
            $tglx = Carbon::now()->format('Y-m-d 23:59:59');
        } else {
            $tgl = request('from') . ' 00:00:00';
            $tglx = request('to') . ' 23:59:59';
        }
        // return $rm;
        $listresep = Resepkeluarheder::select(
            'resep_keluar_h.*'
        )
            ->leftJoin('antrian_ambil', 'antrian_ambil.noreg', '=', 'resep_keluar_h.noreg')
            ->with(
                [
                    'rincian.mobat:kd_obat,nama_obat,satuan_k,status_kronis',
                    'rincianracik.mobat:kd_obat,nama_obat,satuan_k,status_kronis',
                    'permintaanresep.mobat:kd_obat,nama_obat,satuan_k,status_kronis',
                    'permintaanresep.aturansigna:signa,jumlah',
                    'permintaanracikan.mobat:kd_obat,nama_obat,satuan_k,kekuatan_dosis,status_kronis,kelompok_psikotropika',
                    'poli',
                    'info',
                    'antrian' => function ($q) {
                        $q->where('pelayanan_id', 'AP0001');
                    },
                    'ruanganranap',
                    'sistembayar',
                    'sep:rs1,rs8',
                    'dokter:kdpegsimrs,nama',
                    'datapasien' => function ($quer) {
                        $quer->select(
                            'rs1',
                            'rs2 as nama',
                            'rs46 as noka',
                            'rs16 as tgllahir',
                            'rs2 as nama_panggil',
                            DB::raw('concat(rs4," KEL ",rs5," RT ",rs7," RW ",rs8," ",rs6," ",rs11," ",rs10) as alamat'),
                        );
                    }
                ]
            )
            ->where(function ($query) use ($rm) {
                $query->when(count($rm) > 0, function ($wew) use ($rm) {
                    $wew->whereIn('norm', $rm);
                })
                    ->orWhere('noresep', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('norm', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('noreg', 'LIKE', '%' . request('q') . '%');
            })
            ->where('depo', request('kddepo'))
            ->when(!request('tipe') || request('tipe') === null, function ($x) use ($tgl, $tglx) {
                $x->whereBetween('tgl_permintaan', [$tgl, $tglx]);
            })
            ->when(request('tipe'), function ($x) use ($tgl, $tglx) {
                if (request('tipe') === 'iter' && request('kddepo') === 'Gd-05010101') {
                    $x->where('tiperesep', request('tipe'))
                        ->where('noresep_asal', '=', '')
                        ->whereBetween('iter_expired', [date('Y-m-d 00:00:00'), date('Y') . '-0' . ((int)date('m') + 3) . '-31 23:59:59']);
                } else {
                    $x->where('tiperesep', request('tipe'))
                        ->whereBetween('tgl_permintaan', [$tgl, $tglx]);
                }
            })
            ->when(request('flag'), function ($x) {
                if (request('flag') === 'semua') {
                    $x->whereIn('flag', ['', '1', '2', '3', '4', '5']);
                } else if (request('flag') === null || request('flag') === '') {
                    $x->where('flag', '');
                } else {
                    $x->where('flag', request('flag'));
                }
            })
            ->when(!request('flag'), function ($x) {
                $x->where('flag', '');
            })

            ->orderBy('flag', 'ASC')
            ->orderBy('tgl_permintaan', 'ASC')
            ->paginate(request('per_page'));
        // return new JsonResponse(request()->all());
        return new JsonResponse($listresep);
    }
    public function newlistresepbydokter()
    {

        $rm = [];
        if (request('q') !== null) {
            if (preg_match('~[0-9]+~', request('q'))) {
                $rm = Mpasien::select('rs1')
                    ->where('rs46', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('rs1', 'LIKE', '%' . request('q') . '%')
                    ->pluck('rs1')
                    ->toArray();
            } else {
                if (strlen(request('q')) >= 3) {
                    $rm = Mpasien::select('rs1')
                        ->where('rs2', 'LIKE', '%' . request('q') . '%')
                        ->pluck('rs1')
                        ->toArray();
                } else $rm = [];
            }
        }
        // return new JsonResponse([
        //     'rm' => $rm
        // ]);
        if (request('to') === '' || request('from') === null) {
            $tgl = Carbon::now()->format('Y-m-d 00:00:00');
            $tglx = Carbon::now()->format('Y-m-d 23:59:59');
        } else {
            $tgl = request('from') . ' 00:00:00';
            $tglx = request('to') . ' 23:59:59';
        }

        // Construct the query using Eloquent
        $query = Resepkeluarheder::with([
            // 'rincian.mobat:kd_obat,nama_obat,satuan_k,status_kronis',
            // 'rincianracik.mobat:kd_obat,nama_obat,satuan_k,status_kronis',
            'rincian' => function ($ri) {
                $ri->select('*', DB::raw('sum(jumlah) as jumlah'))
                    ->with(
                        'mobat:kd_obat,nama_obat,satuan_k,status_kronis',
                        'mobat.indikasi'
                    )
                    ->groupBy('kdobat', 'noresep', 'noreg');
            },
            'rincianracik' => function ($ri) {
                $ri->select('*', DB::raw('sum(jumlah) as jumlah'))
                    ->with(
                        'mobat:kd_obat,nama_obat,satuan_k,status_kronis',
                        'mobat.indikasi'
                    )
                    ->groupBy('kdobat', 'noresep', 'noreg', 'namaracikan');
            },
            'kunjunganrajal' => function ($kunjunganrajal) {
                $kunjunganrajal->select('rs1', 'rs9')->with('doktersimpeg:kdpegsimrs,nama');
            },
            'permintaanresep.mobat:kd_obat,nama_obat,satuan_k,status_kronis',
            'permintaanresep.mobat.indikasi',
            'permintaanresep.aturansigna:signa,jumlah',
            'permintaanracikan.mobat:kd_obat,nama_obat,satuan_k,kekuatan_dosis,status_kronis,kelompok_psikotropika',
            'permintaanracikan.mobat.indikasi',
            'asalpermintaanresep.mobat:kd_obat,nama_obat,satuan_k,status_kronis',
            'asalpermintaanresep.mobat.indikasi',
            'asalpermintaanresep.aturansigna:signa,jumlah',
            'poli',
            'info',
            'antrian' => function ($q) {
                $q->where('pelayanan_id', 'AP0001');
            },
            'kwitansi',
            'telaah',
            'diagnosas:rs1,rs3,rs13',
            'diagnosas.masterdiagnosa:rs1,rs3,rs4',
            'ruanganranap',
            'laborat.pemeriksaan_laborat',
            'sistembayar',
            'sep:rs1,rs8',
            'dokter:kdpegsimrs,nama',
            'kunjunganranap:rs1,titipan,rs6,rs3,rs4',
            'kunjunganranap.ruangtitipan:rs1,rs2',
            // 'kunjunganranap.kamarranap',
            'datapasien' => function ($quer) {
                $quer->select(
                    'rs1',
                    'rs2 as nama',
                    'rs46 as noka',
                    'rs16 as tgllahir',
                    'rs2 as nama_panggil',
                    DB::raw('concat(rs4," KEL ",rs5," RT ",rs7," RW ",rs8," ",rs6," ",rs11," ",rs10) as alamat'),
                );
            }
        ])
            ->leftJoin(DB::raw(config('database.connections.mysql.database') . '.antrian_ambil'), function ($q) {
                $q->on('antrian_ambil.noreg', '=', 'resep_keluar_h.noreg')
                    ->where('antrian_ambil.pelayanan_id', '=', 'AP0001');
            })
            // ->leftJoin(DB::raw(config('database.connections.mysql.database') . '.kwitansilog'), function ($q) {
            //     $q->on('resep_keluar_h.noresep', 'LIKE', 'kwitansilog.nota' );
            // })
            // ->leftJoin(DB::raw(config('database.connections.mysql.database') . '.antrian_ambil'), 'antrian_ambil.noreg', '=', 'resep_keluar_h.noreg')
            // ->where('antrian_ambil.pelayanan_id', '=', 'AP0001')
            ->select(
                'resep_keluar_h.*',
                'antrian_ambil.nomor',
                DB::raw('(TIMESTAMPDIFF(DAY,resep_keluar_h.tgl_kirim,resep_keluar_h.tgl_selesai)) AS rt_hari'),
                DB::raw('((TIMESTAMPDIFF(HOUR,resep_keluar_h.tgl_kirim,resep_keluar_h.tgl_selesai))%24) AS rt_jam'),
                DB::raw('((TIMESTAMPDIFF(MINUTE,resep_keluar_h.tgl_kirim,resep_keluar_h.tgl_selesai))%60) AS rt_menit'),
                DB::raw('((TIMESTAMPDIFF(SECOND,resep_keluar_h.tgl_kirim,resep_keluar_h.tgl_selesai))%60) AS rt_detik'),
            )
            ->where(function ($query) use ($rm) {
                $query->when(count($rm) > 0, function ($wew) use ($rm) {
                    $wew->whereIn('resep_keluar_h.norm', $rm);
                })
                    ->orWhere('resep_keluar_h.noresep', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('resep_keluar_h.norm', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('resep_keluar_h.noreg', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('antrian_ambil.nomor', 'LIKE', '%' . request('q') . '%');
            })
            ->where('resep_keluar_h.tiperesep', '!=', 'penjualan')
            ->where('resep_keluar_h.depo', request('kddepo'))
            ->when(request('tipe'), function ($qu) {
                if (request('tipe') === 'iter' && request('kddepo') === 'Gd-05010101') {
                    $iterTiming = request('iter_timing');
                    if ($iterTiming == 'berlaku') {
                        // $addThree = Carbon::now()->addMonth(3)->format('m');
                        // $year = ((int)date('m') + 3) <= 12 ? date('Y')  : Carbon::now()->addYears(1)->format('Y');
                        $qu->where('resep_keluar_h.tiperesep', request('tipe'))
                            ->where('resep_keluar_h.noresep_asal', '')
                            ->whereBetween('resep_keluar_h.iter_expired', [request('from') . ' 00:00:00', request('to') . ' 23:59:59']); // luput
                    } else {
                        $qu->where('resep_keluar_h.tiperesep', request('tipe'))
                            ->where('resep_keluar_h.noresep_asal', '')
                            ->whereBetween('resep_keluar_h.tgl_kirim', [request('from') . ' 00:00:00', request('to') . ' 23:59:59']);
                    }
                } else {
                    $qu->where('resep_keluar_h.tiperesep', request('tipe'));
                }
            })
            ->when(request('flag'), function ($qu) use ($tgl, $tglx) {
                if (request('tipe') === 'iter') {
                    if (request('flag') === 'semua') {
                        $qu->where(function ($q) use ($tgl, $tglx) {
                            $q->where(function ($m) use ($tgl, $tglx) {
                                $m->whereIn('resep_keluar_h.flag', ['1', '2', '3', '4']);
                            })->orWhere(function ($q) use ($tgl, $tglx) {
                                $q->whereIn('resep_keluar_h.flag', ['', '5']);
                            });
                        });
                    } else {
                        $qu->where('resep_keluar_h.flag', request('flag'));
                    }
                } else {
                    if (request('flag') === 'semua') {
                        $qu->where(function ($q) use ($tgl, $tglx) {
                            $q->where(function ($m) use ($tgl, $tglx) {
                                $m->whereIn('resep_keluar_h.flag', ['1', '2', '3', '4'])
                                    ->whereBetween('resep_keluar_h.tgl_kirim', [$tgl, $tglx]);
                            })->orWhere(function ($q) use ($tgl, $tglx) {
                                $q->whereIn('resep_keluar_h.flag', ['', '5'])
                                    ->whereBetween('resep_keluar_h.tgl_permintaan', [$tgl, $tglx]);
                            });
                        });
                    } else {
                        $qu->where('resep_keluar_h.flag', request('flag'))->whereBetween('resep_keluar_h.tgl_kirim', [$tgl, $tglx]);
                    }
                }
            }, function ($qu) use ($tgl, $tglx) {
                $qu->where('resep_keluar_h.flag', '')
                    ->whereBetween('resep_keluar_h.tgl_permintaan', [$tgl, $tglx]);
            });
        // ->whereBetween('resep_keluar_h.tgl_permintaan', [$tgl, $tglx]);

        // if (request('tipe')) {
        //     if (request('tipe') === 'iter' && request('kddepo') === 'Gd-05010101') {
        //         $query->where('resep_keluar_h.tiperesep', request('tipe'))
        //             ->where('resep_keluar_h.noresep_asal', '')
        //             ->whereBetween('resep_keluar_h.iter_expired', [date('Y-m-d 00:00:00'), date('Y') . '-0' . ((int)date('m') + 3) . '-31 23:59:59']);
        //     } else {
        //         $query->where('resep_keluar_h.tiperesep', request('tipe'));
        //     }
        // }

        // if (request('flag')) {
        //     if (request('flag') === 'semua') {
        //         $query->where(function ($q) use ($tgl, $tglx) {
        //             $q->where(function ($m) use ($tgl, $tglx) {
        //                 $m->whereIn('resep_keluar_h.flag', ['1', '2', '3', '4', '5'])
        //                     ->whereBetween('resep_keluar_h.tgl_kirim', [$tgl, $tglx]);
        //             })->orWhere(function ($q) use ($tgl, $tglx) {
        //                 $q->where('resep_keluar_h.flag', '')
        //                     ->whereBetween('resep_keluar_h.tgl_permintaan', [$tgl, $tglx]);
        //             });
        //         });
        //     } else {
        //         $query->where('resep_keluar_h.flag', request('flag'));
        //     }
        // } else {
        //     $query->where('resep_keluar_h.flag', '')->whereBetween('resep_keluar_h.tgl_permintaan', [$tgl, $tglx]);
        // }
        if (request('sistembayar')) {
            $query->where('resep_keluar_h.sistembayar', request('sistembayar'));
        }
        if (request('listsistembayar')) {
            $query->whereIn('resep_keluar_h.sistembayar', request('listsistembayar'));
        }

        // Add the ORDER BY clause
        $query
            ->groupBy('resep_keluar_h.noresep')
            ->orderBy('resep_keluar_h.flag', 'ASC')
            ->orderBy('resep_keluar_h.tgl_kirim', 'ASC')
            ->orderBy('resep_keluar_h.tgl_permintaan', 'ASC')
            ->orderBy('antrian_ambil.nomor', 'ASC');

        // Get paginated results
        $listresep = $query->paginate(request('per_page'));

        return new JsonResponse($listresep);
        // $addThree = ((int)date('m') + 3) < 10 ? Carbon::now()->addMonth(3)->format('m') : (((int)date('m') + 3) > 12 ? ('0' . ((int)date('m') + 3) - 12) : ((int)date('m') + 3));
        $addThree = Carbon::now()->addMonth(3)->format('m');
        $year = ((int)date('m') + 3) <= 12 ? date('Y')  : Carbon::now()->addYears(1)->format('Y');
        return new JsonResponse([
            'addThree' => $addThree,
            'year' => $year,
            'betwween' => [date('Y-m-d 00:00:00'), $year . '-' . $addThree . '-31 23:59:59'],
        ]);
    }
    public function getSingleResep()
    {

        $listresep = Resepkeluarheder::with(
            [
                // 'rincian.mobat:kd_obat,nama_obat,satuan_k,status_kronis',
                // 'rincianracik.mobat:kd_obat,nama_obat,satuan_k,status_kronis',
                // 'permintaanresep.mobat:kd_obat,nama_obat,satuan_k,status_kronis',
                // 'permintaanracikan.mobat:kd_obat,nama_obat,satuan_k,status_kronis',
                // 'poli',
                // 'ruanganranap',
                // 'sistembayar',
                // 'dokter:kdpegsimrs,nama',
                // 'datapasien' => function ($quer) {
                //     $quer->select(
                //         'rs1',
                //         'rs2 as nama'
                //     );
                // }
                'rincian.mobat:kd_obat,nama_obat,satuan_k,status_kronis',
                'rincian.mobat.indikasi',
                'rincianracik.mobat:kd_obat,nama_obat,satuan_k,status_kronis',
                'rincianracik.mobat.indikasi',
                'permintaanresep.mobat:kd_obat,nama_obat,satuan_k,status_kronis',
                'permintaanresep.mobat.indikasi',
                'permintaanresep.aturansigna:signa,jumlah',
                'permintaanracikan.mobat:kd_obat,nama_obat,satuan_k,kekuatan_dosis,status_kronis,kelompok_psikotropika',
                'permintaanracikan.mobat.indikasi',
                'poli',
                'info',
                'antrian' => function ($q) {
                    $q->where('pelayanan_id', 'AP0001');
                },
                'ruanganranap',
                'sistembayar',
                'sep:rs1,rs8',
                'dokter:kdpegsimrs,nama',
                'kunjunganranap:rs1,titipan,rs6,rs3,rs4',
                'kunjunganranap.ruangtitipan:rs1,rs2',
                'datapasien' => function ($quer) {
                    $quer->select(
                        'rs1',
                        'rs2 as nama',
                        'rs46 as noka',
                        'rs16 as tgllahir',
                        'rs2 as nama_panggil',
                        DB::raw('concat(rs4," KEL ",rs5," RT ",rs7," RW ",rs8," ",rs6," ",rs11," ",rs10) as alamat'),
                    );
                }
            ]
        )
            ->where('farmasi.resep_keluar_h.id', request('id'))
            ->first();
        return new JsonResponse($listresep);
    }
    public static function pushToArray($cond, $array, $str, $bnd)
    {
        $column = array_column($array, $str);
        $index = array_search($bnd, $column);
        if ($index === false && $cond) return true;
        else return false;
    }
    public function kirimresep(Request $request)
    {
        /**
         * pembatasan start
         */
        $depoLimit = ['Gd-04010102', 'Gd-05010101'];
        if (in_array($request->kodedepo, $depoLimit) && (int)$request->groupsistembayarlain === 1) {
            // batasan obat yang sama
            $sekarang = date('Y-m-d');
            // normal, tidak ada retur
            $normalHeadKel = Resepkeluarheder::when($request->kodedepo === 'Gd-04010102', function ($q) use ($request) {
                $q->where('noreg', $request->noreg);
            })->when($request->kodedepo === 'Gd-05010101', function ($q) use ($request) {
                $q->where('norm', $request->norm);
            })
                ->where('tgl_kirim', 'LIKE', '%' . $sekarang . '%')
                ->whereIn('flag', ['3'])
                ->where('depo', $request->kodedepo)
                ->pluck('noresep');
            $normalHead = Resepkeluarheder::when($request->kodedepo === 'Gd-04010102', function ($q) use ($request) {
                $q->where('noreg', $request->noreg);
            })->when($request->kodedepo === 'Gd-05010101', function ($q) use ($request) {
                $q->where('norm', $request->norm);
            })
                ->where('tgl_kirim', 'LIKE', '%' . $sekarang . '%')
                ->whereIn('flag', ['1', '2'])
                ->where('depo', $request->kodedepo)
                ->pluck('noresep');
            $returHead = Resepkeluarheder::when($request->kodedepo === 'Gd-04010102', function ($q) use ($request) {
                $q->where('noreg', $request->noreg);
            })->when($request->kodedepo === 'Gd-05010101', function ($q) use ($request) {
                $q->where('norm', $request->norm);
            })
                ->where('tgl_kirim', 'LIKE', '%' . $sekarang . '%')
                ->where('flag', '4')
                ->where('depo', $request->kodedepo)
                ->pluck('noresep');
            // ambil detail obat yang akan dikirim
            $obatnya = Permintaanresep::where('noresep', $request->noresep)->whereNull('keterangan_bypass')->with('mobat:kd_obat,nama_obat')->get();
            $obatRacikan = Permintaanresepracikan::where('noresep', $request->noresep)->whereNull('keterangan_bypass')->with('mobat:kd_obat,nama_obat')->get();
            // ambil obat untuk pasien kunjungan sekarang
            $obatKeluar = Resepkeluarrinci::whereIn('noresep', $normalHeadKel)->where('jumlah', '>', 0)->get();
            $obatNormal = Permintaanresep::whereIn('noresep', $normalHead)->get();
            $obatNormalRacikan = Permintaanresepracikan::whereIn('noresep', $normalHead)->orWhereIn('noresep', $returHead)->get();
            // ambil retur obat (kalau ada)
            $obatAdaRetur = Permintaanresep::whereIn('noresep', $returHead)->get();
            // ambil obat yang diretur
            $obatRetur = Returpenjualan_r::whereIn('noresep', $returHead)->get();
            // cek retur, berapa jumlah nya, jika semua maka dianggap tidak diberikan
            $arrayAda = $obatAdaRetur->toArray();
            $keys = array_column($arrayAda, 'kdobat');
            $masukIf = [];
            foreach ($obatRetur as $ret) {
                $index = array_search($ret['kdobat'], $keys);
                $masukIf[] = [
                    'ret' => $ret['kdobat'],
                    'index' => $index,
                    'if' => ($index !== false),
                    'int' => (int)$index,
                ];
                if ((int)$index >= 0) {
                    $keluar = $ret['jumlah_keluar'];
                    $retur = $ret['jumlah_retur'];
                    // yang ada retur, jika di retur semua obatnya berarti dianggap tidak ada
                    if ($keluar == $retur) {
                        array_splice($arrayAda, $index, 1);
                    }
                }
            }
            // bandingkan
            $sudahAda = [];
            $cN = [];
            $cR = [];
            $cRA = [];
            $msg = '';
            $arrayKeluar = $obatKeluar->toArray();
            $arrayNormal = $obatNormal->toArray();
            $arrayNormalRacikan = $obatNormalRacikan->toArray();
            $ret = array_column($arrayAda, 'kdobat');
            $nor = array_column($arrayNormal, 'kdobat');
            $norR = array_column($arrayNormalRacikan, 'kdobat');
            $kel = array_column($arrayKeluar, 'kdobat');
            // bandingkan dengan obat yang akan dikirim
            if (count($obatnya) > 0) {
                foreach ($obatnya as $obt) {

                    $indR = array_search($obt['kdobat'], $ret);
                    $indN = array_search($obt['kdobat'], $nor);
                    $indNRa = array_search($obt['kdobat'], $norR);
                    $indK = array_search($obt['kdobat'], $kel);

                    $fIndR = $indR !== false; // kalo ga ketemu itu false, kelo ketemu itu number, kalo ketemu 0 itu juga dianggap false
                    $fIndN = $indN !== false;
                    $findNRa = $indNRa !== false;

                    if ($fIndR && self::pushToArray($fIndR, $sudahAda, 'kdobat', $obt['kdobat'])) $sudahAda[] = $obt;
                    else if ($fIndN && self::pushToArray($fIndN, $sudahAda, 'kdobat', $obt['kdobat'])) $sudahAda[] = $obt;
                    else if ($findNRa && self::pushToArray($findNRa, $sudahAda, 'kdobat', $obt['kdobat'])) $sudahAda[] = $obt;

                    if (sizeof($sudahAda) == 1) $msg = $msg . $obt['mobat']['nama_obat'] . ' sudah diresepkan sebanyak ' . $obt['jumlah'];
                    if (sizeof($sudahAda) > 1) $msg = $msg . ', ' . $obt['mobat']['nama_obat'] . ' sudah diresepkan sebanyak ' . $obt['jumlah'];
                    $cN[] = [$fIndN, $obt['kdobat']];
                    $cR[] = [$fIndR, $obt['kdobat']];
                    $cRA[] = [$findNRa, $obt['kdobat']];
                }
            }
            // racikan
            if (count($obatRacikan) > 0) {
                foreach ($obatRacikan as $obt) {
                    // $sdh=array_column($sudahAda,'kdobat');
                    $indR = array_search($obt['kdobat'], $ret);
                    $indN = array_search($obt['kdobat'], $nor);
                    $indNRa = array_search($obt['kdobat'], $norR);
                    $indK = array_search($obt['kdobat'], $kel);
                    // $indS=array_search($obt['kdobat'],$sdh);
                    // return new JsonResponse($indS);
                    // if($indS===false){
                    $fIndR = $indR !== false; // kalo ga ketemu itu false, kelo ketemu itu number, kalo ketemu 0 itu juga dianggap false
                    $fIndN = $indN !== false;
                    $findNRa = $indNRa !== false;

                    if ($fIndR && self::pushToArray($fIndR, $sudahAda, 'kdobat', $obt['kdobat'])) $sudahAda[] = $obt;
                    else if ($fIndN && self::pushToArray($fIndN, $sudahAda, 'kdobat', $obt['kdobat'])) $sudahAda[] = $obt;
                    else if ($findNRa && self::pushToArray($findNRa, $sudahAda, 'kdobat', $obt['kdobat'])) $sudahAda[] = $obt;

                    if (sizeof($sudahAda) == 1) $msg = $msg . $obt['mobat']['nama_obat'] . ' sudah diresepkan sebanyak ' . $obt['jumlah'];
                    if (sizeof($sudahAda) > 1) $msg = $msg . ', ' . $obt['mobat']['nama_obat'] . ' sudah diresepkan sebanyak ' . $obt['jumlah'];
                    // }
                    $cN[] = [$fIndN, $obt['kdobat']];
                    $cR[] = [$fIndR, $obt['kdobat']];
                    $cRA[] = [$findNRa, $obt['kdobat']];
                }
            }
            if (sizeof($sudahAda) > 0) {
                // $msg=$msg . ' Sudah diresepkan';
                return new JsonResponse([
                    'message' => $msg,
                    'sudahAda' => $sudahAda,
                    'cR' => $cR,
                    'cN' => $cN,
                    'cRA' => $cRA,
                    'arrayAda' => $arrayAda,
                    'arrayNormal' => $arrayNormal,
                    'arrayNormalRacikan' => $arrayNormalRacikan,
                    'obatnya' => $obatnya,
                    'normalHead' => $normalHead,
                    'masukIf' => $masukIf,
                    'count' => sizeof($sudahAda),

                ], 410);
            }
        }

        /**
         * pembatasan end
         */

        $user = Pegawai::find(auth()->user()->pegawai_id);

        $kirimresep = Resepkeluarheder::where('noresep', $request->noresep)->first();
        if (!$kirimresep) {
            return new JsonResponse([
                'message' => 'Resep tidak ditemukan',
            ], 410);
        }

        $flag = $kirimresep->flag;
        if ((int)$flag >= 1) {
            return new JsonResponse([
                'message' => 'Resep sudah dikirimkan',

            ], 410);
        }
        if ((int)$user->kdgroupnakes !== 1) {
            return new JsonResponse(['message' => 'MAAF KIRIM RESEP HARUS OLEH DOKTER'], 500);
        }
        $kirimresep->flag = '1';
        $kirimresep->tgl_kirim = date('Y-m-d H:i:s');
        $kirimresep->save();

        $kirimresep->load([
            'permintaanresep.mobat:kd_obat,nama_obat,satuan_k,status_kronis',
            'permintaanracikan.mobat:kd_obat,nama_obat,satuan_k,status_kronis',
        ]);

        $msg = [
            'data' => [
                'id' => $kirimresep->id,
                'noreg' => $kirimresep->noreg,
                'depo' => $kirimresep->depo,
                'noresep' => $kirimresep->noresep,
                'status' => '1',
            ]
        ];
        event(new NotifMessageEvent($msg, 'depo-farmasi', auth()->user()));
        // cek apakah pasien rawat jalan, dan ini nanti jadi pasien selesai layanan dan ambil antrian farmasi
        $updatekunjungan = KunjunganPoli::where('rs1', $request->noreg)->where('rs17.rs8', '!=', 'POL014')->first();
        if ($updatekunjungan) {
            self::kirimResepDanSelesaiLayanan($request);
        }
        return new JsonResponse([
            'message' => 'Resep Berhasil Dikirim Kedepo Farmasi...!!!',
            'data' => $kirimresep
        ], 200);
    }


    public function terimaResep(Request $request)
    {
        $data = Resepkeluarheder::find($request->id);
        if ($data) {
            $user = auth()->user()->pegawai_id;
            $data->flag = '2';
            $data->tgl_diterima = date('Y-m-d H:i:s');
            $data->user = $user;
            $data->save();
            // $msg = [
            //     'data' => [
            //         'id' => $data->id,
            //         'noreg' => $data->noreg,
            //         'depo' => $data->depo,
            //         'noresep' => $data->noresep,
            //         'status' => '2',
            //     ]
            // ];
            // event(new NotifMessageEvent($msg, 'depo-farmasi', auth()->user()));

            $kunjunganpoli = KunjunganPoli::where('rs1', $request->noreg)->where('rs17.rs8', '!=', 'POL014')->first();
            if ($kunjunganpoli) {
                /**
                 * update waktu
                 */
                $input = new Request([
                    'noreg' => $request->noreg
                ]);
                $cek = Bpjsrespontime::where('noreg', $request->noreg)->where('taskid', 6)->count();

                if ($cek === 0 || $cek === '') {
                    //5 (akhir waktu layan poli/mulai waktu tunggu farmasi),
                    //6 (akhir waktu tunggu farmasi/mulai waktu layan farmasi membuat obat),
                    //7 (akhir waktu obat selesai dibuat),

                    BridantrianbpjsController::updateWaktu($input, 6);
                }
                /**
                 * update waktu end
                 */
            }
            return new JsonResponse(['message' => 'Resep Diterima', 'data' => $data], 200);
        }
        return new JsonResponse(['message' => 'data tidak ditemukan'], 410);
    }
    public function resepSelesai(Request $request)
    {
        $data = Resepkeluarheder::find($request->id);
        $user = auth()->user()->pegawai_id;
        // return new JsonResponse(['user' => $user, 'data' => $data], 410);
        if ($data) {
            if ($data->flag === '3') {
                return new JsonResponse(['message' => 'Resep Sudah Diselesaikan', 'data' => $data], 200);
            }
            $data->update([
                'flag' => '3',
                'tgl' => date('Y-m-d'),
                'tgl_selesai' => date('Y-m-d H:i:s'),
                'user' => $user
            ]);
            // $msg = [
            //     'data' => [
            //         'id' => $data->id,
            //         'noreg' => $data->noreg,
            //         'depo' => $data->depo,
            //         'noresep' => $data->noresep,
            //         'status' => '3',
            //     ]
            // ];
            // event(new NotifMessageEvent($msg, 'depo-farmasi', auth()->user()));

            $kunjunganpoli = KunjunganPoli::where('rs1', $request->noreg)->where('rs17.rs8', '!=', 'POL014')->first();
            if ($kunjunganpoli) {
                /**
                 * update waktu
                 */
                $input = new Request([
                    'noreg' => $request->noreg
                ]);
                $cek = Bpjsrespontime::where('noreg', $request->noreg)->where('taskid', 7)->count();

                if ($cek === 0 || $cek === '') {
                    //5 (akhir waktu layan poli/mulai waktu tunggu farmasi),
                    //6 (akhir waktu tunggu farmasi/mulai waktu layan farmasi membuat obat),
                    //7 (akhir waktu obat selesai dibuat),

                    BridantrianbpjsController::updateWaktu($input, 7);
                }
                /**
                 * update waktu end
                 */
            }

            return new JsonResponse(['message' => 'Resep Selesai', 'data' => $data], 200);
        }
        return new JsonResponse(['message' => 'data tidak ditemukan'], 410);
    }
    /**
     * old obat keluar
     */
    public function eresepobatkeluar(Request $request)
    {
        if ($request->jenisresep == 'Racikan') {
            $simpanrinci = Resepkeluarrinciracikan::with('mobat:kd_obat,nama_obat')
                ->where('noresep', $request->noresep)
                ->where('namaracikan', $request->namaracikan)
                ->where('kdobat', $request->kdobat)
                ->first();
            if ($simpanrinci) {
                return new JsonResponse([
                    'rinci' => $simpanrinci,
                    'message' => 'Data Sudah Disimpan'
                ], 201);
            }
        } else {
            $simpanrinci = Resepkeluarrinci::with('mobat:kd_obat,nama_obat')
                ->where('noresep', $request->noresep)
                ->where('kdobat', $request->kdobat)
                ->first();
            if ($simpanrinci) {
                return new JsonResponse([
                    'rinci' => $simpanrinci,
                    'message' => 'Data Sudah Disimpan'
                ], 201);
            }
        }
        // return new JsonResponse($request->all());
        $cekjumlahstok = Stokreal::select(DB::raw('sum(jumlah) as jumlahstok'))
            ->where('kdobat', $request->kdobat)->where('kdruang', $request->kodedepo)
            ->where('jumlah', '>', 0)
            ->orderBy('id')
            ->first();
        $jumlahstok = (int)$cekjumlahstok->jumlahstok;
        if ((int)$request->jumlah > $jumlahstok) {
            return new JsonResponse(['message' => 'Maaf Stok Tidak Mencukupi...!!!'], 500);
        }

        $user = FormatingHelper::session_user();
        try {
            DB::connection('farmasi')->beginTransaction();

            // $jmldiminta = $request->jumlah;
            if ($request->jumlah == 0) {

                if ($request->jenisresep == 'Racikan') {
                    $simpanrinci = Resepkeluarrinciracikan::create(
                        [
                            'noreg' => $request->noreg,
                            'noresep' => $request->noresep,
                            'tiperacikan' => $request->tiperacikan,
                            'namaracikan' => $request->namaracikan,
                            'kdobat' => $request->kdobat,
                            // 'nopenerimaan' => $caristok[$index]->nopenerimaan,
                            'nopenerimaan' => 0,
                            'jumlahdibutuhkan' => $request->jumlahdibutuhkan,
                            'jumlah' => $request->jumlah,
                            // 'harga_beli' => $caristok[$index]->harga,
                            'harga_beli' => 0,
                            // 'hpp' => $harga,
                            'hpp' => 0,
                            // 'harga_jual' => $hargajual,
                            'harga_jual' => 0,
                            // 'nilai_r' => $request->nilai_r,
                            'nilai_r' => 0,
                            'user' => $user['kodesimrs']
                        ]
                    );
                } else {
                    $simpanrinci = Resepkeluarrinci::create(
                        [
                            'noreg' => $request->noreg,
                            'noresep' => $request->noresep,
                            'kdobat' => $request->kdobat,
                            'kandungan' => $request->kandungan ?? '',
                            'fornas' => $request->fornas ?? '',
                            'forkit' => $request->forkit ?? '',
                            'generik' => $request->generik ?? '',
                            'kode108' => $request->kode108,
                            'uraian108' => $request->uraian108,
                            'kode50' => $request->kode50,
                            'uraian50' => $request->uraian50,
                            // 'nopenerimaan' => $caristok[$index]->nopenerimaan,
                            'nopenerimaan' => '',
                            // 'jumlah' => $caristok[$index]->jumlah,
                            'jumlah' => $request->jumlah,
                            // 'harga_beli' => $caristok[$index]->harga,
                            'harga_beli' => 0,
                            // 'hpp' => $harga,
                            'hpp' => 0,
                            // 'harga_jual' => $hargajual,
                            'harga_jual' => 0,
                            // 'nilai_r' => $request->nilai_r,
                            'nilai_r' => 0,
                            'aturan' => $request->aturan,
                            // 'konsumsi' => $request->konsumsi,
                            'konsumsi' => 0,
                            'keterangan' => $request->keterangan ?? '',
                            'user' => $user['kodesimrs']
                        ]
                    );
                }
                $simpanrinci->load('mobat:kd_obat,nama_obat');
                DB::connection('farmasi')->commit();
                return new JsonResponse([
                    'rinci' => $simpanrinci,
                    'message' => 'Data Disimpan dengan jumlah 0'
                ], 200);
            }


            $jmldiminta = $request->jumlah;
            $caristok = Stokreal::lockForUpdate()
                ->where('kdobat', $request->kdobat)
                ->where('kdruang', $request->kodedepo)
                ->where('jumlah', '>', 0)
                ->orderBy('tglpenerimaan', 'ASC')
                ->get();

            $index = 0;
            $masuk = $jmldiminta;
            if ($masuk > 0) {
                while ($masuk > 0) {
                    $sisa = $caristok[$index]->jumlah;

                    $har = HargaHelper::getHarga($request->kdobat, $request->groupsistembayar);
                    $res = $har['res'];
                    if ($res) {
                        return new JsonResponse(['message' => $har['message'], 'data' => $har], 410);
                    }
                    $hargajual = $har['hargaJual'];
                    $harga = $har['harga'];

                    if ($sisa >= $masuk) {
                        $sisax = $sisa - $masuk;

                        if ($request->jenisresep == 'Racikan') {
                            $simpanrinci = Resepkeluarrinciracikan::create(
                                [
                                    'noreg' => $request->noreg,
                                    'noresep' => $request->noresep,
                                    'namaracikan' => $request->namaracikan,
                                    'tiperacikan' => $request->tiperacikan,
                                    'kdobat' => $request->kdobat,
                                    'nopenerimaan' => $caristok[$index]->nopenerimaan,
                                    'jumlahdibutuhkan' => $request->jumlahdibutuhkan,
                                    'jumlah' => $masuk,
                                    'harga_beli' => $caristok[$index]->harga,
                                    'hpp' => $harga,
                                    'harga_jual' => $hargajual,
                                    'nilai_r' => $request->nilai_r ?? 0,
                                    'keterangan_bypass' => $request->keterangan_bypass,
                                    'user' => $user['kodesimrs']
                                ]
                            );
                        } else {
                            $simpanrinci = Resepkeluarrinci::create(
                                [
                                    'noreg' => $request->noreg,
                                    'noresep' => $request->noresep,
                                    'kdobat' => $request->kdobat,
                                    'kandungan' => $request->kandungan ?? '',
                                    'fornas' => $request->fornas ?? '',
                                    'forkit' => $request->forkit ?? '',
                                    'generik' => $request->generik ?? '',
                                    'kode108' => $request->kode108,
                                    'uraian108' => $request->uraian108,
                                    'kode50' => $request->kode50,
                                    'uraian50' => $request->uraian50,
                                    'nopenerimaan' => $caristok[$index]->nopenerimaan,
                                    'jumlah' => $masuk,
                                    'harga_beli' => $caristok[$index]->harga,
                                    'hpp' => $harga,
                                    'harga_jual' => $hargajual,
                                    'nilai_r' => $request->nilai_r ?? 0,
                                    'aturan' => $request->aturan,
                                    'konsumsi' => $request->konsumsi,
                                    'keterangan' => $request->keterangan ?? '',
                                    'keterangan_bypass' => $request->keterangan_bypass,
                                    'user' => $user['kodesimrs']
                                ]
                            );
                        }
                        $caristok[$index]->update([
                            'jumlah' => $sisax
                        ]);
                        // Stokreal::where('id', $caristok[$index]->id)
                        //     ->update([
                        //         'jumlah' => $sisax
                        //     ]);


                        $masuk = 0;
                        $simpanrinci->load('mobat:kd_obat,nama_obat');
                    } else {
                        $sisax = $masuk - $sisa;

                        if ($request->jenisresep == 'Racikan') {
                            $simpanrinci = Resepkeluarrinciracikan::create(
                                [
                                    'noreg' => $request->noreg,
                                    'noresep' => $request->noresep,
                                    'kdobat' => $request->kdobat,
                                    'tiperacikan' => $request->tiperacikan,
                                    'namaracikan' => $request->namaracikan,
                                    'nopenerimaan' => $caristok[$index]->nopenerimaan,
                                    'jumlahdibutuhkan' => $request->jumlahdibutuhkan,
                                    'jumlah' => $caristok[$index]->jumlah,
                                    'harga_beli' => $caristok[$index]->harga,
                                    'hpp' => $harga,
                                    'harga_jual' => $hargajual,
                                    'nilai_r' => $request->nilai_r ?? 0,
                                    'keterangan_bypass' => $request->keterangan_bypass,
                                    'user' => $user['kodesimrs']
                                ]
                            );
                        } else {
                            $simpanrinci = Resepkeluarrinci::create(
                                [
                                    'noreg' => $request->noreg,
                                    'noresep' => $request->noresep,
                                    'kdobat' => $request->kdobat,
                                    'kandungan' => $request->kandungan ?? '',
                                    'fornas' => $request->fornas ?? '',
                                    'forkit' => $request->forkit ?? '',
                                    'generik' => $request->generik ?? '',
                                    'kode108' => $request->kode108,
                                    'uraian108' => $request->uraian108,
                                    'kode50' => $request->kode50,
                                    'uraian50' => $request->uraian50,
                                    'nopenerimaan' => $caristok[$index]->nopenerimaan,
                                    'jumlah' => $caristok[$index]->jumlah,
                                    'harga_beli' => $caristok[$index]->harga,
                                    'hpp' => $harga,
                                    'harga_jual' => $hargajual,
                                    'nilai_r' => $request->nilai_r ?? 0,
                                    'aturan' => $request->aturan,
                                    'konsumsi' => $request->konsumsi,
                                    'keterangan' => $request->keterangan ?? '',
                                    'keterangan_bypass' => $request->keterangan_bypass,
                                    'user' => $user['kodesimrs']
                                ]
                            );
                        }

                        $caristok[$index]->update([
                            'jumlah' => 0
                        ]);
                        // Stokreal::where('id', $caristok[$index]->id)
                        //     ->update([
                        //         'jumlah' => 0
                        //     ]);

                        $masuk = $sisax;
                        $index = $index + 1;
                        $simpanrinci->load('mobat:kd_obat,nama_obat');
                    }
                }

                DB::connection('farmasi')->commit();
                return new JsonResponse([
                    'rinci' => $simpanrinci,
                    'stok' => $caristok ?? [],
                    'message' => 'Data Berhasil Disimpan...!!!'
                ], 200);
            }
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
    /**
     * new obat keluar
     */
    public function newEresepobatkeluar(Request $request)
    {
        if ($request->jenisresep == 'Racikan') {
            $simpanrinci = Resepkeluarrinciracikan::with('mobat:kd_obat,nama_obat')
                ->where('noresep', $request->noresep)
                ->where('namaracikan', $request->namaracikan)
                ->where('kdobat', $request->kdobat)
                ->first();
            if ($simpanrinci) {
                return new JsonResponse([
                    'rinci' => $simpanrinci,
                    'message' => 'Data Sudah Disimpan'
                ], 201);
            }
        } else {
            $simpanrinci = Resepkeluarrinci::with('mobat:kd_obat,nama_obat')
                ->where('noresep', $request->noresep)
                ->where('kdobat', $request->kdobat)
                ->first();
            if ($simpanrinci) {
                return new JsonResponse([
                    'rinci' => $simpanrinci,
                    'message' => 'Data Sudah Disimpan'
                ], 201);
            }
        }
        // return new JsonResponse($request->all(), 410);
        // pembatasan obat fornas ranap start --------
        /**
         * 1. cek apakah obat itu ada ppembatasan
         * 2. cek apakah ruangan termasuk yang dikecualikan
         * 3. hitung jumlah obat keluar, jika melebihi batasan, maka return error
         * 4. cek retur, jika diretur maka kurangi sejumlah yang di retur
         *
         * depo = $request->kodedepo
         * kdobat = $request->kdobat
         * kd_ruang = $request->kdruangan
         */
        if ($request->kodedepo === 'Gd-04010102' && (int)$request->groupsistembayar === 1 && (int)$request->jumlah > 0) {
            $headerResep = Resepkeluarheder::where('noresep', $request->noresep)->first();
            if ($headerResep) {
                $kdRuang = $headerResep->ruangan;
                $pembatasanFornas = RestriksiObat::where('depo', $request->kodedepo)
                    ->where('kd_obat', $request->kdobat)
                    ->orderBy('tgl_mulai_berlaku', 'desc')
                    ->first();
                if ($pembatasanFornas) {
                    $jumlahPembatasan = (int) $pembatasanFornas->jumlah;
                    $kecualiRuang = RestriksiObatKecualiRuangan::where('depo', $request->kodedepo)
                        ->where('kd_obat', $request->kdobat)
                        ->where('kd_ruang', $kdRuang)
                        ->first();
                    if (!$kecualiRuang) {
                        $jumlah = (int)$request->jumlah;
                        // jumlah obat keluar
                        $rincianKeluar = Resepkeluarrinci::where('resep_keluar_h.noreg', $request->noreg)
                            ->leftJoin('resep_keluar_h', 'resep_keluar_r.noresep', '=', 'resep_keluar_h.noresep')
                            ->where('kdobat', $request->kdobat)
                            ->where('ruangan', '!=', 'POL014')
                            ->sum('jumlah');
                        // jumlah retur
                        $retur = Returpenjualan_r::where('retur_penjualan_r.noreg', $request->noreg)
                            ->leftJoin('retur_penjualan_h', 'retur_penjualan_r.noretur', '=', 'retur_penjualan_h.noretur')
                            ->where('kdobat', $request->kdobat)
                            ->where('kdruangan', '!=', 'POL014')
                            ->sum('retur_penjualan_r.jumlah_retur');
                        $obatKeluar = (int)$rincianKeluar - (int)$retur;

                        $obat = Mobatnew::select('nama_obat', 'kd_obat')->where('kd_obat', '=', $request->kdobat)->first();
                        if ((int)$obatKeluar >= (int)$jumlahPembatasan) {
                            return new JsonResponse([
                                'message' => 'Jumlah ' . $obat->nama_obat . ' sudah diberikan sebanyak ' . $obatKeluar .
                                    ' batasan Restriksi fornas adalah ' . $jumlahPembatasan,
                            ], 410);
                        }
                        if ((int)$obatKeluar + (int)$jumlah > (int)$jumlahPembatasan) {

                            return new JsonResponse([
                                'message' => 'Jumlah ' . $obat->nama_obat . ' sudah diberikan sebanyak ' . $obatKeluar .
                                    ' ditambahkan dengan jumlah dikeluarkan sekarang sejumlah ' . (int)$jumlah .
                                    ' melebihi batasan Restriksi fornas yaitu ' . $jumlahPembatasan . ' ( ' . (int)$obatKeluar + (int)$jumlah . ' >= ' . (int)$jumlahPembatasan . ' ) ',
                            ], 410);
                        }
                    }
                }
            }

            // return new JsonResponse([
            //     'message' => 'cek pembatasan',
            //     'pembatasanFornas' => $pembatasanFornas,
            //     'kecualiRuang' => $kecualiRuang ?? null,
            //     'jumlah' => $jumlah ?? null,
            //     'rincianKeluar' => $rincianKeluar ?? null,
            //     'retur' => $retur ?? null,
            //     'obatKeluar' => $obatKeluar ?? null,
            //     'jumlahPembatasan' => $jumlahPembatasan ?? null,
            // ], 410);
        }
        // pembatasan obat fornas ranap end --------


        $cekjumlahstok = Stokreal::select(DB::raw('sum(jumlah) as jumlahstok'))
            ->where('kdobat', $request->kdobat)->where('kdruang', $request->kodedepo)
            ->where('jumlah', '>', 0)
            ->orderBy('id')
            ->first();
        $jumlahstok = (int)$cekjumlahstok->jumlahstok;
        if ((int)$request->jumlah > $jumlahstok) {
            return new JsonResponse(['message' => 'Maaf Stok Tidak Mencukupi...!!!'], 500);
        }

        $user = FormatingHelper::session_user();
        try {
            $temp = DB::connection('farmasi')->transaction(function () use ($request, $user) {
                if ($request->jumlah == 0) {

                    if ($request->jenisresep == 'Racikan') {
                        $simpanrinci = Resepkeluarrinciracikan::create(
                            [
                                'noreg' => $request->noreg,
                                'noresep' => $request->noresep,
                                'tiperacikan' => $request->tiperacikan,
                                'namaracikan' => $request->namaracikan,
                                'kdobat' => $request->kdobat,
                                // 'nopenerimaan' => $caristok[$index]->nopenerimaan,
                                'nopenerimaan' => 0,
                                'jumlahdibutuhkan' => $request->jumlahdibutuhkan,
                                'jumlah' => $request->jumlah,
                                // 'harga_beli' => $caristok[$index]->harga,
                                'harga_beli' => 0,
                                // 'hpp' => $harga,
                                'hpp' => 0,
                                // 'harga_jual' => $hargajual,
                                'harga_jual' => 0,
                                // 'nilai_r' => $request->nilai_r,
                                'nilai_r' => 0,
                                'user' => $user['kodesimrs']
                            ]
                        );
                    } else {
                        $simpanrinci = Resepkeluarrinci::create(
                            [
                                'noreg' => $request->noreg,
                                'noresep' => $request->noresep,
                                'kdobat' => $request->kdobat,
                                'kandungan' => $request->kandungan ?? '',
                                'fornas' => $request->fornas ?? '',
                                'forkit' => $request->forkit ?? '',
                                'generik' => $request->generik ?? '',
                                'kode108' => $request->kode108,
                                'uraian108' => $request->uraian108,
                                'kode50' => $request->kode50,
                                'uraian50' => $request->uraian50,
                                // 'nopenerimaan' => $caristok[$index]->nopenerimaan,
                                'nopenerimaan' => '',
                                // 'jumlah' => $caristok[$index]->jumlah,
                                'jumlah' => $request->jumlah,
                                // 'harga_beli' => $caristok[$index]->harga,
                                'harga_beli' => 0,
                                // 'hpp' => $harga,
                                'hpp' => 0,
                                // 'harga_jual' => $hargajual,
                                'harga_jual' => 0,
                                // 'nilai_r' => $request->nilai_r,
                                'nilai_r' => 0,
                                'aturan' => $request->aturan,
                                // 'konsumsi' => $request->konsumsi,
                                'konsumsi' => 0,
                                'keterangan' => $request->keterangan ?? '',
                                'user' => $user['kodesimrs']
                            ]
                        );
                    }
                    if (!$simpanrinci) {
                        throw new \Exception('Rincian Gagal Disimpan...!');
                    }
                    $simpanrinci->load('mobat:kd_obat,nama_obat');
                    // DB::connection('farmasi')->commit();
                    return [
                        'rinci' => $simpanrinci,
                        'message' => 'Data Disimpan dengan jumlah 0'
                    ];
                }
                $jmldiminta = $request->jumlah;
                $caristok = Stokreal::lockForUpdate()
                    ->where('kdobat', $request->kdobat)
                    ->where('kdruang', $request->kodedepo)
                    ->where('jumlah', '>', 0)
                    ->orderBy('tglpenerimaan', 'ASC')
                    ->get();

                foreach ($caristok as $stokItem) { // Perbaikan: ganti while dengan foreach
                    if ($jmldiminta <= 0) break; // Perbaikan: keluar dari loop jika jumlah sudah cukup

                    $sisa = $stokItem->jumlah;
                    $har = HargaHelper::getHarga($request->kdobat, $request->groupsistembayar);
                    $res = $har['res'];
                    if ($res) {
                        throw new \Exception($har['message']); // Perbaikan: lempar exception jika harga tidak ditemukan
                    }

                    $hargajual = $har['hargaJual'];
                    $harga = $har['harga'];

                    // Tentukan jumlah yang akan dikurangi pada stok saat ini
                    $pengurangan = min($jmldiminta, $sisa); // Perbaikan: kurangi sesuai kebutuhan atau stok yang tersedia

                    if ($request->jenisresep == 'Racikan') {
                        $simpanrinci = Resepkeluarrinciracikan::create([
                            'noreg' => $request->noreg,
                            'noresep' => $request->noresep,
                            'namaracikan' => $request->namaracikan,
                            'tiperacikan' => $request->tiperacikan,
                            'kdobat' => $request->kdobat,
                            'nopenerimaan' => $stokItem->nopenerimaan,
                            'jumlahdibutuhkan' => $request->jumlahdibutuhkan,
                            'jumlah' => $pengurangan, // Perbaikan: set jumlah sesuai pengurangan
                            'harga_beli' => $stokItem->harga,
                            'hpp' => $harga,
                            'harga_jual' => $hargajual,
                            'nilai_r' => $request->nilai_r ?? 0,
                            'keterangan_bypass' => $request->keterangan_bypass,
                            'user' => $user['kodesimrs']
                        ]);
                    } else {
                        $simpanrinci = Resepkeluarrinci::create([
                            'noreg' => $request->noreg,
                            'noresep' => $request->noresep,
                            'kdobat' => $request->kdobat,
                            'kandungan' => $request->kandungan ?? '',
                            'fornas' => $request->fornas ?? '',
                            'forkit' => $request->forkit ?? '',
                            'generik' => $request->generik ?? '',
                            'kode108' => $request->kode108,
                            'uraian108' => $request->uraian108,
                            'kode50' => $request->kode50,
                            'uraian50' => $request->uraian50,
                            'nopenerimaan' => $stokItem->nopenerimaan,
                            'jumlah' => $pengurangan, // Perbaikan: set jumlah sesuai pengurangan
                            'harga_beli' => $stokItem->harga,
                            'hpp' => $harga,
                            'harga_jual' => $hargajual,
                            'nilai_r' => $request->nilai_r ?? 0,
                            'aturan' => $request->aturan,
                            'konsumsi' => $request->konsumsi,
                            'keterangan' => $request->keterangan ?? '',
                            'keterangan_bypass' => $request->keterangan_bypass,
                            'user' => $user['kodesimrs']
                        ]);
                    }
                    if (!$simpanrinci) {
                        throw new \Exception('Rincian Obat gagal disimpan');
                    }
                    // Update jumlah stok pada item
                    $stokItem->decrement('jumlah', $pengurangan); // Perbaikan: langsung update jumlah dalam satu langkah
                    $jmldiminta -= $pengurangan; // Perbaikan: kurangi permintaan yang sudah terpenuhi

                    $simpanrinci->load('mobat:kd_obat,nama_obat');
                }
                return [
                    'rinci' => $simpanrinci,
                    'stok' => $caristok ?? [],
                ];
            });
            return new JsonResponse([
                'temp' => $temp,
                'rinci' => $temp['rinci'],
                'stok' => $temp['stok'] ?? [],
                'message' => 'Data Berhasil Disimpan...!!!'
            ], 200);

            // $jmldiminta = $request->jumlah;
        } catch (\Exception $e) {
            DB::connection('farmasi')->rollBack();
            return response()->json([
                'message' => 'ada kesalahan : ' . $e->getMessage(),
                'err line' =>  $e->getLine(),
                'err file' =>  $e->getFile(),
                'error e' => '' . $e,
                'stok' => $dataStok ?? null
            ], 410);
        }
    }

    public function hapusPermintaanObat(Request $request)
    {
        if ($request->has('namaracikan')) {
            $obat = Permintaanresepracikan::find($request->id);
            if ($obat) {
                $obat->delete();
                self::hapusHeader($request);
                $endas = Resepkeluarheder::where('noreg', $request->noreg)->with(
                    'permintaanresep.mobat:kd_obat,nama_obat',
                    'permintaanracikan.mobat:kd_obat,nama_obat'
                )->get();
                return new JsonResponse([
                    'message' => 'Permintaan resep Obat Racikan telah dihapus',
                    'obat' => $obat,
                    'newapotekrajal' => $endas,
                ]);
            }
            return new JsonResponse([
                'message' => 'Permintaan resep Obat Racikan Gagal dihapus',
                'obat' => $obat,
            ], 410);
        }
        $obat = Permintaanresep::find($request->id);
        if ($obat) {
            $obat->delete();
            self::hapusHeader($request);
            $endas = Resepkeluarheder::where('noreg', $request->noreg)->with(
                'permintaanresep.mobat:kd_obat,nama_obat',
                'permintaanracikan.mobat:kd_obat,nama_obat'
            )->get();
            return new JsonResponse([
                'message' => 'Permintaan resep Obat telah dihapus',
                'obat' => $obat,
                'newapotekrajal' => $endas,
            ]);
        }
        return new JsonResponse([
            'message' => 'Permintaan resep Obat Gagal dihapus',
            'obat' => $obat,
        ], 410);
    }

    public static function hapusHeader($request)
    {
        $racik = Permintaanresepracikan::where('noresep', $request->noresep)->get();
        $nonracik = Permintaanresep::where('noresep', $request->noresep)->get();
        if (count($racik) === 0 && count($nonracik) === 0) {
            $head = Resepkeluarheder::where('noresep', $request->noresep)->first();
            if ($head) {
                $head->delete();
            }
            $sistembayarlain = Sistembayarlain::where('noresep', $request->noresep)->first();
            if ($sistembayarlain) {
                $sistembayarlain->delete();
            }
        }
    }
    public function hapusPermintaanResep(Request $request)
    {
        $head = Resepkeluarheder::where('noresep', $request->noresep)->where('flag', '')->first();
        if (!$head) {
            return new JsonResponse([
                'message' => 'Permintaan resep tidak ditemukan atau sudah dikirimkan',
            ], 410);
        }
        $head->delete();
        $racik = Permintaanresepracikan::where('noresep', $request->noresep)->get();
        if (count($racik) > 0) {
            foreach ($racik as $key) {
                $key->delete();
            }
        }
        $nonracik = Permintaanresep::where('noresep', $request->noresep)->get();
        if (count($nonracik) > 0) {
            foreach ($nonracik as $key) {
                $key->delete();
            }
        }
        return new JsonResponse([
            'message' => 'Permintaan resep telah dihapus',
        ]);
    }
    public static function cekpemberianobat($request, $jumlahstok)
    {
        // ini tujuannya mencari sisa obat pasien dengan dihitung jumlah konsumsi obat per hari bersasarkan signa
        // harus ada data jumlah hari (obat dikonsumsi dalam ... hari) di tabel

        $cekmaster = Mobatnew::select('kandungan')->where('kd_obat', $request->kodeobat)->first();

        // $jumlahdosis = $request->jumlahdosis;
        // $jumlah = $request->jumlah;
        // $jmlhari = (int) $jumlah / $jumlahdosis;
        // $total = (int) $jmlhari + (int) $jumlahstok;
        if ($cekmaster->kandungan === '') {
            $hasil = Resepkeluarheder::select(
                'resep_keluar_h.noresep as noresep',
                'resep_keluar_h.tgl as tgl',
                'resep_keluar_r.konsumsi',
                DB::raw('(DATEDIFF(CURRENT_DATE(), resep_keluar_h.tgl)+1) as selisih')
            )
                ->leftjoin('resep_keluar_r', 'resep_keluar_h.noresep', 'resep_keluar_r.noresep')
                ->where('resep_keluar_h.norm', $request->norm)
                ->where('resep_keluar_r.kdobat', $request->kodeobat)
                ->orderBy('resep_keluar_h.tgl', 'desc')
                ->limit(1)
                ->get();
        } else {
            $hasil = Resepkeluarheder::select(
                'resep_keluar_h.noresep as noresep',
                'resep_keluar_h.tgl as tgl',
                'resep_keluar_r.konsumsi',
                DB::raw('(DATEDIFF(CURRENT_DATE(), resep_keluar_h.tgl)+1) as selisih')
            )
                ->leftjoin('resep_keluar_r', 'resep_keluar_h.noresep', 'resep_keluar_r.noresep')
                ->leftjoin('new_masterobat', 'new_masterobat.kd_obat', 'resep_keluar_r.kdobat')
                ->where('resep_keluar_h.norm', $request->norm)
                ->where('resep_keluar_r.kdobat', $request->kodeobat)
                ->where('new_masterobat.kandungan', $request->kandungan)
                ->orderBy('resep_keluar_h.tgl', 'desc')
                ->limit(1)
                ->get();
        }
        $selisih = 0;
        $total = 0;
        if (count($hasil)) {
            $selisih = $hasil[0]->selisih;
            $total = (float)$hasil[0]->konsumsi;
            if ($selisih <= $total) {
                return [
                    'status' => 1,
                    'hasil' => $hasil,
                    'selisih' => $selisih,
                    'total' => $total,
                ];
            } else {
                return [
                    'status' => 2,
                    'hasil' => $hasil,
                    'selisih' => $selisih,
                    'total' => $total,
                ];
                // return 2;
            }
        }
        return [
            'status' => 2,
            'hasil' => $hasil,
            'selisih' => $selisih,
            'total' => $total,
        ];
    }

    public function ambilIter(Request $request)
    {
        // $noresep = $request->noresep_asal ?? $request->noresep;
        // 'permintaanresep.mobat:kd_obat,nama_obat,satuan_k,status_kronis',
        //     'permintaanresep.mobat.indikasi',
        //     'permintaanresep.aturansigna:signa,jumlah',
        //     'permintaanracikan.mobat:kd_obat,nama_obat,satuan_k,kekuatan_dosis,status_kronis,kelompok_psikotropika',
        //     'permintaanracikan.mobat.indikasi',
        //     'asalpermintaanresep.mobat:kd_obat,nama_obat,satuan_k,status_kronis',
        //     'asalpermintaanresep.mobat.indikasi',
        //     'asalpermintaanresep.aturansigna:signa,jumlah',
        $head = Resepkeluarheder::where('noresep', $request->noresep)
            ->when(
                $request->noresep_asal === null || $request->noresep_asal === '',
                function ($h) use ($request) {
                    $h->with([
                        'permintaanresep.mobat:kd_obat,nama_obat,satuan_k,status_kronis',
                        'permintaanresep.mobat.indikasi',
                        'permintaanresep.stok' => function ($stok) use ($request) {
                            $stok->selectRaw('kdobat, sum(jumlah) as total')
                                ->where('kdruang', $request->depo)
                                ->where('jumlah', '>', 0)
                                ->with([
                                    'transnonracikan' => function ($transnonracikan) use ($request) {
                                        $transnonracikan->select(
                                            // 'resep_keluar_r.kdobat as kdobat',
                                            'resep_permintaan_keluar.kdobat as kdobat',
                                            'resep_keluar_h.depo as kdruang',
                                            DB::raw('sum(resep_permintaan_keluar.jumlah) as jumlah')
                                        )
                                            ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_permintaan_keluar.noresep')
                                            ->where('resep_keluar_h.depo', $request->depo)
                                            ->whereIn('resep_keluar_h.flag', ['', '1', '2'])
                                            ->groupBy('resep_permintaan_keluar.kdobat');
                                    },
                                    'transracikan' => function ($transracikan) use ($request) {
                                        $transracikan->select(
                                            // 'resep_keluar_racikan_r.kdobat as kdobat',
                                            'resep_permintaan_keluar_racikan.kdobat as kdobat',
                                            'resep_keluar_h.depo as kdruang',
                                            DB::raw('sum(resep_permintaan_keluar_racikan.jumlah) as jumlah')
                                        )
                                            ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_permintaan_keluar_racikan.noresep')
                                            ->where('resep_keluar_h.depo', $request->depo)
                                            ->whereIn('resep_keluar_h.flag', ['', '1', '2'])
                                            ->groupBy('resep_permintaan_keluar_racikan.kdobat');
                                    },
                                    'permintaanobatrinci' => function ($permintaanobatrinci) use ($request) {
                                        $permintaanobatrinci->select(
                                            'permintaan_r.no_permintaan',
                                            'permintaan_r.kdobat',
                                            DB::raw('sum(permintaan_r.jumlah_minta) as allpermintaan')
                                        )
                                            ->leftJoin('permintaan_h', 'permintaan_h.no_permintaan', '=', 'permintaan_r.no_permintaan')
                                            // biar yang ada di tabel mutasi ga ke hitung
                                            ->leftJoin('mutasi_gudangdepo', function ($anu) {
                                                $anu->on('permintaan_r.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                                                    ->on('permintaan_r.kdobat', '=', 'mutasi_gudangdepo.kd_obat');
                                            })
                                            ->whereNull('mutasi_gudangdepo.kd_obat')

                                            ->where('permintaan_h.tujuan', $request->depo)
                                            ->whereIn('permintaan_h.flag', ['', '1', '2'])
                                            ->groupBy('permintaan_r.kdobat');
                                    },

                                ])
                                ->groupBy('kdobat');
                        },
                        'permintaanracikan.mobat:kd_obat,nama_obat,satuan_k,status_kronis',
                        'permintaanracikan.mobat.indikasi',
                        'permintaanracikan.stok' => function ($stok) use ($request) {
                            $stok->selectRaw('kdobat, sum(jumlah) as total')
                                ->where('kdruang', $request->depo)
                                ->where('jumlah', '>', 0)->with([
                                    'transnonracikan' => function ($transnonracikan) use ($request) {
                                        $transnonracikan->select(
                                            // 'resep_keluar_r.kdobat as kdobat',
                                            'resep_permintaan_keluar.kdobat as kdobat',
                                            'resep_keluar_h.depo as kdruang',
                                            DB::raw('sum(resep_permintaan_keluar.jumlah) as jumlah')
                                        )
                                            ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_permintaan_keluar.noresep')
                                            ->where('resep_keluar_h.depo', $request->depo)
                                            ->whereIn('resep_keluar_h.flag', ['', '1', '2'])
                                            ->groupBy('resep_permintaan_keluar.kdobat');
                                    },
                                    'transracikan' => function ($transracikan) use ($request) {
                                        $transracikan->select(
                                            // 'resep_keluar_racikan_r.kdobat as kdobat',
                                            'resep_permintaan_keluar_racikan.kdobat as kdobat',
                                            'resep_keluar_h.depo as kdruang',
                                            DB::raw('sum(resep_permintaan_keluar_racikan.jumlah) as jumlah')
                                        )
                                            ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_permintaan_keluar_racikan.noresep')
                                            ->where('resep_keluar_h.depo', $request->depo)
                                            ->whereIn('resep_keluar_h.flag', ['', '1', '2'])
                                            ->groupBy('resep_permintaan_keluar_racikan.kdobat');
                                    },
                                    'permintaanobatrinci' => function ($permintaanobatrinci) use ($request) {
                                        $permintaanobatrinci->select(
                                            'permintaan_r.no_permintaan',
                                            'permintaan_r.kdobat',
                                            DB::raw('sum(permintaan_r.jumlah_minta) as allpermintaan')
                                        )
                                            ->leftJoin('permintaan_h', 'permintaan_h.no_permintaan', '=', 'permintaan_r.no_permintaan')
                                            // biar yang ada di tabel mutasi ga ke hitung
                                            ->leftJoin('mutasi_gudangdepo', function ($anu) {
                                                $anu->on('permintaan_r.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                                                    ->on('permintaan_r.kdobat', '=', 'mutasi_gudangdepo.kd_obat');
                                            })
                                            ->whereNull('mutasi_gudangdepo.kd_obat')

                                            ->where('permintaan_h.tujuan', $request->depo)
                                            ->whereIn('permintaan_h.flag', ['', '1', '2'])
                                            ->groupBy('permintaan_r.kdobat');
                                    },

                                ])
                                ->groupBy('kdobat');
                        },
                        'rincian' => function ($ri) {
                            $ri->select('*', DB::raw('sum(jumlah) as jumlah'),  'harga_jual as hargajual')
                                ->with(
                                    'mobat:kd_obat,nama_obat,satuan_k,status_kronis',
                                    'mobat.indikasi'
                                )
                                ->groupBy('kdobat', 'noresep', 'noreg');
                        },
                        'rincianracik' => function ($ri) {
                            $ri->select('*', DB::raw('sum(jumlah) as jumlah'))
                                ->with(
                                    'mobat:kd_obat,nama_obat,satuan_k,status_kronis',
                                    'mobat.indikasi'
                                )
                                ->groupBy('kdobat', 'noresep', 'noreg', 'namaracikan');
                        },
                        'kunjunganrajal' => function ($kunjunganrajal) {
                            $kunjunganrajal->select('rs1', 'rs9')->with('doktersimpeg:kdpegsimrs,nama');
                        },
                        'poli',
                        'info',
                        'antrian' => function ($q) {
                            $q->where('pelayanan_id', 'AP0001');
                        },
                        'kwitansi',
                        'telaah',
                        'diagnosas:rs1,rs3,rs13',
                        'diagnosas.masterdiagnosa:rs1,rs3,rs4',
                        'ruanganranap',
                        'laborat.pemeriksaan_laborat',
                        'sistembayar',
                        'sep:rs1,rs8',
                        'dokter:kdpegsimrs,nama',
                        'kunjunganranap:rs1,titipan,rs6,rs3,rs4',
                        'kunjunganranap.ruangtitipan:rs1,rs2',
                        // 'kunjunganranap.kamarranap',
                        'datapasien' => function ($quer) {
                            $quer->select(
                                'rs1',
                                'rs2 as nama',
                                'rs46 as noka',
                                'rs16 as tgllahir',
                                'rs2 as nama_panggil',
                                DB::raw('concat(rs4," KEL ",rs5," RT ",rs7," RW ",rs8," ",rs6," ",rs11," ",rs10) as alamat'),
                            );
                        }
                    ]);
                },
                function ($h) use ($request) {
                    $h->where('noresep_asal', $request->noresep_asal)
                        ->with([
                            'asalpermintaanresep' => function ($per) {
                                $per->select('resep_permintaan_keluar.*')
                                    ->leftJoin('resep_keluar_r', function ($join) {
                                        $join->on('resep_keluar_r.noresep', '=', 'resep_permintaan_keluar.noresep')
                                            ->on('resep_keluar_r.kdobat', '=', 'resep_permintaan_keluar.kdobat');
                                    })
                                    ->whereNotNull('resep_keluar_r.kdobat')
                                    ->groupBy('resep_keluar_r.kdobat');
                            },
                            'asalpermintaanracikan' => function ($per) {
                                $per->select('resep_permintaan_keluar_racikan.*')
                                    ->leftJoin('resep_keluar_racikan_r', function ($join) {
                                        $join->on('resep_keluar_racikan_r.noresep', '=', 'resep_permintaan_keluar_racikan.noresep')
                                            ->on('resep_keluar_racikan_r.kdobat', '=', 'resep_permintaan_keluar_racikan.kdobat');
                                    })
                                    ->whereNotNull('resep_keluar_racikan_r.kdobat')
                                    ->groupBy('resep_keluar_racikan_r.kdobat');
                            },
                            'asalpermintaanresep.mobat:kd_obat,nama_obat,satuan_k,status_kronis',
                            'asalpermintaanresep.mobat.indikasi',
                            'asalpermintaanresep.aturansigna:signa,jumlah',
                            'asalpermintaanresep.stok' => function ($stok) use ($request) {
                                $stok->selectRaw('kdobat, sum(jumlah) as total')
                                    ->where('kdruang', $request->depo)
                                    ->where('jumlah', '>', 0)
                                    ->with([
                                        'transnonracikan' => function ($transnonracikan) use ($request) {
                                            $transnonracikan->select(
                                                // 'resep_keluar_r.kdobat as kdobat',
                                                'resep_permintaan_keluar.kdobat as kdobat',
                                                'resep_keluar_h.depo as kdruang',
                                                DB::raw('sum(resep_permintaan_keluar.jumlah) as jumlah')
                                            )
                                                ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_permintaan_keluar.noresep')
                                                ->where('resep_keluar_h.depo', $request->depo)
                                                ->whereIn('resep_keluar_h.flag', ['', '1', '2'])
                                                ->groupBy('resep_permintaan_keluar.kdobat');
                                        },
                                        'transracikan' => function ($transracikan) use ($request) {
                                            $transracikan->select(
                                                // 'resep_keluar_racikan_r.kdobat as kdobat',
                                                'resep_permintaan_keluar_racikan.kdobat as kdobat',
                                                'resep_keluar_h.depo as kdruang',
                                                DB::raw('sum(resep_permintaan_keluar_racikan.jumlah) as jumlah')
                                            )
                                                ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_permintaan_keluar_racikan.noresep')
                                                ->where('resep_keluar_h.depo', $request->depo)
                                                ->whereIn('resep_keluar_h.flag', ['', '1', '2'])
                                                ->groupBy('resep_permintaan_keluar_racikan.kdobat');
                                        },
                                        'permintaanobatrinci' => function ($permintaanobatrinci) use ($request) {
                                            $permintaanobatrinci->select(
                                                'permintaan_r.no_permintaan',
                                                'permintaan_r.kdobat',
                                                DB::raw('sum(permintaan_r.jumlah_minta) as allpermintaan')
                                            )
                                                ->leftJoin('permintaan_h', 'permintaan_h.no_permintaan', '=', 'permintaan_r.no_permintaan')
                                                // biar yang ada di tabel mutasi ga ke hitung
                                                ->leftJoin('mutasi_gudangdepo', function ($anu) {
                                                    $anu->on('permintaan_r.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                                                        ->on('permintaan_r.kdobat', '=', 'mutasi_gudangdepo.kd_obat');
                                                })
                                                ->whereNull('mutasi_gudangdepo.kd_obat')

                                                ->where('permintaan_h.tujuan', $request->depo)
                                                ->whereIn('permintaan_h.flag', ['', '1', '2'])
                                                ->groupBy('permintaan_r.kdobat');
                                        },

                                    ])
                                    ->groupBy('kdobat');
                            },
                            'asalpermintaanracikan.mobat:kd_obat,nama_obat,satuan_k,status_kronis',
                            'asalpermintaanracikan.stok' => function ($stok) use ($request) {
                                $stok->selectRaw('kdobat, sum(jumlah) as total')
                                    ->where('kdruang', $request->depo)
                                    ->where('jumlah', '>', 0)->with([
                                        'transnonracikan' => function ($transnonracikan) use ($request) {
                                            $transnonracikan->select(
                                                // 'resep_keluar_r.kdobat as kdobat',
                                                'resep_permintaan_keluar.kdobat as kdobat',
                                                'resep_keluar_h.depo as kdruang',
                                                DB::raw('sum(resep_permintaan_keluar.jumlah) as jumlah')
                                            )
                                                ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_permintaan_keluar.noresep')
                                                ->where('resep_keluar_h.depo', $request->depo)
                                                ->whereIn('resep_keluar_h.flag', ['', '1', '2'])
                                                ->groupBy('resep_permintaan_keluar.kdobat');
                                        },
                                        'transracikan' => function ($transracikan) use ($request) {
                                            $transracikan->select(
                                                // 'resep_keluar_racikan_r.kdobat as kdobat',
                                                'resep_permintaan_keluar_racikan.kdobat as kdobat',
                                                'resep_keluar_h.depo as kdruang',
                                                DB::raw('sum(resep_permintaan_keluar_racikan.jumlah) as jumlah')
                                            )
                                                ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_permintaan_keluar_racikan.noresep')
                                                ->where('resep_keluar_h.depo', $request->depo)
                                                ->whereIn('resep_keluar_h.flag', ['', '1', '2'])
                                                ->groupBy('resep_permintaan_keluar_racikan.kdobat');
                                        },
                                        'permintaanobatrinci' => function ($permintaanobatrinci) use ($request) {
                                            $permintaanobatrinci->select(
                                                'permintaan_r.no_permintaan',
                                                'permintaan_r.kdobat',
                                                DB::raw('sum(permintaan_r.jumlah_minta) as allpermintaan')
                                            )
                                                ->leftJoin('permintaan_h', 'permintaan_h.no_permintaan', '=', 'permintaan_r.no_permintaan')
                                                // biar yang ada di tabel mutasi ga ke hitung
                                                ->leftJoin('mutasi_gudangdepo', function ($anu) {
                                                    $anu->on('permintaan_r.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                                                        ->on('permintaan_r.kdobat', '=', 'mutasi_gudangdepo.kd_obat');
                                                })
                                                ->whereNull('mutasi_gudangdepo.kd_obat')

                                                ->where('permintaan_h.tujuan', $request->depo)
                                                ->whereIn('permintaan_h.flag', ['', '1', '2'])
                                                ->groupBy('permintaan_r.kdobat');
                                        },

                                    ])
                                    ->groupBy('kdobat');
                            },
                            'rincian' => function ($ri) {
                                $ri->select('*', DB::raw('sum(jumlah) as jumlah'))
                                    ->with(
                                        'mobat:kd_obat,nama_obat,satuan_k,status_kronis',
                                        'mobat.indikasi'
                                    )
                                    ->groupBy('kdobat', 'noresep', 'noreg');
                            },
                            'rincianracik' => function ($ri) {
                                $ri->select('*', DB::raw('sum(jumlah) as jumlah'))
                                    ->with(
                                        'mobat:kd_obat,nama_obat,satuan_k,status_kronis',
                                        'mobat.indikasi'
                                    )
                                    ->groupBy('kdobat', 'noresep', 'noreg', 'namaracikan');
                            },
                            'kunjunganrajal' => function ($kunjunganrajal) {
                                $kunjunganrajal->select('rs1', 'rs9')->with('doktersimpeg:kdpegsimrs,nama');
                            },
                            'poli',
                            'info',
                            'antrian' => function ($q) {
                                $q->where('pelayanan_id', 'AP0001');
                            },
                            'kwitansi',
                            'telaah',
                            'diagnosas:rs1,rs3,rs13',
                            'diagnosas.masterdiagnosa:rs1,rs3,rs4',
                            'ruanganranap',
                            'laborat.pemeriksaan_laborat',
                            'sistembayar',
                            'sep:rs1,rs8',
                            'dokter:kdpegsimrs,nama',
                            'kunjunganranap:rs1,titipan,rs6,rs3,rs4',
                            'kunjunganranap.ruangtitipan:rs1,rs2',
                            // 'kunjunganranap.kamarranap',
                            'datapasien' => function ($quer) {
                                $quer->select(
                                    'rs1',
                                    'rs2 as nama',
                                    'rs46 as noka',
                                    'rs16 as tgllahir',
                                    'rs2 as nama_panggil',
                                    DB::raw('concat(rs4," KEL ",rs5," RT ",rs7," RW ",rs8," ",rs6," ",rs11," ",rs10) as alamat'),
                                );
                            }
                        ]);
                }
            )
            ->select(
                'resep_keluar_h.*',
                DB::raw('(TIMESTAMPDIFF(DAY,resep_keluar_h.tgl_kirim,resep_keluar_h.tgl_selesai)) AS rt_hari'),
                DB::raw('((TIMESTAMPDIFF(HOUR,resep_keluar_h.tgl_kirim,resep_keluar_h.tgl_selesai))%24) AS rt_jam'),
                DB::raw('((TIMESTAMPDIFF(MINUTE,resep_keluar_h.tgl_kirim,resep_keluar_h.tgl_selesai))%60) AS rt_menit'),
                DB::raw('((TIMESTAMPDIFF(SECOND,resep_keluar_h.tgl_kirim,resep_keluar_h.tgl_selesai))%60) AS rt_detik'),
            )
            ->first();
        if ($request->noresep_asal !== null) {
            $head->permintaanresep = $head->asalpermintaanresep;
            $head->permintaanracikan = $head->asalpermintaanracikan;
            // $resp['head'] = $head;
            // // $resp['noresep'] = $noresep;
            // // $resp['sistembayar'] = $sistembayar->groups;
            // $resp['req'] = $request->all();
            // return new JsonResponse($resp);
        }
        $sistembayar = SistemBayar::where('rs1', $head->sistembayar)->first();
        if (count($head->permintaanresep) > 0) {
            foreach ($head->permintaanresep as $key) {
                $har = HargaHelper::getHarga($key['kdobat'], $sistembayar->groups);
                $key['res'] = $har;
                $key['hargapokok'] = $har['harga'] ?? 0;
                $key['hargajual'] = $har['hargaJual'] ?? 0;
                $key['groupsistembayar'] = $sistembayar->groups;
            }
        }
        if (count($head->permintaanracikan) > 0) {
            foreach ($head->permintaanracikan as $key) {
                $har = HargaHelper::getHarga($key['kdobat'], $sistembayar->groups);
                $key['res'] = $har;
                $key['hargapokok'] = $har['harga'] ?? 0;
                $key['hargajual'] = $har['hargaJual'] ?? 0;
                $key['groupsistembayar'] = $sistembayar->groups;
            }
        }
        $resp['head'] = $head;
        // $resp['noresep'] = $noresep;
        $resp['sistembayar'] = $sistembayar->groups;
        $resp['req'] = $request->all();
        return new JsonResponse($resp);
    }

    public function copyResep(Request $request)
    {
        // return new JsonResponse([
        //     'req' => $request->all()
        // ], 410);

        $head = $request->head;
        $ada = Resepkeluarheder::where('tiperesep', $head['tiperesep'])
            ->whereDate('tgl', $head['tgl'])
            ->where('noresep_asal', $head['noresep_asal'])
            ->first();
        if ($ada) {
            $ada->load('rincian.mobat:kd_obat,nama_obat', 'rincianracik.mobat:kd_obat,nama_obat');
            return new JsonResponse(
                [
                    'message' => 'Resep Iter Sudah dibuat Hari ini',
                    'data' => $ada
                ],
                410
            );
        }

        $hasilCek = [];
        $lanjut = $request->lanjut ?? '';
        if (count($request->kirimResep) > 0) {
            foreach ($request->kirimResep as $key) {
                $cek = self::cekpemberianobatCopy($key['kdobat'], $head['norm'], $key['kandungan']);
                if ($cek['status'] == 1 && $lanjut !== '1') {
                    return new JsonResponse(['message' => '', 'cek' => $cek], 202);
                }
                $hasilCek[] = $cek;
            }
        }
        $procedure = 'resepkeluardeporajal(@nomor)';
        $colom = 'deporajal';
        $lebel = 'iter-D-RJ';
        DB::connection('farmasi')->select('call ' . $procedure);
        $x = DB::connection('farmasi')->table('conter')->select($colom)->get();
        $wew = $x[0]->$colom;
        $noresep = FormatingHelper::resep($wew, $lebel);

        // $noresep = 'noresepbaru';
        $resep = [];
        $racik = [];

        try {
            DB::connection('farmasi')->beginTransaction();
            $head['noresep'] = $noresep;
            $head['flag'] = '2';
            // $head['tgl'] = date('Y-m-d');
            // $head['tgl_permintaan'] = date('Y-m-d H:i:s');
            $head['tgl_kirim'] = date('Y-m-d H:i:s');
            $head['tgl_diterima'] = date('Y-m-d H:i:s');
            $head['tgl_selesai'] = null;

            if (count($request->kirimResep) > 0) {
                foreach ($request->kirimResep as $key) {
                    // $cek = self::cekpemberianobatCopy($key['kdobat'], $head['norm'], $key['kandungan']);
                    // if ($cek['status'] == 1 && $lanjut !== '1') {
                    //     return new JsonResponse(['message' => '', 'cek' => $cek], 202);
                    // }
                    // $hasilCek[] = $cek;
                    $jmldiminta = $key['jumlahMinta'];
                    unset($key['jumlahMinta']);
                    $caristok = Stokreal::where('kdobat', $key['kdobat'])
                        ->where('kdruang', $request->kddepo)
                        ->where('jumlah', '>', 0)
                        ->orderBy('tglexp')
                        ->get();

                    $index = 0;
                    $masuk = $jmldiminta;
                    while ($masuk > 0) {
                        $sisa = $caristok[$index]->jumlah;

                        $har = HargaHelper::getHarga($key['kdobat'], $request->groupsistembayar);
                        $res = $har['res'];
                        if ($res) {
                            return new JsonResponse(['message' => $har['message'], 'data' => $har], 410);
                        }
                        $hargajual = $har['hargaJual'];
                        $harga = $har['harga'];
                        if ($sisa < $masuk) {
                            $sisax = $masuk - $sisa;

                            $key['nopenerimaan'] = $caristok[$index]->nopenerimaan;
                            $key['jumlah'] = $caristok[$index]->jumlah;
                            $key['harga_beli'] = $caristok[$index]->harga;
                            $key['hpp'] = $harga;
                            $key['harga_jual'] = $hargajual;


                            $key['noresep'] = $noresep;
                            $key['created_at'] = date('Y-m-d H:i:s');
                            $key['updated_at'] = date('Y-m-d H:i:s');

                            $stok = Stokreal::where('id', $caristok[$index]->id)
                                //     ->first();
                                // return new JsonResponse($stok);
                                ->update(['jumlah' => 0]);

                            $masuk = $sisax;
                            $index = $index + 1;
                        } else {
                            $sisax = $sisa - $masuk;

                            $key['nopenerimaan'] = $caristok[$index]->nopenerimaan;
                            $key['jumlah'] = $masuk;
                            $key['harga_beli'] = $caristok[$index]->harga;
                            $key['hpp'] = $harga;
                            $key['harga_jual'] = $hargajual;


                            $key['noresep'] = $noresep;
                            $key['created_at'] = date('Y-m-d H:i:s');
                            $key['updated_at'] = date('Y-m-d H:i:s');

                            $stok = Stokreal::where('id', $caristok[$index]->id)
                                //     ->first();
                                // return new JsonResponse([
                                //     'stok' => $stok,
                                //     'kdruang' => $request->kddepo,
                                //     'caristok' => $caristok[$index],
                                // ]);
                                ->update(['jumlah' => $sisax]);

                            $masuk = 0;
                        }

                        $resep[] = $key;
                    }
                }
            }

            if (count($request->kirimRacik) > 0) {
                foreach ($request->kirimRacik as $key) {
                    $jmldiminta = $key['jumlahMinta'];
                    unset($key['jumlahMinta']);
                    $caristok = Stokreal::where('kdobat', $key['kdobat'])->where('kdruang', $request->kddepo)
                        ->where('jumlah', '>', 0)
                        ->orderBy('tglexp')
                        ->get();

                    $index = 0;
                    $masuk = $jmldiminta;
                    while ($masuk > 0) {
                        $sisa = $caristok[$index]->jumlah;

                        $har = HargaHelper::getHarga($key['kdobat'], $request->groupsistembayar);
                        $res = $har['res'];
                        if ($res) {
                            return new JsonResponse(['message' => $har['message'], 'data' => $har], 410);
                        }
                        $hargajual = $har['hargaJual'];
                        $harga = $har['harga'];
                        if ($sisa < $masuk) {
                            $sisax = $masuk - $sisa;
                            $key['nopenerimaan'] = $caristok[$index]->nopenerimaan;
                            $key['jumlah'] = $caristok[$index]->jumlah;
                            $key['harga_beli'] = $caristok[$index]->harga;
                            $key['hpp'] = $harga;
                            $key['harga_jual'] = $hargajual;

                            $key['jumlahdibutuhkan'] = $key['jumlahdibutuhkan'] ?? 0;
                            $key['noresep'] = $noresep;
                            $key['satuan_racik'] = $key['satuan_racik'] ?? '';
                            $key['created_at'] = date('Y-m-d H:i:s');
                            $key['updated_at'] = date('Y-m-d H:i:s');


                            Stokreal::where('id', $caristok[$index]->id)
                                ->update(['jumlah' => 0]);

                            $masuk = $sisax;
                            $index = $index + 1;
                        } else {
                            $sisax = $sisa - $masuk;

                            $key['nopenerimaan'] = $caristok[$index]->nopenerimaan;
                            $key['jumlah'] = $masuk;
                            $key['harga_beli'] = $caristok[$index]->harga;
                            $key['hpp'] = $harga;
                            $key['harga_jual'] = $hargajual;


                            $key['noresep'] = $noresep;
                            $key['satuan_racik'] = $key['satuan_racik'] ?? '';
                            $key['created_at'] = date('Y-m-d H:i:s');
                            $key['updated_at'] = date('Y-m-d H:i:s');

                            Stokreal::where('id', $caristok[$index]->id)
                                ->update(['jumlah' => $sisax]);

                            $masuk = 0;
                        }
                    }




                    $racik[] = $key;
                }
            }
            if (count($request->kirimResep) <= 0 && count($request->kirimRacik) <= 0) {
                return new JsonResponse(['message' => 'Tidak ada obat untuk di input'], 410);
            }
            /**
             * start of create section
             */

            $createHead = Resepkeluarheder::create($head);
            if (count($resep) > 0) {
                $createResep = Resepkeluarrinci::insert($resep);
            }
            if (count($racik) > 0) {
                $createRacik = Resepkeluarrinciracikan::insert($racik);
            }

            /**
             * end of create section
             */

            $data['req'] = $request->all();
            $data['head'] = $head;
            $data['hasilCek'] = $hasilCek;
            $data['resep'] = $resep;
            $data['racik'] = $racik;
            $data['createHead'] = $createHead;
            $data['createResep'] = $createResep ?? false;
            $data['createRacik'] = $createRacik ?? false;
            $data['caristok'] = $caristok ?? false;
            $data['jmldiminta'] = $jmldiminta ?? false;
            $data['message'] = 'Copy Resep selesai dan Obat sudah berkurang';
            DB::connection('farmasi')->commit();
            return new JsonResponse($data);
        } catch (\Exception $e) {
            DB::connection('farmasi')->rollBack();
            return response()->json(['message' => 'ada kesalahan', 'error' => $e], 410);
        }
    }

    public static function cekpemberianobatCopy($obat, $norm, $kandungan)
    {
        // ini tujuannya mencari sisa obat pasien dengan dihitung jumlah konsumsi obat per hari bersasarkan signa
        // harus ada data jumlah hari (obat dikonsumsi dalam ... hari) di tabel

        $cekmaster = Mobatnew::select('kandungan')->where('kd_obat', $obat)->first();

        // $jumlahdosis = $request->jumlahdosis;
        // $jumlah = $request->jumlah;
        // $jmlhari = (int) $jumlah / $jumlahdosis;
        // $total = (int) $jmlhari + (int) $jumlahstok;
        if ($cekmaster->kandungan === '') {
            $hasil = Resepkeluarheder::select(
                'resep_keluar_h.noresep as noresep',
                'resep_keluar_h.tgl as tgl',
                'resep_keluar_r.konsumsi',
                DB::raw('(DATEDIFF(CURRENT_DATE(), resep_keluar_h.tgl)+1) as selisih')
            )
                ->leftjoin('resep_keluar_r', 'resep_keluar_h.noresep', 'resep_keluar_r.noresep')
                ->where('resep_keluar_h.norm', $norm)
                ->where('resep_keluar_r.kdobat', $obat)
                ->orderBy('resep_keluar_h.tgl', 'desc')
                ->limit(1)
                ->get();
        } else {
            $hasil = Resepkeluarheder::select(
                'resep_keluar_h.noresep as noresep',
                'resep_keluar_h.tgl as tgl',
                'resep_keluar_r.konsumsi',
                DB::raw('(DATEDIFF(CURRENT_DATE(), resep_keluar_h.tgl)+1) as selisih')
            )
                ->leftjoin('resep_keluar_r', 'resep_keluar_h.noresep', 'resep_keluar_r.noresep')
                ->leftjoin('new_masterobat', 'new_masterobat.kd_obat', 'resep_keluar_r.kdobat')
                ->where('resep_keluar_h.norm', $norm)
                ->where('resep_keluar_r.kdobat', $obat)
                ->where('new_masterobat.kandungan', $kandungan)
                ->orderBy('resep_keluar_h.tgl', 'desc')
                ->limit(1)
                ->get();
        }
        $selisih = 0;
        $total = 0;
        if (count($hasil)) {
            $selisih = $hasil[0]->selisih;
            $total = (float)$hasil[0]->konsumsi;
            if ($selisih <= $total) {
                return [
                    'status' => 1,
                    'hasil' => $hasil,
                    'selisih' => $selisih,
                    'total' => $total,
                ];
            } else {
                return [
                    'status' => 2,
                    'hasil' => $hasil,
                    'selisih' => $selisih,
                    'total' => $total,
                ];
                // return 2;
            }
        }
        return [
            'status' => 2,
            'hasil' => $hasil,
            'selisih' => $selisih,
            'total' => $total,
        ];
    }

    public function ambilHistory(Request $request)
    {
        $data['req'] = $request->all();
        $data['data'] = Resepkeluarheder::where('noresep_asal', $request->noresep)
            ->with([
                'rincian',
                'rincianracik',
                'asalpermintaanresep.mobat:kd_obat,nama_obat,satuan_k,kandungan',
                'asalpermintaanracikan.mobat:kd_obat,nama_obat,satuan_k,kandungan',
            ])
            ->get();
        return new JsonResponse($data);
    }

    public function getPegawaiFarmasi()
    {
        $data = Pegawai::select('nama', 'id', 'kdpegsimrs')
            ->where('aktif', '=', 'AKTIF')

            ->where('ruang', '=', 'R00025')
            ->whereNotNull('satset_uuid')


            ->get();

        return new JsonResponse($data);
    }

    public function simPelIOnfOb(Request $request)
    {
        // return $request->all();
        try {
            DB::connection('farmasi')->beginTransaction();
            $data = PelayananInformasiObat::updateOrCreate(
                [
                    'norm' => $request->norm,
                    'noreg' => $request->noreg,
                ],
                [
                    'tanggal' => $request->tanggal,
                    'metode' => $request->metode,
                    'nama_penanya' => $request->nama_penanya,
                    'status_penanya' => $request->status_penanya,
                    'tlp_penanya' => $request->tlp_penanya,
                    'umur_pasien' => $request->umur_pasien,
                    'kehamilan' => $request->kehamilan,
                    'kasus_khusus' => $request->kasus_khusus,
                    'jenis_kelamin' => $request->jenis_kelamin,
                    'menyusui' => $request->menyusui,
                    'uraian_pertanyaan' => $request->uraian_pertanyaan,
                    'obat_non_eresep' => $request->obat_non_eresep,
                    'jenis_pertanyaan' => $request->jenis_pertanyaan,
                    'kode' => $request->kode,
                    'jawaban' => $request->jawaban,
                    'referensi' => $request->referensi,
                    'apoteker' => $request->apoteker,
                    'user_input' => $request->user_input,

                ]
            );
            if (!$data) {
                return new JsonResponse(['message' => 'Pelayanan Infromasi Obat gagal disimpan'], 410);
            }
            DB::connection('farmasi')->commit();
            return new JsonResponse([
                'message' => 'Pelayanan Infromasi Obat sudah disimpan',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            DB::connection('farmasi')->rollBack();
            return response()->json(['message' => 'ada kesalahan', 'error' => $e], 410);
        }
    }
    public static function kirimResepDanSelesaiLayanan($request)
    {

        $newData = new Request([
            'norm' => $request->norm,
            'kodepoli' => 'AP0001',
            // 'kodepoli' => $request->kodepoli,
        ]);
        $input = new Request([
            'noreg' => $request->noreg
        ]);
        AntrianController::ambilnoantrian($newData, $input);


        $cek = Bpjsrespontime::where('noreg', $request->noreg)->where('taskid', 5)->count();

        if ($cek === 0 || $cek === '') {
            //5 (akhir waktu layan poli/mulai waktu tunggu farmasi),
            //6 (akhir waktu tunggu farmasi/mulai waktu layan farmasi membuat obat),

            BridantrianbpjsController::updateWaktu($input, 5);
        }
        $user = Pegawai::find(auth()->user()->pegawai_id);
        if ($user->kdgroupnakes === 1 || $user->kdgroupnakes === '1') {
            $updatekunjungan = KunjunganPoli::where('rs1', $request->noreg)->first();
            $updatekunjungan->rs19 = '1';
            $updatekunjungan->rs24 = '1';
            $updatekunjungan->save();
            return new JsonResponse(['message' => 'ok'], 200);
        } else {
            return new JsonResponse(['message' => 'MAAF FITUR INI HANYA UNTUK DOKTER...!!!'], 500);
        }
    }

    public function tolakResep(Request $request)
    {
        // return new JsonResponse($request->all(), 410);
        $data = Resepkeluarheder::find($request->id);
        if (!$data) {
            $data2 = Resepkeluarheder::where($request->noresep)->first();
            if (!$data2) {
                return new JsonResponse([
                    'message' => 'Resep Tidak Ditemukan',
                    'data' => $data
                ]);
            }
            $data2->flag = '5';
            $data->alasan = $request->alasan ?? null;
            $data2->save();
            return new JsonResponse([
                'message' => 'Resep sudah ditolak',
                'data' => $data2
            ]);
        }
        $rincian = Resepkeluarrinci::where('noresep', $request->noresep)->get();
        $rincianRacik = Resepkeluarrinciracikan::where('noresep', $request->noresep)->get();
        if (sizeof($rincian) > 0 || sizeof($rincianRacik) > 0) {
            return new JsonResponse([
                'message' => 'Resep tidak boleh ditolak karena sudah ada obat keluar',
            ], 410);
        }
        $data->flag = '5';
        $data->alasan = $request->alasan ?? null;
        $data->save();
        return new JsonResponse([
            'message' => 'Resep sudah ditolak',
            'data' => $data
        ]);
    }
    public function isiAlasan(Request $request)
    {
        $data = Resepkeluarheder::find($request->id);
        if (!$data) {
            $data2 = Resepkeluarheder::where($request->noresep)->first();
            if (!$data2) {
                return new JsonResponse([
                    'message' => 'Resep Tidak Ditemukan',
                    'data' => $data
                ]);
            }
            $data->alasan = $request->alasan ?? null;
            $data2->save();
            return new JsonResponse([
                'message' => 'Alasan Sudah Di Isi',
                'data' => $data2
            ]);
        }
        $data->alasan = $request->alasan ?? null;
        $data->save();
        return new JsonResponse([
            'message' => 'Alasan Sudah Di Isi',
            'data' => $data
        ]);
    }

    public function getResepDokter()
    {
        $user = FormatingHelper::session_user();
        $kdDok = [];
        if ($user['kodesimrs'] === 'sa') {
            if (request('dokter')) {
                $dokter = Pegawai::select('kdpegsimrs')->where('nama', 'LIKE', '%' . request('dokter') . '%')->where('aktif', 'AKTIF')->get();
                $kdDok = collect($dokter)->map(function ($dk) {
                    return $dk->kdpegsimrs;
                });
                // return new JsonResponse($dokter);
            }
        } else {
            $kdDok = [$user['kodesimrs']];
        }

        // return new JsonResponse($kdDok);
        $rm = [];
        if (request('q') !== null) {
            if (preg_match('~[0-9]+~', request('q'))) {
                $rm = [];
            } else {
                if (strlen(request('q')) >= 3) {
                    $data = Mpasien::select('rs1 as norm')->where('rs2', 'LIKE', '%' . request('q') . '%')->get();
                    $rm = collect($data)->map(function ($x) {
                        return $x->norm;
                    });
                } else $rm = [];
            }
        }

        $listresep = Resepkeluarheder::with(
            [
                'rincian.mobat:kd_obat,nama_obat,satuan_k,status_kronis',
                'rincianracik.mobat:kd_obat,nama_obat,satuan_k,status_kronis',
                'permintaanresep.mobat:kd_obat,nama_obat,satuan_k,status_kronis',
                'permintaanresep.aturansigna:signa,jumlah',
                'permintaanracikan.mobat:kd_obat,nama_obat,satuan_k,kekuatan_dosis,status_kronis,kelompok_psikotropika',
                'poli',
                'info',
                'ruanganranap',
                'sistembayar',
                'sep:rs1,rs8',
                'dokter:kdpegsimrs,nama',
                'datapasien' => function ($quer) {
                    $quer->select(
                        'rs1',
                        'rs2 as nama',
                        'rs46 as noka',
                        'rs16 as tgllahir',
                        DB::raw('concat(rs4," KEL ",rs5," RT ",rs7," RW ",rs8," ",rs6," ",rs11," ",rs10) as alamat'),
                    );
                }
            ]
        )
            ->where(function ($query) use ($rm) {
                $query->when(count($rm) > 0, function ($wew) use ($rm) {
                    $wew->whereIn('norm', $rm);
                })
                    ->orWhere('noresep', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('norm', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('noreg', 'LIKE', '%' . request('q') . '%');
            })
            // ->where('depo', request('kddepo'))
            ->when(count($kdDok) > 0, function ($q) use ($kdDok) {
                $q->whereIn('dokter', $kdDok);
            })

            ->when(request('flag'), function ($x) {
                if (request('flag') === 'semua') {
                    $x->whereIn('flag', ['', '1', '2', '3', '4', '5']);
                } else if (request('flag') === null || request('flag') === '') {
                    $x->where('flag', '');
                } else {
                    $x->where('flag', request('flag'));
                }
            })
            ->when(!request('flag'), function ($x) {
                $x->where('flag', '');
            })

            ->orderBy('flag', 'ASC')
            ->orderBy('tgl_permintaan', 'ASC')
            ->paginate(request('per_page'));
        // return new JsonResponse(request()->all());
        return new JsonResponse($listresep);
    }


    public function cekTemplateResep(Request $request)
    {
        $templateId = $request->template_id;
        $kodedepo = $request->kodedepo;
        $groupsistembayar = $request->groupsistembayar;

        // ambil template
        $nonRacik = TemplateResepRinci::select('kodeobat', 'namaobat', DB::raw('sum(jumlah_diminta) as jumlah_diminta'))->where('template_id', $templateId)->where('racikan', 0)->groupby('kodeobat')->get();
        $racik = TemplateResepRinci::where('template_id', $templateId)->where('racikan', 1)->get();
        $racikan = [];
        $kdobat = [];
        if (count($racik) > 0) {
            foreach ($racik as $key) {
                $temp = TemplateResepRacikan::select('kodeobat', 'namaobat', DB::raw('sum(jumlah_diminta) as jumlah_diminta'))->where('obat_id', $key['id'])->groupby('kodeobat')->get();
                $key['rinci'] = $temp;
                $racikan[] = $key;
                foreach ($temp as $kd) {
                    $kdobat[] = $kd['kodeobat'];
                }
            }
        }
        foreach ($nonRacik as $kd) {
            $kdobat[] = $kd['kodeobat'];
        }
        $uniqueObat = array_unique($kdobat);
        $cekjumlahstok = Stokreal::select('kdobat', DB::raw('sum(jumlah) as jumlahstok'))
            ->whereIn('kdobat', $uniqueObat)
            ->where('kdruang', $request->kodedepo)
            ->where('jumlah', '>', 0)
            ->with([
                'transnonracikan' => function ($transnonracikan) use ($request) {
                    $transnonracikan->select(
                        // 'resep_keluar_r.kdobat as kdobat',
                        'resep_permintaan_keluar.kdobat as kdobat',
                        'resep_keluar_h.depo as kdruang',
                        DB::raw('sum(resep_permintaan_keluar.jumlah) as jumlah')
                    )
                        ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_permintaan_keluar.noresep')
                        ->where('resep_keluar_h.depo', $request->kodedepo)
                        ->whereIn('resep_keluar_h.flag', ['', '1', '2'])
                        ->groupBy('resep_permintaan_keluar.kdobat');
                },
                'transracikan' => function ($transracikan) use ($request) {
                    $transracikan->select(
                        // 'resep_keluar_racikan_r.kdobat as kdobat',
                        'resep_permintaan_keluar_racikan.kdobat as kdobat',
                        'resep_keluar_h.depo as kdruang',
                        DB::raw('sum(resep_permintaan_keluar_racikan.jumlah) as jumlah')
                    )
                        ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_permintaan_keluar_racikan.noresep')
                        ->where('resep_keluar_h.depo', $request->kodedepo)
                        ->whereIn('resep_keluar_h.flag', ['', '1', '2'])
                        ->groupBy('resep_permintaan_keluar_racikan.kdobat');
                },
                'permintaanobatrinci' => function ($permintaanobatrinci) use ($request) {
                    $permintaanobatrinci->select(
                        'permintaan_r.no_permintaan',
                        'permintaan_r.kdobat',
                        DB::raw('sum(permintaan_r.jumlah_minta) as allpermintaan')
                    )
                        ->leftJoin('permintaan_h', 'permintaan_h.no_permintaan', '=', 'permintaan_r.no_permintaan')
                        // biar yang ada di tabel mutasi ga ke hitung
                        ->leftJoin('mutasi_gudangdepo', function ($anu) {
                            $anu->on('permintaan_r.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                                ->on('permintaan_r.kdobat', '=', 'mutasi_gudangdepo.kd_obat');
                        })
                        ->whereNull('mutasi_gudangdepo.kd_obat')

                        ->where('permintaan_h.tujuan', $request->kodedepo)
                        ->whereIn('permintaan_h.flag', ['', '1', '2'])
                        ->groupBy('permintaan_r.kdobat');
                },
                'persiapanrinci' => function ($res) use ($request) {
                    $res->select(
                        'persiapan_operasi_rincis.kd_obat',

                        DB::raw('sum(persiapan_operasi_rincis.jumlah_minta) as jumlah'),
                    )
                        ->leftJoin('persiapan_operasis', 'persiapan_operasis.nopermintaan', '=', 'persiapan_operasi_rincis.nopermintaan')
                        ->whereIn('persiapan_operasis.flag', ['', '1'])
                        ->groupBy('persiapan_operasi_rincis.kd_obat');
                },
            ])
            ->orderBy('tglexp')
            ->groupBy('kdobat')
            ->get();

        $alokasinya = collect($cekjumlahstok)->map(function ($x, $y) use ($request) {
            $total = $x->jumlahstok ?? 0;
            $jumlahper = $request->kodedepo === 'Gd-04010103' ? $x['persiapanrinci'][0]->jumlah ?? 0 : 0;
            $jumlahtrans = $x['transnonracikan'][0]->jumlah ?? 0;
            $jumlahtransx = $x['transracikan'][0]->jumlah ?? 0;
            $permintaanobatrinci = $x['permintaanobatrinci'][0]->allpermintaan ?? 0; // mutasi antar depo
            $x->alokasi = (float) $total - (float)$jumlahtrans - (float)$jumlahtransx - (float)$permintaanobatrinci - (float)$jumlahper;
            return $x;
        });

        $adaraw = [];
        $adaAlokasi = [];
        $tidakAdaAlokasi = [];
        $adaAlokasiRacikan = [];
        $tidakAdaAlokasiRacikan = [];
        foreach ($nonRacik as $non) {
            $obat = $non;
            $raw = $alokasinya->where('kdobat', $non['kodeobat']);
            $adaraw[] = $raw;
            if (count($raw) > 0) {
                $item = current((array)$raw); // value of current object
                $anu = key((array)$item); // key of the object value
                $alokasi = $item[$anu]->alokasi;
                $obat->alokasi = $alokasi;
                if ((int) $non->jumlah_diminta > (int)$alokasi) {
                    $obat->error = 'Alokasi tidak mencukupi';
                } else {
                    // $cekpemberian = self::cekpemberianobat($request, $alokasi);
                    // if ($cekpemberian['status'] == 1) {
                    //     return new JsonResponse(['message' => '', 'cek' => $cekpemberian], 202);
                    // }
                    $item[$anu]->alokasi = (int)$alokasi - (int) $non->jumlah_diminta;
                    $mobat = Mobatnew::where('kd_obat', $non['kodeobat'])->first();
                    if ($mobat) {
                        $obat->kandungan = $mobat->kandungan ?? '';
                        $obat->fornas = $mobat->fornas ?? '';
                        $obat->forkit = $mobat->forkit ?? '';
                        $obat->generik = $mobat->generik ?? '';
                        $obat->kode108 = $mobat->kode108;
                        $obat->uraian108 = $mobat->uraian108;
                        $obat->kode50 = $mobat->kode50;
                        $obat->uraian50 = $mobat->uraian50;
                    }
                    $har = HargaHelper::getHarga($obat->kodeobat, $request->groupsistembayar);
                    $res = $har['res'];
                    if ($res) {
                        $obat->error = 'tidak ada harga untuk obat ini';
                        $hargajualx = 0;
                        $harga = 0;
                    } else {
                        $hargajualx = $har['hargaJual'];
                        $harga = $har['harga'];
                    }
                    $obat->hpp = $harga;
                    $obat->hargajual = $hargajualx;

                    $templateNon = TemplateResepRinci::where('template_id', $templateId)
                        ->where('racikan', 0)
                        ->where('kodeobat', $obat->kodeobat)
                        ->first();

                    $obat->aturan = $templateNon->signa;
                    $obat->konsumsi = $templateNon->konsumsi ?? 1;
                    $obat->keterangan = $templateNon->keterangan ?? '';
                }
            } else {
                $obat->alokasi = 0;
                $obat->error = 'Alokasi tidak mencukupi';
            }
            // masukkan
            $adaError = $obat->error ?? false;
            if ($adaError) {
                $tidakAdaAlokasi[] = $obat;
            } else {
                $adaAlokasi[] = $obat;
            }
            // return new JsonResponse([
            //     'item' => $item[$anu],
            //     'alokasi' => $alokasi,
            //     'non' => $non->jumlah,
            // ]);
        }
        foreach ($racikan as $rac) {
            foreach ($rac['rinci'] as $rin) {
                $obat = $rin;
                $raw = $alokasinya->where('kdobat', $rin['kodeobat']);
                $adaraw[] = $raw;
                if (count($raw) > 0) {
                    $item = current((array)$raw); // value of current object
                    $anu = key((array)$item); // key of the object value
                    $alokasi = $item[$anu]->alokasi;
                    $obat->alokasi = $alokasi;
                    if ((int) $rin->jumlah_diminta > (int)$alokasi) {
                        $obat->error = 'Alokasi tidak mencukupi';
                    } else {

                        $mobat = Mobatnew::where('kd_obat', $non['kodeobat'])->first();
                        if ($mobat) {
                            $obat->kandungan = $mobat->kandungan ?? '';
                            $obat->fornas = $mobat->fornas ?? '';
                            $obat->forkit = $mobat->forkit ?? '';
                            $obat->generik = $mobat->generik ?? '';
                            $obat->kode108 = $mobat->kode108;
                            $obat->uraian108 = $mobat->uraian108;
                            $obat->kode50 = $mobat->kode50;
                            $obat->uraian50 = $mobat->uraian50;
                        }
                        $har = HargaHelper::getHarga($obat->kodeobat, $request->groupsistembayar);
                        $res = $har['res'];
                        if ($res) {
                            $obat->error = 'tidak ada harga untuk obat ini';
                            $hargajualx = 0;
                            $harga = 0;
                        } else {
                            $item[$anu]->alokasi = (int)$alokasi - (int) $rin->jumlah_diminta;
                            $hargajualx = $har['hargaJual'];
                            $harga = $har['harga'];
                        }
                        $obat->hpp = $harga;
                        $obat->hargajual = $hargajualx;

                        $templateRac = TemplateResepRinci::select('id')->where('template_id', $templateId)
                            ->where('racikan', 1)
                            ->get();
                        $obatRac = TemplateResepRacikan::whereIn('obat_id', $templateRac)
                            ->where('kodeobat', $obat->kodeobat)
                            ->first();

                        $obat->konsumsi = $obatRac->konsumsi ?? 1;
                        $obat->keterangan = $obatRac->keterangan ?? '';

                        $racikannya = TemplateResepRinci::find($obatRac->obat_id);

                        $obat->aturan = $racikannya->signa;
                        $obat->namaracikan = $racikannya->namaobat;
                        $obat->satuan_racik = $racikannya->satuan_kcl;
                        $obat->tiperacikan = $racikannya->tiperacikan;
                        $obat->jumlahdibutuhkan = $racikannya->jumlah_diminta;
                    }
                } else {
                    $templateRac = TemplateResepRinci::select('id')->where('template_id', $templateId)
                        ->where('racikan', 1)
                        ->get();
                    $obatRac = TemplateResepRacikan::whereIn('obat_id', $templateRac)
                        ->where('kodeobat', $obat->kodeobat)
                        ->first();
                    $racikannya = TemplateResepRinci::find($obatRac->obat_id);
                    $obat->koderacikan = $racikannya->kodeobat;
                    $obat->alokasi = 0;
                    $obat->error = 'Alokasi tidak mencukupi';
                }
                // masukkan
                $adaError = $obat->error ?? false;
                if ($adaError) {
                    $tidakAdaAlokasiRacikan[] = $obat;
                } else {
                    $adaAlokasiRacikan[] = $obat;
                }
                // return new JsonResponse([
                //     // 'item' => $item[$anu],
                //     // 'alokasi' => $alokasi,
                //     'rin' => $rin,
                // ]);
            }
        }
        if (count($tidakAdaAlokasi) > 0 || count($tidakAdaAlokasiRacikan) > 0) {

            return new JsonResponse([
                'message' => 'Gagal Alokasi Kurang',

                'adaAlokasi' => $adaAlokasi,
                'tidakAdaAlokasi' => $tidakAdaAlokasi,
                'adaAlokasiRacikan' => $adaAlokasiRacikan,
                'tidakAdaAlokasiRacikan' => $tidakAdaAlokasiRacikan,

            ], 410);
        }

        // mulai insert
        try {
            DB::connection('farmasi')->beginTransaction();

            if ($request->kodedepo === 'Gd-04010102') {
                $procedure = 'resepkeluardeporanap(@nomor)';
                $colom = 'deporanap';
                $lebel = 'D-RI';
            } elseif ($request->kodedepo === 'Gd-04010103') {
                $procedure = 'resepkeluardepook(@nomor)';
                $colom = 'depook';
                $lebel = 'D-KO';
            } elseif ($request->kodedepo === 'Gd-05010101') {


                $procedure = 'resepkeluardeporajal(@nomor)';
                $colom = 'deporajal';
                $lebel = 'D-RJ';
            } else {
                $procedure = 'resepkeluardepoigd(@nomor)';
                $colom = 'depoigd';
                $lebel = 'D-IR';
            }


            DB::connection('farmasi')->select('call ' . $procedure);
            $x = DB::connection('farmasi')->table('conter')->select($colom)->get();
            $wew = $x[0]->$colom;
            $noresep = FormatingHelper::resep($wew, $lebel);


            $created = date('Y-m-d H:i:s');
            $user = FormatingHelper::session_user();

            $simpan = Resepkeluarheder::updateOrCreate(
                [
                    'noresep' => $noresep,
                    'noreg' => $request->noreg,
                ],
                [
                    'norm' => $request->norm,
                    'tgl_permintaan' => date('Y-m-d H:i:s'),
                    'tgl_kirim' => date('Y-m-d H:i:s'),
                    'tgl' => date('Y-m-d'),
                    'depo' => $request->kodedepo,
                    'ruangan' => $request->kdruangan,
                    'dokter' =>  $user['kodesimrs'],
                    'sistembayar' => $request->sistembayar,

                    'diagnosa' => $request->diagnosa,
                    'kodeincbg' => $request->kodeincbg,
                    'uraianinacbg' => $request->uraianinacbg,
                    'tarifina' => $request->tarifina,
                    'tiperesep' => 'normal',
                    'flag' => '1',
                    // 'user' => $user['kodesimrs'],
                    // 'iter_expired' => $iter_expired,
                    // 'iter_jml' => $iter_jml,
                    // 'iter_expired' => $request->iter_expired ?? '',
                    'tagihanrs' => $request->tagihanrs ?? 0,
                ]
            );

            // if (!$simpan) {
            //     return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!'], 500);
            // }

            // ini ranah detail
            $racikandtd = [];
            $racikannondtd = [];
            $rinciaja = [];
            foreach ($adaAlokasi as $non) {
                $har = HargaHelper::getHarga($non['kodeobat'], $request->groupsistembayar);
                $res = $har['res'];
                if ($res) {
                    $hargajualx = $non['hargajual'];
                    $harga = $non['hpp'];
                } else {
                    $hargajualx = $har['hargaJual'];
                    $harga = $har['harga'];
                }

                // rinci
                $simpanrinci =
                    [
                        'noreg' => $request->noreg,
                        'noresep' => $noresep,
                        'kdobat' => $non['kodeobat'],
                        'kandungan' => $non['kandungan'] ?? '',
                        'fornas' => $non['fornas'] ?? '',
                        'forkit' => $non['forkit'] ?? '',
                        'generik' => $non['generik'] ?? '',
                        'kode108' => $non['kode108'],
                        'uraian108' => $non['uraian108'],
                        'kode50' => $non['kode50'],
                        'uraian50' => $non['uraian50'],
                        'stokalokasi' => $non['alokasi'],
                        'r' => $request->groupsistembayar === '1' || $request->groupsistembayar === 1 ? 300 : 0,
                        'jumlah' => $non['jumlah_diminta'],
                        'hpp' => $harga,
                        'hargajual' => $hargajualx,
                        'aturan' => $non['aturan'],
                        'konsumsi' => $non['konsumsi'],
                        'keterangan' => $non['keterangan'] ?? '',
                        'created_at' => $created,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];
                $rinciaja[] = $simpanrinci;
            }

            // racikan
            foreach ($adaAlokasiRacikan as $rac) {
                $har = HargaHelper::getHarga($request->kodeobat, $request->groupsistembayar);
                $res = $har['res'];
                if ($res) {
                    $hargajualx = $rac['hargajual'];
                    $harga = $rac['hpp'];
                } else {
                    $hargajualx = $har['hargaJual'];
                    $harga = $har['harga'];
                }

                if ($rac['tiperacikan'] == 'DTD') {
                    $simpandtd =
                        [
                            'noreg' => $request->noreg,
                            'noresep' => $noresep,
                            'namaracikan' => $rac['namaracikan'],
                            'tiperacikan' => $rac['tiperacikan'],
                            'jumlahdibutuhkan' => $rac['jumlahdibutuhkan'], // jumlah racikan
                            'aturan' => $rac['aturan'],
                            'konsumsi' => $rac['konsumsi'],
                            'keterangan' => $rac['keterangan'],
                            'kdobat' => $rac['kodeobat'],
                            'kandungan' => $rac['kandungan'] ?? '',
                            'fornas' => $rac['fornas'] ?? '',
                            'forkit' => $rac['forkit'] ?? '',
                            'generik' => $rac['generik'] ?? '',
                            'r' => $request->groupsistembayar === '1' || $request->groupsistembayar === 1 ? 500 : 0,
                            'hpp' => $harga,
                            'harga_jual' => $hargajualx,
                            'kode108' => $non['kode108'],
                            'uraian108' => $non['uraian108'],
                            'kode50' => $non['kode50'],
                            'uraian50' => $non['uraian50'],
                            'stokalokasi' => $non['alokasi'],
                            'dosisobat' => $non['dosisobat'] ?? 1,
                            'dosismaksimum' => $non['dosismaksimum'] ?? 1, // dosis resep
                            'jumlah' => $non['jumlah_diminta'], // jumlah obat
                            'satuan_racik' => $non['satuan_racik'], // jumlah obat
                            'keteranganx' => $non['keteranganx'] ?? '', // keterangan obat
                            'created_at' => $created,
                            'updated_at' => date('Y-m-d H:i:s'),
                        ];
                    $racikandtd[] = $simpandtd;
                    // if ($simpandtd) {
                    //     $simpandtd->load('mobat:kd_obat,nama_obat');
                    // }
                } else {
                    $simpannondtd =
                        [
                            'noreg' => $request->noreg,
                            'noresep' => $noresep,
                            'namaracikan' => $rac['namaracikan'],
                            'tiperacikan' => $rac['tiperacikan'],
                            'jumlahdibutuhkan' => $rac['jumlahdibutuhkan'],
                            'aturan' => $rac['aturan'],
                            'konsumsi' => $rac['konsumsi'],
                            'keterangan' => $rac['keterangan'],
                            'kdobat' => $rac['kodeobat'],
                            'kandungan' => $rac['kandungan'] ?? '',
                            'fornas' => $rac['fornas'] ?? '',
                            'forkit' => $rac['forkit'] ?? '',
                            'generik' => $rac['generik'] ?? '',
                            'r' => $request->groupsistembayar === '1' || $request->groupsistembayar === 1 ? 500 : 0,
                            'hpp' => $harga,
                            'harga_jual' => $hargajualx,
                            'kode108' => $rac['kode108'],
                            'uraian108' => $rac['uraian108'],
                            'kode50' => $rac['kode50'],
                            'uraian50' => $rac['uraian50'],
                            'stokalokasi' => $rac['alokasi'],
                            // 'dosisobat' => $rac['dosisobat'],
                            // 'dosismaksimum' => $rac['dosismaksimum'],
                            'jumlah' => $rac['jumlah_diminta'],
                            'satuan_racik' => $rac['satuan_racik'],
                            'keteranganx' => $rac['keteranganx'] ?? '',
                            'created_at' => $created,
                            'updated_at' => date('Y-m-d H:i:s'),
                        ];
                    $racikannondtd[] = $simpannondtd;
                    // if ($simpannondtd) {
                    //     $simpannondtd->load('mobat:kd_obat,nama_obat');
                    // }
                }
            }

            if (count($racikandtd) > 0) {
                Permintaanresepracikan::insert($racikandtd);
            }
            if (count($racikannondtd) > 0) {
                Permintaanresepracikan::insert($racikannondtd);
            }
            if (count($rinciaja) > 0) {
                Permintaanresep::insert($rinciaja);
            }

            DB::connection('farmasi')->commit();
            $simpan->load([
                'permintaanresep.mobat:kd_obat,nama_obat,satuan_k,status_kronis',
                'permintaanracikan.mobat:kd_obat,nama_obat,satuan_k,status_kronis',
            ]);

            $msg = [
                'data' => [
                    'id' => $simpan->id,
                    'noreg' => $simpan->noreg,
                    'depo' => $simpan->depo,
                    'noresep' => $simpan->noresep,
                    'status' => '1',
                ]
            ];
            event(new NotifMessageEvent($msg, 'depo-farmasi', auth()->user()));
            // cek apakah pasien rawat jalan, dan ini nanti jadi pasien selesai layanan dan ambil antrian farmasi
            $updatekunjungan = KunjunganPoli::where('rs1', $request->noreg)->where('rs17.rs8', '!=', 'POL014')->first();
            if ($updatekunjungan) {
                self::kirimResepDanSelesaiLayanan($request);
            }
            return new JsonResponse([
                'message' => 'Resep Berhasil dibuat',
                // 'adaraw' => $adaraw,
                'head' => $simpan,
                'racikandtd' => $racikandtd,
                'racikannondtd' => $racikannondtd,
                'rinci' => $rinciaja,
                'adaAlokasi' => $adaAlokasi,
                'tidakAdaAlokasi' => $tidakAdaAlokasi,
                'adaAlokasiRacikan' => $adaAlokasiRacikan,
                'tidakAdaAlokasiRacikan' => $tidakAdaAlokasiRacikan,
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
                'adaAlokasi' => $adaAlokasi,
                'tidakAdaAlokasi' => $tidakAdaAlokasi,
                'adaAlokasiRacikan' => $adaAlokasiRacikan,
                'tidakAdaAlokasiRacikan' => $tidakAdaAlokasiRacikan,
                'error' => ' ' . $e,
                'message' => 'rolled back ada kesalahan'
            ], 410);
        }
    }

    public function simpanTglPelayananObat(Request $request)
    {

        $data = Resepkeluarheder::find($request->id);
        if (!$data) {
            return new JsonResponse([
                'message' => 'Data Resep Tidak ditemukan'
            ], 410);
        }
        $data->update([
            'tgl_pelayanan_obat' => $request->tgl_pelayanan_obat
        ]);

        return new JsonResponse([
            'message' => 'Tanggal Pelayanan Obat sudah terisi',
            'data' => $data
        ]);
    }


    public function simpanTelaahResep(Request $request)
    {
        $user = auth()->user()->pegawai_id;
        $toSave = array_merge($request->all(), ['user_input' => $user]);
        $simpan = TelaahResep::updateOrCreate(
            [
                'noresep' => $request->noresep,
                'noreg' => $request->noreg,
                'norm' => $request->norm,
            ],
            $toSave
        );
        if (!$simpan) {
            return new JsonResponse([
                'message' => 'Telaah Resep Gagal disimpan',
                'req' => $request->all(),
                'user' => $user,
                'toSave' => $toSave,
                'simpan' => $simpan,
            ], 410);
        }
        return new JsonResponse([
            'message' => 'Telaah Resep Sudah disimpan',
            'req' => $request->all(),
            'user' => $user,
            'toSave' => $toSave,
            'simpan' => $simpan,
        ]);
    }

    public function historyLabPasien()
    {
        $data = LaboratMeta::select(
            'noreg',
            'norm',
            'tgl_permintaan',
            'unit_pengirim',
            'nota',
        )
            ->where('norm', request()->norm)
            ->with([
                'poli:rs1,rs2',
                'ranap:rs4,rs5',
                'details:rs1,rs2,rs3,rs4,rs21,rs27',
                'details.pemeriksaanlab:rs1,rs2,rs21,rs22',
            ])
            ->orderBy('tgl_permintaan', 'DESC')
            ->get();

        return new JsonResponse([
            'req' => request()->all(),
            'data' => $data
        ]);
    }
    public function simpanPersyaratanLab(Request $request)
    {

        $data = Resepkeluarheder::find($request->id);
        if (!$data) {
            return new JsonResponse([
                'message' => 'Data Resep Tidak ditemukan'
            ]);
        }

        $data->update([
            'persyarantan_lab' => $request->persyarantan_lab
        ]);

        return new JsonResponse([
            'req' => $request->all(),
            'data' => $data,
            'message' => 'sukses disimpan',
        ]);
    }
}
