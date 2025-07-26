<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Uang;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PembayaranController extends Controller
{
    //
    public function cariBast()
    {
        $data = PenerimaanHeder::select('nobast')
            ->whereNotNull('tgl_bast')
            ->whereNull('tgl_pembayaran')
            ->distinct('nobast')
            ->orderBy('nobast')
            ->get();
        return new JsonResponse($data);
    }
    public function ambilBast()
    {
        $data = PenerimaanHeder::where('nobast', request('nobast'))
            ->with([
                'faktur',
                'penerimaanrinci:nopenerimaan,subtotal'
            ])
            ->get();
        return new JsonResponse($data);
    }
    public function listPembayaran()
    {
        $res1 = PenerimaanHeder::select('no_kwitansi')
            ->where('no_kwitansi', '<>', '')
            ->distinct('no_kwitansi')
            ->paginate(request('per_page'));

        $col = collect($res1);
        $data = $col['data'];
        $pen = [];
        foreach ($data as $key) {
            $item = current((array)$key);
            $pen[] =  $item;
        }
        $result = PenerimaanHeder::where('no_kwitansi', '<>', '')
            ->whereNotNull('tgl_pembayaran')
            ->with(
                'faktur',
                'penerimaanrinci',
                'pihakketiga',
                'terima:kdpegsimrs,nama',
                'bast:kdpegsimrs,nama',
                'bayar:kdpegsimrs,nama',
            )
            ->whereIn('no_kwitansi', $pen)
            ->orderBy('tgl_pembayaran', 'DESC')
            ->orderBy('no_kwitansi', 'DESC')
            ->get();

        $groupedResult = $result->groupBy('no_kwitansi')->map(function ($group) {
            return $group->map(function ($item) {
                return $item;
            });
        });

        // Convert the result to the desired format
        $formattedResult = $groupedResult->map(function ($items, $kwitansi) {

            return [
                'no_bast' => $kwitansi,
                'totalSemua' =>  $items[0]->total_pembayaran,
                'tanggal' => $items[0]->tgl_pembayaran,
                'nomor' => $items[0]->nopemesanan,
                'terima' => $items[0]->terima,
                'bast' => $items[0]->bast,
                'bayar' => $items[0]->bayar,
                'penyedia' => $items[0]->pihakketiga->nama ?? '',
                'penerimaan' => $items,
            ];
        })->values();
        return new JsonResponse([
            'data' => $formattedResult,
            'meta' => $col->except('data'),
            'res' => $col,
            'dat' => $data,
        ]);
    }
    public function simpan(Request $request)
    {
        $user = FormatingHelper::session_user();
        try {
            DB::connection('farmasi')->beginTransaction();
            foreach ($request->penerimaans as $penerimaan) {
                // simpan header penerimaan
                $terima = PenerimaanHeder::where('nopenerimaan', $penerimaan['nopenerimaan'])->first();
                if ($terima) {
                    $terima->update([
                        'no_kwitansi' => $request->no_kwitansi,
                        'tgl_pembayaran' => $request->tgl_pembayaran,
                        'no_npd' => $request->no_npd,
                        'tgl_pencairan_npk' => $request->tgl_pencairan_npk,
                        'total_pembayaran' => $request->total_pembayaran,
                        'nilai_pembayaran' => $penerimaan['nilai_pembayaran'] ?? 0,
                        'user_bayar' => $user['kodesimrs'],
                    ]);
                }
            }
            DB::connection('farmasi')->commit();

            return new JsonResponse([
                'message' => 'Pembayaran Sudah disimpan'
            ]);
        } catch (\Exception $e) {
            DB::connection('farmasi')->rollBack();
            return response()->json(['message' => 'ada kesalahan', 'error' => $e], 410);
        }
    }
}
