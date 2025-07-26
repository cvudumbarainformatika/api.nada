<?php

namespace App\Http\Controllers\Api\Simrs\Pelayanan\Eresep;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Helpers\FormatingHelper;
use App\Helpers\HargaHelper;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaanresep;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaanresepracikan;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Stokreal;

class EresepController extends Controller
{
    public function listresepbynorm()
    {
        $history = Resepkeluarheder::with(
            [
                'rincian.mobat:kd_obat,nama_obat,satuan_k,status_kronis,jenis_perbekalan',
                'rincianracik.mobat:kd_obat,nama_obat,satuan_k,status_kronis,jenis_perbekalan',
                'permintaanresep.mobat:kd_obat,nama_obat,satuan_k,status_kronis,jenis_perbekalan',
                'permintaanresep.aturansigna:signa,jumlah',
                'permintaanracikan.mobat:kd_obat,nama_obat,satuan_k,kekuatan_dosis,status_kronis,jenis_perbekalan,kelompok_psikotropika',
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
            ->where('norm', request('norm'))
            ->orderBy('tgl_permintaan', 'DESC')
            ->get()
            ->chunk(10);
        // return new JsonResponse(request()->all());
        $collapsed = $history->collapse();

        return new JsonResponse($collapsed->all());
    }

    public function listresepbynoreg()
    {
        $history = Resepkeluarheder::with(
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
            ->where('noreg', request('noreg'))
            ->orderBy('tgl_permintaan', 'DESC')
            ->get();
        // return new JsonResponse(request()->all());
        $collapsed = $history->collapse();

        return new JsonResponse($collapsed->all());
    }

    public function editResep(Request $request)
    {
        $obat = Permintaanresep::find($request->id);
        if (!$obat) {
            return new JsonResponse([
                'message' => 'Data Resep Tidak ditemukan'
            ], 410);
        }
        $obat->update([
            'jumlah' => $request->jumlah,
            'aturan' => $request->aturan,
            'konsumsi' => $request->konsumsi,
            'keterangan' => $request->keterangan,
        ]);
        $obat->load('mobat:bentuk_sediaan,jenis_perbekalan,kd_obat,nama_obat,satuan_k');
        return new JsonResponse([
            'message' => 'Data Pemintaa Obat Berhasil Diubah',
            'data' => $obat
        ]);
    }
    public function newCopiResep(Request $request)
    {

        $cekpemberianobat = false;

        $user = FormatingHelper::session_user();
        if ($user['kdgroupnakes'] != '1') {
            return new JsonResponse(['message' => 'Maaf Anda Bukan Dokter...!!!'], 500);
        }

        $kdobat = [];
        $kddepo = $request->kddepo;
        $jenisresep = [];
        $jumlah = [];
        $lanjuTr = [];
        $norm = [];
        $kandungan = [];
        $jumlah = [];
        $noresep = $request->noresep;
        $jenis_perbekalan = [];

        foreach ($request->kirimResep as $records) {
            $kdobat[] = $records['kodeobat'];
            $jenisresep[] = $records['jenisresep'];
            $jumlah[] = $records['jumlah'];
            $lanjuTr[] = $records['lanjuTr'];
            $norm[] = $records['norm'];
            $kandungan[] = $records['kandungan'];
            $jumlah[] = $records['jumlah'];
            $noreg = $records['noreg'];
            $jenis_perbekalan[] = $records['jenis_perbekalan'];
        }


        $cekjumlahstok = Stokreal::select('stokreal.kdobat as kdobat', DB::raw('sum(jumlah) as jumlahstok'))
            ->whereIn('kdobat', $kdobat)
            ->where('kdruang', $kddepo)
            ->with([
                'transnonracikan' => function ($transnonracikan) use ($kddepo) {
                    $transnonracikan->select(
                        // 'resep_keluar_r.kdobat as kdobat',
                        'resep_permintaan_keluar.kdobat as kdobat',
                        'resep_keluar_h.depo as kdruang',
                        DB::raw('sum(resep_permintaan_keluar.jumlah) as jumlah')
                    )
                        ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_permintaan_keluar.noresep')
                        ->where('resep_keluar_h.depo', $kddepo)
                        ->whereIn('resep_keluar_h.flag', ['', '1', '2'])
                        ->groupBy('resep_permintaan_keluar.kdobat');
                },
                'transracikan' => function ($transracikan) use ($kddepo) {
                    $transracikan->select(
                        // 'resep_keluar_racikan_r.kdobat as kdobat',
                        'resep_permintaan_keluar_racikan.kdobat as kdobat',
                        'resep_keluar_h.depo as kdruang',
                        DB::raw('sum(resep_permintaan_keluar_racikan.jumlah) as jumlah')
                    )
                        ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_permintaan_keluar_racikan.noresep')
                        ->where('resep_keluar_h.depo', $kddepo)
                        ->whereIn('resep_keluar_h.flag', ['', '1', '2'])
                        ->groupBy('resep_permintaan_keluar_racikan.kdobat');
                },
                'permintaanobatrinci' => function ($permintaanobatrinci) use ($kddepo) {
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

                        ->where('permintaan_h.tujuan', $kddepo)
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
            ->groupBy('kdobat')
            ->get();
        $wew = collect($cekjumlahstok)->map(function ($x, $y) use ($request) {
            $total = $x->jumlahstok ?? 0;
            $jumlahper = $request->kodedepo === 'Gd-04010103' ? $x['persiapanrinci'][0]->jumlah ?? 0 : 0;
            $jumlahtrans = $x['transnonracikan'][0]->jumlah ?? 0;
            $jumlahtransx = $x['transracikan'][0]->jumlah ?? 0;
            $permintaanobatrinci = $x['permintaanobatrinci'][0]->allpermintaan ?? 0; // mutasi antar depo
            $x->alokasi = (float) $total - (float)$jumlahtrans - (float)$jumlahtransx - (float)$permintaanobatrinci - (float)$jumlahper;
            return $x;
        });

        $results = $wew->map(function ($x) {
            return [
                'jumlahstok' => $x->jumlahstok,
                'alokasi' => $x->alokasi,
                'sistembayar' => $x->sistembayar,
                'kodeobat' => $x->kdobat
            ];
        })->all();

        $collection = collect($results);

        $sorted = $collection->sortBy(function ($item) use ($kdobat) {
            return array_search($item['kodeobat'], $kdobat);
        })->values()->toArray();

        $jumlahstok = [];
        $alokasi = [];
        $sistembayar = [];
        $kdobats = [];

        foreach ($sorted as $result) {
            $jumlahstok[] = $result['jumlahstok'];
            $alokasi[] = $result['alokasi'];
            $sistembayar[] = $result['sistembayar'];
            $kdobats[] = $result['kodeobat'];
        }

        if ($kddepo === 'Gd-05010101') {
            $cekpemberian = self::cekpemberianobat($norm, $kdobat, $kandungan);
        } else {
            $cekpemberian = '';
        }



        return self::newSimpanCopyResep($request, $alokasi,  $noresep, $cekpemberian, $user, $kdobat, $cekpemberianobat, $kddepo, $noreg);

        return new JsonResponse([
            'message' => 'Duplicate resep sedang dalam perbaikan',
            'data' => $request->all()
        ], 410);
    }
    public static function newSimpanCopyResep($request, $alokasi,  $noreseps, $cekpemberian, $user, $kdobat, $cekpemberianobat, $kddepo, $noreg)
    {

        try {

            DB::connection('farmasi')->beginTransaction();

            if ($kddepo === 'Gd-04010102') {
                $procedure = 'resepkeluardeporanap(@nomor)';
                $colom = 'deporanap';
                $lebel = 'D-RI';
            } elseif ($kddepo === 'Gd-04010103') {
                $procedure = 'resepkeluardepook(@nomor)';
                $colom = 'depook';
                $lebel = 'D-KO';
            } elseif ($kddepo === 'Gd-05010101') {
                $procedure = 'resepkeluardeporajal(@nomor)';
                $colom = 'deporajal';
                $lebel = 'D-RJ';
            } else {
                $procedure = 'resepkeluardepoigd(@nomor)';
                $colom = 'depoigd';
                $lebel = 'D-IR';
            }

            $sudahAda = Resepkeluarheder::where('noresep', $noreseps)->first();
            if ($sudahAda) {
                if ($sudahAda->noreg !== $noreg) $noreseps = null;
            }
            if ($noreseps === '' || $noreseps === null) {
                DB::connection('farmasi')->select('call ' . $procedure);
                $x = DB::connection('farmasi')->table('conter')->select($colom)->get();
                $wew = $x[0]->$colom;
                $noresep = FormatingHelper::resep($wew, $lebel);
            } else {
                $noresep = $noreseps;
            }
            $response = [];
            foreach ($request->kirimResep as $key => $record) {
                $create = true;

                // return new JsonResponse($alokasi[$key]);
                if ($record['jenisresep'] === 'nonRacikan') {
                    if ($record['jumlah_diminta'] > $alokasi[$key]) {
                        // throw new \Exception('Maaf Stok Alokasi Tidak Mencukupi...!!!');
                        $create = false;
                        $response[] = [
                            'newapotekrajal' => $endas ?? [],
                            'noresep_asal' => $request->noresep_asal,
                            'nota' => $noresep,
                            'kdobat' => $kdobat[$key],
                            'messageError' => 'Maaf Stok Alokasi Tidak Mencukupi...!!!',
                        ];
                    }
                } else {

                    if ($record['jumlah'] > $alokasi[$key]) {
                        $create = false;
                        // throw new \Exception('Maaf Stok Alokasi Tidak Mencukupi...!!!');
                        $response[] = [
                            'newapotekrajal' => $endas ?? [],
                            'noresep_asal' => $request->noresep_asal,
                            'nota' => $noresep,
                            'kdobat' => $kdobat[$key],
                            'messageError' => 'Maaf Stok Alokasi Tidak Mencukupi...!!!',
                        ];
                    }
                }

                if ($record['kodedepo'] === 'Gd-05010101') {
                    $tiperesep = $record['tiperesep'] ?? 'normal';
                    $iter_expired = $record['iter_expired'] ?? null;
                    $iter_jml = $record['iter_jml'] ?? null;
                    if ($record['tiperesep'] === 'normal') {
                        $iter_expired =  null;
                        $iter_jml =  null;
                    }

                    $lanjut = $record['lanjuTr'];

                    foreach (json_decode($cekpemberian, true) as $ky => $obatkonsumsi) {

                        if ($obatkonsumsi['kdobat'] === $record['kodeobat']) {
                            if ($obatkonsumsi['status'] === 1 && $lanjut !== '1') {

                                // $cekpemberianobat = true;
                                $resp = [
                                    'messageError' => '',
                                    'cek' => $obatkonsumsi['hasil'],
                                    'code' => $record['kodeobat']
                                ];
                                $response[] = [
                                    'newapotekrajal' => $endas ?? [],
                                    'noresep_asal' => $request->noresep_asal,
                                    'nota' => $noresep,
                                    'kdobat' => $kdobat[$key],
                                    'messageError' => $resp
                                ];
                                $create = false;
                                // throw new \Exception(json_encode($resp));
                            }
                        }
                    }
                } else {
                    $tiperesep =  'normal';
                    $iter_expired =  null;
                    $iter_jml =  null;
                }
                if ($create) {
                    $simpan = Resepkeluarheder::updateOrCreate(
                        [
                            'noresep' => $noresep,
                            'noreg' => $record['noreg'],
                        ],
                        [
                            'norm' => $record['norm'],
                            'tgl_permintaan' => date('Y-m-d H:i:s'),
                            'tgl' => date('Y-m-d'),
                            'depo' => $record['kodedepo'],
                            'ruangan' => $record['kdruangan'],
                            'dokter' =>  $user['kodesimrs'],
                            'sistembayar' => $record['sistembayar'],
                            'diagnosa' => $record['diagnosa'],
                            'kodeincbg' => $record['kodeincbg'],
                            'uraianinacbg' => $record['uraianinacbg'],
                            'tarifina' => $record['tarifina'],
                            'tiperesep' => $tiperesep,
                            'iter_expired' => $iter_expired,
                            'iter_jml' => $iter_jml,
                            'flag_dari' => '2',
                            // 'iter_expired' => $record['iter_expired ?? '',
                            'tagihanrs' => $record['tagihanrs'] ?? 0,
                        ]
                    );
                }



                $har = HargaHelper::getHarga($record['kodeobat'], $record['groupsistembayar']);
                $res = $har['res'];
                if ($res) {
                    $response[] = [
                        'newapotekrajal' => $endas ?? [],
                        'noresep_asal' => $request->noresep_asal,
                        'nota' => $noresep,
                        'kdobat' => $kdobat[$key],
                        'messageError' => 'Obat ini tidak mempunyai harga',
                    ];
                    $create = false;
                    // throw new \Exception('Obat ini tidak mempunyai harga');
                }
                $hargajualx = $har['hargaJual'];
                $harga = $har['harga'];
                if ($create) {

                    if ($record['jenisresep'] == 'Racikan') {
                        if ($record['tiperacikan'] == 'DTD') {
                            $simpandtd = Permintaanresepracikan::updateOrCreate(
                                [
                                    'noreg' => $record['noreg'],
                                    'noresep' => $noresep,
                                    'namaracikan' => $record['namaracikan'],
                                    'kdobat' => $record['kodeobat'],
                                ],
                                [
                                    'tiperacikan' => $record['tiperacikan'],
                                    'jumlahdibutuhkan' => $record['jumlahdibutuhkan'], // jumlah racikan
                                    'aturan' => $record['aturan'],
                                    'konsumsi' => $record['konsumsi'],
                                    'keterangan' => $record['keterangan'],
                                    'kandungan' => $record['kandungan'] ?? '',
                                    'fornas' => $record['fornas'] ?? '',
                                    'forkit' => $record['forkit'] ?? '',
                                    'generik' => $record['generik'] ?? '',
                                    'r' => $request->groupsistembayar === '1' || $request->groupsistembayar === 1 ? 500 : 0,
                                    'hpp' => $harga,
                                    'harga_jual' => $hargajualx,
                                    'kode108' => $record['kode108'],
                                    'uraian108' => $record['uraian108'],
                                    'kode50' => $record['kode50'],
                                    'uraian50' => $record['uraian50'],
                                    'stokalokasi' => $alokasi[$key],
                                    'dosisobat' => $record['dosisobat'] ?? 0,
                                    'dosismaksimum' => $record['dosismaksimum'] ?? 0, // dosis resep
                                    'jumlah' => $record['jumlah'], // jumlah obat
                                    'satuan_racik' => $record['satuan_racik'], // jumlah obat
                                    'keteranganx' => $record['keteranganx'], // keterangan obat
                                    'user' => $user['kodesimrs']
                                ]
                            );
                            // if ($simpandtd) {
                            //     $simpandtd->load('mobat:kd_obat,nama_obat');
                            // }
                        } else {
                            $simpannondtd = Permintaanresepracikan::updateOrCreate(
                                [
                                    'noreg' => $record['noreg'],
                                    'noresep' => $noresep,
                                    'namaracikan' => $record['namaracikan'],
                                    'kdobat' => $record['kodeobat'],
                                ],
                                [
                                    'tiperacikan' => $record['tiperacikan'],
                                    'jumlahdibutuhkan' => $record['jumlahdibutuhkan'],
                                    'aturan' => $record['aturan'],
                                    'konsumsi' => $record['konsumsi'],
                                    'keterangan' => $record['keterangan'],
                                    'kandungan' => $record['kandungan'] ?? '',
                                    'fornas' => $record['fornas'] ?? '',
                                    'forkit' => $record['forkit'] ?? '',
                                    'generik' => $record['generik'] ?? '',
                                    'r' => $request->groupsistembayar === '1' || $request->groupsistembayar === 1 ? 500 : 0,
                                    'hpp' => $harga,
                                    'harga_jual' => $hargajualx,
                                    'kode108' => $record['kode108'],
                                    'uraian108' => $record['uraian108'],
                                    'kode50' => $record['kode50'],
                                    'uraian50' => $record['uraian50'],
                                    'stokalokasi' => $alokasi[$key],
                                    // 'dosisobat' => $record['dosisobat,
                                    // 'dosismaksimum' => $request->dosismaksimum,
                                    'jumlah' => $record['jumlah'],
                                    'satuan_racik' => $record['satuan_racik'],
                                    'keteranganx' => $record['keteranganx'],
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
                                'noreg' => $record['noreg'],
                                'noresep' => $noresep,
                                'kdobat' => $record['kodeobat'],
                            ],
                            [
                                'kandungan' => $record['kandungan'] ?? '',
                                'fornas' => $record['fornas'] ?? '',
                                'forkit' => $record['forkit'] ?? '',
                                'generik' => $record['generik'] ?? '',
                                'kode108' => $record['kode108'],
                                'uraian108' => $record['uraian108'],
                                'kode50' => $record['kode50'],
                                'uraian50' => $record['uraian50'],
                                'stokalokasi' => $alokasi[$key],
                                'r' => $request->groupsistembayar === '1' || $request->groupsistembayar === 1 ? 300 : 0,
                                'jumlah' => $record['jumlah_diminta'],
                                'hpp' => $harga,
                                'hargajual' => $hargajualx,
                                'aturan' => $record['aturan'],
                                'konsumsi' => $record['konsumsi'],
                                'keterangan' => $record['keterangan'] ?? '',
                                'user' => $user['kodesimrs']
                            ]
                        );
                        // if ($simpanrinci) {
                        //     $simpanrinci->load('mobat:kd_obat,nama_obat');
                        // }
                    }
                    $endas = Resepkeluarheder::where('noreg', $record['noreg'])->with(
                        'permintaanresep.mobat:kd_obat,nama_obat',
                        'permintaanracikan.mobat:kd_obat,nama_obat'
                    )->get();
                    $response[] = [
                        'newapotekrajal' => $endas,
                        'heder' => $simpan,
                        'rinci' => $simpanrinci ?? 0,
                        'rincidtd' => $simpandtd ?? 0,
                        'rincinondtd' => $simpannondtd ?? 0,
                        'noresep_asal' => $request->noresep_asal,
                        'nota' => $noresep,
                        'kdobat' => $kdobat[$key],
                        'message' => 'Data Berhasil Disimpan...!!!'
                    ];
                }
            }

            // $endas = Resepkeluarheder::where('noreg', $record['noreg'])->with(
            //     'permintaanresep.mobat:kd_obat,nama_obat',
            //     'permintaanracikan.mobat:kd_obat,nama_obat'
            // )->get();

            // $response[] = [
            //     // 'newapotekrajal' => $endas,
            //     'heder' => $simpan,
            //     'rinci' => $simpanrinci ?? 0,
            //     'rincidtd' => $simpandtd ?? 0,
            //     'rincinondtd' => $simpannondtd ?? 0,
            //     'noresep_asal' => $request->noresep_asal,
            //     'nota' => $noresep,
            //     'kdobat' => $kdobat[$key],
            //     'message' => 'Data Berhasil Disimpan...!!!'
            // ];
            DB::connection('farmasi')->commit();
            return new JsonResponse($response, 200);
        } catch (\Exception $e) {

            DB::connection('farmasi')->rollBack();
            return new JsonResponse([
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'req' => $request->all()
            ], 410);
            // if ($cekpemberianobat) {
            //     $cekpemberianobat =  false;
            //     $response[] = [
            //         'newapotekrajal' => $endas ?? [],
            //         'noresep_asal' => $request->noresep_asal,
            //         'nota' => $noresep,
            //         'kdobat' => $kdobat[$key],
            //         'messageError' => json_decode($e->getMessage(), true)
            //     ];
            //     // continue;
            // } else {
            //     $response[] = [
            //         'newapotekrajal' => $endas ?? [],
            //         'noresep_asal' => $request->noresep_asal,
            //         'nota' => $noresep,
            //         'kdobat' => $kdobat[$key],
            //         'messageError' => $e->getMessage(),
            //     ];
            //     // continue;
            // }
        }
    }
    public function copiresep(Request $request)
    {
        // return new JsonResponse(['message'=>'Duplicate resep sedang dalam perbaikan'],410);
        $response = [];
        $cekpemberianobat = false;

        $user = FormatingHelper::session_user();
        if ($user['kdgroupnakes'] != '1') {
            return new JsonResponse(['message' => 'Maaf Anda Bukan Dokter...!!!'], 500);
        }

        $response = [];
        $kdobat = [];
        $kddepo = $request->kddepo;
        $jenisresep = [];
        $jumlah = [];
        $lanjuTr = [];
        $norm = [];
        $kandungan = [];
        $jumlah = [];
        $noreseps = $request->noresep;
        $groupsistembayar = [];
        $jenis_perbekalan = [];

        foreach ($request->kirimResep as $records) {
            $kdobat[] = $records['kodeobat'];
            // $kddepo[] = $records['kodedepo'];
            $jenisresep[] = $records['jenisresep'];
            $jumlah[] = $records['jumlah'];
            $lanjuTr[] = $records['lanjuTr'];
            $norm[] = $records['norm'];
            $kandungan[] = $records['kandungan'];
            $jumlah[] = $records['jumlah'];
            // $noreseps = $records['noresep'];
            $noreg = $records['noreg'];
            $groupsistembayar = $records['groupsistembayar'];
            $jenis_perbekalan[] = $records['jenis_perbekalan'];
        }

        // try {

        DB::connection('farmasi')->beginTransaction();

        $cekjumlahstok = Stokreal::select('stokreal.kdobat as kdobat', DB::raw('sum(jumlah) as jumlahstok'))
            ->whereIn('kdobat', $kdobat)
            ->where('kdruang', $kddepo)
            // ->where('jumlah', '>', 0)
            ->with([
                'transnonracikan' => function ($transnonracikan) use ($kddepo) {
                    $transnonracikan->select(
                        // 'resep_keluar_r.kdobat as kdobat',
                        'resep_permintaan_keluar.kdobat as kdobat',
                        'resep_keluar_h.depo as kdruang',
                        DB::raw('sum(resep_permintaan_keluar.jumlah) as jumlah')
                    )
                        ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_permintaan_keluar.noresep')
                        ->where('resep_keluar_h.depo', $kddepo)
                        ->whereIn('resep_keluar_h.flag', ['', '1', '2'])
                        ->groupBy('resep_permintaan_keluar.kdobat');
                },
                'transracikan' => function ($transracikan) use ($kddepo) {
                    $transracikan->select(
                        // 'resep_keluar_racikan_r.kdobat as kdobat',
                        'resep_permintaan_keluar_racikan.kdobat as kdobat',
                        'resep_keluar_h.depo as kdruang',
                        DB::raw('sum(resep_permintaan_keluar_racikan.jumlah) as jumlah')
                    )
                        ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_permintaan_keluar_racikan.noresep')
                        ->where('resep_keluar_h.depo', $kddepo)
                        ->whereIn('resep_keluar_h.flag', ['', '1', '2'])
                        ->groupBy('resep_permintaan_keluar_racikan.kdobat');
                },
                'permintaanobatrinci' => function ($permintaanobatrinci) use ($kddepo) {
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

                        ->where('permintaan_h.tujuan', $kddepo)
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
            ->groupBy('kdobat')
            ->get();
        // return new JsonResponse([$kddepo, $kdobat]);
        $wew = collect($cekjumlahstok)->map(function ($x, $y) use ($request) {
            $total = $x->jumlahstok ?? 0;
            $jumlahper = $request->kodedepo === 'Gd-04010103' ? $x['persiapanrinci'][0]->jumlah ?? 0 : 0;
            $jumlahtrans = $x['transnonracikan'][0]->jumlah ?? 0;
            $jumlahtransx = $x['transracikan'][0]->jumlah ?? 0;
            $permintaanobatrinci = $x['permintaanobatrinci'][0]->allpermintaan ?? 0; // mutasi antar depo
            $x->alokasi = (float) $total - (float)$jumlahtrans - (float)$jumlahtransx - (float)$permintaanobatrinci - (float)$jumlahper;
            return $x;
        });

        $results = $wew->map(function ($x) {
            return [
                'jumlahstok' => $x->jumlahstok,
                'alokasi' => $x->alokasi,
                'sistembayar' => $x->sistembayar,
                'kodeobat' => $x->kdobat
            ];
        })->all();

        $collection = collect($results);

        $sorted = $collection->sortBy(function ($item) use ($kdobat) {
            return array_search($item['kodeobat'], $kdobat);
        })->values()->toArray();

        $jumlahstok = [];
        $alokasi = [];
        $sistembayar = [];
        $kdobats = [];

        foreach ($sorted as $result) {
            $jumlahstok[] = $result['jumlahstok'];
            $alokasi[] = $result['alokasi'];
            $sistembayar[] = $result['sistembayar'];
            $kdobats[] = $result['kodeobat'];
        }

        $statuses = [];
        $hasil = [];
        if ($kddepo === 'Gd-05010101') {
            $cekpemberian = self::cekpemberianobat($norm, $kdobat, $kandungan);


            // print_r($cekpemberian);

            // foreach ($cekpemberian as $entry) {
            //     if (is_array($entry) && isset($entry['status'])) {
            //         $statuses[] = $entry['status'];
            //     } elseif (is_array($entry) && isset($entry[0]['status'])) {
            //         $statuses[] = $entry[0]['status'];
            //     }

            //     if (is_array($entry) && isset($entry['hasil'])) {
            //         $hasil[] = $entry['hasil'];
            //     } elseif (is_array($entry) && isset($entry[0]['hasil'])) {
            //         $hasil[] = $entry[0]['hasil'];
            //     }
            // }
        } else {
            $cekpemberian = '';
        }

        if ($kddepo === 'Gd-04010102') {
            $procedure = 'resepkeluardeporanap(@nomor)';
            $colom = 'deporanap';
            $lebel = 'D-RI';
        } elseif ($kddepo === 'Gd-04010103') {
            $procedure = 'resepkeluardepook(@nomor)';
            $colom = 'depook';
            $lebel = 'D-KO';
        } elseif ($kddepo === 'Gd-05010101') {
            $procedure = 'resepkeluardeporajal(@nomor)';
            $colom = 'deporajal';
            $lebel = 'D-RJ';
        } else {
            $procedure = 'resepkeluardepoigd(@nomor)';
            $colom = 'depoigd';
            $lebel = 'D-IR';
        }

        $sudahAda = Resepkeluarheder::where('noresep', $noreseps)->first();
        if ($sudahAda) {
            if ($sudahAda->noreg !== $noreg) $noreseps = null;
        }
        if ($noreseps === '' || $noreseps === null) {
            DB::connection('farmasi')->select('call ' . $procedure);
            $x = DB::connection('farmasi')->table('conter')->select($colom)->get();
            $wew = $x[0]->$colom;
            $noresep = FormatingHelper::resep($wew, $lebel);
        } else {
            $noresep = $noreseps;
        }

        return self::simpancopyresep($request, $alokasi, $statuses, $noresep, $cekpemberian, $user, $kdobat, $cekpemberianobat);

        // } catch (\Exception $e) {
        //     DB::connection('farmasi')->rollBack();
        //         return new JsonResponse([
        //             'error' => $e,
        //             'message' => 'rolled back ada kesalahan'
        //         ], 410);
        // }

    }

    public static function simpancopyresep($request, $alokasi, $statuses, $noresep, $cekpemberian, $user, $kdobat, $cekpemberianobat)
    {



        foreach ($request->kirimResep as $key => $record) {
            try {

                // return new JsonResponse($alokasi[$key]);
                if ($record['jenisresep'] === 'nonRacikan') {
                    if ($record['jumlah_diminta'] > $alokasi[$key]) {
                        throw new \Exception('Maaf Stok Alokasi Tidak Mencukupi...!!!');
                    }
                } else {

                    if ($record['jumlah'] > $alokasi[$key]) {
                        throw new \Exception('Maaf Stok Alokasi Tidak Mencukupi...!!!');
                    }
                }

                if ($record['kodedepo'] === 'Gd-05010101') {
                    $tiperesep = $record['tiperesep'] ?? 'normal';
                    $iter_expired = $record['iter_expired'] ?? null;
                    $iter_jml = $record['iter_jml'] ?? null;
                    if ($record['tiperesep'] === 'normal') {
                        $iter_expired =  null;
                        $iter_jml =  null;
                    }

                    $lanjut = $record['lanjuTr'];

                    foreach (json_decode($cekpemberian, true) as $ky => $obatkonsumsi) {

                        if ($obatkonsumsi['kdobat'] === $record['kodeobat']) {
                            if ($obatkonsumsi['status'] === 1 && $lanjut !== '1') {

                                $cekpemberianobat = true;
                                $resp = [
                                    'messageError' => '',
                                    'cek' => $obatkonsumsi['hasil'],
                                    'code' => $record['kodeobat']
                                ];
                                throw new \Exception(json_encode($resp));
                            }
                        }
                    }
                } else {
                    $tiperesep =  'normal';
                    $iter_expired =  null;
                    $iter_jml =  null;
                }
                DB::connection('farmasi')->beginTransaction();
                $simpan = Resepkeluarheder::updateOrCreate(
                    [
                        'noresep' => $noresep,
                        'noreg' => $record['noreg'],
                    ],
                    [
                        'norm' => $record['norm'],
                        'tgl_permintaan' => date('Y-m-d H:i:s'),
                        'tgl' => date('Y-m-d'),
                        'depo' => $record['kodedepo'],
                        'ruangan' => $record['kdruangan'],
                        'dokter' =>  $user['kodesimrs'],
                        'sistembayar' => $record['sistembayar'],
                        'diagnosa' => $record['diagnosa'],
                        'kodeincbg' => $record['kodeincbg'],
                        'uraianinacbg' => $record['uraianinacbg'],
                        'tarifina' => $record['tarifina'],
                        'tiperesep' => $tiperesep,
                        'iter_expired' => $iter_expired,
                        'iter_jml' => $iter_jml,
                        'flag_dari' => '2',
                        // 'iter_expired' => $record['iter_expired ?? '',
                        'tagihanrs' => $record['tagihanrs'] ?? 0,
                    ]
                );

                if (!$simpan) {
                    throw new \Exception('Data Gagal Disimpan...!!!');
                }

                $har = HargaHelper::getHarga($record['kodeobat'], $record['groupsistembayar']);
                $res = $har['res'];
                if ($res) {
                    throw new \Exception('Obat ini tidak mempunyai harga');
                }
                $hargajualx = $har['hargaJual'];
                $harga = $har['harga'];

                if ($record['jenisresep'] == 'Racikan') {
                    if ($record['tiperacikan'] == 'DTD') {
                        $simpandtd = Permintaanresepracikan::updateOrCreate(
                            [
                                'noreg' => $record['noreg'],
                                'noresep' => $noresep,
                                'namaracikan' => $record['namaracikan'],
                                'kdobat' => $record['kodeobat'],
                            ],
                            [
                                'tiperacikan' => $record['tiperacikan'],
                                'jumlahdibutuhkan' => $record['jumlahdibutuhkan'], // jumlah racikan
                                'aturan' => $record['aturan'],
                                'konsumsi' => $record['konsumsi'],
                                'keterangan' => $record['keterangan'],
                                'kandungan' => $record['kandungan'] ?? '',
                                'fornas' => $record['fornas'] ?? '',
                                'forkit' => $record['forkit'] ?? '',
                                'generik' => $record['generik'] ?? '',
                                'r' => $request->groupsistembayar === '1' || $request->groupsistembayar === 1 ? 500 : 0,
                                'hpp' => $harga,
                                'harga_jual' => $hargajualx,
                                'kode108' => $record['kode108'],
                                'uraian108' => $record['uraian108'],
                                'kode50' => $record['kode50'],
                                'uraian50' => $record['uraian50'],
                                'stokalokasi' => $alokasi[$key],
                                'dosisobat' => $record['dosisobat'] ?? 0,
                                'dosismaksimum' => $record['dosismaksimum'] ?? 0, // dosis resep
                                'jumlah' => $record['jumlah'], // jumlah obat
                                'satuan_racik' => $record['satuan_racik'], // jumlah obat
                                'keteranganx' => $record['keteranganx'], // keterangan obat
                                'user' => $user['kodesimrs']
                            ]
                        );
                        // if ($simpandtd) {
                        //     $simpandtd->load('mobat:kd_obat,nama_obat');
                        // }
                    } else {
                        $simpannondtd = Permintaanresepracikan::updateOrCreate(
                            [
                                'noreg' => $record['noreg'],
                                'noresep' => $noresep,
                                'namaracikan' => $record['namaracikan'],
                                'kdobat' => $record['kodeobat'],
                            ],
                            [
                                'tiperacikan' => $record['tiperacikan'],
                                'jumlahdibutuhkan' => $record['jumlahdibutuhkan'],
                                'aturan' => $record['aturan'],
                                'konsumsi' => $record['konsumsi'],
                                'keterangan' => $record['keterangan'],
                                'kandungan' => $record['kandungan'] ?? '',
                                'fornas' => $record['fornas'] ?? '',
                                'forkit' => $record['forkit'] ?? '',
                                'generik' => $record['generik'] ?? '',
                                'r' => $request->groupsistembayar === '1' || $request->groupsistembayar === 1 ? 500 : 0,
                                'hpp' => $harga,
                                'harga_jual' => $hargajualx,
                                'kode108' => $record['kode108'],
                                'uraian108' => $record['uraian108'],
                                'kode50' => $record['kode50'],
                                'uraian50' => $record['uraian50'],
                                'stokalokasi' => $alokasi[$key],
                                // 'dosisobat' => $record['dosisobat,
                                // 'dosismaksimum' => $request->dosismaksimum,
                                'jumlah' => $record['jumlah'],
                                'satuan_racik' => $record['satuan_racik'],
                                'keteranganx' => $record['keteranganx'],
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
                            'noreg' => $record['noreg'],
                            'noresep' => $noresep,
                            'kdobat' => $record['kodeobat'],
                        ],
                        [
                            'kandungan' => $record['kandungan'] ?? '',
                            'fornas' => $record['fornas'] ?? '',
                            'forkit' => $record['forkit'] ?? '',
                            'generik' => $record['generik'] ?? '',
                            'kode108' => $record['kode108'],
                            'uraian108' => $record['uraian108'],
                            'kode50' => $record['kode50'],
                            'uraian50' => $record['uraian50'],
                            'stokalokasi' => $alokasi[$key],
                            'r' => $request->groupsistembayar === '1' || $request->groupsistembayar === 1 ? 300 : 0,
                            'jumlah' => $record['jumlah_diminta'],
                            'hpp' => $harga,
                            'hargajual' => $hargajualx,
                            'aturan' => $record['aturan'],
                            'konsumsi' => $record['konsumsi'],
                            'keterangan' => $record['keterangan'] ?? '',
                            'user' => $user['kodesimrs']
                        ]
                    );
                    // if ($simpanrinci) {
                    //     $simpanrinci->load('mobat:kd_obat,nama_obat');
                    // }
                }

                $endas = Resepkeluarheder::where('noreg', $record['noreg'])->with(
                    'permintaanresep.mobat:kd_obat,nama_obat',
                    'permintaanracikan.mobat:kd_obat,nama_obat'
                )->get();
                DB::connection('farmasi')->commit();
                $response[] = [
                    'newapotekrajal' => $endas,
                    'heder' => $simpan,
                    'rinci' => $simpanrinci ?? 0,
                    'rincidtd' => $simpandtd ?? 0,
                    'rincinondtd' => $simpannondtd ?? 0,
                    'noresep_asal' => $request->noresep_asal,
                    'nota' => $noresep,
                    'kdobat' => $kdobat[$key],
                    'message' => 'Data Berhasil Disimpan...!!!'
                ];
            } catch (\Exception $e) {

                if ($cekpemberianobat) {
                    $cekpemberianobat =  false;
                    $response[] = [
                        'newapotekrajal' => $endas ?? [],
                        'noresep_asal' => $request->noresep_asal,
                        'nota' => $noresep,
                        'kdobat' => $kdobat[$key],
                        'messageError' => json_decode($e->getMessage(), true)
                    ];
                    continue;
                } else {
                    $response[] = [
                        'newapotekrajal' => $endas ?? [],
                        'noresep_asal' => $request->noresep_asal,
                        'nota' => $noresep,
                        'kdobat' => $kdobat[$key],
                        'messageError' => $e->getMessage(),
                    ];
                    continue;
                }
            }
        }
        DB::connection('farmasi')->commit();
        return new JsonResponse($response, 200);
    }

    public static function cekpemberianobat($norm, $kdobat, $kandungan)
    {
        // ini tujuannya mencari sisa obat pasien dengan dihitung jumlah konsumsi obat per hari bersasarkan signa
        // harus ada data jumlah hari (obat dikonsumsi dalam ... hari) di tabel

        $hasil = [];
        $cekmaster = Mobatnew::select('kandungan')->whereIn('kd_obat', $kdobat)->first();

        if ($cekmaster->kandungan === '') {
            $hasil = Resepkeluarheder::select(
                'resep_keluar_r.kdobat as kdobat',
                'resep_keluar_h.noresep as noresep',
                'resep_keluar_h.tgl as tgl',
                'resep_keluar_r.konsumsi',
                DB::raw('(DATEDIFF(CURRENT_DATE(), resep_keluar_h.tgl)+1) as selisih')
            )
                ->leftjoin('resep_keluar_r', 'resep_keluar_h.noresep', 'resep_keluar_r.noresep')
                ->whereIn('resep_keluar_h.norm', $norm)
                ->whereIn('resep_keluar_r.kdobat', $kdobat)
                ->get();
        } else {
            $hasil = Resepkeluarheder::select(
                'resep_keluar_r.kdobat as kdobat',
                'resep_keluar_h.noresep as noresep',
                'resep_keluar_h.tgl as tgl',
                'resep_keluar_r.konsumsi',
                DB::raw('(DATEDIFF(CURRENT_DATE(), resep_keluar_h.tgl)+1) as selisih')
            )
                ->leftjoin('resep_keluar_r', 'resep_keluar_h.noresep', 'resep_keluar_r.noresep')
                ->leftjoin('new_masterobat', 'new_masterobat.kd_obat', 'resep_keluar_r.kdobat')
                ->whereIn('resep_keluar_h.norm', $norm)
                ->whereIn('resep_keluar_r.kdobat', $kdobat)
                ->whereIn('new_masterobat.kandungan', $kandungan)
                ->get();
        }

        // $total = 0;
        // $selisih = 0;

        $response = [];
        //     if (count($hasil)) {
        //         foreach ($hasil as $item) {
        //             $selisih = $item->selisih;
        //             $total = (float) $item->konsumsi;
        //             $response[] = [
        //                 'status' => ($selisih <= $total) ? 1 : 2,
        //                 'kdobat' => $item->kdobat,
        //                 'hasil' => [
        //                     [
        //                         'noresep' => $item->noresep,
        //                         'tgl' => $item->tgl,
        //                         'total' => $total,
        //                         'selisih' => $selisih,
        //                     ]
        //                 ],
        //                 'selisih' => $selisih,
        //                 'total' => $total,
        //             ];
        //         }
        //         if (count($hasil) !== count($kdobat)) {
        //             $response[] = [
        //                 'status' => 2,
        //                 'kdobat' => null,
        //                 'hasil' => [],
        //                 'selisih' => null,
        //                 'total' => null,
        //             ];
        //         }
        //     } else {
        //         foreach ($kdobat as $item) {
        //             $response[] = [
        //                 'status' => 2,
        //                 'kdobat' => $item,
        //                 'hasil' => [],
        //                 'selisih' => null,
        //                 'total' => null,
        //             ];
        //         }
        //     }
        // // }
        $response = [];
        $selisih = 0;
        $total = 0;
        foreach ($hasil as $item) {
            if (count($hasil)) {
                $selisih = $item->selisih;
                $total = $item->konsumsi;
                if ($selisih <= $total) {
                    $response[] = [
                        'kdobat' => $item->kdobat,
                        'status' => 1,
                        'hasil' => $item,
                        'selisih' => $selisih,
                        'total' => $total,
                    ];
                } else {
                    $response[] = [
                        'kdobat' => $item->kdobat,
                        'status' => 2,
                        'hasil' => $item,
                        'selisih' => $selisih,
                        'total' => $total,
                    ];
                    // return 2;
                }
            }
            $response[] = [
                'kdobat' => $item->kdobat,
                'status' => 2,
                'hasil' => $item,
                'selisih' => $selisih,
                'total' => $total,
            ];
        }


        $collection = collect($response);

        $sorted = $collection->sortBy(function ($item) use ($kdobat) {
            return array_search($item['kdobat'], $kdobat);
        })->values()->toArray();

        // print_r($sorted);

        return (json_encode($sorted));
    }

    public function lihatstokobateresepBydokter()
    {
        // return request()->groups;
        // $req = new Request();
        // $req->request->add([
        //     'groups' => '1',
        //     'kdruang' => 'Gd-05010101',
        //     'q'=>'para',
        //     'tiperesep' => 'normal'
        // ]);
        // penccarian termasuk tiperesep
        $groupsistembayar = request()->groups;
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
                            // 'resep_permintaan_keluar.kdobat as kdobat',
                            // 'resep_keluar_h.depo as kdruang',
                            DB::raw('sum(resep_permintaan_keluar.jumlah) as jumlah')
                        )
                            ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_permintaan_keluar.noresep')
                            ->where('resep_keluar_h.depo', request()->kdruang)
                            ->whereIn('resep_keluar_h.flag', ['', '1', '2'])
                            ->groupBy('resep_permintaan_keluar.kdobat');
                    },
                    'transracikan' => function ($transracikan) {
                        $transracikan->select(
                            // 'resep_keluar_racikan_r.kdobat as kdobat',
                            // 'resep_permintaan_keluar_racikan.kdobat as kdobat',
                            // 'resep_keluar_h.depo as kdruang',
                            DB::raw('sum(resep_permintaan_keluar_racikan.jumlah) as jumlah')
                        )
                            ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_permintaan_keluar_racikan.noresep')
                            ->where('resep_keluar_h.depo', request()->kdruang)
                            ->whereIn('resep_keluar_h.flag', ['', '1', '2'])
                            ->groupBy('resep_permintaan_keluar_racikan.kdobat');
                    },
                    'permintaanobatrinci' => function ($permintaanobatrinci) {
                        $permintaanobatrinci->select(
                            // 'permintaan_r.no_permintaan',
                            // 'permintaan_r.kdobat',
                            DB::raw('sum(permintaan_r.jumlah_minta) as allpermintaan')
                        )
                            ->leftJoin('permintaan_h', 'permintaan_h.no_permintaan', '=', 'permintaan_r.no_permintaan')
                            // biar yang ada di tabel mutasi ga ke hitung
                            ->leftJoin('mutasi_gudangdepo', function ($anu) {
                                $anu->on('permintaan_r.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                                    ->on('permintaan_r.kdobat', '=', 'mutasi_gudangdepo.kd_obat');
                            })
                            ->whereNull('mutasi_gudangdepo.kd_obat')

                            ->where('permintaan_h.tujuan', request()->kdruang)
                            ->whereIn('permintaan_h.flag', ['', '1', '2'])
                            ->groupBy('permintaan_r.kdobat');
                    },
                    'persiapanrinci' => function ($res) {
                        $res->select(
                            // 'persiapan_operasi_rincis.kd_obat',

                            DB::raw('sum(persiapan_operasi_rincis.jumlah_minta) as jumlah'),
                        )
                            ->leftJoin('persiapan_operasis', 'persiapan_operasis.nopermintaan', '=', 'persiapan_operasi_rincis.nopermintaan')
                            ->whereIn('persiapan_operasis.flag', ['', '1'])
                            ->groupBy('persiapan_operasi_rincis.kd_obat');
                    },
                ]
            )
            ->leftjoin('new_masterobat', 'new_masterobat.kd_obat', '=', 'stokreal.kdobat')
            ->where('stokreal.kdruang', request()->kdruang)
            // ->where('stokreal.jumlah', '>', 0)
            ->whereIn('new_masterobat.sistembayar', $sistembayar)
            ->where('new_masterobat.status_konsinyasi', '')
            ->when(request()->tiperesep === 'prb', function ($q) {
                $q->where('new_masterobat.status_prb', '!=', '');
            })
            ->when(request()->tiperesep === 'iter', function ($q) {
                $q->where('new_masterobat.status_kronis', '!=', '');
            })
            ->whereIn('stokreal.kdobat', request()->q)
            ->groupBy('stokreal.kdobat')
            ->get();
        $wew = collect($cariobat)->map(function ($x, $y) {
            $total = $x->total ?? 0;
            $jumlahper = request()->kdruang === 'Gd-04010103' ? $x['persiapanrinci'][0]->jumlah ?? 0 : 0;
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
}
