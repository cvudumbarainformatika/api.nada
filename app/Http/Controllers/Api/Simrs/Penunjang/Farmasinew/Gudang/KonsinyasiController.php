<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Gudang;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mpasien;
use App\Models\Simrs\Master\Mpihakketiga;
use App\Models\Simrs\Penunjang\Farmasinew\Bast\BastKonsinyasi;
use App\Models\Simrs\Penunjang\Farmasinew\Bast\DetailBastKonsinyasi;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarrinci;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Obatoperasi\PersiapanOperasiDistribusi;
use App\Models\Simrs\Penunjang\Farmasinew\Obatoperasi\PersiapanOperasiRinci;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KonsinyasiController extends Controller
{
    //
    public function perusahaan()
    {
        $raw = BastKonsinyasi::select('kdpbf')->whereNull('tgl_bast')->distinct()->get();
        $penye = collect($raw)->map(function ($p) {
            return $p->kdpbf;
        });
        $penyedia = Mpihakketiga::select('kode', 'nama')->whereIn('kode', $penye)->get();
        return new JsonResponse($penyedia);
    }
    public function notranskonsi()
    {
        $data = BastKonsinyasi::whereNull('tgl_bast')->where('kdpbf', request('kdpbf'))->get();

        return new JsonResponse($data);
    }
    public function transkonsiwithrinci()
    {
        $data = BastKonsinyasi::with('rinci.obat:kd_obat,nama_obat,satuan_k')
            ->whereNull('tgl_bast')
            ->where('kdpbf', request('kdpbf'))
            ->where('notranskonsi', request('notranskonsi'))
            ->get();

        return new JsonResponse($data);
    }
    public function newGetPenyedia()
    {
        // tujuannnya untuk mencari kode pbf yang barangnya belum masuk list konsinyasi
        $res = Resepkeluarrinci::select(
            'resep_keluar_r.noresep',
            'resep_keluar_r.kdobat',
            'resep_keluar_r.jumlah',
            'resep_keluar_r.nopenerimaan',
            'new_masterobat.status_konsinyasi',
            // 'persiapan_operasi_distribusis.jumlah',
        )
            ->leftJoin('detail_bast_konsinyasis', function ($q) {
                $q->on('detail_bast_konsinyasis.nopenerimaan', '=', 'resep_keluar_r.nopenerimaan')
                    ->on('detail_bast_konsinyasis.noresep', '=', 'resep_keluar_r.noresep')
                    ->on('detail_bast_konsinyasis.kdobat', '=', 'resep_keluar_r.kdobat');
            })
            ->join('new_masterobat', 'new_masterobat.kd_obat', '=', 'resep_keluar_r.kdobat')
            ->where('resep_keluar_r.jumlah', '>', 0)
            ->where('new_masterobat.status_konsinyasi', '=', '1')
            ->whereNull('detail_bast_konsinyasis.jumlah')
            ->groupBy('resep_keluar_r.nopenerimaan')
            ->get();
        $resep = collect($res)->map(function ($q) {
            return $q->nopenerimaan;
        });
        // return new JsonResponse($resep);
        $rwpenye = PenerimaanHeder::select('kdpbf')
            ->where(function ($q) {
                $q->where('jenis_penerimaan', '=', 'Konsinyasi')
                    ->orWhere('jenis_penerimaan', '=', 'penggantian barang');
            })
            ->whereIn('nopenerimaan', $resep)
            ->distinct('kdpbf')->get();
        $penye = collect($rwpenye)->map(function ($p) {
            return $p->kdpbf;
        });
        $penyedia = Mpihakketiga::select('kode', 'nama')->whereIn('kode', $penye)->get();
        return new JsonResponse($penyedia);
    }
    public function newGetListPemakaianKonsinyasi()
    {
        // tujuan nya untuk megambil data obat konsinyasi yang belum masuk list sesuai dengan pbf
        $rwpene = PenerimaanHeder::select('nopenerimaan')
            ->where(function ($q) {
                $q->where('jenis_penerimaan', '=', 'Konsinyasi')
                    ->orWhere('jenis_penerimaan', '=', 'penggantian barang');
            })
            ->where('kdpbf', '=', request('penyedia'))
            ->whereNull('tgl_bast')
            ->distinct('nopenerimaan')
            ->get();
        $pene = collect($rwpene)->map(function ($p) {
            return $p->nopenerimaan;
        });
        $resep = Resepkeluarrinci::select(
            'resep_keluar_r.noresep',
            'resep_keluar_r.kdobat',
            DB::raw('sum(resep_keluar_r.jumlah) as jumlah'),
            'resep_keluar_r.nopenerimaan',
            'resep_keluar_r.harga_beli',
            'new_masterobat.status_konsinyasi',
        )
            ->with([
                'mobat:kd_obat,nama_obat,satuan_k',
                'heder:noresep,norm,noreg,dokter,tgl_permintaan,tgl_selesai',
                'heder.dokter:kdpegsimrs,nama',
                'heder.datapasien:rs1,rs2',
                'rincian.header',
                'penerimaanrinci' => function ($p) {
                    $p->select(
                        'penerimaan_r.nopenerimaan',
                        'penerimaan_r.bebaspajak',
                        'penerimaan_r.kdobat',
                        'penerimaan_r.satuan_kcl',
                        'penerimaan_r.harga_kcl',
                        'penerimaan_r.ppn',
                        'penerimaan_r.ppn_rp_kecil',
                        'penerimaan_r.diskon',
                        'penerimaan_r.diskon_rp_kecil',
                        'penerimaan_r.harga_netto_kecil',
                        'penerimaan_r.jml_terima_k',
                    )
                        ->join('resep_keluar_r', function ($j) {
                            $j->on('resep_keluar_r.nopenerimaan', '=', 'penerimaan_r.nopenerimaan')
                                ->on('resep_keluar_r.kdobat', '=', 'penerimaan_r.kdobat');
                        })
                        ->with('header:nopenerimaan,tglpenerimaan');
                }
            ])
            ->leftJoin('detail_bast_konsinyasis', function ($q) {
                $q->on('detail_bast_konsinyasis.noresep', '=', 'resep_keluar_r.noresep')
                    // ->on('detail_bast_konsinyasis.nopenerimaan', '=', 'resep_keluar_r.nopenerimaan')
                    ->on('detail_bast_konsinyasis.kdobat', '=', 'resep_keluar_r.kdobat');
            })
            ->join('new_masterobat', 'new_masterobat.kd_obat', '=', 'resep_keluar_r.kdobat')
            ->where('resep_keluar_r.jumlah', '>', 0)
            ->whereIn('resep_keluar_r.nopenerimaan', $pene)
            ->where('new_masterobat.status_konsinyasi', '=', '1')
            ->whereNull('detail_bast_konsinyasis.jumlah')
            ->groupBy('resep_keluar_r.kdobat', 'resep_keluar_r.noresep')
            ->get();

        $data = $resep;
        // $data['pene'] = $pene;
        return new JsonResponse($data);
    }
    public function getPenyedia()
    {
        $res = PersiapanOperasiRinci::select(
            'persiapan_operasi_rincis.nopermintaan',
            'persiapan_operasi_rincis.noresep',
            'persiapan_operasi_rincis.status_konsinyasi',
            'persiapan_operasi_rincis.kd_obat',
            'persiapan_operasi_rincis.jumlah_resep',
            'persiapan_operasi_distribusis.nopenerimaan',
            'persiapan_operasi_distribusis.jumlah',
            'persiapan_operasi_distribusis.jumlah_retur',
        )->leftJoin('persiapan_operasi_distribusis', function ($q) {
            $q->on('persiapan_operasi_distribusis.nopermintaan', '=', 'persiapan_operasi_rincis.nopermintaan')
                ->on('persiapan_operasi_distribusis.kd_obat', '=', 'persiapan_operasi_rincis.kd_obat');
        })
            ->where('persiapan_operasi_rincis.jumlah_resep', '>', 0)
            ->where('persiapan_operasi_rincis.status_konsinyasi', '=', '1')
            ->whereNull('persiapan_operasi_rincis.dibayar')
            ->groupBy('persiapan_operasi_distribusis.nopenerimaan')
            ->get();
        $resep = collect($res)->map(function ($q) {
            return $q->nopenerimaan;
        });
        // return new JsonResponse($resep);
        $rwpenye = PenerimaanHeder::select('kdpbf')
            ->where(function ($q) {
                $q->where('jenis_penerimaan', '=', 'Konsinyasi')
                    ->orWhere('jenis_penerimaan', '=', 'penggantian barang');
            })
            ->whereIn('nopenerimaan', $resep)
            ->distinct('kdpbf')->get();
        $penye = collect($rwpenye)->map(function ($p) {
            return $p->kdpbf;
        });
        $penyedia = Mpihakketiga::select('kode', 'nama')->whereIn('kode', $penye)->get();
        return new JsonResponse($penyedia);
    }
    public function getListPemakaianKonsinyasi()
    {
        $rwpene = PenerimaanHeder::select('nopenerimaan')
            ->where(function ($q) {
                $q->where('jenis_penerimaan', '=', 'Konsinyasi')
                    ->orWhere('jenis_penerimaan', '=', 'penggantian barang');
            })
            ->where('kdpbf', '=', request('penyedia'))
            ->whereNull('tgl_bast')
            ->distinct('nopenerimaan')
            ->get();
        $pene = collect($rwpene)->map(function ($p) {
            return $p->nopenerimaan;
        });
        $resep = PersiapanOperasiRinci::select(
            'persiapan_operasi_rincis.nopermintaan',
            'persiapan_operasi_rincis.noresep',
            'persiapan_operasi_rincis.status_konsinyasi',
            'persiapan_operasi_rincis.kd_obat',
            'persiapan_operasi_rincis.jumlah_resep',
            'persiapan_operasi_distribusis.nopenerimaan',
            'persiapan_operasi_distribusis.jumlah',
            'persiapan_operasi_distribusis.jumlah_retur',
        )
            ->with([
                'obat:kd_obat,nama_obat,satuan_k',
                'header',
                'resep:noresep,norm,noreg,dokter,tgl_permintaan',
                'resep.dokter:kdpegsimrs,nama',
                'resep.datapasien:rs1,rs2',
                'rincian',
                // 'penerimaanrinci'
                // 'rincian' => function ($r) {
                //     $r->select('resep_keluar_r.noresep', 'resep_keluar_r.kdobat', 'resep_keluar_r.jumlah', 'resep_keluar_r.harga_beli')
                //         ->leftJoin('persiapan_operasi_rincis', function ($j) {
                //             $j->on('persiapan_operasi_rincis.noresep', '=', 'resep_keluar_r.noresep')
                //                 ->on('persiapan_operasi_rincis.kd_obat', '=', 'resep_keluar_r.kdobat');
                //         });
                // },
                'penerimaanrinci' => function ($p) {
                    $p->select(
                        'penerimaan_r.nopenerimaan',
                        'penerimaan_r.bebaspajak',
                        'penerimaan_r.kdobat',
                        'penerimaan_r.satuan_kcl',
                        'penerimaan_r.harga_kcl',
                        'penerimaan_r.ppn',
                        'penerimaan_r.ppn_rp_kecil',
                        'penerimaan_r.diskon',
                        'penerimaan_r.diskon_rp_kecil',
                        'penerimaan_r.harga_netto_kecil',
                        'penerimaan_r.jml_terima_k',
                    )
                        ->leftJoin('persiapan_operasi_distribusis', function ($j) {
                            $j->on('persiapan_operasi_distribusis.nopenerimaan', '=', 'penerimaan_r.nopenerimaan')
                                ->on('persiapan_operasi_distribusis.kd_obat', '=', 'penerimaan_r.kdobat');
                        })
                        ->with('header:nopenerimaan,tglpenerimaan');
                }

            ])
            ->leftJoin('persiapan_operasi_distribusis', function ($q) {
                $q->on('persiapan_operasi_distribusis.nopermintaan', '=', 'persiapan_operasi_rincis.nopermintaan')
                    ->on('persiapan_operasi_distribusis.kd_obat', '=', 'persiapan_operasi_rincis.kd_obat');
            })
            ->where('persiapan_operasi_rincis.jumlah_resep', '>', 0)
            ->where('persiapan_operasi_rincis.status_konsinyasi', '=', '1')
            ->whereIn('persiapan_operasi_distribusis.nopenerimaan', $pene)
            ->whereNull('dibayar')
            ->groupBy('persiapan_operasi_distribusis.kd_obat', 'persiapan_operasi_distribusis.nopermintaan')
            ->get();
        $data = $resep;
        // $data['pene'] = $pene;
        return new JsonResponse($data);
    }
    public function simpanListKonsinyasi(Request $request)
    {
        if (count($request->items) <= 0) {
            return new JsonResponse([
                'message' => 'Tidak ada Data Barang',

                $request->all()
            ], 410);
        }

        try {
            DB::connection('farmasi')->beginTransaction();
            $user = FormatingHelper::session_user();
            if (!$request->notranskonsi) {
                $procedure = 'nokonsinyasi(@nomor)';
                $colom = 'konsinyasi';
                $lebel = 'TR-KONS';
                DB::connection('farmasi')->select('call ' . $procedure);
                $x = DB::connection('farmasi')->table('conter')->select($colom)->get();
                $wew = $x[0]->$colom;
                $notranskonsi = FormatingHelper::resep($wew, $lebel);
            } else {
                $notranskonsi = $request->notranskonsi;
            }
            $create = date('Y-m-d H:i:s');
            $head = BastKonsinyasi::updateOrCreate(
                [
                    'notranskonsi' => $notranskonsi,
                    'kdpbf' => $request->penyedia,
                ],
                [
                    'tgl_trans' => $request->tgl_trans,
                    'jumlah_konsi' => $request->jumlah_konsi,
                    'user_konsi' => $user['kodesimrs'],

                ]
            );
            if (!$head) {
                return new JsonResponse([
                    'message' => 'Gagal Menyimpan Head Transaksi',
                    'notranskonsi' => $notranskonsi,
                    'user' => $user,
                    'req' => $request->all()
                ], 410);
            }
            // rinci
            $rinci = [];
            foreach ($request->items as $key) {
                $temp = [
                    'notranskonsi' => $notranskonsi,
                    'nopermintaan' => $key['nopermintaan'],
                    'nopenerimaan' => $key['nopenerimaan'],
                    'kdobat' => $key['kdobat'],
                    'tgl_pakai' => $key['tgl_pakai'],
                    'tgl_penerimaan' => $key['tgl_penerimaan'],
                    'dokter' => $key['dokter'],
                    'noresep' => $key['noresep'],
                    'noreg' => $key['noreg'],
                    'norm' => $key['norm'],
                    'jumlah' => $key['jumlah'],
                    'harga' => $key['harga'],
                    'diskon' => $key['diskon'],
                    'ppn' => $key['ppn'],
                    'diskon_rp' => $key['diskon_rp'],
                    'ppn_rp' => $key['ppn_rp'],
                    'harga_net' => $key['harga_net'],
                    'subtotal' => $key['subtotal'],
                    'created_at' => $create,
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
                $rinci[] = $temp;
            }

            // hapus resep yang ada
            $delRin = DetailBastKonsinyasi::where('notranskonsi', $notranskonsi)->delete();
            // isi
            $ins = DetailBastKonsinyasi::insert($rinci);

            // update dibayar rinci permintaan operasi
            $datanya = DetailBastKonsinyasi::where('notranskonsi', $notranskonsi)->get();
            $rina = [];
            foreach ($datanya as $det) {
                $rinciPermintaanOP = PersiapanOperasiRinci::where('nopermintaan', $det->nopermintaan)
                    ->where('noresep', $det->noresep)
                    ->where('kd_obat', $det->kdobat)
                    ->first();
                $rina[] = $rinciPermintaanOP;
                if ($rinciPermintaanOP) {
                    $rinciPermintaanOP->update(['dibayar' => '1']);
                }
            }

            DB::connection('farmasi')->commit();
            return new JsonResponse([
                'message' => 'List Sudah Disimpan',
                'notranskonsi' => $notranskonsi,
                'rinci' => $rinci,
                'user' => $user,
                'datanya' => $datanya,
                'rina' => $rina,
                'req' => $request->all()
            ]);
        } catch (\Exception $e) {
            DB::connection('farmasi')->rollBack();
            return new JsonResponse([
                'message' => 'Ada Kesalahan, Semua Data Gagal Disimpan',
                'result' => 'err ' . $e,
            ], 410);
        }
    }

    public function simpanBast(Request $request)
    {
        // return new JsonResponse($request->all());
        $user = FormatingHelper::session_user();
        try {
            DB::connection('farmasi')->beginTransaction();
            if ($request->nobast === '' || $request->nobast === null) {
                DB::connection('farmasi')->select('call nobast(@nomor)');
                $x = DB::connection('farmasi')->table('conter')->select('bast')->get();
                $wew = $x[0]->bast;
                $nobast = FormatingHelper::penerimaanobat($wew, 'BAST-KONS-FAR');
            } else {
                $nobast = $request->nobast;
            }

            $head = BastKonsinyasi::where('notranskonsi', $request->notranskonsi)->first();
            if (!$head) {
                return new JsonResponse([
                    'message' => 'Transaksi Konsinyasi Tidak Ditemukan',
                    'req' => $request->all(),
                ], 410);
            }
            $head->update([
                'nobast' => $nobast,
                'tgl_bast' => $request->tgl_bast,
                'jumlah_bast' => $request->jumlah_bast,
                'jumlah_bastx' => $request->jumlah_bastx ?? $request->jumlah_bast,
                'user_bast' => $user['kodesimrs'],
            ]);
            $rinci = [];
            foreach ($request->list as $list) {
                foreach ($list['rinci'] as $key) {
                    $temp = DetailBastKonsinyasi::find($key['id']);
                    if ($temp) {
                        $temp->update([
                            'harga' => $key['harga'],
                            'harga_net' => $key['harga_net'],
                            'ppn' => $key['ppn'],
                            'ppn_rp' => $key['ppn_rp'],
                            'diskon' => $key['diskon'],
                            'diskon_rp' => $key['diskon_rp'],
                            'subtotal' => $key['subtotal'],
                        ]);
                    }
                    $rinci[] = $temp;
                }
            }

            DB::connection('farmasi')->commit();
            return new JsonResponse([
                'message' => 'BAST Sudah Disimpan',
                'req' => $request->all(),
                'head' => $head,
                'rinci bast' => $rinci,
            ]);
        } catch (\Exception $e) {
            DB::connection('farmasi')->rollBack();
            return response()->json(['message' => 'ada kesalahan', 'error' => ' ' . $e, 'rinci' => $rinci], 410);
        }
    }
    public function listKonsinyasi()
    {
        $data = BastKonsinyasi::with([
            'rinci.obat:kd_obat,nama_obat,satuan_k',
            'rinci.iddokter:kdpegsimrs,nama',
            'rinci.pasien:rs1,rs2',
            'penyedia:kode,nama',
            'konsi:kdpegsimrs,nama',
            'bast:kdpegsimrs,nama',
            'bayar:kdpegsimrs,nama',
        ])
            ->when(request('q'), function ($q) {
                $pihak = Mpihakketiga::select('kode')->where('nama', 'LIKE', '%' . request('q') . '%')->pluck('kode');
                if (count($pihak) > 0) {
                    $q->where(function ($x) use ($pihak) {
                        $x->whereIn('kdpbf', $pihak)
                            ->orWhere('nobast', 'LIKE', '%' . request('q') . '%')
                            ->orWhere('notranskonsi', 'LIKE', '%' . request('q') . '%');
                    });
                } else {
                    $q->where(function ($x) {
                        $x->where('nobast', 'LIKE', '%' . request('q') . '%')
                            ->orWhere('notranskonsi', 'LIKE', '%' . request('q') . '%');
                    });
                }
                // $q->when(
                //     count($pihak) > 0,
                //     function ($x) use ($pihak) {
                //         $x->whereIn('kdpbf', $pihak)
                //             ->orWhere('nobast', 'LIKE', '%' . request('q') . '%')
                //             ->orWhere('notranskonsi', 'LIKE', '%' . request('q') . '%');
                //     },
                //     function ($r) {
                //         $r->where('nobast', 'LIKE', '%' . request('q') . '%')
                //             ->orWhere('notranskonsi', 'LIKE', '%' . request('q') . '%');
                //     }
                // );
            })
            ->when(request('from') && request('to'), function ($q) {
                $q->whereBetween('tgl_trans', [request('from') . ' 00:00:00', request('to') . ' 23:59:59']);
            })
            ->when(request('bast') === 'sudah', function ($q) {
                $q->whereNotNull('tgl_bast');
            }, function ($q) {
                $q->whereNull('tgl_bast');
            })
            ->when(request('bayar') === 'sudah', function ($q) {
                $q->whereNotNull('tgl_pembayaran');
            }, function ($q) {
                $q->whereNull('tgl_pembayaran');
            })

            ->orderBy('notranskonsi', 'DESC')
            ->paginate(request('per_page'));

        $meta = collect($data)->except('data');
        $datanya = collect($data)['data'];
        return new JsonResponse([
            'data' => $datanya,
            'meta' => $meta,
        ]);
    }
    public function bastKonsinyasi()
    {
        $data = BastKonsinyasi::with([
            'rinci.obat:kd_obat,nama_obat,satuan_k',
            'rinci.iddokter:kdpegsimrs,nama',
            'rinci.pasien:rs1,rs2',
            'penyedia:kode,nama',
            'konsi:kdpegsimrs,nama',
            'bast:kdpegsimrs,nama',
            'bayar:kdpegsimrs,nama',
        ])
            ->when(request('q'), function ($q) {
                $pihak = Mpihakketiga::select('kode')->where('nama', 'LIKE', '%' . request('q') . '%')->pluck('kode');
                $q->when(
                    count($pihak) > 0,
                    function ($x) use ($pihak) {
                        $x->whereIn('kdpbf', $pihak)
                            ->orWhere('nobast', 'LIKE', '%' . request('q') . '%');
                    },
                    function ($r) {
                        $r->where('nobast', 'LIKE', '%' . request('q') . '%');
                    }
                );
            })
            ->whereNotNull('tgl_bast')
            ->paginate(request('per_page'));

        return new JsonResponse($data);
    }

    public function belumKonsinyasi()
    {
        $master = Mobatnew::select('kd_obat')->where('status_konsinyasi', '1')->pluck('kd_obat');
        $dist = PersiapanOperasiDistribusi::select(
            'persiapan_operasi_rincis.dibayar',
            'persiapan_operasi_distribusis.nopermintaan',
            'persiapan_operasi_distribusis.nopenerimaan',
            'persiapan_operasi_distribusis.kd_obat',
            'penerimaan_r.harga_netto_kecil as harga_net',
            'penerimaan_h.kdpbf',
            'new_masterobat.nama_obat',
            'new_masterobat.satuan_k as satuan',
            DB::raw('sum(persiapan_operasi_distribusis.jumlah) as jumlah'),
            DB::raw('sum(persiapan_operasi_distribusis.jumlah_retur) as jumlah_retur'),
            DB::raw('sum(persiapan_operasi_distribusis.jumlah - persiapan_operasi_distribusis.jumlah_retur) as dipakai'),
            DB::raw('sum((persiapan_operasi_distribusis.jumlah - persiapan_operasi_distribusis.jumlah_retur) * penerimaan_r.harga_netto_kecil) as sub'),
        )
            ->leftJoin('penerimaan_r', function ($jo) {
                $jo->on('penerimaan_r.nopenerimaan', '=', 'persiapan_operasi_distribusis.nopenerimaan')
                    ->on('penerimaan_r.kdobat', '=', 'persiapan_operasi_distribusis.kd_obat');
            })
            ->leftJoin('penerimaan_h', function ($jo) {
                $jo->on('penerimaan_h.nopenerimaan', '=', 'penerimaan_r.nopenerimaan');
            })
            ->leftJoin('new_masterobat', function ($jo) {
                $jo->on('new_masterobat.kd_obat', '=', 'persiapan_operasi_distribusis.kd_obat');
            })
            ->leftJoin('persiapan_operasis', function ($jo) {
                $jo->on('persiapan_operasis.nopermintaan', '=', 'persiapan_operasi_distribusis.nopermintaan');
            })
            ->leftJoin('persiapan_operasi_rincis', function ($jo) {
                $jo->on('persiapan_operasi_rincis.nopermintaan', '=', 'persiapan_operasi_distribusis.nopermintaan')
                    ->on('persiapan_operasi_rincis.kd_obat', '=', 'persiapan_operasi_distribusis.kd_obat');
            })
            ->leftJoin('detail_bast_konsinyasis', function ($join) {
                $join->on('detail_bast_konsinyasis.nopermintaan', '=', 'persiapan_operasi_distribusis.nopermintaan')
                    ->on('detail_bast_konsinyasis.kdobat', '=', 'persiapan_operasi_distribusis.kd_obat');
            })
            ->whereIn('persiapan_operasi_distribusis.kd_obat', $master)
            ->where('persiapan_operasis.flag', '4')
            ->where(function ($q) {
                $q->whereNull('persiapan_operasi_rincis.dibayar')
                    ->orWhere(function ($b) {
                        $b->where('persiapan_operasi_rincis.dibayar', '1')
                            ->whereNull('detail_bast_konsinyasis.nopermintaan');
                    });
            })
            ->havingRaw('dipakai > 0')
            ->with([
                // 'master:kd_obat,nama_obat',
                'persiapan:nopermintaan,norm',
                'persiapan.pasien:rs1,rs2',
                'pbf:kode,nama',

            ])
            ->when(request('q'), function ($q) {
                $kdpbf = [];
                $rm = [];
                if (strlen(request('q')) > 2) {
                    $kdpbf = Mpihakketiga::where('nama', 'LIKE', '%' . request('q') . '%')->pluck('kode');
                    $rm = Mpasien::where('rs2', 'LIKE', '%' . request('q') . '%')->pluck('rs1');
                }
                $q->where(function ($m) use ($kdpbf, $rm) {
                    $m->where('new_masterobat.nama_obat', 'LIKE', '%' . request('q') . '%')
                        ->orWhere('new_masterobat.kd_obat', 'LIKE', '%' . request('q') . '%')
                        ->orWhere('persiapan_operasi_distribusis.nopermintaan', 'LIKE', '%' . request('q') . '%')
                        ->orWhere('persiapan_operasi_distribusis.nopenerimaan', 'LIKE', '%' . request('q') . '%')
                        // ->orWhere('persiapan_operasis.norm','LIKE','%'.request('q').'%')
                        ->orWhereIn('penerimaan_h.kdpbf', $kdpbf)
                        ->orWhereIn('persiapan_operasis.norm', $rm)
                    ;
                });
            })
            ->groupBy('persiapan_operasi_distribusis.nopenerimaan', 'persiapan_operasi_distribusis.kd_obat', 'persiapan_operasi_distribusis.nopermintaan')
            ->get();
        return new JsonResponse($dist);
    }
    public function hapusDibayar(Request $request)
    {

        $data = PersiapanOperasiRinci::where('nopermintaan', $request->nopermintaan)
            ->where('kd_obat', $request->kd_obat)
            ->first();
        if (!$data) {
            return new JsonResponse([
                'message' => 'Data Tidak Ditemukan'
            ], 410);
        }

        $data->update([
            'dibayar' => null
        ]);
        return new JsonResponse([
            'message' => 'Sudah ditandai belum masuk list',
            'data' => $data
        ]);
    }
}
