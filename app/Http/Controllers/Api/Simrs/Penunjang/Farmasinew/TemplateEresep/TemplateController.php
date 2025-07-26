<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\TemplateEresep;

use App\Events\NotifMessageEvent;
use App\Helpers\FormatingHelper;
use App\Http\Controllers\Api\Simrs\Antrian\AntrianController;
use App\Http\Controllers\Api\Simrs\Pendaftaran\Rajal\BridantrianbpjsController;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Depo\EresepController;
use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Pendaftaran\Rajalumum\Bpjsrespontime;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaanresep;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaanresepracikan;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use App\Models\Simrs\Penunjang\Farmasinew\Harga\DaftarHarga;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use App\Models\Simrs\Penunjang\Farmasinew\Retur\Returpenjualan_r;
use App\Models\Simrs\Penunjang\Farmasinew\Stokreal;
use App\Models\Simrs\Penunjang\Farmasinew\Template\Templateresep;
use App\Models\Simrs\Penunjang\Farmasinew\Template\TemplateResepRacikan;
use App\Models\Simrs\Penunjang\Farmasinew\Template\TemplateResepRinci;
use App\Models\Simrs\Rajal\KunjunganPoli;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Stmt\TryCatch;

class TemplateController extends Controller
{
    public function cariobat()
    {
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
            )
            ->where(function ($query) {
                $query->where('new_masterobat.nama_obat', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('new_masterobat.kandungan', 'LIKE', '%' . request('q') . '%');
            })->limit(30)->get();
        return new JsonResponse(
            ['dataobat' => $listobat]
        );
    }

    public function simpantemplate(Request $request)
    {
        $user = auth()->user()->pegawai_id;
        $request->request->add(['pegawai_id' => $user]);

        $cek = Templateresep::where('pegawai_id', $user)->where('nama', $request->nama)->where('kodedepo', $request->kodedepo)->first();
        if ($cek) {
            // return new JsonResponse(['message' => 'Maaf ... Template sudah ada, ganti nama'], 406);
            return self::updatetemplate($request, $cek);
        }

        // iF save not update
        return self::store($request);
    }

    public static function updatetemplate($request, $template)
    {
        try {
            //code...
            DB::beginTransaction();
            TemplateResepRinci::where('template_id', $template->id)->delete();

            $rinci = $request->items;
            foreach ($rinci as $key => $value) {
                $simpanRincian = [
                    'kodeobat'  => $value['kodeobat'],
                    'namaobat'  => $value['namaobat'],
                    'forkit'  => $value['forkit'],
                    'fornas'  => $value['fornas'],
                    'generik'  => $value['generik'],
                    'kandungan'  => $value['kandungan'],
                    'kekuatandosis'  => $value['kekuatandosis'],
                    'keterangan'  => $value['keterangan'],
                    'kode50'  => $value['kode50'],
                    'kode108'  => $value['kode108'],
                    'konsumsi'  => $value['konsumsi'],
                    'racikan'  => $value['racikan'] === true ? 1 : 0,
                    'satuan_kcl'  => $value['satuan_kcl'],
                    'signa'  => $value['signa'],
                    'jumlah_diminta'  => $value['jumlah_diminta'],
                    'tiperacikan'  => $value['tiperacikan'],
                    'tiperesep'  => $value['tiperesep'],
                    'template_id'  => $template->id
                ];

                $rincian = TemplateResepRinci::create($simpanRincian);
                if ($value['racikan'] === true && $value['kodeobat'] === $rincian->kodeobat) {
                    // hapus dulu rincian racikan
                    TemplateResepRacikan::where('obat_id', $rincian->id)->delete();
                    foreach ($value['rincian'] as $k => $val) {
                        $racikan = [
                            'obat_id'  => $rincian->id,
                            'kodeobat'  => $val['kodeobat'],
                            'namaobat'  => $val['namaobat'],
                            'forkit'  => $val['forkit'],
                            'fornas'  => $val['fornas'],
                            'generik'  => $val['generik'],
                            // 'kandungan'  => $val['kandungan'],
                            'kekuatandosis'  => $val['kekuatandosis'],
                            'keterangan'  => $val['keterangan'],
                            'kode50'  => $val['kode50'],
                            'kode108'  => $val['kode108'],
                            // 'konsumsi'  => $val['konsumsi'],
                            'satuan_kcl'  => $val['satuan_kcl'],
                            // 'signa'  => $val['signa'],
                            'jumlah_diminta'  => $val['jumlah_diminta'],
                            'dosis'  => $val['dosis'],
                            // 'tiperacikan'  => $val['tiperacikan'],
                            // 'tiperesep'  => $val['tiperesep'],
                        ];

                        TemplateResepRacikan::create($racikan);
                    }
                }
            };
            DB::commit();
            return new JsonResponse($template->load(['rincian.rincian']), 200);
        } catch (\Throwable $th) {
            DB::rollback();
            return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!', 'result' => $th], 500);
        }
    }

