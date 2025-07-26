<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\KontrakPengerjaan;
use App\Models\Sigarang\MonthlyStokUpdate;
use App\Models\Sigarang\Pegawai;
use App\Models\Sigarang\RecentStokUpdate;
use App\Models\Sigarang\Transaksi\Pemesanan\Pemesanan;
use App\Models\Sigarang\Transaksi\Penerimaan\DetailPenerimaan;
use App\Models\Sigarang\Transaksi\Penerimaan\Penerimaan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BastController extends Controller
{
    public function cariPerusahaan()
    {
        $user = auth()->user();
        $pegawai = Pegawai::find($user->pegawai_id);
        // ambil data kode perusahaan, masing2 satu aja
        $raw = Penerimaan::select(
            'penerimaans.kode_perusahaan'
        )
            ->leftJoin('pemesanans', function ($q) {
                $q->on('pemesanans.nomor', '=', 'penerimaans.nomor');
            })
            ->when($pegawai->role_id !== 1, function ($q) use ($user) {
                $q->where('pemesanans.created_by', $user->pegawai_id);
            })
            ->where(function ($q) {
                $q->where('penerimaans.tanggal_bast', null)
                    ->orWhere('penerimaans.nilai_tagihan', '<=', 0);
            })
            ->distinct()->get();

        // map ke bentuk array
        $temp = collect($raw)->map(function ($y) {
            return $y->kode_perusahaan;
        });

        // ambil data perusahaan tsh, cuma butuh nama dan kode perusahaan saja. masing2 perusahaan cuma butuh satu.
        $data = KontrakPengerjaan::select('kodeperusahaan', 'namaperusahaan')->whereIn('kodeperusahaan', $temp)->distinct()->get();

        return new JsonResponse($data);
    }

    public function cariKontrak()
    {
        $user = auth()->user();
        $pegawai = Pegawai::find($user->pegawai_id);
        $data = Penerimaan::select('penerimaans.kontrak')
            ->leftJoin('pemesanans', function ($q) {
                $q->on('pemesanans.nomor', '=', 'penerimaans.nomor');
            })
            ->when($pegawai->role_id !== 1, function ($q) use ($user) {
                $q->where('pemesanans.created_by', $user->pegawai_id);
            })
            ->where('pemesanans.kode_perusahaan', request('kode_perusahaan'))
            ->where(function ($a) {
                $a->whereNull('penerimaans.tanggal_bast')
                    ->orWhere('penerimaans.nilai_tagihan', '<=', 0);
            })
            ->distinct('penerimaans.kontrak')
            ->get();

        return new JsonResponse($data);
        // $anu['raw'] = $raw;
        // return new JsonResponse($anu);
    }
    // public function cariPemesanan()
    // {
    //     $data = Penerimaan::select('kontrak')
    //         ->where('kode_perusahaan', request('kode_perusahaan'))
    //         ->where(function ($a) {
    //             $a->where('tanggal_bast', null)
    //                 ->orWhere('nilai_tagihan', '<=', 0);
    //         })
    //         ->distinct('kontrak')
    //         ->get();

    //     return new JsonResponse($data);
    //     // $anu['raw'] = $raw;
    //     // return new JsonResponse($anu);
    // }

    public function ambilPemesanan()
    {
        $data = Pemesanan::where('kontrak', request('kontrak'))
            ->where('kode_perusahaan', request('kode_perusahaan'))
            ->with([
                'details',
                'penerimaan' => function ($anu) {
                    $anu->with('details')->whereNull('tanggal_bast')
                        ->orWhere('nilai_tagihan', '<=', 0);
                }
            ])
            ->get();

        return new JsonResponse($data);
    }

    public function simpanBast(Request $request)
    {
        $id = auth()->user()->pegawai_id;
        try {
            DB::beginTransaction();
            $berubah = [];
            foreach ($request->penerimaans as $terima) {
                foreach ($terima as $penerimaan) {
                    $data = Penerimaan::find($penerimaan['id']);
                    $data->update([
                        'no_bast' => $request->no_bast,
                        'tanggal_bast' => $request->tanggal_bast,
                        'nilai_tagihan' => $penerimaan['nilai_tagihan'],
                        'total' => $penerimaan['nilai_tagihan'],
                        'faktur' => $penerimaan['faktur'],
                        'bast_by' => $id,
                    ]);
                    foreach ($penerimaan['details'] as $det) {
                        $detail = DetailPenerimaan::find($det['id']);
                        $detail->update([
                            'diskon' => $det['diskon'],
                            'harga_kontrak' => $det['harga_kontrak'],
                            'harga_jadi' => $det['harga_jadi'],
                            'ppn' => $det['ppn'],
                            'sub_total' => $det['sub_total'],
                        ]);
                        $stok = RecentStokUpdate::where('no_penerimaan', $penerimaan['no_penerimaan'])
                            ->where('kode_rs', $detail['kode_rs'])
                            ->get();
                        if (count($stok) >= 0) {
                            foreach ($stok as $key) {
                                $key->update(['harga' => $det['harga_jadi']]);
                            }
                        }
                        $month = MonthlyStokUpdate::where('no_penerimaan', $penerimaan['no_penerimaan'])
                            ->where('kode_rs', $detail['kode_rs'])
                            ->get();
                        if (count($month) >= 0) {
                            foreach ($month as $key) {
                                $key->update(['harga' => $det['harga_jadi']]);
                            }
                        }
                    }
                    if ($data->wasChanged()) {
                        array_push($berubah, $data);
                    }
                }
            }
            DB::commit();
            if (count($berubah) > 0) {
                return new JsonResponse(['message' => 'data Sudah di update', 'data' => $berubah], 200);
            }
            return new JsonResponse(['message' => 'data tidak berubah', 'data' => $berubah], 410);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'ada kesalahan', 'error' => $e], 500);
        }
    }
    public function listBast()
    {

        $data = Penerimaan::with('details.satuan', 'perusahaan', 'dibuat', 'dibast', 'dibayar')
            ->whereNotNull('tanggal_bast')
            ->whereNull('tanggal_pembayaran')
            ->when(request('q'), function ($query) {
                $query->where('nomor', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('no_penerimaan', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('kontrak', 'LIKE', '%' . request('q') . '%');
            })
            ->orderBy('tanggal_bast', 'desc')
            ->paginate(request('per_page'));

        return new JsonResponse($data);
    }
    public function listBastByKwitansi()
    {

        $res1 = Penerimaan::select('no_bast')
            ->distinct()
            ->where('no_bast', '<>', '')
            ->orderBy('tanggal_bast', 'DESC')
            // ->get();
            ->paginate(request('per_page'));

        $col = collect($res1);
        $data = $col['data'];

        $result = Penerimaan::where('no_bast', '<>', '')
            // ->whereNull('tanggal_pembayaran')
            ->with('details.satuan', 'perusahaan', 'dibuat', 'dibast', 'dibayar')
            ->whereIn('no_bast', $data)
            ->orderBy('tanggal_bast', 'DESC')
            ->get();
        // ->paginate(request('per_page'));

        $groupedResult = $result->groupBy('no_bast')->map(function ($group) {
            return $group->map(function ($item) {
                return $item;
            });
        });

        // Convert the result to the desired format
        $formattedResult = $groupedResult->map(function ($items, $kwitansi) {
            $total = $items->sum('total');
            return [
                'no_bast' => $kwitansi,
                'totalSemua' => $total,
                'tanggal' => $items[0]->tanggal_bast,
                'dibuat' => $items[0]->dibuat,
                'dibast' => $items[0]->dibast,
                'nomor' => $items[0]->nomor,
                'penyedia' => $items[0]->perusahaan->nama ?? '',
                'penerimaan' => $items,
            ];
        })->values();
        $anu = collect($result);
        return new JsonResponse([
            'data' => $formattedResult,
            'meta' => $col->except('data'),
            'res' => $col,
            'dat' => $data,
        ]);
    }
    public function jumlahNomorBast()
    {
        // $data = penerimaan::where('nomor', request('nomor'))->get();
        $data = Penerimaan::selectRaw('kontrak, no_bast')
            ->where('kontrak', request('kontrak'))
            ->where('no_bast', '!=', '')
            ->distinct('no_bast')
            ->count();

        return new JsonResponse($data);
        // return new JsonResponse(['jumlah' => $data]);
    }
}
