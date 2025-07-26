<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\RecentStokUpdate;
use App\Models\Sigarang\Transaksi\DistribusiDepo\DetailDistribusiDepo;
use App\Models\Sigarang\Transaksi\DistribusiDepo\DistribusiDepo;
use App\Models\Sigarang\Transaksi\Penerimaan\Penerimaan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DistribusiDepoController extends Controller
{
    public function index()
    {
        $data = DistribusiDepo::latest('id')
            ->filter(request(['q']))
            ->paginate(request('per_page'));
        $collect = collect($data);
        $balik = $collect->only('data');
        $balik['meta'] = $collect->except('data');

        return new JsonResponse($balik);
    }

    /** new distribusi depo start */
    // cari datapenerimaan yang statusnya 2
    public function penerimaan()
    {
        $data = Penerimaan::latest('id')
            ->where('status', 2)
            ->with([
                'details.barangrs.depo',
                'perusahaan',
                'stokgudang' => function ($anu) {
                    $anu->where('kode_ruang', 'Gd-02010100');
                }
            ])
            ->filter(request(['q', 'r']))
            ->paginate(request('per_page'));
        $collect = collect($data);
        $balik = $collect->only('data');
        $balik['meta'] = $collect->except('data');

        return new JsonResponse($balik);
    }
    // distribusikan data penerimaan
    public function newStore(Request $request)
    {
        $penerimaan = Penerimaan::find($request->trmid);
        // return new JsonResponse(['penerimaan' => $penerimaan, 'request' => $request->all()]);
        try {
            DB::beginTransaction();
            $details = $request->details;
            $data = DistribusiDepo::create($request->only('reff', 'no_distribusi',  'kode_depo', 'tanggal', 'status'));
            if ($data) {
                $stok = [];
                foreach ($details as $key) {
                    $data->details()->create($key);

                    $kirim = (object)[];
                    $kirim->kode_rs = $key['kode_rs'];
                    $kirim->no_penerimaan = $key['no_penerimaan'];
                    $kirim->jumlah = $key['jumlah'];
                    $kirim->kode_depo = $key['kode_depo'];
                    $kirim->harga = $key['harga'];
                    $kirim->kode_satuan = $key['kode_satuan'];
                    $kirim->satuan = $key['satuan_besar'];

                    $ok = $this->updateStok($kirim);
                    array_push($stok, $ok);
                }
                $penerimaan->update(['status' => 3]);
            }
            DB::commit();
            return new JsonResponse([
                'message' => 'data berhasil di simpan',
                'data' => $data,
                'penerimaan' => $penerimaan,
                'stok' => $stok
            ]);
        } catch (\Exception $th) {
            //throw $th;
            DB::rollBack();
            return response()->json(['Gagal tersimpan' => $th], 417);
        }
    }
    // distribusikan data yang ada detailnya tapi sudah ada sebagian yang di distribusikan
    public function saveDetail(Request $request)
    {
        // $penerimaan = Penerimaan::find($request->trmid);
        // return new JsonResponse(['penerimaan' => $penerimaan, 'request' => $request->all()]);
        try {
            DB::beginTransaction();
            $data = DistribusiDepo::updateOrCreate(
                [
                    'reff' => $request->reff,
                    'no_distribusi' => $request->no_distribusi,
                    'kode_depo' => $request->kode_depo, //', 'reff', 'no_distribusi')
                ],
                [
                    'tanggal' => $request->tanggal,
                    'status' => $request->status,
                    // $request->only('tanggal', 'status')
                ],
            );
            if ($data) {
                $data->details()->create($request->all());

                $stok = $this->updateStok($request);
                // $penerimaan->update(['status' => 3]);
            }
            DB::commit();
            return new JsonResponse([
                'message' => 'data berhasil di simpan',
                'data' => $data,
                // 'penerimaan' => $penerimaan,
                'stok' => $stok
            ]);
        } catch (\Exception $th) {
            //throw $th;
            DB::rollBack();
            return response()->json(['Gagal tersimpan' => $th], 417);
        }
    }
    private function updateStok($data)
    {
        $recent = RecentStokUpdate::where('kode_rs', $data->kode_rs)
            ->where('kode_ruang', 'Gd-02010100')
            ->where('no_penerimaan', $data->no_penerimaan)
            ->first();
        if (!$recent) {
            return false;
        }
        if ($recent->sisa_stok >= $data->jumlah) {
            $stok = RecentStokUpdate::create([
                'kode_rs' => $data->kode_rs,
                'kode_ruang' => $data->kode_depo,
                'sisa_stok' => $data->jumlah,
                'no_penerimaan' => $data->no_penerimaan,
                'harga' => $data->harga,
                'kode_satuan' => $data->kode_satuan,
                'satuan' => $data->satuan,
            ]);
            $sisa = $recent->sisa_stok - $data->jumlah;
            $recent->update(['sisa_stok' => $sisa]);
            return $stok;
        } else {
            return false;
        }
    }
    // ganti status penerimaan yang sudah di distribusikan semua
    public function gantiStatusPenerimaan(Request $request)
    {
        $penerimaan = Penerimaan::find($request->id);
        if (!$penerimaan) {
            return new JsonResponse(['message' => 'Data Penerimaan tidak ditemukan'], 410);
        }
        $penerimaan->update(['status' => 3]);
        return new JsonResponse(['message' => 'Status penerimaan sudah diganti']);
    }
    /** new distribusi depo end */


    public function toDistribute()
    {
        $data = DistribusiDepo::where('status', '=', 1)
            ->latest('id')
            ->with('details.barangrs', 'depo')
            ->get();

        return new JsonResponse($data);
    }

    public function getDistribusi()
    {
        // $data = DistribusiDepo::where('status', '=', 1)
        //     ->with('details')
        //     ->get();
        $data = DetailDistribusiDepo::selectRaw('kode_rs,sum(jumlah) as jml')
            ->whereHas('distribusi', function ($a) {
                $a->where('status', '=', 1);
            })->groupBy('kode_rs')->get();
        return new JsonResponse($data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'details' => 'required',
            // 'no_penerimaan' => 'required'
        ]);
        //
        $details = $request->details;
        $data = DistribusiDepo::create($request->only('reff', 'no_distribusi',  'kode_depo'));
        if ($data) {
            foreach ($details as $key) {
                $data->details()->create($key);
            }
        }
        if (!$data->wasRecentlyCreated) {
            return new JsonResponse(['message' => 'data gagal dibuat'], 500);
        }
        return new JsonResponse(['message' => 'data telah dibuat'], 201);
    }



    public function hapusDataStokGudang(Request $request)
    {
        $data = RecentStokUpdate::find($request->id);

        $data->delete();
        if (!$data) {
            return new JsonResponse(['message' => 'Data Gagal di Hapus'], 410);
        }

        return new JsonResponse(['message' => 'Data telah di Hapus'], 200);
    }

    public function diterimaDepo(Request $request)
    {
        $tanggal = $request->tanggal !== null ? $request->tanggal : date('Y-m-d H:i:s');
        $data = DistribusiDepo::with('details')->find($request->id);


        foreach ($data->details as $key) {
            $jumlah = $key->jumlah;
            $stok = RecentStokUpdate::where('kode_ruang', 'Gd-02010100')
                ->where('kode_rs', $key->kode_rs)
                ->where('sisa_stok', '>', 0)
                ->oldest()
                ->get();
            $index = 0;
            $sisaStok = collect($stok)->sum('sisa_stok');

            if ($jumlah > $sisaStok) {
                return new JsonResponse(['message' => 'Stok tidak mencukupi permintaan', $jumlah, $sisaStok], 413);
            }
            $masuk = $jumlah;
            // pengecekan stok FIFO
            do {
                $ada = $stok[$index]->sisa_stok;

                // return new JsonResponse(['message' => 'Stok tidak mencukupi permintaan'], 413);
                if ($ada < $masuk) {
                    $sisa = $masuk - $ada;
                    RecentStokUpdate::create([
                        'kode_rs' => $key->kode_rs,
                        'kode_ruang' => $data->kode_depo,
                        'sisa_stok' => $ada,
                        'harga' => $stok[$index]->harga,
                        'no_penerimaan' => $stok[$index]->no_penerimaan,
                    ]);
                    $stok[$index]->update([
                        'sisa_stok' => 0
                    ]);
                    $data->details()->update(['no_penerimaan' => $stok[$index]->no_penerimaan]);

                    $index = $index + 1;
                    $masuk = $sisa;
                    $loop = true;
                } else {
                    $sisa = $ada - $masuk;

                    RecentStokUpdate::create([
                        'kode_rs' => $key->kode_rs,
                        'kode_ruang' => $data->kode_depo,
                        'sisa_stok' => $masuk,
                        'harga' => $stok[$index]->harga,
                        'no_penerimaan' => $stok[$index]->no_penerimaan,
                    ]);

                    $stok[$index]->update([
                        'sisa_stok' => $sisa
                    ]);

                    $data->details()->update(['no_penerimaan' => $stok[$index]->no_penerimaan]);
                    $loop = false;
                }
            } while ($loop);


            // $stok = RecentStokUpdate::where('kode_ruang', 'Gd-02010100')
            //     ->where('kode_rs', $key->kode_rs)
            //     ->where('no_penerimaan', $data->no_penerimaan)
            //     ->first();
            // $diStok = $stok->sisa_stok;
            // $jumlah = $key->jumlah;
            // if ($diStok > $jumlah) {
            //     $sisa = $diStok - $jumlah;
            //     $stok->update([
            //         'sisa_stok' => $sisa
            //     ]);
            //     RecentStokUpdate::create([
            //         'kode_rs' => $key->kode_rs,
            //         'sisa_stok' => $key->jumlah,
            //         'harga' => $stok->harga,
            //         'no_penerimaan' => $data->no_penerimaan,
            //     ]);
            // } else {
            //     // cari sisa pengurangan
            //     $sisaKurang = $jumlah - $diStok;
            //     //kurangi stok lama
            //     $stok->update([
            //         'sisa_stok' => 0
            //     ]);
            //     // buat update dengan nomor terkait
            //     RecentStokUpdate::create([
            //         'kode_rs' => $key->kode_rs,
            //         'sisa_stok' => $key->$diStok,
            //         'harga' => $stok->harga,
            //         'no_penerimaan' => $data->no_penerimaan,
            //     ]);
            //     // ambil stok baru
            //     $stok2 = RecentStokUpdate::where('kode_ruang', 'Gd-02010100')
            //         ->where('kode_rs', $key->kode_rs)
            //         ->where('sisa_stok', '>', 0)
            //         ->first();
            //     // hitung dengan stok yang baru
            //     $diStok2 = $stok2->sisa_stok;
            //     $sisa2 = $diStok2 - $sisaKurang;
            //     $stok2->update([
            //         'sisa_stok' => $sisa2
            //     ]);
            //     RecentStokUpdate::create([
            //         'kode_rs' => $key->kode_rs,
            //         'sisa_stok' => $key->$diStok2,
            //         'harga' => $stok2->harga,
            //         'no_penerimaan' => $stok2->no_penerimaan,
            //     ]);
            // }
        }

        $data->update([
            'tanggal' => $tanggal,
            'status' => 2,
        ]);
        if (!$data->wasChanged()) {
            return new JsonResponse(['message' => 'data gagal diterima'], 500);
        }
        return new JsonResponse(['message' => 'data telah diterima'], 200);
    }
}
