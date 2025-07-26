<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\BarangRS;
use App\Models\Sigarang\MaxRuangan;
use App\Models\Sigarang\Pegawai;
use App\Models\Sigarang\RecentStokUpdate;
use App\Models\Sigarang\Transaksi\Permintaanruangan\DetailPermintaanruangan;
use App\Models\Sigarang\Transaksi\Permintaanruangan\Permintaanruangan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DistribusiController extends Controller
{

    public function getPermintaanVerified()
    {
        $user = auth()->user();
        $pegawai = Pegawai::find($user->pegawai_id);

        $p = Permintaanruangan::query();
        $td = Permintaanruangan::query();

        if ($pegawai->role_id === 4) {
            $p->where('dari', $pegawai->kode_ruang);
            $td->where('dari', $pegawai->kode_ruang);
        }
        if (request('status') && request('status') !== null) {
            $p->where('status', '=', request('status'));
            $td->where('status', '=', request('status'));
        } else {
            $p->where('status', '>=', 4)
                ->where('status', '<=', 7);
            $td->where('status', '>=', 4)
                ->where('status', '<=', 7);
        }

        // cari data kode rs di detail
        $per = $td->select('id')->paginate(request('per_page'));
        $colId = collect($per)->only('data');
        $perId = $colId['data'];
        $det = DetailPermintaanruangan::select('kode_rs')->whereIn('permintaanruangan_id', $perId)->distinct('kode_rs')->get();

        $data = $p->orderBy(request('order_by'), request('sort'))
            ->with([
                'pj', 'pengguna', 'details' => function ($wew) use ($pegawai, $det) {
                    if ($pegawai->role_id === 4) {
                        $wew->where('dari', $pegawai->kode_ruang);
                    }
                    $wew->select(
                        'detail_permintaanruangans.id',
                        'detail_permintaanruangans.permintaanruangan_id',
                        'detail_permintaanruangans.dari',
                        'detail_permintaanruangans.tujuan',
                        'detail_permintaanruangans.kode_rs',
                        'detail_permintaanruangans.kode_satuan',
                        'detail_permintaanruangans.jumlah',
                        'detail_permintaanruangans.jumlah_disetujui',
                        'detail_permintaanruangans.jumlah_distribusi',
                        'detail_permintaanruangans.alasan',

                    )

                        ->with([
                            'satuan:kode,nama',
                            'ruang:kode,uraian',
                            'maxruangan' => function ($ma) use ($det) {
                                $ma->select(
                                    'kode_rs',
                                    'kode_ruang',
                                    'max_stok',
                                    'minta'
                                )->whereIn('kode_rs', $det);
                            },
                            'sisastok' => function ($s) {
                                $s->select(
                                    'kode_rs',
                                    'kode_ruang',
                                    'sisa_stok',
                                )
                                    ->selectRaw('sum(sisa_stok) as stok_total')
                                    ->where('sisa_stok', '>', 0)
                                    ->groupBy('kode_rs', 'kode_ruang');
                            },
                            'barangrs' => function ($anu) {
                                $anu->select(
                                    'kode',
                                    'nama',
                                    'kode_satuan',
                                    'kode_depo'
                                );
                            }
                        ]);
                }
            ])
            ->filter(request(['q', 'r']))
            ->paginate(request('per_page'));


        foreach ($data as $key) {
            foreach ($key->details as $detail) {
                $detail->append('all_minta');
                $sisastok = collect($detail['sisastok']);

                $stokMe = $sisastok->where('kode_ruang', $detail['tujuan'])->all();
                $stokR = 0;
                foreach ($stokMe as $st) {
                    $stokR = $st->stok_total;
                }

                $stokDe = $sisastok->where('kode_ruang', $detail['barangrs']->kode_depo)->all();
                $stokD = 0;
                foreach ($stokDe as $st) {
                    $stokD = $st->stok_total;
                }

                $maxruangan = collect($detail['maxruangan']);
                $maxRe = $maxruangan->where('kode_rs', $detail['kode_rs'])->all();
                $maxR = 0;
                $mintaR = 0;
                foreach ($maxRe as $st) {
                    $maxR = $st->max_stok;
                    $mintaR = $st->minta;
                }

                $sum = $detail['all_minta'];
                $alokasi = 0;
                if ($stokD >= $sum) {
                    $alokasi =  $stokD - $sum;
                } else {
                    $alokasi = 0;
                }
                $detail['barangrs']->maxStok = $maxR > 0 ? $maxR : $mintaR;
                $detail['barangrs']->stokRuangan = $stokR;
                $detail['barangrs']->stokDepo = $stokD;
                $detail['barangrs']->alokasi = $alokasi;
            }
        }

        // if (count($data)) {
        //     foreach ($data as $key) {
        //         $key->gudang = collect($key->details)->groupBy('dari');
        //     }
        // }
        $collection = collect($data);
        return new JsonResponse([
            'data' => $collection->only('data'),
            'meta' => $collection->except('data'),
        ], 200);
    }
    public function updateDistribusi(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'no_distribusi' => 'required',
        ]);

        $det = DetailPermintaanruangan::where('permintaanruangan_id', $request->id)->get();
        foreach ($det as $key => $detail) {

            if (!$detail['tujuan']) {
                return new JsonResponse(['message' => 'periksa data ruangan yang melakukan permintaan'], 422);
            }

            // check stok masih cukup atau tidak
            $dari = RecentStokUpdate::where('kode_ruang', $detail['dari'])
                ->where('kode_rs', $detail['kode_rs'])
                ->where('sisa_stok', '>', 0)
                ->with('barang')
                ->get();

            $sisaStok = collect($dari)->sum('sisa_stok');
            $disetujui = $detail['jumlah_disetujui'];

            if ($disetujui > 0) {
                if (count($dari) === 0) {
                    $barang = BarangRS::where('kode', $detail['kode_rs'])->first();
                    $pesan = 'stok ' .  $barang->nama . ' tidak ada';
                    $status = 410;

                    return new JsonResponse(['status' => $status, 'message' => $pesan,], 410);
                }

                if ($sisaStok < $disetujui) {
                    $barang = $dari[$key]['barang']['nama'];
                    $pesan = 'stok ' .  $barang . ' tidak mencukupi';
                    $status = 410;

                    return new JsonResponse(['status' => $status, 'message' => $pesan,], 410);
                }
            }
        }
        $permintaanruangan = Permintaanruangan::find($request->id);
        // $permintaanruangan = Permintaanruangan::with('details')->find($request->id);
        // return new JsonResponse($permintaanruangan);
        // $permintaanruangan = Permintaanruangan::find($request->id);
        $temp = PenerimaanruanganController::telahDiDistribusikan($request, $permintaanruangan);
        if ($temp['status'] !== 201) {
            return new JsonResponse($temp, $temp['status']);
        }
        try {

            DB::beginTransaction();

            $tanggal_distribusi = $request->tanggal !== null ? $request->tanggal : date('Y-m-d H:i:s');
            $status = 7;
            // $status = 8;
            // $data = Permintaanruangan::find($request->id);
            $data = $permintaanruangan;
            $data->update([
                'no_distribusi' => $request->no_distribusi,
                'tanggal_distribusi' => $tanggal_distribusi,
                'status' => $status,
            ]);
            foreach ($data->details as $key) {
                // $data->details()->updateOrCreate(['id' => $key['id']], ['jumlah_distribusi' => $key['jumlah_distribusi']]);
                $data->details()->updateOrCreate(['id' => $key['id']], ['jumlah_distribusi' => $key['jumlah_disetujui']]);
            }

            DB::commit();

            if (!$data->wasChanged()) {
                return new JsonResponse(['message' => 'data gagal di update'], 501);
            }
            return new JsonResponse(['message' => 'data berhasi di update'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return new JsonResponse([
                'message' => 'ada kesalahan',
                'error' => $e
            ], 417);
        }
    }
}
