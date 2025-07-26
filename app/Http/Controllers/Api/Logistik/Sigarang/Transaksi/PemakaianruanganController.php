<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Sigarang\PenggunaRuang;
use App\Models\Sigarang\RecentStokUpdate;
use App\Models\Sigarang\Transaksi\Pemakaianruangan\DetailsPemakaianruangan;
use App\Models\Sigarang\Transaksi\Pemakaianruangan\Pemakaianruangan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PemakaianruanganController extends Controller
{
    //ambil data barang dan penanggungjawab ruangan
    public function allData()
    {
        $pengguna = PenggunaRuang::with('ruang', 'pengguna', 'penanggungjawab')
            ->get();
        $temp = collect($pengguna);
        $apem = $temp->map(function ($item, $key) {
            if ($item->kode_penanggungjawab === null || $item->kode_penanggungjawab === '') {
                $item->kode_penanggungjawab = $item->kode_pengguna;
            }
            return $item;
        });

        $apem->all();
        $group = $apem->groupBy('kode_penanggungjawab');

        $rawStok = RecentStokUpdate::selectRaw('* , sum(sisa_stok) as stok')
            ->groupBy('kode_rs', 'kode_ruang')
            ->where('kode_ruang', 'LIKE', 'R-' . '%')
            ->get();

        $data['penanggungjawab'] = $group;
        $data['stok'] = $rawStok;

        return new JsonResponse($data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'reff' => 'required',
            'kode_penanggungjawab' => 'required',
            'kode_pengguna' => 'required',
            'tanggal' => 'required',
        ]);
        try {
            DB::beginTransaction();
            $user = auth()->user();
            $pegawai = Pegawai::find($user->pegawai_id);
            // $masuk = $request->all();
            // $request['kode_ruang'] = $pegawai->kode_ruang;
            $pakai = Pemakaianruangan::updateOrCreate(['id' => $request->id], $request->all());

            if ($request->details) {
                foreach ($request->details as $key) {
                    $pakai->details()->updateOrCreate(
                        [
                            'kode_rs' => $key['kode_rs'],
                            'no_penerimaan' => $key['no_penerimaan']
                        ],
                        $key
                    );
                    $recentStok = RecentStokUpdate::where('kode_ruang', $request->kode_ruang)
                        ->where('kode_rs', $key['kode_rs'])
                        ->where('sisa_stok', '>', 0)
                        ->oldest()
                        ->get();
                    $sisaStok = collect($recentStok)->sum('sisa_stok');
                    $jumlah = $key['jumlah'];
                    $index = 0;

                    if ($jumlah > $sisaStok) {
                        return new JsonResponse(['message' => 'Stok tidak mencukupi pemakaian', $jumlah, $sisaStok], 413);
                    }
                    $masuk = $jumlah;
                    do {
                        $ada = $recentStok[$index]->sisa_stok;
                        if ($ada < $masuk) {
                            $sisa = $masuk - $ada;
                            $recentStok[$index]->update([
                                'sisa_stok' => 0
                            ]);
                            $index = $index + 1;
                            $masuk = $sisa;
                            $loop = true;
                        } else {
                            $sisa = $ada - $masuk;
                            $recentStok[$index]->update([
                                'sisa_stok' => $sisa
                            ]);
                            $loop = false;
                        }
                    } while ($loop);
                    // $sisa = $recentStok->sisa_stok - $key['jumlah'];
                    // $recentStok->update([
                    //     'sisa_stok' => $sisa
                    // ]);
                }
            }
            DB::commit();
            if ($pakai->wasRecentlyCreated) {
                $status = 201;
                $pesan = ['message' => 'Pemakaian Ruangan telah disimpan'];
            } else if ($pakai->wasChanged()) {
                $status = 200;
                $pesan = ['message' => 'Pemakaian Ruangan telah diupdate'];
            }
            // else {
            //     $status = 410;
            //     $pesan = ['message' => 'Pemakaian Ruangan gagal dibuat'];
            // }
            return new JsonResponse($pesan, $status);
        } catch (\Exception $e) {
            DB::rollBack();
            return new JsonResponse([
                'message' => 'ada kesalahan',
                'error' => $e
            ], 500);
        }
    }
    public function simpanRusak(Request $request)
    {
        $request->validate([
            'reff' => 'required',
            'kode_pengguna' => 'required',
        ]);
        $tanggal = $request->tanggal !== null ? $request->tanggal : date('Y-m-d H:m:s');
        // return new JsonResponse($request->all(), 500);
        $pakai = Pemakaianruangan::create($request->all());
        $pakai->update(['tanggal' => $request->tanggal]);

        if ($request->details) {
            foreach ($request->details as $key) {
                $pakai->details()->create($key);
            }
        }
        if ($pakai->wasRecentlyCreated) {
            $status = 201;
            $pesan = ['message' => 'Pemakaian Ruangan telah disimpan'];
        } else if ($pakai->wasChanged()) {
            $status = 200;
            $pesan = ['message' => 'Pemakaian Ruangan telah diupdate'];
        } else {
            $status = 500;
            $pesan = ['message' => 'Pemakaian Ruangan gagal dibuat'];
        }
        return new JsonResponse($pesan, $status);
        // } catch (\Exception $e) {
        //         DB::rollBack();
        //         return new JsonResponse([
        //             'message' => 'ada kesalahan',
        //             'error' => $e
        //         ], 500);
        //     }
    }
}