    public static function store($request)
    {
        try {
            DB::beginTransaction();
            $saved = Templateresep::create($request->all());

            $rinci = $request->items;
            foreach ($rinci as $key => $value) {
                $simpanRincian = [
                    'kodeobat'  => $value['kodeobat'],
                    'namaobat'  => $value['namaobat'],
                    'forkit'  => $value['forkit'],
                    'fornas'  => $value['fornas'],
                    'generik'  => $value['generik'],
                    'kandungan'  => $value['kandungan'],
                    'kekuatandosis'  => $value['kekuatandosis'],
                    'keterangan'  => $value['keterangan'],
                    'kode50'  => $value['kode50'],
                    'kode108'  => $value['kode108'],
                    'konsumsi'  => $value['konsumsi'],
                    'racikan'  => $value['racikan'] === true ? 1 : 0,
                    'satuan_kcl'  => $value['satuan_kcl'],
                    'signa'  => $value['signa'],
                    'jumlah_diminta'  => $value['jumlah_diminta'],
                    'tiperacikan'  => $value['tiperacikan'],
                    'tiperesep'  => $value['tiperesep'],
                    'template_id'  => $saved->id
                ];

                $rincian = TemplateResepRinci::create($simpanRincian);
                if ($value['racikan'] === true && $value['kodeobat'] === $rincian->kodeobat) {
                    foreach ($value['rincian'] as $k => $val) {
                        $racikan = [
                            'obat_id'  => $rincian->id,
                            'kodeobat'  => $val['kodeobat'],
                            'namaobat'  => $val['namaobat'],
                            'forkit'  => $val['forkit'],
                            'fornas'  => $val['fornas'],
                            'generik'  => $val['generik'],
                            // 'kandungan'  => $val['kandungan'],
                            'kekuatandosis'  => $val['kekuatandosis'],
                            'keterangan'  => $val['keterangan'],
                            'kode50'  => $val['kode50'],
                            'kode108'  => $val['kode108'],
                            // 'konsumsi'  => $val['konsumsi'],
                            'satuan_kcl'  => $val['satuan_kcl'],
                            // 'signa'  => $val['signa'],
                            'jumlah_diminta'  => $val['jumlah_diminta'],
                            'dosis'  => $val['dosis'],
                            // 'tiperacikan'  => $val['tiperacikan'],
                            // 'tiperesep'  => $val['tiperesep'],
                        ];

                        TemplateResepRacikan::create($racikan);
                    }
                }
            };
            DB::commit();
            return new JsonResponse($saved->load(['rincian.rincian']), 200);
        } catch (\Throwable $th) {
            DB::rollback();
            return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!', 'result' => $th], 500);
        }
    }

    public function gettemplate()
    {
        $data = Templateresep::where('pegawai_id', auth()->user()->pegawai_id)
            ->where('kodedepo', request('kodedepo'))
            ->when(request('q'), function ($q) {
                $q->where('nama', 'like', '%' . request('q') . '%');
            })
            ->with([
                // 'rincian.rincian'
                'rincian' => function ($ri) {
                    $ri->select(
                        'template_resep_rinci.*',
                        'new_masterobat.jenis_perbekalan'
                    )
                        ->leftJoin('new_masterobat', 'new_masterobat.kd_obat', '=', 'template_resep_rinci.kodeobat')
                        ->with('rincian');
                }
            ])
            ->limit(20)
            ->get();

        return new JsonResponse($data, 200);
    }

    public function destroy(Request $request)
    {
        $id = $request->id;
        $data = Templateresep::find($id);
        $del = $data->delete();
        if (!$del) {
            return new JsonResponse(['message' => 'Error on Delete'], 500);
        }
        return new JsonResponse(['message' => 'Data berhasil dihapus'], 200);
    }


