<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Obatoperasi;

use App\Helpers\FormatingHelper;
use App\Helpers\HargaHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mpasien;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaanresep;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarrinci;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Obatoperasi\PersiapanOperasi;
use App\Models\Simrs\Penunjang\Farmasinew\Obatoperasi\PersiapanOperasiDistribusi;
use App\Models\Simrs\Penunjang\Farmasinew\Obatoperasi\PersiapanOperasiRinci;
use App\Models\Simrs\Penunjang\Farmasinew\Stokreal;
use App\Models\Simrs\Penunjang\Kamaroperasi\PermintaanOperasi;
use App\Models\Simrs\Rajal\KunjunganPoli;
use App\Models\Simrs\Ranap\Kunjunganranap;
use App\Models\SistemBayar;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PersiapanOperasiController extends Controller
{
    // get area
    public function getPermintaan()
    {
        $flag = request('flag') ?? [];
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
        $data = PersiapanOperasi::with([
            'rinci' => function ($q) {
                $q->with([
                    'obat:kd_obat,nama_obat,satuan_k',
                    'susulan:kdpegsimrs,nama'
                ])->orderBy('id', 'ASC');
            },
            // 'rinci.obat:kd_obat,nama_obat,satuan_k',
            // 'rinci.susulan:kdpegsimrs,nama',
            'pasien:rs1,rs2',
            'list:rs1,rs4,rs14',
            'list.sistembayar:rs1,rs2,groups',
            'list.kunjunganranap:rs1,rs5,rs6',
            'list.kunjunganranap.relmasterruangranap:rs1,rs2',
            'list.kunjunganrajal:rs1,rs8',
            'list.kunjunganrajal.relmpoli:rs1,rs2'
        ])
            ->whereIn('flag', $flag)
            ->whereBetween('tgl_permintaan', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
            ->where(function ($query) use ($rm) {
                $query->when(count($rm) > 0, function ($wew) use ($rm) {
                    $wew->whereIn('norm', $rm);
                })
                    ->orWhere('nopermintaan', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('norm', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('noreg', 'LIKE', '%' . request('q') . '%');
            })
            ->orderBy('tgl_permintaan', "desc")
            ->simplePaginate(request('per_page'));
        // ->paginate(request('per_page'));
        return new JsonResponse($data);
    }
    public function getPermintaanForDokter()
    {
        $belum = PersiapanOperasi::with([
            'rinci' => function ($ri) {
                $ri->with('obat:kd_obat,nama_obat,satuan_k')->orderBy('id', 'ASC');;
            },
            'pasien:rs1,rs2',
            'userminta:kdpegsimrs,nama',
            'userdist:kdpegsimrs,nama',
            'dokter:kdpegsimrs,nama',
        ])

            ->whereIn('flag', ['1', '2'])
            ->where('noreg', '=', request('noreg'))
            ->whereBetween('tgl_permintaan', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
            ->orderBy('tgl_permintaan', "desc")
            ->get();
        $sudah = PersiapanOperasi::select(
            'persiapan_operasi_rincis.*',
            'persiapan_operasis.id as headid',
            'persiapan_operasis.noreg',
            'persiapan_operasis.norm',
            'persiapan_operasis.tgl_resep',
            'persiapan_operasis.dokter',
            'persiapan_operasis.user_minta',
            'persiapan_operasis.flag',
            'new_masterobat.nama_obat',
            'new_masterobat.satuan_k',
        )
            ->with([
                'pasien:rs1,rs2',
                'userminta:kdpegsimrs,nama',
                'dokter:kdpegsimrs,nama',
            ])
            // ->leftJoin('persiapan_operasi_rincis', function ($q) {
            //     $q->on('persiapan_operasis.nopermintaan', '=', 'persiapan_operasi_rincis.nopermintaan')
            //         ->leftJoin('new_masterobat', 'persiapan_operasi_rincis.kd_obat', '=', 'new_masterobat.kd_obat');
            // })
            ->leftJoin('persiapan_operasi_rincis', 'persiapan_operasis.nopermintaan', '=', 'persiapan_operasi_rincis.nopermintaan')
            ->leftJoin('new_masterobat', 'persiapan_operasi_rincis.kd_obat', '=', 'new_masterobat.kd_obat')
            ->whereIn('persiapan_operasis.flag', ['2', '3', '4'])
            ->where('persiapan_operasis.noreg', '=', request('noreg'))
            // ->when(request('noresep'), function ($q) {
            //     $q->where('persiapan_operasi_rincis.noresep', request('noresep'));
            // })
            ->where('persiapan_operasi_rincis.noresep', '!=', '')
            ->whereBetween('persiapan_operasis.tgl_permintaan', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
            ->orderBy('persiapan_operasis.tgl_permintaan', "desc")
            ->get();
        return new JsonResponse([
            'belum' => $belum,
            'sudah' => $sudah,
        ]);
    }
    public function getObatPersiapan()
    {
        // penccarian termasuk tiperesep
        $groupsistembayar = request('groups');
        if ($groupsistembayar === '1' || $groupsistembayar === 1) {
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
            'new_masterobat.status_konsinyasi',
            'new_masterobat.kekuatan_dosis as kekuatandosis',
            'new_masterobat.volumesediaan as volumesediaan',
            DB::raw('sum(stokreal.jumlah) as total')
        )
            ->with(
                [
                    'minmax',
                    'persiapanrinci' => function ($res) {
                        $res->select(
                            'persiapan_operasi_rincis.kd_obat',
                            DB::raw('sum(persiapan_operasi_rincis.jumlah_minta) as jumlah'),
                        )
                            ->leftJoin('persiapan_operasis', 'persiapan_operasis.nopermintaan', '=', 'persiapan_operasi_rincis.nopermintaan')
                            ->whereIn('persiapan_operasis.flag', ['', '1'])
                            ->groupBy('persiapan_operasi_rincis.kd_obat');
                    },
                    // 'transnonracikan' => function ($transnonracikan) {
                    //     $transnonracikan->select(
                    //         'resep_permintaan_keluar.kdobat as kdobat',
                    //         'resep_keluar_h.depo as kdruang',
                    //         DB::raw('sum(resep_permintaan_keluar.jumlah) as jumlah')
                    //     )
                    //         ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_permintaan_keluar.noresep')
                    //         ->where('resep_keluar_h.depo', request('kdruang'))
                    //         ->whereIn('flag', ['', '1', '2'])
                    //         ->groupBy('resep_permintaan_keluar.kdobat');
                    // },
                    // 'transracikan' => function ($transracikan) {
                    //     $transracikan->select(
                    //         'resep_permintaan_keluar_racikan.kdobat as kdobat',
                    //         'resep_keluar_h.depo as kdruang',
                    //         DB::raw('sum(resep_permintaan_keluar_racikan.jumlah) as jumlah')
                    //     )
                    //         ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_permintaan_keluar_racikan.noresep')
                    //         ->where('resep_keluar_h.depo', request('kdruang'))
                    //         ->whereIn('flag', ['', '1', '2'])
                    //         ->groupBy('resep_permintaan_keluar_racikan.kdobat');
                    // },
                    'permintaanobatrinci' => function ($permintaanobatrinci) {
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

                            ->where('permintaan_h.tujuan', request('kdruang'))
                            ->whereIn('permintaan_h.flag', ['', '1', '2'])
                            ->groupBy('permintaan_r.kdobat');
                    },
                ]
            )
            ->leftjoin('new_masterobat', 'new_masterobat.kd_obat', 'stokreal.kdobat')
            ->where('stokreal.kdruang', request('kdruang'))
            ->where('stokreal.jumlah', '>', 0)
            ->whereIn('new_masterobat.sistembayar', $sistembayar)
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
            $jumlahper = $x['persiapanrinci'][0]->jumlah ?? 0;
            // $jumlahtrans = $x['transnonracikan'][0]->jumlah ?? 0;
            // $jumlahtransx = $x['transracikan'][0]->jumlah ?? 0;
            $permintaanobatrinci = $x['permintaanobatrinci'][0]->allpermintaan ?? 0; // mutasi antar depo
            // $x->alokasi = (float)$total - (float)$jumlahtrans - (float)$jumlahtransx - (float)$jumlahper - (float)$permintaanobatrinci;
            $x->alokasi = (float)$total -  (float)$jumlahper - (float)$permintaanobatrinci;
            return $x;
        });
        return new JsonResponse(
            [
                'dataobat' => $wew,
                'siba' => $sistembayar
            ]
        );
    }
    // post placed down below
    public function simpanPermintaan(Request $request)
    {
        $user = FormatingHelper::session_user();
        $kode = $user['kodesimrs'];

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
            'new_masterobat.status_konsinyasi',
            'new_masterobat.kekuatan_dosis as kekuatandosis',
            'new_masterobat.volumesediaan as volumesediaan',
            DB::raw('sum(stokreal.jumlah) as total')
        )
            ->with(
                [
                    'minmax',
                    'persiapanrinci' => function ($res) {
                        $res->select(
                            'persiapan_operasi_rincis.kd_obat',
                            DB::raw('sum(persiapan_operasi_rincis.jumlah_minta) as jumlah'),
                        )
                            ->leftJoin('persiapan_operasis', 'persiapan_operasis.nopermintaan', '=', 'persiapan_operasi_rincis.nopermintaan')
                            ->whereIn('persiapan_operasis.flag', ['', '1'])
                            ->groupBy('persiapan_operasi_rincis.kd_obat');
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
                ]
            )
            ->leftjoin('new_masterobat', 'new_masterobat.kd_obat', 'stokreal.kdobat')
            ->where('stokreal.kdruang', $request->kodedepo)
            ->where('stokreal.jumlah', '>', 0)
            ->where('stokreal.kdobat', $request->kodeobat)
            ->first();

        $total = $cariobat->total ?? 0;
        $jumlahper = $cariobat->persiapanrinci[0]->jumlah ?? 0;
        $permintaanobatrinci = $cariobat->permintaanobatrinci[0]->allpermintaan ?? 0;
        $alokasi = (float)$total -  (float)$jumlahper - (float)$permintaanobatrinci;

        if ($request->jumlah_minta > $alokasi) {
            return new JsonResponse([
                'message' => 'Maaf Stok Alokasi tidak mencukupi, sisa alokasi : ' . $alokasi,
                'cari' => $cariobat
            ], 410);
        }
        // return new JsonResponse([
        //     'cariobat' => $cariobat,
        //     'total' => $total,
        //     'jumlahper' => $jumlahper,
        //     'permintaanobatrinci' => $permintaanobatrinci,
        //     'alokasi' => $alokasi,
        //     // 'wew' => $wew,
        //     // 'req' => $request->all(),
        //     // 'nopermintaan' => $nopermintaan,
        //     // 'num' => $num
        // ], 410);
        if (!$request->nopermintaan) {
            // $jum = PersiapanOperasi::whereMonth('tgl_permintaan', date('m'))->latest('id')->get();
            // // $jum = PersiapanOperasi::whereMonth('tgl_permintaan', '01')->latest('id')->get();
            // $num = 0;
            // if (count($jum) >= 1) {
            //     $expl = explode('/', $jum[0]->nopermintaan);
            //     $num = (int) $expl[0] ?? 0;
            // }
            // $jmlChar = count(str_split(strval($num)));
            // $nol = [];
            // for ($i = 0; $i < 8 - $jmlChar; $i++) {
            //     $nol[] = '0';
            // }
            // $imp = implode('', $nol) . ($num + 1);

            $procedure = 'persiapanok(@nomor)';
            $colom = 'persiapanok';
            $lebel = 'OP-KO';
            DB::connection('farmasi')->select('call ' . $procedure);
            $x = DB::connection('farmasi')->table('conter')->select($colom)->get();
            $wew = $x[0]->$colom;
            $nopermintaan = FormatingHelper::resep($wew, $lebel);
            // $nopermintaan = $imp  . '/OP/' . date('dmY');
        } else {
            $ada = PersiapanOperasi::where('nopermintaan', $request->nopermintaan)->first();
            if ($ada) {
                $flag = (int)$ada->flag;
                if ($flag >= 1) {
                    return new JsonResponse([
                        'message' => 'Nomor Permintaan Bukan draft, silakan ganti nomor permintaan',
                    ], 410);
                }
                if ($ada->flag === '' && $ada->noreg !== $request->noreg) {
                    return new JsonResponse([
                        'message' => 'Nomor Permintaan Sudah dipakai pasien yang lain, silakan ganti nomor permintaan',
                    ], 410);
                }
            }
            $adaDist = PersiapanOperasiDistribusi::where('nopermintaan', $request->nopermintaan)->get();
            if (count($adaDist)) {
                return new JsonResponse([
                    'message' => 'Nomor Permintaan Ini sudah pernah di distribusikan silahkan pilin nomor yang lain',
                ], 410);
            }
            $nopermintaan = $request->nopermintaan;
        }
        // return new JsonResponse([
        //     'jum' => $jum,
        //     'nopermintaan' => $nopermintaan,
        //     'num' => $num
        // ], 410);
        // cek lagi alokasi

        $head = PersiapanOperasi::updateOrCreate(
            [
                'noreg' => $request->noreg,
                'norm' => $request->norm,
                'nopermintaan' => $nopermintaan,
            ],
            [
                'tgl_permintaan' => date('Y-m-d H:i:s'),
                'user_minta' => $kode,
            ]
        );
        $rinci = PersiapanOperasiRinci::updateOrCreate(
            [
                'nopermintaan' => $nopermintaan,
                'kd_obat' => $request->kodeobat,
            ],
            [
                'jumlah_minta' => $request->jumlah_minta,
                'status_konsinyasi' => $request->status_konsinyasi ?? '',
            ]
        );
        if ($rinci) {
            $rinci->load('obat:kd_obat,nama_obat');
        }
        $all = PersiapanOperasi::with('rinci.obat:kd_obat,nama_obat')->find($head->id);
        return new JsonResponse(
            [
                'message' => 'Data Berhasil Disimpan',
                'heder' => $head,
                'all' => $all,
                'rinci' => $rinci,
                'nota' => $nopermintaan,
            ],
            200
        );
    }

    public function hapusObatPermintaan(Request $request)
    {
        $data = PersiapanOperasiRinci::find($request->id);
        if (!$data) {
            return new JsonResponse([
                'message' => 'Gagal hapus, data tidak ditemukan'
            ], 410);
        }
        $allRinci = PersiapanOperasiRinci::where('nopermintaan', $request->nopermintaan)->count();
        $data->delete();
        if ($allRinci <= 1) {
            $head = PersiapanOperasi::where('nopermintaan', $request->nopermintaan)->first();
            $head->delete();
        }
        return new JsonResponse([
            'message' => 'Data berhasil dihapus',
            'obat' => $data,
            'head' => $head ?? null,
            'all rinci' => $allRinci
        ], 200);
    }

    public function selesaiObatPermintaan(Request $request)
    {
        $data = PersiapanOperasi::where('nopermintaan', $request->nopermintaan)->first();
        if (!$data) {
            return new JsonResponse([
                'data' => $data,
                'message' => 'data tidak ditemukan'
            ], 410);
        }
        $data->flag = '1';
        $data->save();
        $data->load('rinci.obat:kd_obat,nama_obat');
        return new JsonResponse([
            'data' => $data,
            'message' => 'Permintaan obat untuk operasi sudah dikirimkan ke depo'
        ], 200);
    }

    public function simpanDistribusi(Request $request)
    {
        // cek stok
        $cek = $request->rinci;
        $st = [];
        if (count($cek) > 0) {
            foreach ($cek as $key) {
                $stok = Stokreal::selectRaw('*, sum(jumlah) as total')
                    ->where('kdobat', $key['kd_obat'])
                    ->where('kdruang', 'Gd-04010103')
                    ->where('jumlah', '>', 0)
                    ->groupBy('kdobat')
                    ->first();
                // $namaobat = $key['obat']['nama_obat'] ?? '';
                $obat = Mobatnew::where('kd_obat', $key['kd_obat'])->first();
                if (!$stok) {
                    return new JsonResponse(['message' => 'Stok Obat' . $obat->nama_obat . ' tidak tersedia'], 410);
                }
                if ($stok->total < $key['jumlah_distribusi']) {
                    return new JsonResponse([
                        'message' => 'stok ' . $obat->nama_obat . ' tidak mencukupi, stok tersisa' . $stok->total . ' silahkan kurangi jumlah distribusi'
                    ], 410);
                }
            }
        }
        $kode = collect($request->rinci)->pluck('kd_obat');
        // $awaStok = Stokreal::whereIn('kdobat', $kode)
        //     ->where('kdruang', 'Gd-04010103')
        //     ->where('jumlah', '>', 0)
        //     ->orderBy('tglExp', 'ASC')
        //     ->get();
        $allStok = Stokreal::lockForUpdate()
            ->whereIn('kdobat', $kode)
            ->where('kdruang', 'Gd-04010103')
            ->where('jumlah', '>', 0)
            ->orderBy('tglpenerimaan', 'ASC')
            ->get();
        $anu = $allStok->toArray();
        $col = collect($anu);
        // return new JsonResponse([
        //     'message' => 'test',
        //     'kode' => $kode,
        //     'allStok' => $allStok,
        // ], 410);
        try {
            DB::connection('farmasi')->beginTransaction();
            $rinci = $request->rinci;
            $user = FormatingHelper::session_user();
            $kode = $user['kodesimrs'];

            // pastikan ada data
            if (count($rinci) > 0) {
                $data = [];
                foreach ($rinci as $key) {

                    // update rinci
                    $dataRinci = PersiapanOperasiRinci::find($key['id']);
                    if (!$dataRinci) {
                        return new JsonResponse(['message' => 'Data Rinci tidak ditemukan']);
                    }
                    $dataRinci->jumlah_distribusi = $key['jumlah_distribusi'];
                    $dataRinci->save();

                    // lanjut ngisi data by fifo
                    $distribusi = (float)$key['jumlah_distribusi'];

                    // pastikan jumlah distribusi lebih dari 0
                    if ($distribusi > 0) {
                        // $stok = Stokreal::where('kdobat', $key['kd_obat'])
                        //     ->where('kdruang', 'Gd-04010103')
                        //     ->where('jumlah', '>', 0)
                        //     ->orderBy('tglExp', 'ASC')
                        //     ->get();
                        // $stok = collect($allStok)->where('kdobat', $key['kd_obat'])->toArray();

                        // return new JsonResponse([
                        //     'message' => 'test',
                        //     'kode' => $key['kd_obat'],
                        //     'stok' => $stok,
                        //     'allStok' => $allStok,
                        // ], 410);

                        // $index = 0;

                        while ($distribusi > 0) {
                            $stok = $col->where('kdobat', $key['kd_obat'])->first();
                            $ids =  array_column($col->toArray(), 'id');
                            $ind = array_search($stok['id'], $ids);
                            // return new JsonResponse([
                            //     'message' => 'test',
                            //     'ids' => $ids,
                            //     'stok' => $stok,
                            //     'ind' => $ind,
                            // ], 410);
                            $ada = (float)$stok['jumlah'];
                            if ($ada < $distribusi) {
                                $temp = [
                                    'nopermintaan' => $key['nopermintaan'],
                                    'kd_obat' => $key['kd_obat'],
                                    'nopenerimaan' => $stok['nopenerimaan'],
                                    'nobatch' => $stok['nobatch'],
                                    'harga' => $stok['harga'],
                                    'nodistribusi' => $stok['nodistribusi'],
                                    'jumlah' => $ada,
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'updated_at' => date('Y-m-d H:i:s'),
                                ];
                                $adaSt = collect($data)->where('nopermintaan', $key['nopermintaan'])
                                    ->where('kd_obat', $key['kd_obat'])
                                    ->where('nopenerimaan', $stok['nopenerimaan'],)
                                    ->where('nobatch', $stok['nobatch'],)
                                    ->where('nodistribusi', $stok['nodistribusi'])
                                    ->where('jumlah', $ada,)
                                    ->first();
                                if (!$adaSt) $data[] = $temp;
                                $sisa = $distribusi - $ada;
                                // $index += 1;
                                $col->splice($ind, 1);
                                $distribusi = $sisa;
                            } else {
                                $temp = [
                                    'nopermintaan' => $key['nopermintaan'],
                                    'kd_obat' => $key['kd_obat'],
                                    'nopenerimaan' => $stok['nopenerimaan'],
                                    'nobatch' => $stok['nobatch'],
                                    'harga' => $stok['harga'],
                                    'nodistribusi' => $stok['nodistribusi'],
                                    'jumlah' => $distribusi,
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'updated_at' => date('Y-m-d H:i:s'),
                                ];

                                $adaSt = collect($data)->where('nopermintaan', $key['nopermintaan'])
                                    ->where('kd_obat', $key['kd_obat'])
                                    ->where('nopenerimaan', $stok['nopenerimaan'],)
                                    ->where('nobatch', $stok['nobatch'],)
                                    ->where('nodistribusi', $stok['nodistribusi'])
                                    ->where('jumlah', $distribusi,)
                                    ->first();
                                if (!$adaSt) $data[] = $temp;
                                $distribusi = 0;
                            }
                        }
                    }
                }
            }

            // return new JsonResponse([
            //     'message' => 'test hasil',
            //     // 'kode' => $key['kd_obat'],
            //     'ada' => $adaSt ?? false,
            //     'data' => $data,
            //     'allStok' => $allStok,
            //     'anu' => $anu,
            //     'col' => $col,
            // ], 410);
            // update header
            $head = PersiapanOperasi::where('nopermintaan', $request->nopermintaan)->first();
            if (!$head) {
                return new JsonResponse(['message' => 'Data Header tidak ditemukan'], 410);
            }
            $head->flag = '2';
            $head->user_distribusi = $kode;
            $head->tgl_distribusi = date('Y-m-d H:i:s');
            $head->save();

            //simpan ditribusi
            $dist = PersiapanOperasiDistribusi::insert($data); // ini hasilnya kalo berhasil itu true
            if (!$dist) {
                return new JsonResponse(['message' => 'Data gagal disimpan '], 410);
            }
            // update stok
            $dataDist = PersiapanOperasiDistribusi::where('nopermintaan', $request->nopermintaan)->get();
            foreach ($dataDist as $rin) {
                $stok = Stokreal::where('kdobat', $rin['kd_obat'])
                    ->where('kdruang', 'Gd-04010103')
                    ->where('nopenerimaan', $rin['nopenerimaan'])
                    ->where('nodistribusi', $rin['nodistribusi'])
                    ->where('jumlah', '>', 0)
                    ->first();
                if (!$stok) {
                    return new JsonResponse(['message' => 'Data stok tidak ditemukan'], 410);
                }
                if ($stok->jumlah <= 0) {
                    $obat = Mobatnew::where('kd_obat', $rin['kd_obat'])->first();
                    return new JsonResponse(['message' => 'Data stok ' . $obat->nama_obat . ' kurang dari 0'], 410);
                }
                $sisa = $stok->jumlah - $rin['jumlah'];
                $stok->jumlah = $sisa;
                $stok->save();
            }

            DB::connection('farmasi')->commit();

            return new JsonResponse([
                'rinci' => $rinci,
                'data' => $dist,
                'head' => $head,
                'message' => 'Data berhasil di simpan'
            ]);
        } catch (\Exception $e) {
            DB::connection('farmasi')->rollBack();
            return new JsonResponse([
                'message' => 'Data Gagal Disimpan ' . $e->getMessage(),
                'line' => '' . $e->getLine(),
                'file' => '' . $e->getFile(),
                'result' => '' . $e,
            ], 410);
        }
    }
    public function tambahDistribusi(Request $request)
    {
        // cek  alokasi
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
            'new_masterobat.status_konsinyasi',
            'new_masterobat.kekuatan_dosis as kekuatandosis',
            'new_masterobat.volumesediaan as volumesediaan',
            DB::raw('sum(stokreal.jumlah) as total')
        )
            ->with(
                [
                    'minmax',
                    'persiapanrinci' => function ($res) {
                        $res->select(
                            'persiapan_operasi_rincis.kd_obat',
                            DB::raw('sum(persiapan_operasi_rincis.jumlah_minta) as jumlah'),
                        )
                            ->leftJoin('persiapan_operasis', 'persiapan_operasis.nopermintaan', '=', 'persiapan_operasi_rincis.nopermintaan')
                            ->whereIn('persiapan_operasis.flag', ['', '1'])
                            ->groupBy('persiapan_operasi_rincis.kd_obat');
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

                            ->where('permintaan_h.tujuan', 'Gd-04010103')
                            ->whereIn('permintaan_h.flag', ['', '1', '2'])
                            ->groupBy('permintaan_r.kdobat');
                    },
                ]
            )
            ->leftjoin('new_masterobat', 'new_masterobat.kd_obat', 'stokreal.kdobat')
            ->where('stokreal.kdruang', 'Gd-04010103')
            ->where('stokreal.jumlah', '>', 0)
            ->where('stokreal.kdobat', $request->kodeobat)
            ->first();

        $total = $cariobat->total ?? 0;
        $jumlahper = $cariobat->persiapanrinci[0]->jumlah ?? 0;
        $permintaanobatrinci = $cariobat->permintaanobatrinci[0]->allpermintaan ?? 0;
        $alokasi = (float)$total -  (float)$jumlahper - (float)$permintaanobatrinci;

        if ($request->jumlah_distribusi > $alokasi) {
            return new JsonResponse([
                'message' => 'Maaf Stok Alokasi tidak mencukupi, sisa alokasi : ' . $alokasi,
                'cari' => $cariobat
            ], 410);
        }
        // cek stok
        $stok = Stokreal::selectRaw('sum(jumlah) as total')
            ->where('kdobat', $request->kodeobat)
            ->where('kdruang', 'Gd-04010103')
            ->where('jumlah', '>', 0)
            ->groupBy('kdobat')
            ->first();

        if (!$stok) {
            $obat = Mobatnew::select('nama_obat', 'satuan_k')->where('kd_obat', $request->kodeobat)->first();
            return new JsonResponse([
                'message' => 'Stok Obat' . $obat->nama_obat ?? 'obat tidak ditemukan'  . ' tidak tersedia',
                'request' => $request->all(),
            ], 410);
        }
        if ((float)$stok->total < (float)$request->jumlah_distribusi) {
            $obat = Mobatnew::select('nama_obat', 'satuan_k')->where('kd_obat', $request->kodeobat)->first();
            if ($obat) {
                return new JsonResponse([
                    'message' => 'stok ' . $obat->nama_obat ?? 'obat tidak ditemukan' . ' tidak mencukupi, stok tersisa ' . $stok->total . ' ' . $obat->satuan_k . ' silahkan kurangi jumlah distribusi',
                    'request' => $request->all(),
                ], 410);
            } else {
                return new JsonResponse([
                    'message' => 'stok obat tidak ditemukan , stok tersisa ' . $stok->total . ' silahkan kurangi jumlah distribusi',
                    'request' => $request->all(),
                ], 410);
            }
        }
        $ada = PersiapanOperasiRinci::where('nopermintaan', $request->nopermintaan)->where('kd_obat', $request->kodeobat)->first();
        if ($ada) {
            return new JsonResponse([
                'message' => 'Obat Sudah di distribusikan, sebaiknya dibuatkan permintaan baru jika akan menambahkan jumlah obat yang telah di distribusikan',
                'request' => $request->all(),
            ], 410);
        }
        try {
            DB::connection('farmasi')->beginTransaction();
            $data = PersiapanOperasiRinci::create(
                [
                    'nopermintaan' => $request->nopermintaan,
                    'kd_obat' => $request->kodeobat,
                    // ],
                    // [
                    'jumlah_minta' => 0,
                    'jumlah_distribusi' => (float)$request->jumlah_distribusi,
                    'susulan' => $request->susulan ?? null,
                    'status_konsinyasi' => $request->status_konsinyasi ?? '',
                ]
            );
            if (!$data) {
                return new JsonResponse([
                    'message' => 'Data Gagal Disimpan...!!!',
                ], 410);
            }
            if ((float)$data->jumlah_distribusi <= 0) {
                return new JsonResponse([
                    'message' => 'Jumlah distribusi gagal disimpan',
                    'data' => $data
                ], 410);
            }
            $rinci = PersiapanOperasiRinci::with('obat:kd_obat,nama_obat,satuan_k', 'susulan:kdpegsimrs,nama')->find($data->id);
            if (!$rinci) {
                return new JsonResponse([
                    'message' => 'Data Tersimpan gagal ditemukan',
                    'data' => $data
                ], 410);
            }

            // lanjut ngisi data by fifo
            $dist = [];
            $distribusi = (float)$request->jumlah_distribusi;
            if ($distribusi > 0) {
                $stok = Stokreal::lockForUpdate()
                    ->where('kdobat', $request->kodeobat)
                    ->where('kdruang', 'Gd-04010103')
                    ->where('jumlah', '>', 0)
                    ->orderBy('tglpenerimaan', 'ASC')
                    ->get();
                $index = 0;
                while ($distribusi > 0) {
                    $ada = (float)$stok[$index]->jumlah;
                    if ($ada < $distribusi) {
                        $temp = [
                            'nopermintaan' => $request->nopermintaan,
                            'kd_obat' => $request->kodeobat,
                            'nopenerimaan' => $stok[$index]->nopenerimaan,
                            'nodistribusi' => $stok[$index]->nodistribusi,
                            'nobatch' => $stok[$index]->nobatch,
                            'harga' => $stok[$index]->harga,
                            'jumlah' => $ada,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ];
                        $adaSt = collect($data)->where('nopermintaan', $request->nopermintaan)
                            ->where('kd_obat', $request->kodeobat)
                            ->where('nopenerimaan', $stok[$index]->nopenerimaan,)
                            ->where('nodistribusi', $stok[$index]->nodistribusi)
                            ->where('nobatch', $stok[$index]->nobatch)
                            ->where('jumlah', $ada,)
                            ->first();
                        if (!$adaSt) $dist[] = $temp;
                        // $dist[] = $temp;
                        $sisa = $distribusi - $ada;
                        $index += 1;
                        $distribusi = $sisa;
                    } else {
                        $temp = [
                            'nopermintaan' => $request->nopermintaan,
                            'kd_obat' => $request->kodeobat,
                            'nopenerimaan' => $stok[$index]->nopenerimaan,
                            'nodistribusi' => $stok[$index]->nodistribusi,
                            'nobatch' => $stok[$index]->nobatch,
                            'harga' => $stok[$index]->harga,
                            'jumlah' => $distribusi,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ];
                        $adaSt = collect($data)->where('nopermintaan', $request->nopermintaan)
                            ->where('kd_obat', $request->kodeobat)
                            ->where('nopenerimaan', $stok[$index]->nopenerimaan,)
                            ->where('nodistribusi', $stok[$index]->nodistribusi)
                            ->where('nobatch', $stok[$index]->nobatch)
                            ->where('jumlah', $distribusi,)
                            ->first();
                        if (!$adaSt) $dist[] = $temp;
                        // $dist[] = $temp;
                        $distribusi = 0;
                    }
                }
            }
            //simpan ditribusi
            $dist = PersiapanOperasiDistribusi::insert($dist); // ini hasilnya kalo berhasil itu true
            if (!$dist) {
                return new JsonResponse(['message' => 'Data gagal disimpan!'], 410);
            }
            // update stok
            $dataDist = PersiapanOperasiDistribusi::where('nopermintaan', $request->nopermintaan)->where('kd_obat', $request->kodeobat)->get();
            foreach ($dataDist as $rin) {
                $stok = Stokreal::where('kdobat', $rin['kd_obat'])
                    ->where('kdruang', 'Gd-04010103')
                    ->where('nopenerimaan', $rin['nopenerimaan'])
                    ->when($rin['nodistribusi'] !== '', function ($x) use ($rin) {
                        $x->where('nodistribusi', $rin['nodistribusi']);
                    })
                    ->first();

                if ($stok->jumlah <= 0) {
                    return new JsonResponse(['message' => 'Data stok kurang dari 0'], 410);
                }
                $sisa = $stok->jumlah - $rin['jumlah'];
                $stok->jumlah = $sisa;
                $stok->save();
            }
            DB::connection('farmasi')->commit();
            return new JsonResponse([
                'message' => 'Sudah disimpan dan di ditribusikan',
                'request' => $request->all(),
                'rinci' => $rinci,
            ]);
        } catch (\Exception $e) {
            DB::connection('farmasi')->rollBack();
            return new JsonResponse([
                'message' => 'Data Gagal Disimpan, Ada Kesalahan Prosedur, silahkan hubungi tim IT',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                // 'result' => '' . $e,
            ], 410);
        }
    }
    public function simpanEresep(Request $request)
    {

        // cek user
        $user = FormatingHelper::session_user();
        if ($user['kdgroupnakes'] != '1') {
            return new JsonResponse(['message' => 'Maaf Anda Bukan Dokter...!!!'], 500);
        }

        // buat no resep
        if ($request->noresep === '' || $request->noresep === null) {
            DB::connection('farmasi')->select('call resepkeluardepook(@nomor)');
            $x = DB::connection('farmasi')->table('conter')->select('depook')->get();
            $wew = $x[0]->depook;
            $noresep = FormatingHelper::resep($wew, 'D-KO');
        } else {
            $noresep = $request->noresep;
        }
        $head =            [
            // 'noresep' => $noresep,
            'noreg' => $request->noreg,
            'norm' => $request->norm,
            'tgl_permintaan' => date('Y-m-d H:i:s'),
            'tgl_kirim' => date('Y-m-d H:i:s'),
            'tgl' => date('Y-m-d'),
            'depo' => 'Gd-04010103',
            'ruangan' => $request->ruangan,
            'dokter' =>  $user['kodesimrs'],
            'sistembayar' => $request->sistembayar,
            'diagnosa' => $request->diagnosa ?? '',
            'kodeincbg' => $request->kodeincbg ?? '',
            'uraianinacbg' => $request->uraianinacbg ?? '',
            'tarifina' => $request->tarifina ?? '',
            'tiperesep' => $request->tiperesep ?? 'normal',
            'tagihanrs' => $request->tagihanrs ?? 0,
            'flag' => '9',
        ];
        $obat = $request->obats;
        $rinci = [];
        $noper = [];
        if (count($obat) > 0) {
            foreach ($obat as $key) {
                // cari harga
                $sistemBayar = SistemBayar::select('groups')->where('rs1', $request->kodesistembayar)->first();
                $gr = $sistemBayar->groups ?? '';


                $har = HargaHelper::getHarga($key['kd_obat'], $gr);
                $res = $har['res'];
                if ($res) {
                    return new JsonResponse(['message' => $har['message']], 410);
                }
                $hargajualx = $har['hargaJual'];
                $harga = $har['harga'];
                $masterObat = Mobatnew::where('kd_obat', $key['kd_obat'])->first();
                $rin = [
                    'noreg' => $request->noreg,
                    'noresep' => $noresep,
                    'kdobat' => $masterObat->kd_obat,
                    'kandungan' => $masterObat->kandungan,
                    'fornas' => $masterObat->status_fornas,
                    'forkit' => $masterObat->status_forkid,
                    'generik' => $masterObat->status_generik,
                    'kode108' => $masterObat->kode108,
                    'uraian108' => $masterObat->uraian108,
                    'kode50' => $masterObat->kode50,
                    'uraian50' => $masterObat->uraian50,
                    'stokalokasi' => $request->stokalokasi ?? 0,
                    'r' => $request->groupsistembayar == '1'  ? 300 : 0,
                    'jumlah' => $key['jumlah_resep'],
                    'hpp' => $harga ?? 0,
                    'hargajual' => $hargajualx,
                    'aturan' => '-',
                    'konsumsi' => 1,
                    'keterangan' => 'Di pakai untuk operasi' ?? '',
                    'created_at' => date('Y-m-d'),
                    'updated_at' => date('Y-m-d'),
                ];
                $rinci[] = $rin;
                $noper[] = $key['nopermintaan'];
                // update rinci no resep
                $adaRinci = PersiapanOperasiRinci::find($key['id']);
                if (!$adaRinci) {
                    return new JsonResponse(['message' => 'update no resep gagal']);
                }
                $adaRinci->noresep = $noresep;
                $adaRinci->jumlah_resep = $key['jumlah_resep'];
                $adaRinci->jumlah_resep = $key['jumlah_resep'];
                $adaRinci->save();
            }
        } else {

            return new JsonResponse(['message' => 'Tidak ada Obat untuk disimpan'], 410);
        }
        $header = Resepkeluarheder::updateOrCreate(['noresep' => $noresep], $head);
        if (!$header) {
            return new JsonResponse(['message' => 'Resep gagal di buat'], 410);
        }
        // hapus resep yang ada
        // $delRin = Permintaanresep::where('noresep', $noresep)->delete();

        // insert permintaan resep
        $insRinci = Permintaanresep::insert($rinci);

        $unoper = array_unique($noper);
        // cek untuk update header
        foreach ($unoper as $key) {
            $temp = PersiapanOperasiRinci::where('noresep', '')->where('nopermintaan', $key)->get();
            if (count($temp) === 0) {
                $he = PersiapanOperasi::where('nopermintaan', $key)->first();
                $he->flag = '3';
                $he->save();
            }
            $per = PersiapanOperasi::where('nopermintaan', $key)->first();
            if ($per) {
                $per->update(['tgl_resep' => date('Y-m-d H:i:s')]);
            }
        }
        return new JsonResponse([
            'message' => 'resep sudah di dimpan',
            'header' => $header,
            'rinci' => $insRinci,
            'noresep' => $noresep,
            // 'header' => $head,
            // 'rinci' => $rinci,
            // 'noper' => $noper,
            // 'unoper' => $unoper,

        ]);
    }
    public function selesaiEresep(Request $request)
    {
        $data = PersiapanOperasi::find($request->id);
        if ($data) {
            $data->flag = '3';
            $data->save();
            return new JsonResponse([
                'message' => 'Resep untuk nomor permintaan ' . $request->nopermintaan . ' sudah selesai'
            ]);
        }
        return new JsonResponse([
            'message' => 'Nomor permintaan ' . $request->nopermintaan . ' gagal diselesaikan, tidak adakan diterima oleh depo'
        ], 410);
    }
    public function batalObatResep(Request $request)
    {
        $head = PersiapanOperasi::find($request->headid);
        $data = PersiapanOperasiRinci::find($request->id);
        $flag = (int) $head->flag;
        if ($flag <= 3) {
            $data->noresep = '';
            $data->jumlah_resep = 0;
            $data->save();
            $head->flag = '2';
            $head->save();
        } else {
            return new JsonResponse(['message' => 'Tidak boleh di hapus dari resep karena sudah di proses di apotek'], 410);
        }
        return new JsonResponse([
            'message' => 'Obat sudah di hapus dari resep',
            'head' => $head,
            'data' => $data,
            // 'req' => $request->all(),
        ]);
    }
    public function batalOperasi(Request $request)
    {
        try {
            DB::connection('farmasi')->beginTransaction();
            $head = PersiapanOperasi::find($request->id);
            if (!$head) {
                return new JsonResponse([
                    'message' => 'Persiapan untuk operasi tidak ditemukan',
                    // 'head' => $head,
                    // 'data' => $data,
                    'req' => $request->all(),
                ], 410);
            }
            if ($head->flag === '1') {
                $head->update(['flag' => '5']);

                DB::connection('farmasi')->commit();
                return new JsonResponse([
                    'message' => 'Persiapan untuk operasi dibatalkan',
                    'head' => $head,
                    // 'data' => $data,
                    'req' => $request->all(),
                ]);
            } else if ($head->flag === '2') {
                $rinci = PersiapanOperasiRinci::where('nopermintaan', $head->nopermintaan)->get();
                $dist = PersiapanOperasiDistribusi::where('nopermintaan', $head->nopermintaan)->get();
                // if (count($dist) <= 0 || count($rinci) <= 0) {
                //     return new JsonResponse([
                //         'message' => 'Rincian persiapan untuk operasi tidak ditemukan',
                //         'head' => $head,
                //         'rinci' => $rinci,
                //         'dist' => $dist,
                //         // 'data' => $data,
                //         'req' => $request->all(),
                //     ], 410);
                // }
                if (count($rinci) > 0) {
                    foreach ($rinci as $key) {
                        $key->update(['jumlah_kembali' => $key->jumlah_distribusi]);
                    }
                }
                if (count($dist) > 0) {
                    foreach ($dist as $key) {
                        if (!$key->jumlah) {
                            throw new Exception('Jumlah obat kosong ' . $key->kd_obat . ' sejumlah ' . $key->jumlah);
                        }
                        $key->update([
                            'jumlah_retur' => $key->jumlah,
                            'tgl_retur' => date('Y-m-d H:i:s')
                        ]);
                        $stok = Stokreal::where('kdobat', $key->kd_obat)
                            ->where('nopenerimaan', $key->nopenerimaan)
                            ->when(!empty($key->nodistribusi), function ($x) use ($key) {
                                $x->where('nodistribusi', $key->nodistribusi);
                            })
                            ->when(!empty($key->nobatch), function ($x) use ($key) {
                                $x->where('nobatch', $key->nobatch);
                            })
                            ->where('kdruang', 'Gd-04010103')
                            ->first();
                        if (!$stok) {
                            $stok2 = Stokreal::where('kdobat', $key->kd_obat)
                                ->where('nopenerimaan', $key->nopenerimaan)

                                ->when(!empty($key->nobatch), function ($x) use ($key) {
                                    $x->where('nobatch', $key->nobatch);
                                })
                                ->where('kdruang', 'Gd-04010103')
                                ->first();
                            if (!$stok2) {
                                throw new \Exception("Stok obat " . $key->kd_obat . " tidak ditemukan");
                            }
                            $totalStok2 = (float)$stok2->jumlah + $key->jumlah;
                            $stok2->update([
                                'jumlah' => $totalStok2
                            ]);
                        } else {
                            $totalStok = (float)$stok->jumlah + $key->jumlah;
                            $stok->update([
                                'jumlah' => $totalStok
                            ]);
                        }
                        // $stok->jumlah = $totalStok;
                        // $stok->save();
                    }
                }
                $head->update(['flag' => '5']);
                DB::connection('farmasi')->commit();
                return new JsonResponse([
                    'message' => 'Persiapan untuk operasi dibatalkan',
                    'head' => $head,
                    'rinci' => $rinci,
                    'dist' => $dist,
                    // 'data' => $data,
                    'req' => $request->all(),
                ]);
            }
            DB::connection('farmasi')->commit();
            return new JsonResponse([
                // 'message' => 'Obat sudah di hapus dari resep',
                'head' => $head,
                // 'data' => $data,
                'req' => $request->all(),
            ]);
        } catch (Exception $e) {
            DB::connection('farmasi')->rollBack();
            return new JsonResponse([
                'message' => $e->getMessage(),
                'line' => '' . $e->getLine(),
                'file' =>  $e->getFile(),
                'req' => $request->all(),
            ], 410);
        }
    }
    public static function resepKeluar($key, $request, $kode, $data)
    {
        $rinci = [];
        $listPasienOp = PermintaanOperasi::where('rs1', $request->noreg)->first();

        // cari harga
        $sistemBayar = 0;
        if ($listPasienOp) {
            $tmp = SistemBayar::select('groups')->where('rs1', $listPasienOp->rs14)->first();
            $sistemBayar = $tmp->groups;
        }
        $gr = $sistemBayar ?? '';

        foreach ($data as $key) {

            $har = HargaHelper::getHarga($key['kd_obat'], $gr);
            $res = $har['res'];
            // if ($res) {
            //     return new JsonResponse(['message' => $har['message']], 410);
            // }
            $hargajualx = $har['hargaJual'] ?? 0;
            $harga = $har['harga'] ?? 0;

            $masterObat = Mobatnew::where('kd_obat', $key['kd_obat'])->first();
            $dist = PersiapanOperasiDistribusi::where('kd_obat', $key['kd_obat'])
                ->where('nopermintaan', $key['nopermintaan'])
                ->orderBy('id', 'ASC')
                ->get();
            $index = 0;
            $maxindex = sizeof($dist) - 1;
            $masuk = (float) $key['jumlah_resep'];
            if ($masuk <= 0) continue;

            while ($masuk > 0) {
                if ($index > $maxindex) {
                    $obat = Mobatnew::select('nama_obat')->where('kd_obat', $key['kd_obat'])->first();
                    throw new \Exception('Distribusi Persiapan obat ' . $obat->nama_obat . ' untuk operasi tidak ditemukan');
                }
                $cek = Resepkeluarrinci::where('nopenerimaan', $dist[$index]->nopenerimaan)
                    ->where('nobatch', $dist[$index]->nobatch)
                    ->where('noresep', $key['noresep'])
                    ->exists();

                if ($cek) {
                    $index++;
                    continue; // skip distribusi yang sudah kepakai
                }
                $ada = (float)$dist[$index]->jumlah;
                $hargaBeli = Stokreal::where('kdobat', $key['kd_obat'])
                    ->where('nopenerimaan', $dist[$index]->nopenerimaan)
                    ->where('kdruang', 'Gd-04010103')
                    ->when(!empty($dist[$index]->nobatch), function ($x) use ($dist, $index) {
                        $x->where('nobatch', $dist[$index]->nobatch);
                    })
                    ->first();
                if ($ada < $masuk) {
                    $rin = [
                        'noreg' => $request->noreg,
                        'noresep' => $key['noresep'],
                        'kdobat' => $masterObat->kd_obat,
                        'kandungan' => $masterObat->kandungan,
                        'fornas' => $masterObat->status_fornas,
                        'forkit' => $masterObat->status_forkid,
                        'generik' => $masterObat->status_generik,
                        'kode108' => $masterObat->kode108,
                        'uraian108' => $masterObat->uraian108,
                        'kode50' => $masterObat->kode50,
                        'uraian50' => $masterObat->uraian50,
                        'nopenerimaan' => $dist[$index]->nopenerimaan,
                        'nobatch' => $dist[$index]->nobatch,
                        'nilai_r' => 300,
                        'jumlah' => $ada,
                        'harga_beli' => $hargaBeli->harga ?? 0,
                        'hpp' => $harga ?? 0,
                        'harga_jual' => $hargajualx,
                        'aturan' => 'dipakai untuk operasi',
                        'konsumsi' => 1,
                        'keterangan' => 'Di pakai untuk operasi',
                        'user' => $kode,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];
                    $rinci[] = $rin;
                    $sisa = $masuk - $ada;
                    $index += 1;
                    $masuk = $sisa;
                } else {
                    $rin = [
                        'noreg' => $request->noreg,
                        'noresep' => $key['noresep'],
                        'kdobat' => $masterObat->kd_obat,
                        'kandungan' => $masterObat->kandungan,
                        'fornas' => $masterObat->status_fornas,
                        'forkit' => $masterObat->status_forkid,
                        'generik' => $masterObat->status_generik,
                        'kode108' => $masterObat->kode108,
                        'uraian108' => $masterObat->uraian108,
                        'kode50' => $masterObat->kode50,
                        'uraian50' => $masterObat->uraian50,
                        'nopenerimaan' => $dist[$index]->nopenerimaan,
                        'nobatch' => $dist[$index]->nobatch,
                        'nilai_r' => 300,
                        'jumlah' => $masuk,
                        'harga_beli' => $hargaBeli->harga ?? 0,
                        'hpp' => $harga ?? 0,
                        'harga_jual' => $hargajualx,
                        'aturan' => 'dipakai untuk operasi',
                        'konsumsi' => 1,
                        'keterangan' => 'Di pakai untuk operasi' ?? '',
                        'user' => $kode,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];
                    $rinci[] = $rin;

                    $masuk = 0;
                }
            }
        }
        return $rinci;
    }
    public function terimaPengembalian(Request $request)
    {

        try {

            DB::connection('farmasi')->beginTransaction();
            $msg = 'Data berhasil disimpan, Obat masih ada yang belum kembali';
            $rinci = $request->rinci;
            $user = FormatingHelper::session_user();
            $kode = $user['kodesimrs'];
            $resepKeluar = [];
            // GET data
            // step 1 tulis persiapan operasi rinci
            foreach ($rinci as $item) {
                if ((float)$item['jumlah_kembali'] > 0) {
                    $dataRinci = PersiapanOperasiRinci::find($item['id']);
                    if (!$dataRinci) throw new \Exception("Data Rinci tidak ditemukan");
                    $dataRinci->jumlah_kembali = $item['jumlah_kembali'];
                    $dataRinci->save();
                }
            }
            // step 2 cek apakah semua sudah kembali
            $flag = '3';
            $details = PersiapanOperasiRinci::where('nopermintaan', $request->nopermintaan)->get();
            $cek = [];
            foreach ($details as $detItem) {
                $dist = (float)$detItem->jumlah_distribusi;
                $resep = (float)$detItem->jumlah_resep;
                $kem = (float)$detItem->jumlah_kembali;

                if ($resep + $kem > $dist) {
                    $obat = Mobatnew::where('kd_obat', $detItem->kd_obat)->first();
                    throw new \Exception("Jumlah kembali obat " . $obat->nm_obat . " melebihi jumlah distribusi");
                }
                if ($resep + $kem == $dist) {
                    $cek[] = '4';
                } else $cek[] = '3';
            }
            if (!empty($cek) && count(array_unique($cek)) === 1 && $cek[0] === '4') {
                $flag = '4';
            }
            // jika semua obat sudah kembali maka lakukan
            if ($flag == '4') {
                // step 3 isi retur dan tanggal retur di persiapan operasi distribusi
                // step 4 bentuk array untuk simpan resep keluar
                $listPasienOp = PermintaanOperasi::where('rs1', $request->noreg)->first();
                // cari harga
                $sistemBayar = 0;
                if ($listPasienOp) {
                    $tmp = SistemBayar::select('groups')->where('rs1', $listPasienOp->rs14)->first();
                    $sistemBayar = $tmp->groups;
                }
                $gr = $sistemBayar ?? '';

                $resepKeluar = [];
                foreach ($details as $detItem) {
                    $kem = (float)$detItem->jumlah_kembali;
                    $resep = (float)$detItem->jumlah_resep;
                    if ($kem == 0 && $resep == 0) {
                        $perDistA = PersiapanOperasiDistribusi::where('kd_obat', $detItem->kd_obat)
                            ->where('nopermintaan', $request->nopermintaan)
                            ->orderBy('id', 'DESC')
                            ->lockForUpdate()
                            ->get();
                        foreach ($perDistA as $distItem) {
                            $distItem->tgl_retur = now();
                            $distItem->save();
                        }
                        continue;
                    }

                    $har = HargaHelper::getHarga($detItem->kd_obat, $gr);
                    $res = $har['res'];

                    $hargajualx = $har['hargaJual'] ?? 0;
                    $harga = $har['harga'] ?? 0;

                    $masterObat = Mobatnew::where('kd_obat', $detItem->kd_obat)->first();
                    $perDist = PersiapanOperasiDistribusi::where('kd_obat', $detItem->kd_obat)
                        ->where('nopermintaan', $request->nopermintaan)
                        ->orderBy('id', 'DESC')
                        ->lockForUpdate()
                        ->get();

                    foreach ($perDist as $distItem) {

                        $jum = (float)$distItem->jumlah;

                        $retu = min($jum, $kem);
                        $hargaBeli = (float)$distItem->harga;
                        $distItem->tgl_retur = now();
                        $distItem->jumlah_retur = $retu;
                        $distItem->save();
                        if ($detItem->jumlah_resep > 0) {
                            if (!$hargaBeli) {
                                $dfthrg = Stokreal::where('kdobat', $distItem->kd_obat)
                                    ->where('nopenerimaan', $distItem->nopenerimaan)
                                    ->where('kdruang', 'Gd-04010103')
                                    ->when(!empty($distItem->nobatch), function ($x) use ($distItem) {
                                        $x->where('nobatch', $distItem->nobatch);
                                    })
                                    ->first();
                                $hargaBeli = $dfthrg->harga ?? 0;
                            }
                            $jmlResep = $detItem->jumlah_resep;
                            $ada = Resepkeluarrinci::where('noresep', $detItem->noresep)->where('kdobat', $distItem->kd_obat)->first();
                            if (!$ada) {
                                $rin = [
                                    'noreg' => $request->noreg,
                                    'noresep' => $detItem->noresep,
                                    'kdobat' => $masterObat->kd_obat,
                                    'kandungan' => $masterObat->kandungan,
                                    'fornas' => $masterObat->status_fornas,
                                    'forkit' => $masterObat->status_forkid,
                                    'generik' => $masterObat->status_generik,
                                    'kode108' => $masterObat->kode108,
                                    'uraian108' => $masterObat->uraian108,
                                    'kode50' => $masterObat->kode50,
                                    'uraian50' => $masterObat->uraian50,
                                    'nopenerimaan' => $distItem->nopenerimaan,
                                    'nobatch' => $distItem->nobatch,
                                    'nilai_r' => 300,
                                    'jumlah' => $jmlResep,
                                    'harga_beli' => $hargaBeli,
                                    'hpp' => $harga ?? 0,
                                    'harga_jual' => $hargajualx,
                                    'aturan' => 'dipakai untuk operasi',
                                    'konsumsi' => 1,
                                    'keterangan' => 'Di pakai untuk operasi',
                                    'user' => $kode,
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'updated_at' => date('Y-m-d H:i:s'),
                                ];
                                $resepKeluar[] = $rin;
                            }
                        }
                        $this->updateStok($distItem->kd_obat, $distItem->nopenerimaan, $distItem->nodistribusi, $distItem->nobatch, $retu);
                        $kem -= $retu;
                    }
                }

                // step 5 simpan resep keluar -> ini pake fungsi yang sudah ada
                $nores = array_unique(array_column($resepKeluar, 'noresep'));
                Resepkeluarrinci::insert($resepKeluar);
                // step 6 ganti flag header resep dan isi tanggal selesai
                foreach ($nores as $nor) {
                    $temp = Resepkeluarheder::where('noresep', $nor)->first();
                    $temp?->update([
                        'flag' => '3',
                        'tgl' => date('Y-m-d'),
                        'tgl_selesai' => now(),
                        'user' => $kode
                    ]);
                    $resepH[] = $temp;
                }
                // step 7 ganti flag persiapan_operasis
                $head = PersiapanOperasi::where('nopermintaan', $request->nopermintaan)->first();
                $head->update([
                    'flag' => $flag,
                    'tgl_retur' => now()
                ]);

                $msg = 'Data berhasil disimpan, Permintaan Sudah Selesai';
            }


            DB::connection('farmasi')->commit();
            return new JsonResponse([
                'rinci' => $rinci,
                'head' => $head ?? null,
                'kurang' => $kurang ?? 0,
                'resepKeluar' => $resepKeluar,
                'dataDistribusi' => $dataDistribusi ?? [],
                'message' => $msg,
                'req' => $request->all(),
            ]);
        } catch (\Exception $e) {
            DB::connection('farmasi')->rollBack();
            return new JsonResponse([
                'message' => 'Data Gagal Disimpan ' . $e->getMessage(),
                'file' =>  $e->getFile(),
                'line' =>  $e->getLine(),
                'rinci' => $rinci ?? '',
                'head' => $head ?? '',
                'resepKeluar' => $resepKeluar ?? '',
                'resepH' => $resepH ?? "",
                'dataDistribusi' => $dataDistribusi ?? [],
                'getDataDistribusi' => $getDataDistribusi ?? [],
                'countDist' => $countDist ?? null,
                'stok' => $stok ?? null,
            ], 410);
        }
    }



    public function terimaPengembalianRf(Request $request)
    {
        DB::connection('farmasi')->beginTransaction();

        try {
            $rinci = $request->rinci;
            $user = FormatingHelper::session_user();
            $kode = $user['kodesimrs'];
            $resepKeluar = [];
            $resepH = [];
            $alurNormal = true;
            $kurang = 0;

            foreach ($rinci as $item) {
                $kembali = (float) $item['jumlah_kembali'];
                $jmlResep = (float) $item['jumlah_resep'];
                $dataDistribusi = PersiapanOperasiDistribusi::where('kd_obat', $item['kd_obat'])
                    ->where('nopermintaan', $item['nopermintaan'])
                    ->orderByDesc('id')
                    ->get();

                $dataDistribusisudah = $dataDistribusi->whereNotNull('tgl_retur')->count();
                $isNormal = $dataDistribusisudah == 0;


                if ($isNormal) {
                    $this->prosesReturNormal($item, $dataDistribusi, $kembali);
                } else {
                    $this->prosesReturLanjutan($item, $dataDistribusi, $kembali, $kurang);
                }
                $alurNormal = $alurNormal && $isNormal;
            }

            if ($alurNormal) {
                $keluar = self::resepKeluar(end($rinci), $request, $kode, $rinci);
                $resepKeluar = array_merge($resepKeluar, $keluar);
            }

            $head = PersiapanOperasi::where('nopermintaan', $request->nopermintaan)->first();
            if (!$head) {
                return new JsonResponse(['message' => 'Data Header tidak ditemukan'], 410);
            }

            $flag = '4';
            $details = PersiapanOperasiRinci::where('nopermintaan', $head->nopermintaan)->get();
            foreach ($details as $detItem) {
                if ((float)$detItem->jumlah_resep + (float)$detItem->jumlah_kembali != (float)$detItem->jumlah_distribusi) {
                    $flag = '3';
                    break;
                }
            }
            $head->update([
                'flag' => $flag,
                'tgl_retur' => now()
            ]);

            if ($alurNormal && count($resepKeluar)) {
                $nores = array_unique(array_column($resepKeluar, 'noresep'));
                Resepkeluarrinci::insert($resepKeluar);

                foreach ($nores as $nor) {
                    $temp = Resepkeluarheder::where('noresep', $nor)->first();
                    $temp?->update([
                        'flag' => '3',
                        'tgl' => date('Y-m-d'),
                        'tgl_selesai' => now(),
                        'user' => $kode
                    ]);
                    $resepH[] = $temp;
                }
            }

            DB::connection('farmasi')->commit();

            return new JsonResponse([
                'rinci' => $rinci,
                'head' => $head,
                'kurang' => $kurang,
                'resepKeluar' => $resepKeluar,
                'resepH' => $resepH,
                'message' => 'Data berhasil disimpan'
            ]);
        } catch (\Exception $e) {
            DB::connection('farmasi')->rollBack();
            return new JsonResponse([
                'message' => 'Data Gagal Disimpan: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 410);
        }
    }

    private function prosesReturNormal($item, $dataDistribusi, $kembali)
    {
        if ($kembali > 0) {
            $dataRinci = PersiapanOperasiRinci::find($item['id']);
            if (!$dataRinci) throw new \Exception("Data Rinci tidak ditemukan");

            $dataRinci->jumlah_kembali = $item['jumlah_kembali'];
            $dataRinci->save();

            foreach ($dataDistribusi as $dist) {
                if ($kembali <= 0) break;
                $jumlah = min($kembali, (float)$dist->jumlah);

                $dist->update([
                    'jumlah_retur' => $jumlah,
                    'tgl_retur' => now()
                ]);


                $this->updateStok($dist->kd_obat, $dist->nopenerimaan, $dist->nodistribusi, $dist->nobatch, $jumlah);
                $kembali -= $jumlah;
            }
        }
        // else {
        //     foreach ($dataDistribusi as $dist) {
        //         // $dist->update(['tgl_retur' => now()]);
        //         if (!$dist->jumlah_retur || $dist->jumlah_retur == 0) {
        //             $dist->update(['tgl_retur' => now()]);
        //         }
        //     }
        // }
    }

    private function prosesReturLanjutan($item, $dataDistribusi, $kembali, $kurang)
    {
        $det = PersiapanOperasiRinci::where('nopermintaan', $item['nopermintaan'])
            ->where('kd_obat', $item['kd_obat'])
            ->first();
        $sudahKembali = $det->jumlah_kembali;
        $kurang = $kembali - $sudahKembali;

        if ($kurang < 0) {
            throw new \Exception("Jumlah kembali harus lebih besar dari sebelumnya");
        }

        $dataRinci = PersiapanOperasiRinci::find($item['id']);
        if (!$dataRinci) throw new \Exception("Data Rinci tidak ditemukan");
        $dataRinci->jumlah_kembali = $item['jumlah_kembali'];
        $dataRinci->save();

        $ind = 0;
        while ($kurang > 0 && $ind < count($dataDistribusi)) {
            $dist = $dataDistribusi[$ind];
            $returSekarang = min($kurang, (float)$dist->jumlah - (float)$dist->jumlah_retur);

            $dist->update([
                'jumlah_retur' => (float)$dist->jumlah_retur + $returSekarang,
                'tgl_retur' => now()
            ]);

            $this->updateStok($dist->kd_obat, $dist->nopenerimaan, $dist->nodistribusi, $dist->nobatch, $returSekarang);
            $kurang -= $returSekarang;
            $ind++;
        }
    }

    private function updateStok($kdobat, $nopenerimaan, $nodistribusi, $nobatch, $jumlah)
    {
        if ($jumlah < 0) {
            throw new \Exception("Jumlah tidak boleh negatif");
        }
        $stok = Stokreal::where('kdobat', $kdobat)
            ->where('nopenerimaan', $nopenerimaan)
            ->when(!empty($nodistribusi), function ($q) use ($nodistribusi) {
                $q->where('nodistribusi', $nodistribusi);
            })
            ->when(!empty($nobatch) && $nobatch != '-', function ($q) use ($nobatch) {
                $q->where('nobatch', $nobatch);
            })
            ->where('kdruang', 'Gd-04010103')
            ->lockForUpdate() // tambahin ini untuk cegah race condition
            ->first();

        if ($stok) {
            $stok->jumlah = (float)$stok->jumlah + $jumlah;
            $stok->save();
        } else {
            $stok2 = Stokreal::where('kdobat', $kdobat)
                ->where('nopenerimaan', $nopenerimaan)

                ->when(!empty($nobatch) && $nobatch != '-', function ($q) use ($nobatch) {
                    $q->where('nobatch', $nobatch);
                })
                ->where('kdruang', 'Gd-04010103')
                ->lockForUpdate() // tambahin ini untuk cegah race condition
                ->first();

            if (!$stok2) throw new \Exception("Stok tidak ditemukan untuk $kdobat");
        }
    }

    public function hapusRincianPerpersiapanOperasi(Request $request)
    {
        $header = PersiapanOperasi::where('nopermintaan', $request->nopermintaan)->first();
        if (!$header) {
            return new JsonResponse(['message' => 'Data tidak ditemukan'], 410);
        }
        if ((int)$header->flag > 1) {
            return new JsonResponse(['message' => 'Sudah di distribusikan, data tidak boleh di hapus'], 410);
        }
        $data = PersiapanOperasiRinci::find($request->id);
        if (!$data) {
            return new JsonResponse(['message' => 'Data tidak ditemukan'], 410);
        }
        $data->delete();
        return new JsonResponse([
            'data' => $data,
            'header' => $header,
            'message' => 'Data berhasil di hapus'
        ]);
    }
}
