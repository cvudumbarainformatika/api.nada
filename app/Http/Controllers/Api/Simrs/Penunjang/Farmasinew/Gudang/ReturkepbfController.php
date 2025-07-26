<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Gudang;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Sigarang\KontrakPengerjaan;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Obat\BarangRusak;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanRinci;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\Returpbfheder;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\Returpbfrinci;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokrel;
use App\Models\Simrs\Penunjang\Farmasinew\Stokreal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReturkepbfController extends Controller
{

    public function cariPerusahaan()
    {
        // ambil perusahaan yang sedang belum bast
        $raw = PenerimaanHeder::select('kdpbf')
            ->when(request('kd_ruang'), function ($q) {
                $q->where('gudang', request('kd_ruang'));
            })
            ->distinct('kdpbf')
            ->where('kunci', '1')
            ->get();
        // map kode perusahaan ke bentuk array, biar aman jika nanti ada append
        $temp = collect($raw)->map(function ($y) {
            return $y->kdpbf;
        });
        $data = KontrakPengerjaan::select('kodeperusahaan', 'namaperusahaan')->whereIn('kodeperusahaan', $temp)->distinct()->get();

        return new JsonResponse($data);
    }
    public function cariObat()
    {
        if (!request('kdpbf')) {
            return new JsonResponse(['message' => 'Tidak ada kode pbf']);
        }

        $data = Mobatnew::select(
            'new_masterobat.kd_obat',
            'new_masterobat.nama_obat',
            'new_masterobat.satuan_k',
        )
            ->leftJoin('penerimaan_r', 'penerimaan_r.kdobat', '=', 'new_masterobat.kd_obat')
            ->leftJoin('penerimaan_h', 'penerimaan_h.nopenerimaan', '=', 'penerimaan_r.nopenerimaan')
            ->where('penerimaan_h.kdpbf', '=', request('kdpbf'))
            ->whereNotIn('penerimaan_h.jenis_penerimaan', ['APBD', 'APBN'])
            ->groupBy('new_masterobat.kd_obat')
            ->get();
        return new JsonResponse($data);
    }
    public function newCariObat()
    {


        $data = Mobatnew::select(
            'new_masterobat.kd_obat',
            'new_masterobat.nama_obat',
            'new_masterobat.satuan_k',
        )
            ->leftJoin('stokreal', 'stokreal.kdobat', '=', 'new_masterobat.kd_obat')
            ->where('stokreal.jumlah', '>', 0)
            ->where('stokreal.kdruang', request('kd_ruang'))
            ->groupBy('new_masterobat.kd_obat')
            ->get();
        return new JsonResponse($data);
    }
    public function ambilData()
    {

        $data = PenerimaanRinci::select(
            'fakturs.no_faktur',
            'fakturs.tgl_faktur',
            'penerimaan_h.jenissurat',
            'penerimaan_h.tgl_pembayaran',
            'penerimaan_h.tglpenerimaan',
            'penerimaan_h.tglsurat',
            'penerimaan_h.nomorsurat',
            'penerimaan_r.kdobat',
            'penerimaan_r.nopenerimaan',
            'penerimaan_r.no_batch',
            'penerimaan_r.tgl_exp',
            'penerimaan_r.harga_kcl as harga',
            'penerimaan_r.diskon',
            'penerimaan_r.ppn',
            'penerimaan_r.harga_netto_kecil as harga_neto',
            'penerimaan_r.jml_terima_k as jumlah',
        )
            ->leftJoin('penerimaan_h', 'penerimaan_h.nopenerimaan', '=', 'penerimaan_r.nopenerimaan')
            ->leftJoin('fakturs', 'fakturs.nopenerimaan', '=', 'penerimaan_r.nopenerimaan')
            ->where('penerimaan_h.kdpbf', '=', request('kdpbf'))
            ->where('penerimaan_r.kdobat', '=', request('kd_obat'))
            ->when(request('nopenerimaan'), function ($q) {
                $q->where('penerimaan_r.nopenerimaan', 'LIKE', '%' . request('nopenerimaan') . '%');
            })
            ->with([
                'stokterima' => function ($st) {
                    $st->where('kdobat', '=', request('kd_obat'));
                    $st->where('kdruang', '=', request('kd_ruang'));
                },
            ])
            ->orderBy('penerimaan_h.tglpenerimaan', 'DESC')
            ->orderBy('penerimaan_h.nopenerimaan', 'DESC')
            ->limit(20)
            ->get();

        $rusak = BarangRusak::where('kd_obat', '=', request('kd_obat'))
            ->where(function ($w) {
                $w->where('kdpbf', '=', request('kdpbf'))
                    ->orWhere('kdpbf', '=', '');
            })
            ->where('kunci', '1')
            ->where('status', 'Rusak')
            ->whereNull('tgl_retur')
            ->whereNull('tgl_pemusnahan')
            ->get();
        $stok = Stokreal::where('kdobat', '=', request('kd_obat'))
            ->where('kdruang', '=', request('kd_ruang'))
            ->where('nopenerimaan', 'LIKE', '%awal%')
            ->where('jumlah', '>', 0)
            ->get();
        return new JsonResponse([
            'penerimaan' => $data,
            'rusak' => $rusak,
            'stok' => $stok,
        ]);
    }
    public function listRetur()
    {
        $data = Returpbfheder::where('gudang', request('kd_ruang'))
            ->with('rinci.mobatnew:kd_obat,nama_obat,satuan_k', 'penyedia:kode,nama', 'gudang:kode,nama')
            ->orderBy('tgl_retur', 'DESC')
            ->paginate(request('per_page'));
        $raw = collect($data);
        $result = $raw->only('data');
        $result['meta'] = $raw->except('data');
        return new JsonResponse($result);
    }
    public function simpanEditRinci(Request $request)
    {
        $data = Returpbfrinci::find($request->id);
        if (!$data) {
            return new JsonResponse([
                'message' => 'Data Tidak Ditemukan',
                'req' => $request->all()
            ], 410);
        }
        $data->update([
            'no_batch' => $request->nobatch ?? '',
            'nopenerimaan' => $request->nopenerimaan,
            'tgl_exp' => $request->tglexp,
            'subtotal' => $request->subtotal ?? 0,
            'harga_net' => $request->harga ?? 0,
        ]);

        return new JsonResponse([
            'message' => 'Data Sudah Disimpan',
            'data' => $data ?? null,
            'req' => $request->all(),
        ]);
    }
    public function simpanEditFaktur(Request $request)
    {
        $data = Returpbfheder::find($request->id);
        if (!$data) {
            return new JsonResponse([
                'message' => 'Data Tidak Ditemukan',
                'req' => $request->all()
            ], 410);
        }
        $data->update([
            'no_faktur_retur_pbf' => $request->nomor ?? '',
            'tgl_faktur_retur_pbf' => $request->tanggal
        ]);
        return new JsonResponse([
            'message' => 'Data Sudah Disimpan',
            'data' => $data,
            'req' => $request->all(),
        ]);
    }
    public function simpanretur(Request $request)
    {
        // $data = [
        //     'no_retur' => '$noretur',
        //     'nopenerimaan' => $request->item['nopenerimaan'],
        //     'kdpbf' => $request->kdpbf,
        //     'gudang' => $request->kd_ruang,

        //     'tgl_retur' => $request->tgl_retur,
        //     'no_faktur_retur_pbf' => $request->no_faktur_retur_pbf ?? '',
        //     'tgl_faktur_retur_pbf' => $request->tgl_faktur_retur_pbf ?? null,

        //     'no_kwitansi_pembayaran' => $request->no_kwitansi_pembayaran ?? '',
        //     'tgl_kwitansi_pembayaran' => $request->tgl_kwitansi_pembayaran ?? null,

        //     'no_retur' => '$noretur',
        //     'kd_obat' => $request->item['kdobat'],
        //     'jumlah_retur' => $request->item['jumlah_retur'],

        //     'kondisi_barang' => $request->item['kondisi_barang'],
        //     'tgl_rusak' => $request->item['kdobat'],
        //     'tgl_exp' => $request->item['tgl_exp'],
        // ];
        // $data = PenerimaanRinci::where('kdobat', $request->item['kdobat'])
        //     ->where('nopenerimaan', $request->item['nopenerimaan'])
        //     ->where('no_batch', $request->item['no_batch'])
        //     ->first();
        // $jumlahStok = (float)$data->jml_terima_k - (float)$request->item['jumlah_retur'];
        // $stok = Stokrel::where('kdobat', $request->item['kdobat'])
        //     ->where('nopenerimaan', $request->item['nopenerimaan'])
        //     ->where('nobatch', $request->item['no_batch'])
        //     ->where('kdruang', $request->kd_ruang)
        //     ->first();

        // return new JsonResponse([
        //     'stok' => $stok,
        //     'data' => $data,
        //     'jumlahStok' => $jumlahStok,
        //     'request' => $request->all(),
        //     'message' => 'Cek Data cuy'
        // ]);

        try {
            DB::connection('farmasi')->beginTransaction();
            if ($request->no_retur == '' || $request->no_retur == null) {
                DB::connection('farmasi')->select('call retur_pbf(@nomor)');
                $x = DB::connection('farmasi')->table('conter')->select('returpbf')->get();
                $wew = $x[0]->returpbf;
                $no_retur = FormatingHelper::penerimaanobat($wew, 'RET-PBF');
            } else {
                $no_retur = $request->no_retur;
            }

            $user = FormatingHelper::session_user();

            $simpan_h = Returpbfheder::updateOrCreate(
                [
                    'no_retur' => $no_retur,
                    'kdpbf' => $request->kdpbf,
                    'gudang' => $request->kd_ruang
                ],
                [
                    'tgl_retur' => $request->tgl_retur,
                    'opsiretur' => $request->opsiretur,
                    'no_faktur_retur_pbf' => $request->no_faktur_retur_pbf ?? '',
                    'tgl_faktur_retur_pbf' => $request->tgl_faktur_retur_pbf ?? null,

                    'no_kwitansi_pembayaran' => $request->no_kwitansi_pembayaran ?? '',
                    'tgl_kwitansi_pembayaran' => $request->tgl_kwitansi_pembayaran ?? null,
                    'user_entry' => $user['kodesimrs'],
                ]
            );
            if (!$simpan_h) {
                return new JsonResponse(['message' => 'Maaf retur Gagal Disimpan...!!!'], 500);
            }

            $simpan_r = Returpbfrinci::updateOrCreate(
                [
                    'no_retur' => $no_retur,
                    'kd_obat' => $request->kd_obat,
                    'nopenerimaan' => $request->nopenerimaan,
                    'no_batch' => $request->no_batch,
                ],
                [
                    'nopenerimaan_default' => $request->nopenerimaan,
                    'no_batch_default' => $request->no_batch,
                    'jumlah_retur' => $request->jumlah_retur,
                    'kondisi_barang' => $request->kondisi_barang,
                    'tgl_rusak' => $request->tgl_rusak,
                    'harga_net' => $request->harga_neto,
                    'harga_net_default' => $request->harga_neto,
                    'subtotal' => $request->subtotal,
                    'subtotal_default' => $request->subtotal,
                    'tgl_exp' => $request->tgl_exp ?? null,
                    'tgl_exp_default' => $request->tgl_exp ?? null,
                    'flag_tbl_rusak' => $request->flag_tbl_rusak ?? '',
                ]
            );
            // jika dari tabel barang rusak update tabel barang rusak
            $barangRusak = BarangRusak::where('kd_obat', $request->kd_obat)
                ->where('nopenerimaan', $request->nopenerimaan)
                ->where('nobatch', $request->no_batch)
                ->first();
            if ($barangRusak) {
                $barangRusak->tgl_retur = date('Y-m-d H:i:s');
                $barangRusak->user_retur = $user['kodesimrs'];
                $barangRusak->save();
            }

            // mengurangi stok ( pastikan hanya mengurangi satu kali saja)
            // ini belum termasuk jika barang sudah pernah keluar
            // ini nanti pindah ke pas ngunci aja
            // $stok = Stokrel::where('kdobat', $request->kd_obat)
            //     ->where('nopenerimaan', $request->nopenerimaan)
            //     ->where('nobatch', $request->no_batch)
            //     ->where('kdruang', $request->kd_ruang)
            //     ->first();
            // $jumlahStok = (float)$stok->jumlah - (float)$request->item['jumlah_retur'];
            // if ($stok) {
            //     $stok->jumlah = $jumlahStok;
            //     $stok->save();
            // }

            DB::connection('farmasi')->commit();
            return new JsonResponse(
                [
                    'no_retur' => $no_retur,
                    'heder' => $simpan_h,
                    'rinci' => $simpan_r->load('mobatnew:kd_obat,nama_obat'),
                    // 'jumlahStok' => $jumlahStok,
                    'message' => 'Retur Berhasil Disimpan...!!!'
                ],
                200
            );
        } catch (\Exception $e) {
            DB::connection('farmasi')->rollBack();
            return response()->json([
                'message' => 'ada kesalahan : ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'error' => $e
            ], 410);
        }
    }
    public function kunciRetur(Request $request)
    {
        try {
            DB::connection('farmasi')->beginTransaction();

            $data = Returpbfheder::where('no_retur', $request->no_retur)->first();
            if (!$data) {
                return new JsonResponse(['message' => 'Data Retur Tidak ditemukan, Data tidak terkunci'], 410);
            }
            $rinci = Returpbfrinci::where('no_retur', $request->no_retur)->get();
            if (count($rinci) <= 0) {
                return new JsonResponse(['message' => 'Data Obat Tidak ditemukan, Data tidak terkunci'], 410);
            }
            foreach ($rinci as $key) {
                if ($key['flag_tbl_rusak'] !== '1') {
                    $stok = Stokrel::where('kdobat', $key['kd_obat'])
                        ->where('nopenerimaan', $key['nopenerimaan_default'])
                        ->where('nobatch', $key['no_batch_default'])
                        ->where('kdruang', $data->gudang)
                        ->first();
                    if (!$stok) {
                        return new JsonResponse([
                            'message' => 'Data Stok Tidak ditemukan, Data tidak terkunci',
                        ], 410);
                    }
                    $adaStok = (float)$stok->jumlah;
                    if ($adaStok < $key['jumlah_retur']) {
                        return new JsonResponse([
                            'message' => 'Data Stok Tidak mencukupi, Data tidak terkunci',
                        ], 410);
                    }
                    $jumlahStok = (float)$stok->jumlah - (float)$key['jumlah_retur'];
                    $stok->jumlah = $jumlahStok;
                    $stok->save();
                }
            }
            $data->kunci = '1';
            $data->tgl_kunci = date('Y-m-d H:i:s');
            $data->save();
            DB::connection('farmasi')->commit();
            return new JsonResponse([
                'message' => 'Data Retur Sudah Terkunci dan Stok Sudah berkurang'
            ]);
        } catch (\Exception $e) {
            DB::connection('farmasi')->rollBack();
            return response()->json([
                'message' => 'ada kesalahan : ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 410);
        }
    }
    public function deleteHeader(Request $request)
    {
        // hapus header
        $head = Returpbfheder::where('no_retur', $request->no_retur)->first();
        if (!$head) {
            return new JsonResponse(['message' => 'Data tidak ditemukan'], 410);
        }
        // menhapus rinci
        $rinci = Returpbfrinci::where('no_retur', $request->no_retur)->get();
        // $stok = [];
        foreach ($rinci as $key) {
            // mengembalikan barang rusak
            if ($key['flag_tbl_rusak'] === '1') {
                $barangRusak = BarangRusak::where('kd_obat', $key['kd_obat'])
                    ->where('nopenerimaan', $key['nopenerimaan'])
                    ->where('nobatch', $key['no_batch'])
                    ->first();
                $barangRusak->tgl_retur = null;
                $barangRusak->user_retur = '';
                $barangRusak->save();
            }
            // menambahkan stok setiap obat di rinci
            // $stokNya = Stokrel::where('kdobat', $key['kd_obat'])
            //     ->where('nopenerimaan', $key['nopenerimaan'])
            //     ->where('nobatch', $key['no_batch'])
            //     ->where('kdruang', $head->gudang)
            //     ->first();
            // if ($stokNya) {
            //     $jumlahStok = (float)$stokNya->jumlah + (float)$key['jumlah_retur'];
            //     $stokNya->jumlah = $jumlahStok;
            //     $stokNya->save();
            //     // $stok[] = [
            //     //     'stok' => $stokNya,
            //     //     'jumlahStok' => $jumlahStok,
            //     // ];
            // }
            $key->delete();
        }
        $head->delete();


        return new
            JsonResponse([
                'req' => $request->all(),
                'head' => $head,
                'rinci' => $rinci,
                // 'stok' => $stok,
                'message' => 'Data Sudah dihapus',
            ]);
    }
    public function deleteRinci(Request $request)
    {
        // hitung jumlah rinci
        $count = Returpbfrinci::where('no_retur', $request->no_retur)->count();
        // menghapus rinci
        $data = Returpbfrinci::where('no_retur', $request->no_retur)
            ->where('kd_obat', $request->kd_obat)
            ->where('nopenerimaan', $request->nopenerimaan)
            ->first();
        if (!$data) {
            return new JsonResponse(['message' => 'Data Obat tidak ditemukan'], 410);
        }
        // mengembalikan barang rusak
        $barangRusak = BarangRusak::where('kd_obat', $request->kd_obat)
            ->where('nopenerimaan', $request->nopenerimaan)
            ->where('nobatch', $request->no_batch)
            ->first();
        $barangRusak->tgl_retur = null;
        $barangRusak->user_retur = '';
        $barangRusak->save();
        // mngembalikan stok di rinci
        // $stok = Stokrel::where('kdobat', $request->kd_obat)
        //     ->where('nopenerimaan', $request->nopenerimaan)
        //     ->where('nobatch', $data->no_batch)
        //     ->where('kdruang', $head->gudang)
        //     ->first();
        // if ($stok) {
        //     $jumlahStok = (float)$stok->jumlah + (float)$data->jumlah_retur;
        //     $stok->jumlah = $jumlahStok;
        //     $stok->save();
        // }

        $data->delete();
        // jika rinci hanya satu maka hapus header
        if ($count <= 1) {
            $head = Returpbfheder::where('no_retur', $request->no_retur)->first();
            $head->delete();
        }
        return new JsonResponse([
            'req' => $request->all(),
            'count' => $count,
            'data' => $data,
            // 'stok' => $stok,
            // 'jumlahStok' => $jumlahStok,
            'message' => 'Obat Sudah dihapus',
        ]);
    }
}