    public function order(Request $request)
    {

        /**
         * cek pembatasan obat start
         */
        $depoLimit = ['Gd-04010102', 'Gd-05010101'];
        $gr = $request->groupsistembayarlain ? (int)$request->groupsistembayarlain === 1 : true;
        if (in_array($request->kodedepo, $depoLimit) && $gr) {
            // batasan obat yang sama
            $sekarang = date('Y-m-d');
            // normal, tidak ada retur
            $normalHead = Resepkeluarheder::where('noreg', $request->noreg)
                ->where('tgl_kirim', 'LIKE', '%' . $sekarang . '%')
                ->whereIn('flag', ['1', '2', '3'])
                ->pluck('noresep');
            $returHead = Resepkeluarheder::where('noreg', $request->noreg)
                ->where('tgl_kirim', 'LIKE', '%' . $sekarang . '%')
                ->where('flag', '4')
                ->pluck('noresep');
            // ambil detail obat yang akan dikirim
            $kode = array_column($request->merged, 'kodeobat');
            $obatnya = Mobatnew::select('kd_obat as kdobat', 'nama_obat')->whereIn('kd_obat', $kode)->get();
            // ambil obat untuk pasien kunjungan sekarang
            $obatNormal = Permintaanresep::whereIn('noresep', $normalHead)->get();
            $obatNormalRacikan = Permintaanresepracikan::whereIn('noresep', $normalHead)->orWhereIn('noresep', $returHead)->get();
            // ambil retur obat (kalau ada)
            $obatAdaRetur = Permintaanresep::whereIn('noresep', $returHead)->get();
            // ambil obat yang diretur
            $obatRetur = Returpenjualan_r::whereIn('noresep', $returHead)->get();
            // cek retur, berapa jumlah nya, jika semua maka dianggap tidak diberikan
            $arrayAda = $obatAdaRetur->toArray();
            $keys = array_column($arrayAda, 'kdobat');
            foreach ($obatRetur as $ret) {
                $index = array_search($ret['kdobat'], $keys);
                if (!($index !== false)) {
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
            $arrayNormal = $obatNormal->toArray();
            $arrayNormalRacikan = $obatNormalRacikan->toArray();
            $ret = array_column($arrayAda, 'kdobat');
            $nor = array_column($arrayNormal, 'kdobat');
            $norR = array_column($arrayNormalRacikan, 'kdobat');
            // bandingkan dengan obat yang akan dikirim
            if (count($obatnya) > 0) {
                foreach ($obatnya as $obt) {

                    $indR = array_search($obt['kdobat'], $ret);
                    $indN = array_search($obt['kdobat'], $nor);
                    $indNRa = array_search($obt['kdobat'], $norR);

                    $cN[] = [$indN, $obt['kdobat']];
                    $cR[] = [$indR, $obt['kdobat']];
                    $cRA[] = [$indNRa, $obt['kdobat']];
                    $fIndR = $indR !== false; // kalo ga ketemu itu false, kelo ketemu itu number, kalo ketemu 0 itu juga dianggap false
                    $fIndN = $indN !== false;
                    $findNRa = $indNRa !== false;

                    if ($fIndR && EresepController::pushToArray($fIndR, $sudahAda, 'kdobat', $obt['kdobat'])) {
                        $obt['ada'] = $obatAdaRetur[$indR];
                        $sudahAda[] = $obt;
                        if (sizeof($sudahAda) == 1) $msg = $msg . $obt['nama_obat'] . ' sudah diresepkan sebanyak ' . $obt['ada']['jumlah'];
                        if (sizeof($sudahAda) > 1) $msg = $msg . ', ' . $obt['nama_obat'] . ' sudah diresepkan sebanyak ' . $obt['ada']['jumlah'];
                    } else if ($fIndN && EresepController::pushToArray($fIndN, $sudahAda, 'kdobat', $obt['kdobat'])) {
                        $obt['ada'] = $obatNormal[$indN];
                        $sudahAda[] = $obt;
                        if (sizeof($sudahAda) == 1) $msg = $msg . $obt['nama_obat'] . ' sudah diresepkan sebanyak ' . $obt['ada']['jumlah'];
                        if (sizeof($sudahAda) > 1) $msg = $msg . ', ' . $obt['nama_obat'] . ' sudah diresepkan sebanyak ' . $obt['ada']['jumlah'];
                    } else if ($findNRa && EresepController::pushToArray($findNRa, $sudahAda, 'kdobat', $obt['kdobat'])) {
                        $obt['ada'] = $obatNormalRacikan[$indNRa];
                        $sudahAda[] = $obt;
                        if (sizeof($sudahAda) == 1) $msg = $msg . $obt['nama_obat'] . ' sudah diresepkan sebanyak ' . $obt['ada']['jumlah'];
                        if (sizeof($sudahAda) > 1) $msg = $msg . ', ' . $obt['nama_obat'] . ' sudah diresepkan sebanyak ' . $obt['ada']['jumlah'];
                    }
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
                    'kode' => $kode,
                    'normalHead' => $normalHead,
                    'count' => sizeof($sudahAda),

                ], 410);
            }
            // return new JsonResponse([
            //   'req'=>$request->all()
            // ],410);
        }
        /**
         * cek pembatasan obat end
         */
        $user = auth()->user()->pegawai_id;
        $request->request->add(['pegawai_id' => $user]);

        $items = collect($request->merged);

        // jadikan satu obat nya dan jumlahkan jumlah_diminta untuk mencari alokasi stok
        $cekObat = self::cekStokNonRacikan($items, $request->kodedepo, $request->groupsistembayar);
        // return $cekObat;
        $obatDiminta = [];
        // return $cekNonRacikan;
        // $cekRacikan = self::cekStokRacikan($racikan, $request->kodedepo, $request->groupsistembayar);
        foreach ($request->items as $key) {
            $data = collect($cekObat)->firstWhere('kdobat', $key['kodeobat']);
            $key['isError'] = $data['isError'];
            $key['errors'] = $data['errors'];
            $key['harga'] = $data['harga'];
            $key['hargajual'] = $data['hargajual'];
            $key['jumlah_all_diminta'] = $data['jumlah_diminta'];
            $key['item'] = $data['data'];
            $key['sistembayar'] = $request->groupsistembayar;
            // $key['signa'] = $key['signa'];
            // $key['keterangan'] = $key['keterangan'];
            $obatDiminta[] = $key;
        }
        // return $request;


        $a = count($obatDiminta) > 0 ? collect($obatDiminta)->pluck('isError')->toArray() : [];
        $msg = 'ok';
        $isError = false;
        if (in_array(true, $a)) {
            $isError = true;
            $msg = 'Gagal Alokasi Kurang';
        } else {
            $isError = false;
        }

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

            return self::sendOrder($request, $obatDiminta);
        }
    }

