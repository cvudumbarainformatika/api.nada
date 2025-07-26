<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Ruangan;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Ruangan\PemakaianH;
use App\Models\Simrs\Penunjang\Farmasinew\Ruangan\PemakaianR;
use App\Models\Simrs\Penunjang\Farmasinew\Stokreal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PemakaianRuanganController extends Controller
{
    //
    public function getStokRuangan()
    {
        $kode = null;
        if (request('q') !== null) {
            $raw = Mobatnew::select('kd_obat')
                ->where('kd_obat', 'LIKE', '%' . request('q') . '%')
                ->orWhere('nama_obat', 'LIKE', '%' . request('q') . '%')
                ->get();
            $it = collect($raw);
            $kode = $it->map(function ($item) {
                return $item->kd_obat;
            });
        }
        $obat = Stokreal::selectRaw('*, sum(jumlah) as stok')
            ->with('obat:kd_obat,nama_obat,satuan_k', 'ruang:kode,uraian')
            ->where('jumlah', '>', 0)
            ->where('kdruang', request('kdruang'))
            ->when(request('q'), function ($query) use ($kode) {
                $query->whereIn('kdobat', $kode);
            })
            ->groupBy('kdobat', 'kdruang')
            ->paginate(request('per_page'));

        // return new JsonResponse(['ko' => $kode, 'ob' => $obat]);
        return new JsonResponse($obat);
    }

    public function getPemakaianRuangan()
    {
        $data = PemakaianH::with([
            'rinci' => function ($rin) {
                $rin->with('obat')
                    ->selectRaw('*, sum(jumlah) as total')
                    ->groupBy('nopemakaian', 'kd_obat');
            },
            'ruangan'
        ])
            ->when(request('flag'), function ($q) {
                $flag = request('flag');
                $anu = [];
                foreach ($flag as $key) {
                    $anu[] = $key ?? '';
                }
                $q->whereIn('flag', $anu);
            })
            ->when(request('kdruang'), function ($q) {
                $q->where('kdruang', request('kdruang'));
            })
            ->whereBetween('tgl', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
            ->paginate(request('per_page'));

        return new JsonResponse($data);
    }
    public function simpanpemaikaianruangan(Request $request)
    {
        // return new JsonResponse($request->all());
        try {
            DB::beginTransaction();

            if (!$request->nopemakaian) {
                DB::connection('farmasi')->select('call pemakaianruangan(@nomor)');
                $x = DB::connection('farmasi')->table('conter')->select('pemakaianruangan')->get();
                $wew = $x[0]->pemakaianruangan;
                $pemakaianruangan = FormatingHelper::nopemakaianruangan($wew, 'RUA-FAR');
            } else {
                $pemakaianruangan = $request->nopemakaian;
            }
            $user = FormatingHelper::session_user();
            $kode = $user['kodesimrs'];
            // header
            $header = [
                'nopemakaian' => $pemakaianruangan,
                'tgl' => $request->tgl ? $request->tgl . date(' H:i:s') : date('Y-m-d H:i:s'),
                'kdruang' => $request->kdruang,
                'user' => $kode,
            ];

            // rinci
            $rinci = [];
            foreach ($request->obats as $rin) {
                if ((float)$rin['dipakai'] > 0) {
                    $stok = Stokreal::where('kdobat', $rin['kdobat'])
                        ->where('kdruang', $request->kdruang)
                        ->where('jumlah', '>', 0)
                        ->orderBy('tglexp', 'ASC')
                        ->get();
                    $index = 0;
                    $dipakai = (float)$rin['dipakai'];
                    while ($dipakai > 0) {
                        $ada = (float)$stok[$index]->jumlah;
                        if ($ada < $dipakai) {
                            $temp = [
                                'nopemakaian' => $pemakaianruangan,
                                'kd_obat' => $rin['kdobat'],
                                'nopenerimaan' => $stok[$index]->nopenerimaan,
                                'nobatch' => $stok[$index]->nobatch,
                                'nodistribusi' => $stok[$index]->nodistribusi,
                                'jumlah' => $ada,
                                'flag' => '',
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s')
                            ];
                            $rinci[] = $temp;
                            $sisa = $dipakai - $ada;
                            $index += 1;
                            $dipakai = $sisa;
                        } else {
                            $temp = [
                                'nopemakaian' => $pemakaianruangan,
                                'kd_obat' => $rin['kdobat'],
                                'nopenerimaan' => $stok[$index]->nopenerimaan,
                                'nobatch' => $stok[$index]->nobatch,
                                'nodistribusi' => $stok[$index]->nodistribusi,
                                'jumlah' => $dipakai,
                                'flag' => '',
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s')
                            ];
                            $rinci[] = $temp;
                            $dipakai = 0;
                            $sisa = $ada - $dipakai;
                        }
                    }
                }
            }
            //simpan header
            $simpahHead = PemakaianH::firstOrCreate(['nopemakaian' => $pemakaianruangan], $header);
            // if (!$simpahHead) {
            //     return new JsonResponse(['message' => 'Header Data Gagal Disimpan'], 410);
            // }
            // simpan rinci
            $simpanRinci = PemakaianR::insert($rinci);
            // update stok
            $rinc = PemakaianR::where('nopemakaian', $pemakaianruangan)->where('flag', '')->get();
            $st = [];
            $ss = [];
            foreach ($rinc as $rin) {
                $stok = Stokreal::where('kdobat', $rin['kd_obat'])
                    ->where('kdruang', $request->kdruang)
                    ->where('nopenerimaan', $rin['nopenerimaan'])
                    ->where('nodistribusi', $rin['nodistribusi'])
                    ->where('jumlah', '>', 0)
                    ->first();
                $st[] = $stok;
                if ($stok->jumlah > 0) {

                    $sisa = $stok->jumlah - $rin['jumlah'];
                    $ss[] = ['sisa' => $sisa, 'stok' => $stok->jumlah, 'dipakai' => $rin['jumlah']];
                    $stok->jumlah = $sisa;
                    $stok->save();
                    $rin['flag'] = '1';
                    $rin->save();
                }
            }
            DB::commit();
            return new JsonResponse([
                'head' => $simpahHead,
                'rinci' => $simpanRinci,
                'message' => 'Pemakaian Disimpan, stok berkurang. '
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return new JsonResponse([
                'message' => 'Data Gagal Disimpan...!!!',
                'result' => 'err' . $e,
                'rinc' => $rinc ?? 'tidak ada',
                'st' => $st ?? 'tidak ada',
                'ss' => $ss ?? 'tidak ada',
            ], 410);
        }
    }

    public function selesaiPakai(Request $request)
    {
        // return new JsonResponse($request->all());
        $data = PemakaianH::where('nopemakaian', $request->nopemakaian)->first();
        $data->flag = '1';
        $data->save();
        return new JsonResponse([
            'message' => 'Data sudah dikunci.',
            'data' => $data
        ]);
    }
    public function hapusHeader(Request $request)
    {
        $head = PemakaianH::where('nopemakaian', $request->nopemakaian)->first();
        if (!$head) {
            return new JsonResponse(['message' => 'Data tidak ditermukan'], 410);
        }
        $rinci = PemakaianR::where('nopemakaian', $request->nopemakaian)->where('flag', '1')->get();
        $stok = [];
        if (count($rinci)) {
            foreach ($rinci as $rinc) {
                $temp = Stokreal::where('kdruang', $head->kdruang)
                    ->where('kdobat', $rinc['kd_obat'])
                    ->where('nopenerimaan', $rinc['nopenerimaan'])
                    ->first();
                $total = (float)$temp->jumlah + (float) $rinc['jumlah'];
                $temp->jumlah = $total;
                $temp->save();
                $stok[] = $temp;
                $rinc->delete();
            }
        }
        $head->delete();
        return new JsonResponse([
            'head' => $head,
            'rinci' => $rinci,
            'stok' => $stok,
            'message' => 'Data berhasil di hapus, Stok sudah dikembalikan'
        ]);
    }
    public function hapusRinci(Request $request)
    {
        $rinci = PemakaianR::where('nopemakaian', $request->nopemakaian)
            ->where('kd_obat', $request->kd_obat)
            ->where('flag', '1')
            ->get();
        if (count($rinci)) {
            $head = PemakaianH::where('nopemakaian', $request->nopemakaian)->first();
            $stok = [];
            foreach ($rinci as $rinc) {
                $temp = Stokreal::where('kdruang', $head->kdruang)
                    ->where('kdobat', $rinc['kd_obat'])
                    ->where('nopenerimaan', $rinc['nopenerimaan'])
                    ->first();
                $total = (float)$temp->jumlah + (float) $rinc['jumlah'];
                $temp->jumlah = $total;
                $temp->save();
                $stok[] = $temp;
                $rinc->delete();
            }
            $adaRinci = PemakaianR::where('nopemakaian', $request->nopemakaian)->where('flag', '1')->get();
            if (count($adaRinci) <= 0) {
                $head->delete();
            }
            return new JsonResponse([
                'head' => $head,
                'rinci' => $rinci,
                'adaRinci' => $adaRinci,
                'stok' => $stok,
                'message' => 'Data berhasil di hapus, Stok sudah dikembalikan'
            ]);
        }
        return new JsonResponse(['message' => 'Data tidak ditemukan'], 410);
    }
}
