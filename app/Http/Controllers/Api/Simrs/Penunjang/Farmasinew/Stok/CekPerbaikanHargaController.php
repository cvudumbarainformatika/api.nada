<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Stok;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaandepoheder;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarrinci;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarrinciracikan;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Mutasi\Mutasigudangkedepo;
use App\Models\Simrs\Penunjang\Farmasinew\Obatoperasi\PersiapanOperasiDistribusi;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanRinci;
use App\Models\Simrs\Penunjang\Farmasinew\Retur\Returpenjualan_h;
use App\Models\Simrs\Penunjang\Farmasinew\Retur\Returpenjualan_r;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokopname;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokrel;
use App\Models\Simrs\Penunjang\Farmasinew\Stokreal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CekPerbaikanHargaController extends Controller
{
    public function getPerbaikanHarga(Request $request)
    {
        $data['awal'] = Stokopname::where('kdobat', $request->kdobat)
            ->where('nopenerimaan', $request->nopenerimaan)
            ->where('tglopname', 'LIKE', '%2024-05%')
            ->get();
        $data['penerimaan'] = PenerimaanRinci::select('id', 'nopenerimaan', 'kdobat', 'jml_terima_k as jumlah', 'tgl_exp as tglexp', 'no_batch as nobatch', 'harga_netto_kecil as harga')
            ->with('header:nopenerimaan,tglpenerimaan')
            ->where('kdobat', $request->kdobat)
            ->where('nopenerimaan', $request->nopenerimaan)
            ->get();
        $data['mutasi'] = Mutasigudangkedepo::where('nopenerimaan', $request->nopenerimaan)
            ->where('kd_obat', $request->kdobat)
            ->where('no_permintaan', $request->no_permintaan)
            ->get();
        return response()->json([
            'status' => 'success',
            'req' => $request->all(),
            'data' => $data,
        ]);
    }
    public function simpanPerbaikanHarga(Request $request)
    {
        $data = Mutasigudangkedepo::where('nopenerimaan', $request->nopenerimaan)
            ->where('kd_obat', $request->kd_obat)
            ->get();
        if (sizeof($data) <= 0) {
            return new JsonResponse([
                'message' => 'Data Mutasi Tidak Ditemukan',
                'req' => $request->all(),
            ], 410);
        }
        foreach ($data as $key) {
            if ($key->harga != $request->harga) $key->update(['harga' => $request->harga]);
            if ($key->nobatch != $request->nobatch) $key->update(['nobatch' => $request->nobatch]);
            if ($key->tglexp != $request->tglexp) $key->update(['tglexp' => $request->tglexp]);
            if ($key->tglpenerimaan != $request->tglpenerimaan) $key->update(['tglpenerimaan' => $request->tglpenerimaan]);
        }
        // $data = Mutasigudangkedepo::find($request->id);
        // if (!$data) {
        //     return new JsonResponse([
        //         'message' => 'Data Mutasi Tidak Ditemukan',
        //         'req' => $request->all(),
        //     ], 410);
        // }

        // if ($data->harga != $request->harga) $data->update(['harga' => $request->harga]);
        // if ($data->nobatch != $request->nobatch) $data->update(['nobatch' => $request->nobatch]);
        // if ($data->tglexp != $request->tglexp) $data->update(['tglexp' => $request->tglexp]);
        // if ($data->tglpenerimaan != $request->tglpenerimaan) $data->update(['tglpenerimaan' => $request->tglpenerimaan]);

        return new JsonResponse([
            'message' => 'Data Mutasi sudah diganti',
            'data' => $data,
            'req' => $request->all(),
        ]);
    }
    public function simpanPecahNomor(Request $request)
    {
        try {
            DB::connection('farmasi')->beginTransaction();
            $data = [];
            if ($request->tipe == 'racikan') {
                foreach ($request->data as $key) {
                    if ($key['satuan_racik'] == null) $key['satuan_racik'] = '';
                    if ($key['id']) {
                        $temp = Resepkeluarrinciracikan::find($key['id']);
                        if (!$temp) return new JsonResponse([
                            'message' => 'Data Racikan Tidak Ditemukan',
                            'req' => $request->all(),
                        ], 410);
                        $temp->update([
                            'jumlah' => $key['jumlah']
                        ]);
                        $data[] = $temp;
                    } else {
                        unset($key['id']);
                        $temp = Resepkeluarrinciracikan::create($key);
                        $data[] = $temp;
                    }
                }
            }
            if ($request->tipe == 'resep') {
                foreach ($request->data as $key) {
                    // if ($key['satuan_racik'] == null) $key['satuan_racik'] = '';
                    if ($key['id']) {
                        $temp = Resepkeluarrinci::find($key['id']);
                        if (!$temp) return new JsonResponse([
                            'message' => 'Data Racikan Tidak Ditemukan',
                            'req' => $request->all(),
                        ], 410);
                        $temp->update([
                            'jumlah' => $key['jumlah']
                        ]);
                        $data[] = $temp;
                    } else {
                        unset($key['id']);
                        if ($key['kandungan'] == null) $key['kandungan'] = '';
                        if ($key['fornas'] == null) $key['fornas'] = '';
                        if ($key['forkit'] == null) $key['forkit'] = '';
                        if ($key['generik'] == null) $key['generik'] = '';
                        $temp = Resepkeluarrinci::create($key);
                        $data[] = $temp;
                    }
                }
            }
            if ($request->tipe == 'mutasi') {
                foreach ($request->data as $key) {
                    // if ($key['satuan_racik'] == null) $key['satuan_racik'] = '';
                    if ($key['id']) {
                        $temp = Mutasigudangkedepo::find($key['id']);
                        if (!$temp) return new JsonResponse([
                            'message' => 'Data Racikan Tidak Ditemukan',
                            'req' => $request->all(),
                        ], 410);
                        $temp->update([
                            'jml' => $key['jml']
                        ]);
                        $data[] = $temp;
                    } else {
                        unset($key['id']);
                        if ($key['nobatch'] == null) $key['nobatch'] = '';
                        $temp = Mutasigudangkedepo::create($key);
                        $data[] = $temp;
                    }
                }
            }
            if ($request->tipe == 'persiapan') {
                foreach ($request->data as $key) {
                    // if ($key['satuan_racik'] == null) $key['satuan_racik'] = '';
                    if ($key['id']) {
                        $temp = PersiapanOperasiDistribusi::find($key['id']);
                        if (!$temp) return new JsonResponse([
                            'message' => 'Data Racikan Tidak Ditemukan',
                            'req' => $request->all(),
                        ], 410);
                        $temp->update([
                            'jumlah' => $key['jumlah']
                        ]);
                        $data[] = $temp;
                    } else {
                        unset($key['id']);
                        if ($key['nodistribusi'] == null) $key['nodistribusi'] = '';
                        $temp = PersiapanOperasiDistribusi::create($key);
                        $data[] = $temp;
                    }
                }
            }
            DB::connection('farmasi')->commit();
            return new JsonResponse([
                'message' => 'Data Mutasi sudah diganti',
                'data' => $data,
                'req' => $request->all(),
            ]);
        } catch (\Exception $e) {
            DB::connection('farmasi')->rollBack();
            return new JsonResponse([
                'message' => $e->getMessage(),
                'line' => '' . $e->getLine(),
                'file' =>  $e->getFile(),
                'req' => $request->all(),
            ], 500);
        }
    }
    public function gantiNomor(Request $request)
    {
        try {
            DB::connection('farmasi')->beginTransaction();
            $data = null;
            $str = str_contains($request->targetNoper, 'awal');
            if ($str) {
                $penerimaan = Stokopname::where('nopenerimaan', $request->targetNoper)
                    ->where('kdobat', $request->kdobat)
                    ->where('tglOpname', 'like', '%2024-05%')
                    ->first();
            } else {
                $penerimaan = PenerimaanRinci::select('nopenerimaan', 'kdobat', 'jml_terima_k as jumlah', 'tgl_exp as tglexp', 'no_batch as nobatch', 'harga_netto_kecil as harga')
                    ->with('header:nopenerimaan,tglpenerimaan')
                    ->where('nopenerimaan', $request->targetNoper)
                    ->where('kdobat', $request->kdobat)
                    ->first();
                $penerimaan->tglpenerimaan = $penerimaan->header->tglpenerimaan;
            }
            if ($request->tipe == 'racikan') {
                $data = Resepkeluarrinciracikan::find($request->id);
                if (!$data) return new JsonResponse([
                    'message' => 'Data Racikan Tidak Ditemukan',
                    'req' => $request->all(),
                ], 410);
                $data->update([
                    'nopenerimaan' => $penerimaan->nopenerimaan,
                    'harga_beli' => $penerimaan->harga,
                ]);
            } else if ($request->tipe == 'resep') {
                $data = Resepkeluarrinci::find($request->id);
                if (!$data) return new JsonResponse([
                    'message' => 'Data Resep Tidak Ditemukan',
                    'req' => $request->all(),
                ]);
                $data->update([
                    'nopenerimaan' => $penerimaan->nopenerimaan,
                    'harga_beli' => $penerimaan->harga,
                ]);
            } else if ($request->tipe == 'mutasi') {
                $data = Mutasigudangkedepo::find($request->id);
                if (!$data) return new JsonResponse([
                    'message' => 'Data Mutasi Tidak Ditemukan',
                    'req' => $request->all(),
                ]);
                $data->update(['nopenerimaan' => $penerimaan->nopenerimaan]);
                if ($data->harga != $penerimaan->harga) $data->update(['harga' => $penerimaan->harga]);
                if ($data->nobatch != $penerimaan->nobatch) $data->update(['nobatch' => $penerimaan->nobatch]);
                if ($data->tglexp != $penerimaan->tglexp) $data->update(['tglexp' => $penerimaan->tglexp]);
                if ($data->tglpenerimaan != $penerimaan->tglpenerimaan) $data->update(['tglpenerimaan' => $penerimaan->tglpenerimaan]);
            } else if ($request->tipe == 'persiapan') {
                $data = PersiapanOperasiDistribusi::find($request->id);
                if (!$data) return new JsonResponse([
                    'message' => 'Data Distribusi Persiapan Tidak Ditemukan',
                    'req' => $request->all(),
                ], 410);
                $data->update([
                    'nopenerimaan' => $penerimaan->nopenerimaan,
                ]);
            }

            DB::connection('farmasi')->commit();
            return new JsonResponse([
                'message' => 'Data Mutasi sudah diganti',
                'data' => $data,
                'penerimaan' => $penerimaan,
                'req' => $request->all(),
            ],);
        } catch (\Exception $e) {
            DB::connection('farmasi')->rollBack();
            return new JsonResponse([
                'message' => $e->getMessage(),
                'line' => '' . $e->getLine(),
                'file' =>  $e->getFile(),
                'req' => $request->all(),
            ], 500);
        }
    }

    /**
     * Pengecekan Harga
     */
    public function getObat(Request $request)
    {
        $temp = Mobatnew::select('kd_obat', 'nama_obat')
            ->when($request->q, function ($query) use ($request) {
                $query->where('kd_obat', 'like', '%' . $request->q . '%')
                    ->orWhere('nama_obat', 'like', '%' . $request->q . '%');
            })
            ->paginate($request->per_page);
        $data['data'] = collect($temp)['data'];
        $data['meta'] = collect($temp)->except('data');
        $data['kode'] = collect($data['data'])->pluck('kd_obat');
        if ($request->kdruang) {
            $noper = [];
            $now = $request->tahun . "-" . $request->bulan;
            $data['stok'] = Stokrel::whereIn('kdobat', $data['kode'])->where('kdruang', $request->kdruang)->get();
            $data['opname'] = Stokopname::whereIn('kdobat', $data['kode'])->where('kdruang', $request->kdruang)->where('tglOpname', 'like', '%' . $now . '%')->get();
            $headMut = Permintaandepoheder::select('no_permintaan')
                ->where('dari', $request->kdruang)
                ->where('tgl_terima_depo', 'LIKE', '%' . $now . '%')
                ->pluck('no_permintaan');
            $data['mutasi'] = Mutasigudangkedepo::select('id', 'no_permintaan', 'tglpenerimaan', 'nopenerimaan', 'kd_obat as kdobat', 'jml as jumlah', 'tglexp', 'nobatch', 'harga')->whereIn('no_permintaan', $headMut)
                ->whereIn('kd_obat', $data['kode'])
                ->get();

            $headMutKel = Permintaandepoheder::select('no_permintaan')
                ->where('tujuan', $request->kdruang)
                ->where('tgl_kirim_depo', 'LIKE', '%' . $now . '%')
                ->pluck('no_permintaan');
            $data['mutasikeluar'] = Mutasigudangkedepo::select('id', 'no_permintaan', 'tglpenerimaan', 'nopenerimaan', 'kd_obat as kdobat', 'jml as jumlah', 'tglexp', 'nobatch', 'harga')->whereIn('no_permintaan', $headMutKel)
                ->whereIn('kd_obat', $data['kode'])
                ->get();

            $haResep = Resepkeluarheder::select('noresep')
                ->where('depo', $request->kdruang)
                ->where('tgl_selesai', 'LIKE', '%' . $now . '%')
                ->whereIn('flag', ['3', '4'])
                ->pluck('noresep');
            $data['resep'] = Resepkeluarrinci::select('id', 'noresep', 'kdobat', 'jumlah', 'harga_beli as harga', 'nopenerimaan')
                ->whereIn('noresep', $haResep)
                ->whereIn('kdobat', $data['kode'])
                ->where('jumlah', '>', 0)
                ->get();
            $data['racikan'] = Resepkeluarrinciracikan::select('id', 'noresep', 'kdobat', 'jumlah', 'harga_beli as harga', 'nopenerimaan')
                ->whereIn('noresep', $haResep)
                ->whereIn('kdobat', $data['kode'])
                ->where('jumlah', '>', 0)
                ->get();

            $heRet = Returpenjualan_h::select('retur_penjualan_h.noretur')
                ->join('resep_keluar_h', 'resep_keluar_h.noresep', '=', 'retur_penjualan_h.noresep')
                ->where('resep_keluar_h.depo', $request->kdruang)
                ->where('retur_penjualan_h.tgl_retur', 'LIKE', '%' . $now . '%')
                ->pluck('retur_penjualan_h.noretur');
            $data['retur'] = Returpenjualan_r::select('id', 'noresep', 'noretur', 'kdobat', 'jumlah_retur as jumlah', 'harga_beli as harga', 'nopenerimaan')
                ->whereIn('noretur', $heRet)
                ->whereIn('kdobat', $data['kode'])
                ->with([
                    'resep' => function ($q) use ($data) {
                        $q->select('id', 'noresep', 'kdobat', 'jumlah', 'harga_beli', 'nopenerimaan')
                            ->whereIn('kdobat', $data['kode']);
                    }
                ])
                ->get();
            $noper = array_merge(
                $data['opname']->pluck('nopenerimaan')->toArray(),
                $data['mutasi']->pluck('nopenerimaan')->toArray(),
                $data['mutasikeluar']->pluck('nopenerimaan')->toArray(),
                $data['resep']->pluck('nopenerimaan')->toArray(),
                $data['racikan']->pluck('nopenerimaan')->toArray(),
                $data['retur']->pluck('nopenerimaan')->toArray(),
                $data['stok']->pluck('nopenerimaan')->toArray()
            );

            $data['noper'] = array_unique($noper);
            $data['awal'] = Stokopname::whereIn('kdobat', $data['kode'])->whereIn('nopenerimaan', $data['noper'])->where('tglOpname', 'like', '%2024-05%')->get();
            $penerimaan = PenerimaanRinci::select('nopenerimaan', 'kdobat', 'jml_terima_k as jumlah', 'tgl_exp as tglexp', 'no_batch as nobatch', 'harga_netto_kecil as harga')
                ->with('header:nopenerimaan,tglpenerimaan')
                ->whereIn('kdobat', $data['kode'])
                ->whereIn('nopenerimaan', $data['noper'])->get();
            if (sizeof($penerimaan) > 0) {
                foreach ($penerimaan as $key) {
                    $key['tglpenerimaan'] = $key['header']['tglpenerimaan'];
                }
            }
            $data['penerimaan'] = $penerimaan;
        }
        return new JsonResponse([
            'message' => 'OK',
            'data' => $data,
            'req' => $request->all(),
        ]);
    }
    public function simpanPerbaikanHargaArray(Request $request)
    {
        try {
            $data = DB::connection('farmasi')->transaction(function () use ($request) {
                $items = [];
                foreach ($request->item as $key) {
                    if ($request->tipe == 'stok') {
                        $data = Stokrel::find($key['id']);
                        if (!$data) throw new \Exception('Data Stok Tidak Ditemukan', 410);
                        $data->update([
                            'harga' => $key['harga'],
                        ]);
                        if ($data->tglpenerimaan != $key['tglpenerimaan']) $data->update(['tglpenerimaan' => $key['tglpenerimaan']]);
                        $items[] = $data;
                    }
                    if ($request->tipe == 'opname') {
                        $data = Stokopname::find($key['id']);
                        if (!$data) throw new \Exception('Data Stok Tidak Ditemukan', 410);
                        $data->update([
                            'harga' => $key['harga'],
                        ]);
                        if ($data->tglpenerimaan != $key['tglpenerimaan']) $data->update(['tglpenerimaan' => $key['tglpenerimaan']]);
                        $items[] = $data;
                    }
                    if ($request->tipe == 'mutasi') {
                        $data = Mutasigudangkedepo::find($key['id']);
                        if (!$data) throw new \Exception('Data Stok Tidak Ditemukan', 410);
                        $data->update(['harga' => $key['harga']]);
                        if ($data->nobatch != $key['nobatch']) $data->update(['nobatch' => $key['nobatch']]);
                        if ($data->tglexp != $key['tglexp']) $data->update(['tglexp' => $key['tglexp']]);
                        if ($data->tglpenerimaan != $key['tglpenerimaan']) $data->update(['tglpenerimaan' => $key['tglpenerimaan']]);
                        $items[] = [
                            'data' => $data,
                            'penerimaan' => $key,
                            'nobatch' => $key['nobatch'],
                            'tglexp' => $key['tglexp'],
                            'tglpenerimaan' => $key['tglpenerimaan'],
                        ];
                    }
                    if ($request->tipe == 'resep') {
                        $data = Resepkeluarrinci::find($key['id']);
                        if (!$data) throw new \Exception('Data Stok Tidak Ditemukan', 410);
                        $data->update([
                            'harga_beli' => $key['harga'],
                        ]);
                        $items[] = $data;
                    }
                    if ($request->tipe == 'racikan') {
                        $data = Resepkeluarrinciracikan::find($key['id']);
                        if (!$data) throw new \Exception('Data Stok Tidak Ditemukan', 410);
                        $data->update([
                            'harga_beli' => $key['harga'],
                        ]);
                        $items[] = $data;
                    }
                    if ($request->tipe == 'retur') {
                        $data = Returpenjualan_r::find($key['id']);
                        if (!$data) throw new \Exception('Data Stok Tidak Ditemukan', 410);
                        $data->update([
                            'harga_beli' => $key['harga'],
                        ]);
                        $items[] = $data;
                    }
                }
            });
            DB::connection('farmasi')->commit();
            return new JsonResponse([
                'message' => 'Data Berhasil di Simpan',
                'data' => $data,
                'req' => $request->all(),
            ]);
        } catch (\Exception $th) {
            DB::connection('farmasi')->rollBack();
            return new JsonResponse([
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' =>  $th->getLine(),
                'req' => $request->all(),
            ], 410);
        }
    }
    public function simpanPerbaikanHargaDua(Request $request)
    {
        $this->validate($request, [
            'harga' => 'required|numeric|gt:0',
        ]);
        try {
            $data = DB::connection('farmasi')->transaction(function () use ($request) {
                if ($request->tipe == 'stok') {
                    $data = Stokrel::find($request->id);
                    if (!$data) throw new \Exception('Data Stok Tidak Ditemukan', 410);
                    $data->update([
                        'harga' => $request->harga,
                    ]);
                    return $data;
                }
                if ($request->tipe == 'opname') {
                    $data = Stokopname::find($request->id);
                    if (!$data) throw new \Exception('Data Stok Tidak Ditemukan', 410);
                    $data->update([
                        'harga' => $request->harga,
                    ]);
                    return $data;
                }
                if ($request->tipe == 'mutasi') {
                    $data = Mutasigudangkedepo::find($request->id);
                    if (!$data) throw new \Exception('Data Stok Tidak Ditemukan', 410);
                    $data->update(['harga' => $request->harga]);
                    if ($data->nobatch != $request->penerimaan['nobatch']) $data->update(['nobatch' => $request->penerimaan['nobatch']]);
                    if ($data->nobatch != $request->penerimaan['tglexp']) $data->update(['tglexp' => $request->penerimaan['tglexp']]);

                    $tglpenerimaan = $request->penerimaan['header']['tglpenerimaan'] ?? $request->penerimaan['tglpenerimaan'];
                    if ($data->nobatch != $tglpenerimaan) $data->update(['tglpenerimaan' => $tglpenerimaan]);
                    return [
                        'data' => $data,
                        'penerimaan' => $request->penerimaan,
                        'nobatch' => $request->penerimaan['nobatch'],
                        'tglexp' => $request->penerimaan['tglexp'],
                        'tglpenerimaan' => $tglpenerimaan,
                    ];
                }
                if ($request->tipe == 'resep') {
                    $data = Resepkeluarrinci::find($request->id);
                    if (!$data) throw new \Exception('Data Stok Tidak Ditemukan', 410);
                    $data->update([
                        'harga_beli' => $request->harga,
                    ]);
                    return $data;
                }
                if ($request->tipe == 'racikan') {
                    $data = Resepkeluarrinciracikan::find($request->id);
                    if (!$data) throw new \Exception('Data Stok Tidak Ditemukan', 410);
                    $data->update([
                        'harga_beli' => $request->harga,
                    ]);
                    return $data;
                }
                if ($request->tipe == 'retur') {
                    $data = Returpenjualan_r::find($request->id);
                    if (!$data) throw new \Exception('Data Stok Tidak Ditemukan', 410);
                    $data->update([
                        'harga_beli' => $request->harga,
                    ]);
                    return $data;
                }
            });
            DB::connection('farmasi')->commit();
            return new JsonResponse([
                'message' => 'Data Berhasil di Simpan',
                'data' => $data,
                'req' => $request->all(),
            ]);
        } catch (\Exception $th) {
            DB::connection('farmasi')->rollBack();
            return new JsonResponse([
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' =>  $th->getLine(),
                'req' => $request->all(),
            ], 410);
        }
    }
}