    public static function cekStokNonRacikan($nonRacikan, $kodedepo, $sistembayar)
    {
        $kodeobat = $nonRacikan->pluck('kodeobat');
        $adaRaw = self::cekJumlahStok($kodeobat, $kodedepo, $sistembayar, false);
        return self::outputRaw($adaRaw, $nonRacikan, $sistembayar);
        // return $adaRaw;
    }

    public static function cekStokRacikan($racikan, $kodedepo, $sistembayar)
    {
        $racik = $racikan->map(function ($x) use ($kodedepo, $sistembayar) {
            $obat['koderacikan'] = $x['kodeobat'];
            $obat['sistembayar'] = $sistembayar;
            $rincian = collect($x['rincian']);
            $obat['rincian'] = count($rincian) > 0 ? $rincian->implode('kodeobat', ',') : null;
            $obat['kodedepo'] = $kodedepo;
            return $obat;
        });

        $kode = $racik->pluck('rincian')
            ->map(function (string $kd) {
                return explode(',', $kd);
            })
            ->flatten();

        $adaRaw = self::cekJumlahStok($kode, $kodedepo, $sistembayar, true);
        return self::outputRaw($adaRaw, $racikan, $sistembayar);
    }

    public static function cekJumlahStok($obat, $kodedepo, $sistembayar, $racikan)
    {
        $uniqueObat = $obat;
        $limitHargaTertinggi = 5;
        $cekjumlahstok = Stokreal::query()
            ->select(
                'stokreal.kdobat as kdobat',
                DB::raw('sum(stokreal.jumlah) as jumlahstok'),
                'new_masterobat.nama_obat as nama_obat',
                'new_masterobat.kandungan as kandungan',
                'new_masterobat.status_fornas as fornas',
                'new_masterobat.status_forkid as forkit',
                'new_masterobat.status_generik as generik',
                'new_masterobat.kode108 as kode108',
                'new_masterobat.uraian108 as uraian108',
                'new_masterobat.kode50 as kode50',
                'new_masterobat.uraian50 as uraian50',
                'new_masterobat.obat_program as obat_program',
            )
            ->leftJoin('new_masterobat', 'new_masterobat.kd_obat', 'stokreal.kdobat')
            ->whereIn('kdobat', $uniqueObat)
            ->where('kdruang', $kodedepo)
            ->where('jumlah', '>', 0)
            ->with([
                'transnonracikan' => function ($transnonracikan) use ($kodedepo) {
                    $transnonracikan->select(
                        // 'resep_keluar_r.kdobat as kdobat',
                        'resep_permintaan_keluar.kdobat as kdobat',
                        'resep_keluar_h.depo as kdruang',
                        DB::raw('sum(resep_permintaan_keluar.jumlah) as jumlah')
                    )
                        ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_permintaan_keluar.noresep')
                        ->where('resep_keluar_h.depo', $kodedepo)
                        ->whereIn('resep_keluar_h.flag', ['', '1', '2'])
                        ->groupBy('resep_permintaan_keluar.kdobat');
                },
                'transracikan' => function ($transracikan) use ($kodedepo) {
                    $transracikan->select(
                        // 'resep_keluar_racikan_r.kdobat as kdobat',
                        'resep_permintaan_keluar_racikan.kdobat as kdobat',
                        'resep_keluar_h.depo as kdruang',
                        DB::raw('sum(resep_permintaan_keluar_racikan.jumlah) as jumlah')
                    )
                        ->leftjoin('resep_keluar_h', 'resep_keluar_h.noresep', 'resep_permintaan_keluar_racikan.noresep')
                        ->where('resep_keluar_h.depo', $kodedepo)
                        ->whereIn('resep_keluar_h.flag', ['', '1', '2'])
                        ->groupBy('resep_permintaan_keluar_racikan.kdobat');
                },
                'permintaanobatrinci' => function ($permintaanobatrinci) use ($kodedepo) {
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

                        ->where('permintaan_h.tujuan', $kodedepo)
                        ->whereIn('permintaan_h.flag', ['', '1', '2'])
                        ->groupBy('permintaan_r.kdobat');
                },
                'persiapanrinci' => function ($res) use ($kodedepo) {
                    $res->select(
                        'persiapan_operasi_rincis.kd_obat',

                        DB::raw('sum(persiapan_operasi_rincis.jumlah_minta) as jumlah'),
                    )
                        ->leftJoin('persiapan_operasis', 'persiapan_operasis.nopermintaan', '=', 'persiapan_operasi_rincis.nopermintaan')
                        ->whereIn('persiapan_operasis.flag', ['', '1'])
                        ->groupBy('persiapan_operasi_rincis.kd_obat');
                },
                // 'daftarharga'=>function($q){
                //   $q->select(
                //     DB::raw('MAX(harga) as harga'),
                //     'tgl_mulai_berlaku',
                //     'kd_obat'
                //     )
                //   ->orderBy('tgl_mulai_berlaku','desc')
                //   ->take(5);
                // }
            ])
            // ->withCount(['daftarharga' => function ($q){
            //       $q->select(
            //         DB::raw('MAX(harga) as harga'),
            //         'tgl_mulai_berlaku',
            //         'kd_obat'
            //         )
            //       ->orderBy('tgl_mulai_berlaku','desc')
            //       ->limit(5);
            // }])
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
            ->orderBy('tglexp')
            ->groupBy('kdobat')
            ->get();

        // $ht = $cekjumlahstok->value('harga_tertinggi');
        // $hrg = self::penentuanHarga($ht, $sistembayar,$cekjumlahstok->pluck('obat_program'));


        $hrgTertinggiIds = $cekjumlahstok->pluck('harga_tertinggi_ids')
            ->map(function (string $daftarHargaKodes) {
                return explode(',', $daftarHargaKodes);
            })
            ->flatten();

        $hargaTertinggi = DaftarHarga::select('id', 'kd_obat', 'tgl_mulai_berlaku', 'harga')
            ->whereIn('id', $hrgTertinggiIds)
            ->orderBy('tgl_mulai_berlaku', 'desc')
            ->get();

        // $ht = collect($hargaTertinggi);

        foreach ($cekjumlahstok as $stok) {
            // menjadikan array dari string $stok->harga_tertinggi_ids
            $ids = explode(',', $stok->harga_tertinggi_ids);

            $stokHargaTertinggi = $hargaTertinggi
                ->whereIn('id', $ids)
                ->sortBy(fn(DaftarHarga $daftarHarga) => array_flip($ids)[$daftarHarga->id])
                ->values();

            // masukkan ke object harga_teringgi_kodes
            $stok->setRelation('harga_tertinggi_ids', $stokHargaTertinggi)->toArray();
        }



        $alokasiNharga = collect($cekjumlahstok)->map(function ($x, $y) use ($kodedepo) {
            $total = $x->jumlahstok ?? 0;
            $jumlahper = $kodedepo === 'Gd-04010103' ? $x['persiapanrinci'][0]->jumlah ?? 0 : 0;
            $jumlahtrans = $x['transnonracikan'][0]->jumlah ?? 0;
            $jumlahtransx = $x['transracikan'][0]->jumlah ?? 0;
            $permintaanobatrinci = $x['permintaanobatrinci'][0]->allpermintaan ?? 0; // mutasi antar depo
            $x->alokasi = (float) $total - (float)$jumlahtrans - (float)$jumlahtransx - (float)$permintaanobatrinci - (float)$jumlahper;
            // $ids = explode(',', $x->harga_tertinggi_ids);
            // $x->setRelation('harga_tertinggi_ids', $ht->whereIn('id', $ids)
            //   ->sortByDesc('tgl_mulai_berlaku')->values());// = $ht->whereIn('id', $x['ids_harga'])->values();?
            // $x->harga = $ht->whereIn('id', $ids)->max('harga') ?? 0;
            // $x->harga = $hrg;
            return $x;
        });

        return $alokasiNharga->toArray();
    }

