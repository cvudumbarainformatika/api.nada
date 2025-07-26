<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\KontrakPengerjaan;
use App\Models\Sigarang\Supplier;
use App\Models\Sigarang\Transaksi\Penerimaan\Penerimaan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PembayaranController extends Controller
{
    // cari kontrak nya dulu, yang sudah BAST tapi belum dibayar
    public function cariKontrak()
    {
        $data = Penerimaan::selectRaw('kontrak')
            // ->whereNot('tanggal_bast', null)
            // ->where('nilai_tagihan', '>=', 0)
            // ->where(function ($x) {
            //     $x->where('tanggal_bast', '<>', null)
            //         ->orWhere('nilai_tagihan', '>=', 0);
            // })
            ->where('no_bast', '<>', '')
            ->where('no_kwitansi', '')
            ->distinct()->get();

        return new JsonResponse($data);
    }
    // cari penyedianya nya dulu, yang sudah BAST tapi belum dibayar
    public function cariPenyedia()
    {
        $raw = Penerimaan::selectRaw('kode_perusahaan')
            // ->whereNot('tanggal_bast', null)
            // ->where('nilai_tagihan', '>=', 0)
            // ->where(function ($x) {
            //     $x->where('tanggal_bast', '<>', null)
            //         ->orWhere('nilai_tagihan', '>=', 0);
            // })
            ->where('no_bast', '<>', '')
            ->where('no_kwitansi', '')
            ->distinct()->get();
        $col = collect($raw)->map(function ($item) {
            return $item->kode_perusahaan;
        });
        $data = Supplier::select('nama', 'kode')->whereIn('kode', $col)->get();

        return new JsonResponse($data);
    }
    public function cariBast()
    {
        $data = Penerimaan::select('no_bast', 'kontrak')->where('kode_perusahaan', request('kode'))
            ->where('no_bast', '<>', '')
            ->where('no_kwitansi', '')
            ->distinct('no_bast')
            ->with('details_kontrak')
            ->orderBy('no_bast')
            ->get();

        return new JsonResponse($data);
    }
    public function ambilKontrak()
    {
        $data = KontrakPengerjaan::where('nokontrakx', request('kontrak'))
            ->with('penyedia')
            ->first();

        return new JsonResponse($data);
    }
    public function ambilPenerimaanByBast()
    {
        $data = Penerimaan::where('no_bast', request('no_bast'))
            ->whereNotNull('tanggal_bast')
            ->whereNull('tanggal_pembayaran')
            ->with(['details' => function ($anu) {
                $anu->select('uraian_50', 'penerimaan_id')
                    ->distinct('uraian_50');
            }])
            ->get();

        return new JsonResponse($data);
    }
    public function ambilPenerimaan()
    {
        $data = Penerimaan::where('kontrak', request('kontrak'))
            ->whereNotNull('tanggal_bast')
            ->whereNull('tanggal_pembayaran')
            ->with(['details' => function ($anu) {
                $anu->select('uraian_50', 'penerimaan_id')
                    ->distinct('uraian_50');
            }])
            ->get();

        return new JsonResponse($data);
    }
    public function ambilNoBayar()
    {
        $data = Penerimaan::select('kontrak', 'tanggal_bast', 'tanggal_pembayaran')
            ->distinct('tanggal_pembayaran')
            ->where('kontrak', request('kontrak'))
            ->whereNotNull('tanggal_bast')
            ->whereNotNull('tanggal_pembayaran')
            ->count();

        return new JsonResponse($data);
    }

    public function simpanBayar(Request $request)
    {
        $anu = [];
        $id = auth()->user()->pegawai_id;
        foreach ($request->penerimaans as $terima) {
            $temp = Penerimaan::find($terima['id']);
            if ($temp) {
                $temp->update([
                    'nilai_pembayaran' => $terima['nilai_pembayaran'],
                    'no_kwitansi' => $request->no_kwitansi,
                    'no_pembayaran' => $request->no_pembayaran,
                    'tanggal_pembayaran' => $request->tanggal_pembayaran,
                    'pembayaran_by' => $id,
                ]);
                array_push($anu, $temp);
            }
        }
        return new JsonResponse($anu);
    }
    public function listBayar()
    {

        $data = Penerimaan::with('details.satuan', 'perusahaan', 'dibuat', 'dibast', 'dibayar')
            ->whereNotNull('tanggal_bast')
            ->whereNotNull('tanggal_pembayaran')
            ->when(request('q'), function ($query) {
                $query->where('nomor', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('no_penerimaan', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('kontrak', 'LIKE', '%' . request('q') . '%');
            })
            ->orderBy('tanggal_pembayaran', 'desc')
            ->paginate(request('per_page'));

        return new JsonResponse($data);
    }
    public function listBayarByKwitansi()
    {

        $result = Penerimaan::where('no_pembayaran', '<>', '')
            ->with('details')
            ->orderBy('no_pembayaran')
            ->get();

        $groupedResult = $result->groupBy('no_pembayaran')->map(function ($group) {
            return $group->map(function ($item) {
                return $item;
            });
        });

        // Convert the result to the desired format
        $formattedResult = $groupedResult->map(function ($items, $kwitansi) {
            $total = $items->sum('total');
            return [
                'kwitansi' => $kwitansi,
                'totalSemua' => $total,
                'penerimaan' => $items,
            ];
        })->values();

        return new JsonResponse($formattedResult);
    }
}
