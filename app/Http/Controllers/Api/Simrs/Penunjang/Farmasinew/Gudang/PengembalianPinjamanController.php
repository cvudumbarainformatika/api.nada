<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Gudang;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mpihakketiga;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanRinci;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\Pengembalian;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PengembalianRinci;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PengembalianRinciFifo;
use App\Models\Simrs\Penunjang\Farmasinew\Stokreal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PengembalianPinjamanController extends Controller
{
    /**
     * Form Sections
     */
    public function getPbfPeminjam()
    {
        // ini nanti di moodif cari yang pinjaman nya belum di kembalikan
        $kode = PenerimaanHeder::select('kdpbf')
            ->where('jenis_penerimaan', 'Pinjaman')
            ->where('kunci', '1')
            ->where('flag_bayar', '')
            ->distinct()->pluck('kdpbf');

        $pihaktiga = Mpihakketiga::select('nama', 'kode')->whereIn('kode', $kode)->get();
        return new JsonResponse([
            "data" => $pihaktiga,
            'req' => request()->all(),
        ]);
    }
    public function getNopenerimaan()
    {
        // ini nanti di moodif cari yang pinjaman nya belum di kembalikan

        $data = PenerimaanHeder::select('nopenerimaan')
            ->where('jenis_penerimaan', 'Pinjaman')
            ->where('kdpbf', request('kdpbf'))
            ->with([
                'penerimaanrinci:id as id_rincipenerimaan,nopenerimaan,nopenerimaan as nopenerimaan_asal,harga_netto_kecil as harga,no_batch,kdobat,jml_terima_k',
                'penerimaanrinci.masterobat:kd_obat,nama_obat,satuan_k',
                'penerimaanrinci.pengembalian_rinci',
            ])
            ->where('kunci', '1')
            ->where('flag_bayar', '')
            ->get();
        return new JsonResponse([
            'data' => $data,
            'req' => request()->all(),
        ]);
    }
    public function simpan(Request $request)
    {
        try {
            DB::connection('farmasi')->beginTransaction();
            if (!$request->nopengembalian) {
                DB::connection('farmasi')->select('call pengembalian(@nomor)');
                $x = DB::connection('farmasi')->table('conter')->select('pengembalian')->first();
                $wew = $x->pengembalian;

                $nopengembalian = FormatingHelper::pengembalian($wew);
            } else {
                $nopengembalian = $request->nopengembalian;
            }
            $header = Pengembalian::updateOrCreate(
                [
                    'nopengembalian' => $nopengembalian,
                    'nopenerimaan_asal' => $request->nopenerimaan_asal,
                ],
                [
                    'kdpbf' => $request->kdpbf,
                    'kdruang' => $request->kdruang,
                    'tgl_pengembalian' => $request->tgl_pengembalian,
                ]
            );
            if (!$header) {
                DB::connection('farmasi')->rollBack();
                return new JsonResponse([
                    'message' => 'Data Gagal Disimpan',
                    'req' => $request->all(),
                ], 410);
            }
            $detail = PengembalianRinci::updateOrCreate(
                [
                    'nopengembalian' => $nopengembalian,
                    'nopenerimaan_asal' => $request->nopenerimaan_asal,
                    'kdobat' => $request->kdobat,
                ],
                [
                    'id_rincipenerimaan' => $request->id_rincipenerimaan,
                    'jml_dikembalikan' => $request->jml_dikembalikan,
                    'harga' => $request->harga,
                ]
            );
            if (!$detail) {
                DB::connection('farmasi')->rollBack();
                return new JsonResponse([
                    'message' => 'Data Gagal Disimpan',
                    'req' => $request->all(),
                ], 410);
            }
            $penerimaanRinci = PenerimaanRinci::select(
                'id as id_rincipenerimaan',
                'nopenerimaan as nopenerimaan_asal',
                'nopenerimaan',
                'harga_netto_kecil as harga',
                'no_batch',
                'kdobat',
                'jml_terima_k'
            )
                ->with('pengembalian_rinci', 'masterobat:kd_obat,nama_obat,satuan_k')
                ->find($request->id_rincipenerimaan);

            DB::connection('farmasi')->commit();
            return new JsonResponse([
                'message' => 'Data Berahasil Disimpan',
                'nopengembalian' => $nopengembalian,
                'penerimaanrinci' => $penerimaanRinci,
                'detail' => $detail,
                'req' => $request->all(),
            ]);
        } catch (\Throwable $th) {
            DB::connection('farmasi')->rollBack();
            return new JsonResponse([
                'message' => 'Data Gagal Disimpan ' . $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'req' => $request->all(),
            ], 410);
        }
    }

    /**
     * List Sections
     */
    public function getList()
    {
        $raw = Pengembalian::with([
            'rincian' => function ($query) {
                $query->with([
                    'masterobat' => function ($q) {
                        $q->select('kd_obat', 'nama_obat', 'satuan_k')
                            ->with([
                                'onepermintaandeporinci' => function ($q) {
                                    $q->select(
                                        'permintaan_r.no_permintaan',
                                        'permintaan_r.kdobat',
                                        'permintaan_h.tujuan as kdruang',
                                        DB::raw('sum(permintaan_r.jumlah_minta) as jumlah_minta')
                                    )
                                        ->leftJoin('permintaan_h', 'permintaan_h.no_permintaan', '=', 'permintaan_r.no_permintaan')
                                        ->leftJoin('mutasi_gudangdepo', function ($anu) {
                                            $anu->on('permintaan_r.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                                                ->on('permintaan_r.kdobat', '=', 'mutasi_gudangdepo.kd_obat');
                                        })
                                        ->where('permintaan_h.tujuan', request('kdruang'))
                                        ->whereIn('permintaan_h.flag', ['', '1', '2'])
                                        ->groupBy('permintaan_r.kdobat');
                                },
                            ]);
                    },
                    'stok' => function ($q) {
                        $q->where('kdruang', request('kdruang'))
                            ->where('jumlah', '>', 0);
                    }
                ]);
            },
            'rincian_fifo',
            'pihakketiga:kode,nama',
        ])
            ->where(function ($query) {
                $query->where('nopengembalian', 'like', '%' . request('q') . '%')
                    ->orWhere('nopenerimaan_asal', 'like', '%' . request('q') . '%');
                // ->orWhere('nopengembalian', 'like', '%' . request('q') . '%');
            })
            ->whereBetween('tgl_pengembalian', [request('from') . ' 00:00:00', request('to') . ' 23:59:59'])
            ->where('kdruang', request('kdruang'))
            ->paginate(request('per_page'));
        $data['data'] = collect($raw)['data'];
        $data['meta'] = collect($raw)->except('data');
        $data['req'] = request()->all();

        return new JsonResponse($data);
    }

    public function kunci(Request $request)
    {
        // jika sudah dikembalikan semua makan isi flag_bayar, sehingga nanti tidak muncul lagi di form
        // sebelum kunci cek stok alokasi dulu. kalo ada alokasi bisa ya lanjut kunci
        // cek alokasi
        $rawobat = Mobatnew::query()
            ->select(
                'new_masterobat.kd_obat',
                DB::raw('SUM(
                CASE When stokreal.kdruang="' . request('kdruang') . '" AND stokreal.kdobat = new_masterobat.kd_obat Then stokreal.jumlah Else 0 End )
                 as total'),
            )
            ->leftjoin('stokreal', 'new_masterobat.kd_obat', '=', 'stokreal.kdobat')
            ->whereIn('kd_obat', $request->kdobat)
            ->with([
                'onepermintaandeporinci' => function ($q) {
                    $q->select(
                        'permintaan_r.no_permintaan',
                        'permintaan_r.kdobat',
                        'permintaan_h.tujuan as kdruang',
                        DB::raw('sum(permintaan_r.jumlah_minta) as jumlah_minta')
                    )
                        ->leftJoin('permintaan_h', 'permintaan_h.no_permintaan', '=', 'permintaan_r.no_permintaan')
                        ->leftJoin('mutasi_gudangdepo', function ($anu) {
                            $anu->on('permintaan_r.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                                ->on('permintaan_r.kdobat', '=', 'mutasi_gudangdepo.kd_obat');
                        })
                        ->where('permintaan_h.tujuan', request('kdruang'))
                        ->whereIn('permintaan_h.flag', ['', '1', '2'])
                        ->groupBy('permintaan_r.kdobat');
                },
            ])
            ->groupBy('new_masterobat.kd_obat')
            ->get();
        $obat = collect($rawobat)->map(function ($x, $y) {
            $total = $x->total ?? 0;
            $permintaanobatrinci = $x['onepermintaandeporinci']->jumlah_minta ?? 0; // mutasi antar depo

            $alokasi = (float) $total  - (float)$permintaanobatrinci;
            $x->alokasi = $alokasi <= 0 ? 0 : $alokasi;
            return $x;
        });

        $header = Pengembalian::find($request->id);
        if (!$header) {
            return new JsonResponse(['message' => 'Data tidak ditemukan, gagal kunci'], 410);
        }
        $rinci = PengembalianRinci::where('nopengembalian', $header->nopengembalian)->get();
        if (sizeof($rinci) <= 0) {
            return new JsonResponse(['message' => 'Data Rincian tidak ditemukan, gagal kunci'], 410);
        }
        try {
            DB::connection('farmasi')->beginTransaction();

            // keluarkan obat dari stok sesuai fifo
            $stok = [];
            foreach ($rinci as $key) {

                $jumlahDikembalikan = $key->jml_dikembalikan;
                $caristok = Stokreal::lockForUpdate()
                    ->where('kdobat', $key->kdobat)
                    ->where('kdruang', $request->kdruang)
                    ->where('jumlah', '>', 0)
                    ->orderBy('tglpenerimaan', 'ASC')
                    ->get();
                foreach ($caristok as $stokItem) {
                    if ($jumlahDikembalikan <= 0) break; // keluar dari loop jika jumlah sudah cukup
                    $sisa = $stokItem->jumlah;

                    $pengurangan = min($jumlahDikembalikan, $sisa);

                    $rincianFifo = PengembalianRinciFifo::updateOrCreate(
                        [
                            'nopengembalian' => $key->nopengembalian,
                            'kdobat' => $key->kdobat,
                        ],
                        [
                            'id_rincipenerimaan' => $key->id_rincipenerimaan,
                            'nopenerimaan' => $stokItem->nopenerimaan,
                            'jml_dikembalikan' => $pengurangan,
                            'harga' => $stokItem->harga,
                        ]
                    );
                    // Update jumlah stok pada item
                    $stokItem->decrement('jumlah', $pengurangan); // langsung update jumlah dalam satu langkah
                    $jumlahDikembalikan -= $pengurangan;
                }
                $stok[] = [
                    'jumlahDikembalikan' => $jumlahDikembalikan,
                    'rincianFifo' => $rincianFifo,
                    'stok' => $caristok
                ];
            }

            // // kasih flag header dan tgl_kunci
            $header->update([
                'flag' => '1',
                'tgl_kunci' => date('Y-m-d H:i:s'),
            ]);
            $header->load([
                'rincian' => function ($query) {
                    $query->with([
                        'masterobat' => function ($q) {
                            $q->select('kd_obat', 'nama_obat', 'satuan_k')
                                ->with([
                                    'onepermintaandeporinci' => function ($q) {
                                        $q->select(
                                            'permintaan_r.no_permintaan',
                                            'permintaan_r.kdobat',
                                            'permintaan_h.tujuan as kdruang',
                                            DB::raw('sum(permintaan_r.jumlah_minta) as jumlah_minta')
                                        )
                                            ->leftJoin('permintaan_h', 'permintaan_h.no_permintaan', '=', 'permintaan_r.no_permintaan')
                                            ->leftJoin('mutasi_gudangdepo', function ($anu) {
                                                $anu->on('permintaan_r.no_permintaan', '=', 'mutasi_gudangdepo.no_permintaan')
                                                    ->on('permintaan_r.kdobat', '=', 'mutasi_gudangdepo.kd_obat');
                                            })
                                            ->where('permintaan_h.tujuan', request('kdruang'))
                                            ->whereIn('permintaan_h.flag', ['', '1', '2'])
                                            ->groupBy('permintaan_r.kdobat');
                                    },
                                ]);
                        },
                        'stok' => function ($q) {
                            $q->where('kdruang', request('kdruang'))
                                ->where('jumlah', '>', 0);
                        }
                    ]);
                },
                'rincian_fifo',
                'pihakketiga:kode,nama',
            ]);
            // cek penerimaan sudah dikembalikan apa belum jika sudah selesai dikembalikan semua isi flag bayar dengan 1
            $allBack = true;
            $penerimaanR = PenerimaanRinci::where('nopenerimaan', $header->nopenerimaan_asal)->get();
            foreach ($penerimaanR as $key) {
                $kem = PengembalianRinciFifo::selectRaw('sum(jml_dikembalikan) as jumlah')->where('id_rincipenerimaan', $key->id)->first();
                if ($kem->jumlah != $key->jml_terima_k) {
                    $allBack = false;
                }
            }
            if ($allBack) {
                $penerimaanH = PenerimaanHeder::where('nopenerimaan', $header->nopenerimaan_asal)->first();
                $penerimaanH->update([
                    'flag_bayar' => '1',
                ]);
            }
            DB::connection('farmasi')->commit();
            return new JsonResponse([
                'message' => 'Data Sudah di Kunci',
                'req' => $request->all(),
                'obat' => $obat,
                'header' => $header,
                'rinci' => $rinci,
                'stok' => $stok,
                'penerimaanR' => $penerimaanR,
                'kem' => $kem,
                'allBack' => $allBack,
            ]);
        } catch (\Exception $e) {
            DB::connection('farmasi')->rollBack();
            return response()->json([
                'message' => 'ada kesalahan : ' . $e->getMessage(),
                'err line' =>  $e->getLine(),
                'err file' =>  $e->getFile(),
            ], 410);
        }
    }
    public function hapusHeader(Request $request)
    {
        $header = Pengembalian::find($request->id);
        if (!$header) {
            return new JsonResponse(['message' => 'Data tidak ditemukan, gagal hapus'], 410);
        }
        $rincis = PengembalianRinci::where('nopengembalian', $header->nopengembalian)->get();
        if (count($rincis) > 0) {
            foreach ($rincis as $rinci) {
                $rinci->delete();
            }
        }
        $header->delete();
        return new JsonResponse([
            'message' => 'Data Header Berahasil dihapus',
            'req' => $request->all(),
        ]);
    }
    public function hapusRinci(Request $request)
    {
        $rinci = PengembalianRinci::find($request->id);
        if (!$rinci) {
            return new JsonResponse(['message' => 'Data tidak ditemukan, gagal hapus'], 410);
        }
        $header = Pengembalian::where('nopengembalian', $rinci->nopengembalian)->first();
        if (!$header) {
            return new JsonResponse(['message' => 'Data tidak ditemukan, gagal hapus'], 410);
        }
        $hapusHead = 'tidak';
        $rinci->delete();
        $rincis = PengembalianRinci::where('nopengembalian', $rinci->nopengembalian)->get();
        if (count($rincis) === 0) {
            $header->delete();
            $hapusHead = 'ya';
        }

        return new JsonResponse([
            'message' => 'Data Rinci Berahasil dihapus',
            'hapusHead' => $hapusHead,
            'req' => $request->all(),
        ]);
    }
}