    public static function outputRaw($adaraw, $requestnya, $sistembayar)
    {
        // return $requestnya;
        $obatYgDiminta = [];
        foreach ($requestnya as $key) {
            // mapping racikan =============================================================================
            // if ($key['racikan'] === true ) {
            //   $obat['kdobat'] = $key['kodeobat'];
            //   $obat['jumlah_diminta'] = $key['jumlah_diminta'];
            //   $obat['sistembayar'] = $sistembayar;
            //   $rincian = $key['rincian'];
            //   $rinci = [];
            //   foreach ($rincian as $sub) {
            //     $data = collect($adaraw)->firstWhere('kdobat', $key['kodeobat']);
            //     $rinci[] = self::mappingObat($data, $sub, $sistembayar);
            //   }
            //   $obat['rincian'] = $rinci;
            //   $ceks = collect($rinci)->pluck('isError');
            //   $valueToCheck = true;
            //   $obat['isError'] = false;
            //   if (in_array($valueToCheck, (array)$ceks)) {
            //       $obat['isError'] = true;
            //   } else {
            //       $obat['isError'] = false;
            //   }
            //   $obatYgDiminta[] = $obat;
            // } else {
            // mapping non racikan =============================================================================
            $data = collect($adaraw)->firstWhere('kdobat', $key['kodeobat']);
            $obatYgDiminta[] = self::mappingObat($data, $key, $sistembayar);
            // $data = $adaraw;
            // $obatYgDiminta[] = $data;
            // }
        }

        return $obatYgDiminta;
    }

