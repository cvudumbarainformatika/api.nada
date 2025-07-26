<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Depo;

use App\Events\NotifMessageEvent;
use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaandepoheder;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaandeporinci;
use App\Models\Simrs\Penunjang\Farmasinew\Mutasi\Mutasigudangkedepo;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokrel;
use App\Models\Simrs\Penunjang\Farmasinew\Stokreal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DepoController extends Controller
{
    public function lihatstokgudang()
    {

        $gudang = request('kdgudang');
        $depo = request('kddepo');
        // $gudang = ['Gd-05010100', 'Gd-03010100'];
        $anu = Stokreal::select('stokreal.kdobat')
            ->join('new_masterobat', 'new_masterobat.kd_obat', '=', 'stokreal.kdobat')
            ->where('new_masterobat.nama_obat', 'Like', '%' . request('nama_obat') . '%')
            ->where('stokreal.jumlah', '>', 0)
            ->where('stokreal.kdruang', $gudang)
            ->where('stokreal.flag', '')
            ->orderBy('new_masterobat.nama_obat', 'ASC')
            ->groupBy('stokreal.kdobat')
            ->limit(20)
            ->get();
        $obat = $anu->map(function ($xxx) {
            return $xxx->kdobat;
        });
        $stokgudang = Stokreal::select(
            'stokreal.*',
            'new_masterobat.bentuk_sediaan',
            'new_masterobat.satuan_k',
            'new_masterobat.nama_obat',
            DB::raw('sum(stokreal.jumlah) as  jumlah'),
        )->with([
            'transnonracikan' => function ($transnonracikan) {
                $transnonracikan->select(
                    // 'resep_keluar_r.kdobat as kdobat',
                    'resep_permintaan_keluar.kdobat as kdobat',
                    'resep_keluar_h.depo as kdruang',
                    DB::raw('sum(resep_permintaan_keluar.jumlah) as jumlah')
                )
                    ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_permintaan_keluar.noresep')
                    ->where('resep_keluar_h.depo', request('kdgudang'))
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
                    ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_permintaan_keluar_racikan.noresep')
                    ->where('resep_keluar_h.depo', request('kdgudang'))
                    ->whereIn('resep_keluar_h.flag', ['', '1', '2'])
                    ->groupBy('resep_permintaan_keluar_racikan.kdobat');
            },
            'permintaanobatrinci' => function ($permintaanobatrinci) use ($gudang) {
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

                    ->where('permintaan_h.tujuan', $gudang)
                    ->whereIn('permintaan_h.flag', ['', '1', '2'])
                    ->groupBy('permintaan_r.kdobat');
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
            'minmax' => function ($mimnmax) use ($depo) {
                $mimnmax->select('kd_obat', 'kd_ruang', 'max')->when($depo, function ($xxx) use ($depo) {
                    $xxx->where('kd_ruang', $depo);
                });
            }
        ])
            ->join('new_masterobat', 'new_masterobat.kd_obat', '=', 'stokreal.kdobat')
            ->when($gudang, function ($wew) use ($gudang) {
                $wew->where('stokreal.kdruang', $gudang);
            })
            // ->where('new_masterobat.nama_obat', 'Like', '%' . request('nama_obat') . '%')
            ->where('stokreal.jumlah', '>', 0)
            ->whereIn('stokreal.kdobat', $obat)
            ->where('stokreal.flag', '')
            ->orderBy('new_masterobat.nama_obat', 'ASC')
            ->groupBy('stokreal.kdobat', 'stokreal.kdruang')
            ->get();
        $datastok = $stokgudang->map(function ($xxx) {
            $stolreal = $xxx->jumlah;
            $jumlahper = request('kdgudang') === 'Gd-04010103' ? $xxx['persiapanrinci'][0]->jumlah ?? 0 : 0;
            $jumlahtrans = $xxx['transnonracikan'][0]->jumlah ?? 0;
            $jumlahtransx = $xxx['transracikan'][0]->jumlah ?? 0;
            $permintaantotal = count($xxx->permintaanobatrinci) > 0 ? $xxx->permintaanobatrinci[0]->allpermintaan : 0;
            $stokalokasi = (float) $stolreal - (float) $permintaantotal - (float) $jumlahtrans - (float) $jumlahtransx - (float)$jumlahper;
            $xxx['stokalokasi'] = $stokalokasi;
            $xxx['permintaantotal'] = $permintaantotal;
            return $xxx;
        });

        $stokdewe = Stokrel::select('kdobat', DB::raw('sum(stokreal.jumlah) as  jumlah'), 'kdruang')
            ->when($depo, function ($wew) use ($depo) {
                $wew->where('stokreal.kdruang', $depo);
            })
            ->where('jumlah', '>', 0)
            ->whereIn('kdobat', $obat)
            ->groupBy('stokreal.kdobat', 'stokreal.kdruang')
            ->get();

        return new JsonResponse(
            [
                'obat' => $datastok,
                'stokdewe' => $stokdewe,
                'obat l' => $obat,
                // 'stokgudang' => $stokgudang,
            ]
        );
    }

    public function simpanpermintaandepo(Request $request)
    {


        // return new JsonResponse($request->all());
        $validator = Validator::make($request->all(), [
            'kdobat' => 'required',
            'tujuan' => 'required',
            'dari' => 'required',
            'jumlah_minta' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // jika masih ada yang belum selesai suruh selesaikan dulu
        $ada = Permintaandepoheder::select('no_permintaan')->where('dari', $request->dari)->where('flag', '3')->get();
        if (count($ada) > 0) {
            $arr = collect($ada)->map(function ($x) {
                return $x->no_permintaan;
            });
            $noper = join(' ,', $arr->all());
            return new JsonResponse(
                [
                    'message' => 'Selesaikan dulu Transaksi sebelumnya, nomor permintaan : ' . $noper,
                    'arr' => $arr,
                    'ada' => $ada,
                    'req' => $request->all()
                ],
                410
            );
        }
        // return new JsonResponse([
        //     'message'=>'lpt',
        //     'req'=>$request->all()
        // ],410);
        try {
            DB::connection('farmasi')->beginTransaction();
            $cek = Permintaandepoheder::where('flag', '!=', '')->where('no_permintaan', $request->no_permintaan)->count();
            if ($cek > 0) {
                return new JsonResponse(['message' => 'Maaf Data ini Sudah Dikunci...!!!'], 500);
            }
            $stokreal = Stokreal::selectRaw('sum(jumlah) as stok')->where('kdobat', $request->kdobat)->where('kdruang', $request->tujuan)->first();
            $stokrealx = (float) $stokreal->stok;
            $allpermintaan = Permintaandeporinci::select(DB::raw('sum(permintaan_r.jumlah_minta) as allpermintaan'))
                ->leftjoin('permintaan_h', 'permintaan_h.no_permintaan', '=', 'permintaan_r.no_permintaan')
                ->leftJoin('mutasi_gudangdepo', function ($anu) {
                    $anu->on('permintaan_r.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                        ->on('permintaan_r.kdobat', '=', 'mutasi_gudangdepo.kd_obat');
                })
                ->whereNull('mutasi_gudangdepo.kd_obat')
                ->whereIn('permintaan_h.flag', ['', '1', '2'])
                ->where('kdobat', $request->kdobat)
                ->where('tujuan', $request->tujuan)
                ->groupby('kdobat')->get();
            $allpermintaanx =  $allpermintaan[0]->allpermintaan ?? 0;
            $stokalokasi = $stokrealx - (float) $allpermintaanx;

            if ($request->jumlah_minta > $stokalokasi) {
                return new JsonResponse([
                    'message' => 'Maaf Stok Alokasi Tidak mencukupi...!!!',
                    'alokasi' => $stokalokasi,
                    'stokreal' => $stokreal,
                    'allpermintaan' => $allpermintaan,
                ], 500);
            }

            if ($request->no_permintaan === '' || $request->no_permintaan === null) {
                DB::connection('farmasi')->select('call permintaandepo(@nomor) ');
                $x = DB::connection('farmasi')->table('conter')->select('permintaandepo')->get();
                $wew = $x[0]->permintaandepo;
                $nopermintaandepo = FormatingHelper::permintaandepo($wew, 'REQ-DEPO');
            } else {
                $nopermintaandepo = $request->no_permintaan;
            }

            $simpanpermintaandepo = Permintaandepoheder::updateorcreate(
                [
                    'no_permintaan' => $nopermintaandepo,
                ],
                [
                    'tgl_permintaan' => $request->tgl_permintaan ? $request->tgl_permintaan . date(' H:i:s') : date('Y-m-d H:i:s'),
                    'dari' => $request->dari,
                    'tujuan' => $request->tujuan,
                    'user' => auth()->user()->pegawai_id
                ]
            );
            if (!$simpanpermintaandepo) {
                return new JsonResponse(['message' => 'Permintaan Gagal Disimpan...!!!'], 500);
            }

            $simpanrincipermintaandepo = Permintaandeporinci::updateorcreate(
                [
                    'no_permintaan' => $nopermintaandepo,
                    'kdobat' => $request->kdobat
                ],
                [
                    'stok_alokasi' => $request->stok_alokasi,
                    'mak_stok' => $request->mak_stok,
                    'jumlah_minta' => $request->jumlah_minta,
                    'status_obat' => $request->status_obat ?? ''
                ]
            );

            if (!$simpanrincipermintaandepo) {
                return new JsonResponse(['message' => 'Permintaan Gagal Disimpan...!!!'], 500);
            }
            $msg = [
                'data' => [
                    'aksi' => 'simpan',
                    'dari' => $simpanpermintaandepo->dari,
                    'no_permintaan' => $nopermintaandepo,
                    'kdobat' => $simpanrincipermintaandepo->kdobat,
                    'depo' => $simpanpermintaandepo->dari,
                    'jumlah_minta' => $simpanrincipermintaandepo->jumlah_minta,
                    'flag' => $simpanpermintaandepo->flag
                ]
            ];
            event(new NotifMessageEvent($msg, 'depo-farmasi', auth()->user()));
            DB::connection('farmasi')->commit();
            return new JsonResponse(
                [
                    'message' => 'Data Berhasil Disimpan...!!!',
                    'notrans' => $nopermintaandepo,
                    'heder' => $simpanpermintaandepo,
                    'rinci' => $simpanrincipermintaandepo,
                    'stokalokasi' => $stokalokasi
                ]
            );
        } catch (\Exception $e) {
            DB::connection('farmasi')->rollBack();
            return response()->json(['message' => 'ada kesalahan', 'error' => $e], 410);
        }
    }

    public function kuncipermintaan(Request $request)
    {
        $kuncipermintaan = Permintaandepoheder::where('no_permintaan', $request->no_permintaan)->first();
        $kuncipermintaan->flag = '1';
        $kuncipermintaan->tgl_kirim = date('Y-m-d H:i:s');
        $kuncipermintaan->save();

        $gudang = ['Gd-05010100', 'Gd-03010100'];
        $tujuan = 'Depo';
        if (in_array($kuncipermintaan->tujuan, $gudang)) {
            $tujuan = 'Gudang';
        }
        $kdobat = Permintaandeporinci::select('kdobat')->where('no_permintaan', $request->no_permintaan)->get();
        $msg = [
            'data' => [
                'aksi' => 'kunci',
                'dari' => $kuncipermintaan->dari,
                'no_permintaan' => $kuncipermintaan->no_permintaan,
                'depo' => $kuncipermintaan->dari,
                'flag' => $kuncipermintaan->flag,
                'kodeobats' => $kdobat,

            ]
        ];
        event(new NotifMessageEvent($msg, 'depo-farmasi', auth()->user()));
        return new JsonResponse(['message' => 'Permintaan Berhasil Dikirim ke ' . $tujuan], 200);
    }
    public function hapusHead(Request $request)
    {
        // return new JsonResponse([
        //     'req' => $request->all(),
        //     'message' => 'Data sudah dihapus.'
        // ]);
        $data = Permintaandepoheder::find($request->id);
        if (!$data) {
            return new JsonResponse(['message' => 'Gagal menghapus permintaan. Data tidak ditemukan.'], 410);
        }
        $rinci = Permintaandeporinci::where('no_permintaan', $data->no_permintaan)->get();
        $data->delete();
        foreach ($rinci as $key) {
            $key->delete();
        }
        return new JsonResponse([
            'rinci' => $rinci,
            'data' => $data,
            'message' => 'Data sudah dihapus.'
        ]);
    }
    public function hapusRinci(Request $request)
    {
        // return new JsonResponse([
        //     'req' => $request->all(),
        //     'message' => 'Data sudah dihapus.'
        // ]);
        $data = Permintaandeporinci::find($request->id);
        if (!$data) {
            return new JsonResponse(['message' => 'Gagal menghapus permintaan. Data tidak ditemukan.'], 410);
        }
        $count = Permintaandeporinci::where('no_permintaan', $data->no_permintaan)->count();
        $data->delete();
        if ($count <= 1) {
            $head = Permintaandepoheder::where('no_permintaan', $data->no_permintaan)->first();
            $head->delete();
        }
        return new JsonResponse([
            'count' => $count,
            'message' => 'Data sudah dihapus.'
        ]);
    }

    public function listpermintaandepo()
    {
        $depo = request('kddepo');
        $nopermintaan = request('no_permintaan');
        $fl = [''];
        if (request('nama') === 'penerimaan depo') {
            $fl[] = request('flag');
        } else if (request('nama') === 'permintaan depo') {
            foreach (request('flag') as $key) {
                $fl[] = $key;
            }
        }
        // return new JsonResponse($fl);
        $listpermintaandepo = Permintaandepoheder::with('permintaanrinci.masterobat', 'asal:kode,nama', 'menuju:kode,nama', 'mutasigudangkedepo')
            ->where('no_permintaan', 'Like', '%' . $nopermintaan . '%')
            ->where('dari', 'like', '%' . $depo . '%')
            ->whereIn('flag', $fl)
            ->orderBY('tgl_permintaan', 'desc')
            ->paginate(request('per_page'));
        // ->get();

        return new JsonResponse($listpermintaandepo);
        // }
    }
    public function listPermintaanRuangan()
    {
        $depo = request('kddepo') ?? 'R-';
        $nopermintaan = request('no_permintaan');

        $listpermintaandepo = Permintaandepoheder::with('permintaanrinci.masterobat', 'ruangan:kode,uraian', 'menuju:kode,nama', 'mutasigudangkedepo')
            ->where('no_permintaan', 'Like', '%' . $nopermintaan . '%')
            ->where('dari', 'like', '%' . $depo . '%')
            ->orderBY('tgl_permintaan', 'desc')
            ->paginate(request('per_page'));
        return new JsonResponse($listpermintaandepo);
        // }
    }

    public function terimadistribusi(Request $request)
    {
        $obatditerima = Mutasigudangkedepo::select(
            'mutasi_gudangdepo.no_permintaan as nopermintaan',
            'mutasi_gudangdepo.nopenerimaan as nopenerimaan',
            'mutasi_gudangdepo.kd_obat as kodeobat',
            'mutasi_gudangdepo.jml as jml',
            'stokreal.tglpenerimaan as tglpenerimaan',
            'stokreal.harga as harga',
            'stokreal.tglexp as tglexp',
            'stokreal.nobatch as nobatch',
            'stokreal.nodistribusi as nodistribusi',
            'stokreal.jumlah',
            DB::raw('(stokreal.jumlah-mutasi_gudangdepo.jml) as sisa')
        )
            ->leftjoin('stokreal', function ($x) {
                $x->on('mutasi_gudangdepo.nopenerimaan', '=', 'stokreal.nopenerimaan')
                    ->on('mutasi_gudangdepo.kd_obat', '=', 'stokreal.kdobat');
            })
            ->where('mutasi_gudangdepo.no_permintaan', $request->no_permintaan)
            ->where('stokreal.kdruang', $request->kdruang)
            ->orderBy('stokreal.tglexp')
            ->get();
        foreach ($obatditerima as $wew) {

            Stokreal::updateOrCreate(
                [
                    'nopenerimaan' => $wew->nopenerimaan,
                    'nodistribusi' => $request->no_permintaan,
                    'kdobat' => $wew->kodeobat,
                ],
                [
                    'tglpenerimaan' => $wew->tglpenerimaan,
                    'jumlah' => $wew->jml,
                    'kdruang' => $request->tujuan,
                    'harga' => $wew->harga,
                    'tglexp' => $wew->tglexp,
                    'nobatch' => $wew->nobatch,
                ]
            );
        }

        $user = FormatingHelper::session_user();
        $kuncipermintaan = Permintaandepoheder::where('no_permintaan', $request->no_permintaan)->first();
        $kuncipermintaan->flag = '4';
        $kuncipermintaan->tgl_terima_depo = date('Y-m-d H:i:s');
        $kuncipermintaan->user_terima_depo = $user['kodesimrs'];
        $kuncipermintaan->save();

        return new JsonResponse(['message' => 'Permintaan Berhasil Diterima & Masuk Ke stok...!!!'], 200);
    }
    public function newterimadistribusi(Request $request)
    {
        // $obatditerima = Mutasigudangkedepo::select('*', DB::raw('sum(jml) as jumlah'))
        //     ->where('no_permintaan', $request->no_permintaan)
        //     ->groupBy('no_permintaan', 'kd_obat', 'nopenerimaan')
        //     ->get();
        try {
            DB::connection('farmasi')->beginTransaction();
            $obatditerima = Mutasigudangkedepo::select(
                'nopenerimaan',
                'no_permintaan',
                'kd_obat',
                'nobatch',
                'tglexp',
                'tglpenerimaan',
                'harga',
                DB::raw('sum(jml) as jumlah')
            )
                ->where('no_permintaan', $request->no_permintaan)
                ->groupBy(
                    'nopenerimaan',
                    'kd_obat',
                    'harga',
                )
                ->get();
            // return new JsonResponse(['message' => 'Permintaan Berhasil Diterima & Masuk Ke stok...!!!', 'data' => $obatditerima], 410);
            foreach ($obatditerima as $wew) {
                $stoknya = Stokreal::lockForUpdate()
                    ->where('kdobat', $wew->kd_obat)
                    ->where('nopenerimaan', $wew->nopenerimaan)
                    ->where('harga', $wew->harga)
                    ->where('kdruang', $request->tujuan)
                    ->orderBy('tglpenerimaan', 'DESC')
                    ->first();
                if ($stoknya) {
                    $total = (float)$wew->jumlah + (float)$stoknya->jumlah;
                    $stoknya->update(['jumlah' => $total]);
                } else {
                    $create = Stokreal::create(
                        [
                            'nopenerimaan' => $wew->nopenerimaan,
                            'nodistribusi' => $wew->no_permintaan,
                            'kdobat' => $wew->kd_obat,
                            'nobatch' => $wew->nobatch,
                            'tglexp' => $wew->tglexp,
                            'tglpenerimaan' => $wew->tglpenerimaan,
                            'jumlah' => $wew->jumlah,
                            'kdruang' => $request->tujuan,
                            'harga' => $wew->harga,
                        ]
                    );
                }
            }

            $user = FormatingHelper::session_user();
            $kuncipermintaan = Permintaandepoheder::where('no_permintaan', $request->no_permintaan)->first();

            $kuncipermintaan->update([
                'flag' => '4',
                'tgl_terima_depo' => date('Y-m-d H:i:s'),
                'user_terima_depo' => $user['kodesimrs'],

            ]);

            // $kdobat = Permintaandeporinci::select('kdobat')->where('no_permintaan', $request->no_permintaan)->get();
            $msg = [
                'data' => [
                    'aksi' => 'kunci',
                    'dari' => $kuncipermintaan->dari,
                    'no_permintaan' => $kuncipermintaan->no_permintaan,
                    'depo' => $kuncipermintaan->dari,
                    'flag' => $kuncipermintaan->flag,
                    // 'kodeobats' => $kdobat,

                ]
            ];
            event(new NotifMessageEvent($msg, 'depo-farmasi', auth()->user()));
            DB::connection('farmasi')->commit();
            return new JsonResponse([
                'message' => 'Permintaan Berhasil Diterima & Masuk Ke stok...!!!',
                'stoknya' => $stoknya ?? null,
                'create' => $create ?? null,
            ], 200);
        } catch (\Exception $e) {
            DB::connection('farmasi')->rollBack();
            return new JsonResponse([
                'message' => 'Data Gagal Disimpan ',
                'result' => '' . $e,
                'err' =>  $e,
                'caristok' => $caristok ?? '',
                'mutasi' => $mutasi ?? '',
                'stoknya' => $data ?? '',
                'rinciPer' => $rinciPer ?? '',
            ], 410);
        }
    }

    public function listMutasi()
    {
        // return request()->all();
        $gudang = request('kdgudang');
        $nopermintaan = request('no_permintaan');
        $flag = request('flag');
        $depo = request('kddepo');
        // return new JsonResponse(!!$flag);
        $listpermintaandepo = Permintaandepoheder::with([
            'permintaanrinci.masterobat',
            'user:id,nip,nama',
            'permintaanrinci' => function ($rinci) {
                $rinci->with([
                    'stokreal' => function ($stokdendiri) {
                        $stokdendiri
                            ->select(
                                'kdobat',
                                'kdruang',
                                'jumlah',
                            );
                    }
                ]);
            },
            'mutasigudangkedepo',
            'asal:kode,nama',
            'menuju:kode,nama',
        ])
            ->where('no_permintaan', 'Like', '%' . $nopermintaan . '%')
            ->when($gudang, function ($wew) use ($gudang) {
                $all = ['Gd-02010104', 'Gd-05010101', 'Gd-04010103', 'Gd-03010101', 'Gd-04010102'];
                if ($gudang === 'all') {
                    $wew->whereIn('tujuan', $all);
                } else {
                    $wew->where('tujuan', $gudang);
                }
            })
            ->when($flag || $flag === '0', function ($wew) use ($flag) {
                $all = ['', '1', '2', '3', '4'];
                if ($flag === '0' || $flag === 0) {
                    $wew->where('flag', '');
                } else if ($flag === '5' || $flag === 5) {
                    $wew->whereIn('flag', $all);
                } else {
                    $wew->where('flag', $flag);
                }
            })
            ->when($depo, function ($wew) use ($depo) {
                $wew->where('dari', $depo);
            })
            ->orderBY('tgl_permintaan', 'desc')
            ->paginate(request('per_page'));
        // $listpermintaandepo['req'] = request()->all();
        return new JsonResponse($listpermintaandepo);
    }
}