    public static function mappingObat($data, $key, $sistembayar)
    {
        $isError = $data ? false : true;
        $obat['isError'] = $isError;
        $obat['errors'] = $isError ? 'Stok Obat Tidak Tersedia' : null;
        $obat['kdobat'] = $key['kodeobat'];
        $obat['sistembayar'] = $sistembayar;
        $obat['data'] = $data;

        $alokasi = $isError ? false : (int)$data['alokasi'];
        $jumlahDiminta = $isError ? false : (int)$key['jumlah_diminta'];
        $jumlahstok = $isError ? false : $data['jumlahstok'];
        $obat['jumlahstok'] = $jumlahstok;
        $obat['alokasi'] = $alokasi;
        $obat['jumlah_diminta'] = $jumlahDiminta;

        // $validasiJmldiminta = (int)$key['jumlah_diminta'] > $alokasi;
        if ($alokasi) {
            if ($jumlahDiminta > $alokasi) {
                $obat['isError'] = true;
                $obat['errors'] = 'Jumlah diminta melebihi Alokasi yang tersedia cek juga pd obat racikan';
            }
        }

        $harga = $isError ? false : $data['harga_tertinggi'];
        $hargajual = $isError ? false : self::penentuanHarga($harga, $sistembayar, $data['obat_program']);
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

    public static function penentuanHarga($harga, $sistembayar, $obatprogram)
    {
        $hargajualx = 0;
        if ($obatprogram === '1') {
            $hargajualx = 0;
        } else {
            if ($sistembayar === null || $sistembayar === '1' || $sistembayar === 1 || !$sistembayar) {
                if ($harga <= 50000) {
                    $hargajualx = (int) $harga + (int) $harga * (int) 28 / (int) 100;
                } elseif ($harga > 50000 && $harga <= 250000) {
                    $hargajualx = (int) $harga + ((int) $harga * (int) 26 / (int) 100);
                } elseif ($harga > 250000 && $harga <= 500000) {
                    $hargajualx = (int) $harga + (int) $harga * (int) 21 / (int) 100;
                } elseif ($harga > 500000 && $harga <= 1000000) {
                    $hargajualx = (int) $harga + (int) $harga * (int) 16 / (int)100;
                } elseif ($harga > 1000000 && $harga <= 5000000) {
                    $hargajualx = (int) $harga + (int) $harga * (int) 11 /  (int)100;
                } elseif ($harga > 5000000 && $harga <= 10000000) {
                    $hargajualx = (int) $harga + (int) $harga * (int) 9 / (int) 100;
                } elseif ($harga > 10000000) {
                    $hargajualx = (int) $harga + (int) $harga * (int) 7 / (int) 100;
                }
            } else if ($sistembayar == 2 || $sistembayar == '2') {
                $hargajualx = (int) $harga + (int) $harga * (int) 25 / (int)100;
            } else {
                $hargajualx = (int) $harga + (int) $harga * (int) 30 / (int)100;
            }
        }


        return $hargajualx;
    }


    public static function sendOrder($request, $obatyangsudahdicek)
    {
        // return $request;
        // mulai insert
        //  $obatdiminta = $request->items;
        //  return [
        //   'obatdiminta' => $obatdiminta,
        //   'obatyangsudahdicek' => $obatyangsudahdicek];

        // $adaAlokasiRacikan = array_filter($obatyangsudahdicek, function($obat) {
        //   return $obat['racikan'] !== false;
        // });

        // return $adaAlokasiRacikan;


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

                    'diagnosa' => $request->diagnosa ?? '',
                    'kodeincbg' => $request->kodeincbg ?? '',
                    'uraianinacbg' => $request->uraianinacbg ?? '',
                    'tarifina' => $request->tarifina ?? '',
                    'tiperesep' => $request->tiperesep,
                    'flag' => '1',
                    'flag_dari' => '1',
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

            $adaAlokasi = array_filter($obatyangsudahdicek, function ($obat) {
                return $obat['racikan'] === false;
            });

            // return $adaAlokasi;

            $adaAlokasiRacikan = array_filter($obatyangsudahdicek, function ($obat) {
                return $obat['racikan'] !== false;
            });

            // return $adaAlokasiRacikan;


            if (count($adaAlokasi) > 0) {
                foreach ($adaAlokasi as $non) {
                    // $har = HargaHelper::getHarga($non['kodeobat'], $request->groupsistembayar);
                    // $res = $har['res'];
                    // if ($res) {
                    //     $hargajualx = $non['hargajual'];
                    //     $harga = $non['hpp'];
                    // } else {
                    //     $hargajualx = $har['hargaJual'];
                    //     $harga = $har['harga'];
                    // }

                    // rinci
                    $simpanrinci =
                        [
                            'noreg' => $request->noreg,
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
                            'stokalokasi' => $non['item']['alokasi'],
                            'r' => $request->groupsistembayar === '1' || $request->groupsistembayar === 1 ? 300 : 0,
                            'jumlah' => $non['jumlah_diminta'],
                            'hpp' => $non['harga'],
                            'hargajual' => $non['hargajual'],
                            'aturan' => $non['signa'],
                            'konsumsi' => $non['konsumsi'],
                            'keterangan' => $non['keterangan'] ?? '',
                            'created_at' => $created,
                            'updated_at' => date('Y-m-d H:i:s'),
                        ];
                    $rinciaja[] = $simpanrinci;
                }
            }

            // racikan
            if (count($adaAlokasiRacikan) > 0) {
                foreach ($adaAlokasiRacikan as $rac) {
                    // $har = HargaHelper::getHarga($request->kodeobat, $request->groupsistembayar);
                    // $res = $har['res'];
                    // if ($res) {
                    //     $hargajualx = $rac['hargajual'];
                    //     $harga = $rac['hpp'];
                    // } else {
                    //     $hargajualx = $har['hargaJual'];
                    //     $harga = $har['harga'];
                    // }

                    if ($rac['tiperacikan'] == 'DTD') {
                        $simpandtd =
                            [
                                'noreg' => $request->noreg,
                                'noresep' => $noresep,
                                'namaracikan' => $rac['namaracikan'],
                                'tiperacikan' => $rac['tiperacikan'],
                                'jumlahdibutuhkan' => $rac['jumlah_diminta'], // jumlah racikan
                                'aturan' => $rac['signa'],
                                'konsumsi' => $rac['konsumsi'] ?? 1,
                                'keterangan' => $rac['keterangan'],
                                'kdobat' => $rac['kodeobat'],
                                'kandungan' => $rac['item']['kandungan'] ?? '',
                                'fornas' => $rac['item']['fornas'] ?? '',
                                'forkit' => $rac['item']['forkit'] ?? '',
                                'generik' => $rac['item']['generik'] ?? '',
                                'r' => $request->groupsistembayar === '1' || $request->groupsistembayar === 1 ? 500 : 0,
                                'hpp' => $rac['harga'],
                                'harga_jual' => $rac['hargajual'],
                                'kode108' => $rac['item']['kode108'],
                                'uraian108' => $rac['item']['uraian108'],
                                'kode50' => $rac['item']['kode50'],
                                'uraian50' => $rac['item']['uraian50'],
                                'stokalokasi' => $rac['item']['alokasi'],
                                'dosisobat' => $rac['dosis'] ?? 1,
                                'dosismaksimum' => $rac['dosismaksimum'] ?? 1, // dosis resep
                                'jumlah' => $rac['jumlah_diminta'], // jumlah obat
                                'satuan_racik' => $rac['kemasan'], // jumlah obat
                                'keteranganx' => $rac['item']['keterangan'] ?? '', // keterangan obat
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
                                'jumlahdibutuhkan' => $rac['jumlah_diminta'], // jumlah racikan
                                'aturan' => $rac['signa'],
                                'konsumsi' => $rac['konsumsi'] ?? 1,
                                'keterangan' => $rac['keterangan'],
                                'kdobat' => $rac['kodeobat'],
                                'kandungan' => $rac['item']['kandungan'] ?? '',
                                'fornas' => $rac['item']['fornas'] ?? '',
                                'forkit' => $rac['item']['forkit'] ?? '',
                                'generik' => $rac['item']['generik'] ?? '',
                                'r' => $request->groupsistembayar === '1' || $request->groupsistembayar === 1 ? 500 : 0,
                                'hpp' => $rac['harga'],
                                'harga_jual' => $rac['hargajual'],
                                'kode108' => $rac['item']['kode108'],
                                'uraian108' => $rac['item']['uraian108'],
                                'kode50' => $rac['item']['kode50'],
                                'uraian50' => $rac['item']['uraian50'],
                                'stokalokasi' => $rac['item']['alokasi'],
                                // 'dosisobat' => $rac['dosisobat'],
                                // 'dosismaksimum' => $rac['dosismaksimum'],
                                'jumlah' => $rac['jumlah_diminta'], // jumlah obat
                                'satuan_racik' => $rac['kemasan'], // jumlah obat
                                'keteranganx' => $rac['item']['keterangan'] ?? '', // keterangan obat
                                'created_at' => $created,
                                'updated_at' => date('Y-m-d H:i:s'),
                            ];
                        $racikannondtd[] = $simpannondtd;
                        // if ($simpannondtd) {
                        //     $simpannondtd->load('mobat:kd_obat,nama_obat');
                        // }
                    }
                }
            }

            if (count($rinciaja) > 0) {
                $filteredRinciaja = collect($rinciaja)->unique('kdobat')->values()->all();
                Permintaanresep::insert($filteredRinciaja);
            }

            if (count($racikandtd) > 0) {
                Permintaanresepracikan::insert($racikandtd);
            }
            if (count($racikannondtd) > 0) {
                Permintaanresepracikan::insert($racikannondtd);
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
            // cek apakah pasien rawat jalan, dan ini nanti jadi pasien selesai layanan dan ambil antrian farmasi iki
            $updatekunjungan = KunjunganPoli::where('rs1', $request->noreg)->where('rs17.rs8', '!=', 'POL014')->first();
            if ($updatekunjungan) {
                self::kirimResepDanSelesaiLayanan($request);
            }
            return new JsonResponse([
                'message' => 'Resep Berhasil dibuat',
                // 'adaraw' => $adaraw,
                'data' => $simpan,
                'racikandtd' => $racikandtd,
                'racikannondtd' => $racikannondtd,
                'rinci' => $rinciaja,
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
}
