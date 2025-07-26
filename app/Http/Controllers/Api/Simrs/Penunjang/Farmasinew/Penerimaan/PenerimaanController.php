<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Penerimaan;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Stok\StokrealController;
use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Master\Mpihakketiga;
use App\Models\Simrs\Penunjang\Farmasinew\Harga\DaftarHarga;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Pemesanan\PemesananHeder;
use App\Models\Simrs\Penunjang\Farmasinew\Pemesanan\PemesananRinci;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanRinci;
use App\Models\Simrs\Penunjang\Farmasinew\RencanabeliR;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokrel;
use App\Models\Simrs\Penunjang\Farmasinew\Stokreal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PenerimaanController extends Controller
{
    public function listpemesananfix()
    {
        $supl = [];
        if (request('q')) {
            $supl = Mpihakketiga::select('kode')->where('nama', 'Like', '%' . request('q') . '%')->pluck('kode');
        }

        // return new JsonResponse($supl);
        $listpemesanan = PemesananHeder::select('nopemesanan', 'tgl_pemesanan', 'kdpbf', 'kd_ruang')
            ->with([
                'gudang:kode,nama',
                'pihakketiga:kode,nama,alamat,telepon,npwp,cp',
                'rinci:nopemesanan,kdobat,jumlahdpesan,harga as harga_kcl,flag',
                'rinci.masterobat:kd_obat,nama_obat,merk,kandungan,bentuk_sediaan,satuan_b,satuan_k,kekuatan_dosis,volumesediaan,kelas_terapi',
                //'penerimaan'
                'penerimaan' => function ($penerimaan) {
                    //$penerimaan->select('nopemesanan', 'penerimaan.penerimaanrinci:nopemesanan,kdobat,jml_terima');
                    $penerimaan->select('nopenerimaan', 'nopemesanan')->with('penerimaanrinci:kdobat,nopenerimaan,jml_terima_b,jml_terima_k');
                },
            ])
            ->when(request('gudang'), function ($q) {
                $q->where('kd_ruang', request('gudang'));
            })
            ->when(count($supl) > 0, function ($q) use ($supl) {
                $q->whereIn('kdpbf', $supl);
            })
            ->where('flag', '1')
            ->get();
        return new JsonResponse($listpemesanan);
    }

    public function simpanpenerimaan(Request $request)
    {
        // validasi jumlah terima dengan pesanan

        $penerimaan = PenerimaanHeder::selectRaw('sum(penerimaan_r.jml_terima_k) as terima, penerimaan_r.jml_pesan as pesan')
            ->leftJoin('penerimaan_r', 'penerimaan_r.nopenerimaan', '=', 'penerimaan_h.nopenerimaan')
            ->where('penerimaan_h.nopemesanan', $request->nopemesanan)
            ->where('penerimaan_r.kdobat', $request->kdobat)
            ->groupBy('penerimaan_r.nopenerimaan', 'penerimaan_r.kdobat')
            ->first();
        if ($penerimaan) {
            $totalAkanTerima = (float)$penerimaan->terima + (float) $request->jml_terima_k;
            if ($totalAkanTerima > $penerimaan->pesan) {
                return new JsonResponse([
                    'message' => 'Jumlah Terima melebihi jumlah dipesan'
                ], 410);
            }
        }

        try {
            DB::connection('farmasi')->beginTransaction();


            if ($request->gudang === 'Gd-05010100') {
                $procedure = 'penerimaan_obat_ko(@nomor)';
                $colom = 'penerimaanko';
                $lebel = 'G-KO';
            } else {
                $procedure = 'penerimaan_obat_fs(@nomor)';
                $colom = 'penerimaanfs';
                $lebel = 'G-FS';
            }
            if ($request->nopenerimaan === '' || $request->nopenerimaan === null) {
                DB::connection('farmasi')->select('call ' . $procedure);
                $x = DB::connection('farmasi')->table('conter')->select($colom)->get();
                $wew = $x[0]->$colom;
                $nopenerimaan = FormatingHelper::penerimaanobat($wew, $lebel);
            } else {
                $nopenerimaan = $request->nopenerimaan;
            }

            $user = FormatingHelper::session_user();
            $simpanheder = PenerimaanHeder::updateorcreate(
                [
                    'nopenerimaan' => $nopenerimaan,
                    'nopemesanan' => $request->nopemesanan,
                    'kdpbf' => $request->kdpbf,
                    'gudang' => $request->gudang
                ],
                [
                    'tglpenerimaan' => $request->tglpenerimaan . date(' H:i:s'),
                    'pengirim' => $request->pengirim,
                    'jenissurat' => $request->jenissurat,
                    'jenis_penerimaan' => 'Pesanan',
                    'nomorsurat' => $request->nomorsurat,
                    'tglsurat' => $request->tglsurat,
                    'batasbayar' => $request->batasbayar,
                    'user' => $user['kodesimrs'],
                    // 'total_faktur_pbf' => $request->total_faktur_pbf,
                ]
            );
            if (!$simpanheder) {
                return new JsonResponse(['message' => 'not ok'], 500);
            }
            $simpanrinci = PenerimaanRinci::updateorcreate(
                [
                    'nopenerimaan' => $nopenerimaan,
                    'kdobat' => $request->kdobat,
                    'no_batch' => $request->no_batch,
                    'harga_netto_kecil' => $request->harga_netto_kecil,
                ],
                [
                    'tgl_exp' => $request->tgl_exp,
                    'jml_terima_b' => $request->jml_terima_b,
                    'jml_terima_k' => $request->jml_terima_k,
                    'harga' => $request->harga,
                    'harga_kcl' => $request->harga_kcl,
                    'satuan' => $request->satuan_bsr,
                    'satuan_kcl' => $request->satuan_kcl,
                    'isi' => $request->isi,
                    'diskon' => $request->diskon ?? 0,
                    'diskon_rp' => $request->diskon_rp ?? 0,
                    'diskon_rp_kecil' => $request->diskon_rp_kecil ?? 0,
                    'ppn' => $request->ppn ?? 0,
                    'ppn_rp' => $request->ppn_rp ?? 0,
                    'ppn_rp_kecil' => $request->ppn_rp_kecil ?? 0,
                    'harga_netto' => $request->harga_netto,
                    'jml_pesan' => $request->jml_pesan,
                    'jml_terima_lalu' => $request->jml_terima_lalu,
                    'jml_all_penerimaan' => $request->jml_all_penerimaan,
                    'subtotal' => $request->subtotal,
                    'user' => $user['kodesimrs']
                ]
            );
            if (!$simpanrinci) {
                PenerimaanHeder::where('nopenerimaan', $nopenerimaan)->first()->delete();
                return new JsonResponse(['message' => 'Data Heder Gagal Disimpan...!!!'], 500);
            }
            if ($request->jenissurat === 'Faktur') {
                $sub = PenerimaanRinci::selectRaw('sum(subtotal) as total')->where('nopenerimaan', $nopenerimaan)->groupBy('nopenerimaan')->first();
                if ($sub) {
                    $head = PenerimaanHeder::where('nopenerimaan', $nopenerimaan)->first();
                    if ($head) {
                        $head->total_faktur_pbf = $sub->total;
                        $head->save();
                    }
                }
            }
            $stokrealsimpan = StokrealController::stokreal($nopenerimaan, $request);
            if ($stokrealsimpan !== 200) {
                PenerimaanHeder::where('nopenerimaan', $nopenerimaan)->first()->delete();
                PenerimaanRinci::where('nopenerimaan', $nopenerimaan)->first()->delete();
                return new JsonResponse(['message' => 'Gagal Tersimpan Ke Stok...!!!'], 500);
            }

            // cari rinci pesanan
            $jumlahpesan = PemesananRinci::select('jumlahdpesan')
                ->with(['pemesananheder'])
                ->where('nopemesanan', $request->nopemesanan)
                ->where('kdobat', $request->kdobat)->sum('jumlahdpesan');

            // cari sudah berapa dari pesanan tsb yang diterima
            $jumlahterima = PenerimaanRinci::select('penerimaan_r.jml_terima_k')
                ->join('penerimaan_h', 'penerimaan_h.nopenerimaan', '=', 'penerimaan_r.nopenerimaan')
                ->where('penerimaan_h.nopemesanan', $request->nopemesanan)
                ->where('penerimaan_r.kdobat', $request->kdobat)->sum('penerimaan_r.jml_terima_k');

            // jika jumlah dipesan sudah sama dengan jumlah diterima maka pemesanan ditutup
            if ((int) $jumlahpesan === (int)$jumlahterima) {
                PemesananRinci::where('nopemesanan', $request->nopemesanan)->where('kdobat', $request->kdobat)
                    ->update(['flag' => '1']);
            }

            $rinciTrm = PenerimaanRinci::where('nopenerimaan', $nopenerimaan)->where('kdobat', $request->kdobat)->latest('id')->first();
            if ($rinciTrm) {
                $rinciTrm->jml_all_penerimaan = $jumlahterima;
                $rinciTrm->save();
            }

            // jika sudah tidak ada rincian pemesanan yang perlu diterima maka kunci header
            $pesan = PemesananRinci::where('nopemesanan', $request->nopemesanan)->where('flag', '')->get();
            $pesananDikunci = false;
            if (count($pesan) === 0) {
                $kuncipermintaan = PemesananHeder::where('nopemesanan', $request->nopemesanan)->first();
                $kuncipermintaan->flag = '2';
                $kuncipermintaan->save();
                $pesananDikunci = true;
            }
            DB::connection('farmasi')->commit();
            return new JsonResponse([
                'message' => 'ok',
                'nopenerimaan' => $nopenerimaan,
                'heder' => $simpanheder,
                'rinci' => $simpanrinci,
                'kunci pesanan' => $pesananDikunci,
                'penerimaan' => $penerimaan,
            ]);
        } catch (\Exception $e) {
            DB::connection('farmasi')->rollBack();
            return response()->json(['message' => 'ada kesalahan', 'error' => $e], 500);
        }
    }

    public function tolakRinciPesanan(Request $request)
    {
        /// seharusnya cek apakan ada rincian penerimaan untuk pesanan tsb

        $rinciPenerimaan =
            // cari pesanan rinci
            $rinciPesanan = PemesananRinci::where('nopemesanan', $request->nopemesanan)
            ->where('kdobat', $request->kdobat)
            ->first();
        if (!$rinciPesanan) {
            return new JsonResponse([
                'req' => $request->all(),
                'message' => 'Obat Pesanan tidak ditemukan'
            ], 410);
        }
        // cari rencana
        $rinciRencana = RencanabeliR::where('no_rencbeliobat', $rinciPesanan->noperencanaan)
            ->where('kdobat', $request->kdobat)
            ->first();
        if (!$rinciRencana) {
            return new JsonResponse([
                'req' => $request->all(),
                'message' => 'Rencana Pesanan Obat tidak ditemukan'
            ], 410);
        }
        // flag pemesanan adalah 2
        $rinciPesanan->flag = '2';
        $rinciPesanan->save();
        // flag perencanaan di kosongkan
        $rinciRencana->flag = '';
        $rinciRencana->save();
        // jika sudah tidak ada rincian pemesanan yang perlu diterima maka kunci header
        $pesan = PemesananRinci::where('nopemesanan', $request->nopemesanan)->where('flag', '')->get();
        // $pesananDikunci = false;
        if (count($pesan) === 0) {
            $kuncipermintaan = PemesananHeder::where('nopemesanan', $request->nopemesanan)->first();
            $kuncipermintaan->flag = '2';
            // $kuncipermintaan->flag = '3';
            $kuncipermintaan->save();
            // $pesananDikunci = true;
        }
        return new JsonResponse([
            'req' => $request->all(),
            'rinciPesanan' => $rinciPesanan,
            'rinciRencana' => $rinciRencana,
            'message' => 'Pesanan sudah ditolak'
        ]);
    }
    public function simpanEditNomorFaktur(Request $request)
    {
        $penerimaan = PenerimaanHeder::find($request->id);

        if (!$penerimaan) {
            return new JsonResponse([
                'req' => $request->all(),
                'message' => 'Penerimaan tidak ditemukan'
            ], 410);
        }
        $penerimaan->update([
            'nomorsurat' => $request->nomorsurat,
        ]);
        return new JsonResponse([
            'req' => $request->all(),
            'data' => $penerimaan,
            'message' => 'Nomor Faktur berhasil diubah'
        ]);
    }
    public function listepenerimaan()
    {
        // $idpegawai = auth()->user()->pegawai_id;
        // $kodegudang = Pegawai::find($idpegawai);
        $kodegudang = request('gudang');
        $supl = [];
        if (request('cari')) {
            $supl = Mpihakketiga::select('kode')->where('nama', 'Like', '%' . request('cari') . '%')->pluck('kode');

            // $supl = collect($temp)->map(function ($item, $key) {
            //     return $item->kode;
            // });
        }
        $listpenerimaan = PenerimaanHeder::select(
            'id',
            'nopenerimaan',
            'nopemesanan',
            'tglpenerimaan',
            'kdpbf',
            'gudang',
            'pengirim',
            'jenissurat',
            'nomorsurat',
            'tglsurat',
            'batasbayar',
            'jenis_penerimaan',
            'kunci',
            'total_faktur_pbf as total',
        )
            // ->leftJoin('siasik.pihak_ketiga', 'siasik.pihak_ketiga.kode', 'penerimaan_h.kdpbf')
            ->when(request('gudang'), function ($q) {
                $q->where('gudang', '=', request('gudang'));
            })
            ->when(count($supl) > 0, function ($e) use ($supl) {
                $e->whereIn('kdpbf', $supl);
            })
            ->when(count($supl) <= 0, function ($e) use ($supl) {
                $e->where(function ($qu) {
                    $qu->where('nopemesanan', 'Like', '%' . request('cari') . '%')
                        ->orWhere('nopenerimaan', 'Like', '%' . request('cari') . '%')
                        // ->orWhere('tglpenerimaan', 'Like', '%' . request('cari') . '%')
                        ->orWhere('pengirim', 'Like', '%' . request('cari') . '%')
                        ->orWhere('jenissurat', 'Like', '%' . request('cari') . '%')
                        ->orWhere('nomorsurat', 'Like', '%' . request('cari') . '%');
                });
            })
            ->when(request('jenispenerimaan'), function ($q) {
                $q->where('jenis_penerimaan', request('jenispenerimaan'));
            })
            ->when(request('from'), function ($q) {
                $q->whereBetween('tglpenerimaan', [request('from') . ' 00:00:00', request('to') . ' 23:59:59']);
            })
            ->with([
                'penerimaanrinci',
                'penerimaanrinci.masterobat',
                'pihakketiga:kode,nama',
                'faktur'
            ])
            ->orderBy('tglpenerimaan', 'desc')
            ->paginate(request('per_page'));
        return new JsonResponse([
            'data' => $listpenerimaan,
            'req' => request()->all(),
            'kode' => $supl
        ]);
    }

    public function bukaKunciPenerimaan(Request $request)
    {
        $head = PenerimaanHeder::where('nopenerimaan', $request->nopenerimaan)
            ->where('kunci', '1')
            ->first();
        if (!$head) {
            return new JsonResponse([
                'message' => 'Penerimaan tidak ditemukan, apakah kunci sudah dibuka? atau penerimaan sudah dihapus?',
                'data' => $head
            ], 410);
        }
        $rawStok = Stokrel::lockForUpdate()
            ->where('nopenerimaan', $request->nopenerimaan)
            ->where('kdruang', $head->gudang)
            ->get();
        $rinci = PenerimaanRinci::select('nopenerimaan', 'kdobat', 'no_batch', 'tgl_exp', 'jml_terima_k as jumlah')
            ->with('masterobat:kd_obat,nama_obat')
            ->where('nopenerimaan', $request->nopenerimaan)
            ->get();
        $stok = collect($rawStok);
        $ada = [];
        $str = 'Stok ';
        foreach ($rinci as $key) {
            $temp = $stok->where('kdobat', $key['kdobat'])
                ->where('nobatch', $key['no_batch'])
                ->where('tglexp', $key['tgl_exp'])
                ->first();
            $namaOb = $key['masterobat']['nama_obat'] ?? '';
            if (!$temp) {
                return new JsonResponse([
                    'message' => 'Data Stok ' . ($namaOb) . ' Tidak Ditemukan',
                ], 410);
            }
            $trm = (float)$key['jumlah'];
            $st = (float)$temp['jumlah'];
            $selisih = (float)($trm - $st);
            if ($selisih != 0) {
                $tmpStr = $str . $namaOb . ' keluar sebanyak ' . ($selisih) . ', ';
                $str = $tmpStr;
                $ada[] = [
                    'rinc' => $key,
                    'stok' => $temp,
                    'selisih' => $selisih,
                    'cond' => $selisih == 0,
                    'cond1' => $selisih != 0,
                ];
            }
        }
        if (sizeof($ada) > 0) {
            return new JsonResponse([
                'message' => 'Kunci tidak dibuka, ' . $str,
                'data' => $ada
            ], 410);
        }
        $data = [
            'req' => $request->all(),
            'head' => $head,
            'stok' => $stok,
            'rinci' => $rinci,
        ];
        foreach ($rawStok as $st) {
            $st->update(['flag' => '1']);
        }
        $head->update(['kunci' => '']);
        // return new JsonResponse([
        //     'message' => 'Kunci Penerimaan tidak dibuka',
        //     'data' => $data
        // ], 410);
        return new JsonResponse([
            'message' => 'Kunci Penerimaan sudah dibuka',
            'data' => $data
        ]);
    }
    public function kuncipenerimaan(Request $request)
    {
        $cek = PenerimaanHeder::where('nopenerimaan', $request->nopenerimaan)->first();
        if (($cek->jenissurat === 'Faktur' && $cek->jenis_penerimaan === 'Pesanan') || ($cek->jenis_penerimaan !== 'Pesanan')) {
            $masukstok = Stokrel::where('nopenerimaan', $request->nopenerimaan)
                ->update(['flag' => '']);
            if (!$masukstok) {
                return new JsonResponse(['message' => 'Stok Tidak Terupdate,mohon segera cek Data Stok Anda...!!!'], 500);
            }
        }

        $kuncipenerimaan = PenerimaanHeder::where('nopenerimaan', $request->nopenerimaan)
            ->update(['kunci' => '1']);
        if (!$kuncipenerimaan) {
            return new JsonResponse(['message' => 'Gagal Mengunci Penerimaan,Cek Lagi Data Yang Anda Input...!!!'], 500);
        }
        if (($cek->jenissurat !== 'Faktur' && $cek->jenis_penerimaan === 'Pesanan')) {
            return new JsonResponse(['message' => 'Penerimaan Sudah Terkunci. Dan Stok Tidak Bertambah. Silahkan Gunakan Menu Pemfakturan Untuk Menambah Stok'], 200);
        }
        $penerimaan = PenerimaanHeder::where('nopenerimaan', $request->nopenerimaan)->first();
        if ($penerimaan) {
            if (($penerimaan->jenissurat === 'Faktur' && $penerimaan->jenis_penerimaan === 'Pesanan') || ($penerimaan->jenis_penerimaan !== 'Pesanan')) {
                $rin = PenerimaanRinci::select('nopenerimaan', 'kdobat', 'harga_netto_kecil')->where('nopenerimaan', $request->nopenerimaan)->get();
                if (count($rin) > 0) {
                    $harga = [];
                    foreach ($rin as $key) {
                        $tHarga['nopenerimaan'] = $key['nopenerimaan'];
                        $tHarga['kd_obat'] = $key['kdobat'];
                        $tHarga['harga'] = $key['harga_netto_kecil'];
                        $tHarga['tgl_mulai_berlaku'] = date('Y-m-d H:i:s');
                        $tHarga['created_at'] = date('Y-m-d H:i:s');
                        $tHarga['updated_at'] = date('Y-m-d H:i:s');
                        // if ((int)$key['harga_netto_kecil'] > 0) $harga[] = $tHarga;
                        $harga[] = $tHarga;
                    }

                    if (count($harga) > 0) {
                        foreach (array_chunk($harga, 1000) as $t) {
                            DaftarHarga::insert($t);
                        }
                    }
                }
            }
        }
        return new JsonResponse(['message' => 'Penerimaan Sudah Terkunci, Dan Stok Sudah Bertambah...!!!'], 200);
    }

    public function simpanpenerimaanlangsung(Request $request)
    {
        try {
            DB::connection('farmasi')->beginTransaction();

            $gudang = ['Gd-05010100', 'Gd-03010100'];
            if (!in_array($request->gudang, $gudang)) {
                return new JsonResponse([
                    'message' => 'Anda tidak menggunakan user gudang, pastikan anda memiliki user gudang untuk melakukan penerimaan'
                ], 410);
            }
            if ($request->gudang === 'Gd-05010100') {
                $procedure = 'penerimaan_obat_ko(@nomor)';
                $colom = 'penerimaanko';
                $lebel = 'G-KO';
            } else {
                $procedure = 'penerimaan_obat_fs(@nomor)';
                $colom = 'penerimaanfs';
                $lebel = 'G-FS';
            }
            if ($request->nopenerimaan === '' || $request->nopenerimaan === null) {
                DB::connection('farmasi')->select('call ' . $procedure);
                $x = DB::connection('farmasi')->table('conter')->select($colom)->get();
                $wew = $x[0]->$colom;
                $nopenerimaan = FormatingHelper::penerimaanobat($wew, $lebel);
            } else {
                $nopenerimaan = $request->nopenerimaan;
            }
            $user = FormatingHelper::session_user();
            $tglPenerimaan = date('Y-m-d', strtotime($request->tglpenerimaan)) . ' ' . date('H:i:s');
            $simpanheder = PenerimaanHeder::updateorcreate(
                [
                    'nopenerimaan' => $nopenerimaan,
                    'kdpbf' => $request->kdpbf,
                    'gudang' => $request->gudang,
                ],
                [
                    //'nopemesanan' => $request->nopemesanan,
                    // 'tglpenerimaan' => $request->tglpenerimaan,
                    'tglpenerimaan' => $tglPenerimaan,
                    'pengirim' => $request->pengirim,
                    'tglsurat' => $request->tglsurat,
                    //'batasbayar' => $request->batasbayar,
                    'jenissurat' => $request->jenissurat,
                    'jenis_penerimaan' => $request->jenispenerimaan,
                    'nomorsurat' => $request->nomorsurat,
                    'user' => $user['kodesimrs'],
                    'total_faktur_pbf' => $request->total_faktur_pbf,
                ]
            );
            if (!$simpanheder) {
                return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!'], 410);
            }
            $simpanrinci = PenerimaanRinci::updateorcreate(
                [
                    'nopenerimaan' => $request->nopenerimaan ?? $nopenerimaan,
                    'kdobat' => $request->kdobat,
                    'no_batch' => $request->no_batch,
                ],
                [
                    'jml_terima_b' => $request->jml_terima_b,
                    'jml_terima_k' => $request->jml_terima_k,
                    'harga' => $request->harga,
                    'harga_kcl' => $request->harga_kcl,
                    'no_retur_rs' => $request->no_retur_rs ?? '',
                    'tgl_exp' => $request->tgl_exp,
                    'satuan' => $request->satuan_bsr,
                    'satuan_kcl' => $request->satuan_kcl,
                    'isi' => $request->isi,
                    'diskon' => $request->diskon ?? 0,
                    'diskon_rp' => $request->diskon_rp ?? 0,
                    'diskon_rp_kecil' => $request->diskon_rp_kecil ?? 0,
                    'ppn' => $request->ppn ?? 0,
                    'ppn_rp' => $request->ppn_rp ?? 0,
                    'ppn_rp_kecil' => $request->ppn_rp_kecil ?? 0,
                    'harga_netto' => $request->harga_netto,
                    'harga_netto_kecil' => $request->harga_netto_kecil,
                    'jml_pesan' => $request->jml_pesan,
                    'jml_terima_lalu' => $request->jml_terima_lalu,
                    'jml_all_penerimaan' => $request->jml_all_penerimaan,
                    'subtotal' => $request->subtotal,
                    'user' => $user['kodesimrs']
                ]
            );
            if (!$simpanrinci) {
                PenerimaanHeder::where('nopenerimaan', $nopenerimaan)->first()->delete();
                return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!'], 410);
            }
            $stokrealsimpan = StokrealController::stokreal($nopenerimaan, $request);
            if ($stokrealsimpan !== 200) {
                PenerimaanHeder::where('nopenerimaan', $nopenerimaan)->first()->delete();
                PenerimaanRinci::where('nopenerimaan', $nopenerimaan)->first()->delete();
                return new JsonResponse(['message' => 'Gagal Tersimpan Ke Stok...!!!'], 410);
            }

            DB::connection('farmasi')->commit();
            $simpanrinci->load('masterobat:kd_obat,nama_obat,satuan_b');
            return new JsonResponse([
                'message' => 'ok',
                'nopenerimaan' => $nopenerimaan,
                'heder' => $simpanheder,
                'rinci' => $simpanrinci
            ]);
        } catch (\Exception $e) {
            DB::connection('farmasi')->rollBack();
            return new JsonResponse([
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => '' . $e
            ], 410);
        }
    }
    public function batalHeader(Request $request)
    {
        $pemesananH = PemesananHeder::where('nopemesanan', $request->nopemesanan)->first();
        $pemesananR = [];
        $penerimaanH = PenerimaanHeder::where('nopenerimaan', $request->nopenerimaan)->first();
        $penerimaanR = PenerimaanRinci::where('nopenerimaan', $request->nopenerimaan)->get();
        $stok = Stokreal::where('nopenerimaan', $request->nopenerimaan)->where('flag', '1')->get();

        if (!$penerimaanH) {
            return new JsonResponse(['message' => 'Gagal hapus, data tidak ditemukan'], 410);
        }

        if (count($penerimaanR)) {
            foreach ($penerimaanR as $key) {
                $item = PemesananRinci::where('nopemesanan', $request->nopemesanan)
                    ->where('kdobat', $key['kdobat'])
                    ->get();
                if (count($item)) {
                    if (count($item) > 1) {
                        foreach ($item as $it) {
                            $it->flag = '';
                            $it->save();
                            $pemesananR[] = $it;
                        }
                    } else {
                        $item[0]->flag = '';
                        $item[0]->save();
                        $pemesananR[] = $item[0];
                    }
                }
                $key->delete();
            }
        }

        if ($pemesananH) {
            $pemesananH->flag = '1';
            $pemesananH->save();
        }

        $penerimaanH->delete();

        if (count($stok)) {
            foreach ($stok as $st) {
                $st->delete();
            }
        }
        return new JsonResponse([
            'message' => 'Data berhasil dihapus',
            'pemesanan header' => $pemesananH,
            'pemesanan rinci' => $pemesananR,
            'penerimaan header' => $penerimaanH,
            'penerimaan rinci' => $penerimaanR,
        ]);
    }
    public function batalRinci(Request $request)
    {
        $penerimaanH = PenerimaanHeder::where('nopenerimaan', $request->nopenerimaan)->first();
        $penerimaanR = PenerimaanRinci::find($request->id);
        if (!$penerimaanR) {
            return new JsonResponse(['message' => 'gagal dihapus, data tidak ditemukan'], 410);
        }
        $pemesananH = PemesananHeder::where('nopemesanan', $penerimaanH->nopemesanan)->first();

        $pemesananR = PemesananRinci::where('nopemesanan', $penerimaanH->nopemesanan)
            ->where('kdobat', $penerimaanR->kdobat)
            ->get();

        if (count($pemesananR) > 0) {
            if (count($pemesananR) > 1) {
                foreach ($pemesananR as $it) {
                    $it->flag = '';
                    $it->save();
                    $pemesananR[] = $it;
                }
            } else {
                $pemesananR[0]->flag = '';
                $pemesananR[0]->save();
            }
        }

        if ($pemesananH) {
            $pemesananH->flag = '1';
            $pemesananH->save();
        }

        $stok = Stokreal::where('nopenerimaan', $request->nopenerimaan)->where('kdobat', $penerimaanR->kdobat)->where('flag', '1')->get();
        if (count($stok)) {
            foreach ($stok as $st) {
                $st->delete();
            }
        }

        $penerimaanR->delete();

        $allRinci = PenerimaanRinci::where('nopenerimaan', $request->nopenerimaan)->get();
        if (count($allRinci) <= 0) {
            $penerimaanH->delete();
        }
        return new JsonResponse([
            'message' => 'Data Berhasil dihapus',
            'pemesanan header' => $pemesananH,
            'pemesanan rinci' => $pemesananR,
            'penerimaan header' => $penerimaanH,
            'penerimaan rinci' => $penerimaanR,
            'all rinci' => $allRinci,

        ]);
    }

    public function listepenerimaanBynomor()
    {
        // $idpegawai = auth()->user()->pegawai_id;
        // $kodegudang = Pegawai::find($idpegawai);
        $kodegudang = FormatingHelper::session_user();

        $temp = Mpihakketiga::select('kode')->where('nama', 'Like', '%' . request('cari') . '%')->get('kode');
        $supl = collect($temp)->map(function ($item, $key) {
            return $item->kode;
        });
        $listpenerimaan = PenerimaanHeder::select(
            'penerimaan_h.nopenerimaan as nopenerimaan',
            'penerimaan_h.nopemesanan as nopemesanan',
            'penerimaan_h.tglpenerimaan as tglpenerimaan',
            'penerimaan_h.kdpbf',
            // 'siasik.pihak_ketiga.nama as pbf',
            'penerimaan_h.pengirim as pengirim',
            'penerimaan_h.jenissurat as jenissurat',
            'penerimaan_h.nomorsurat as nomorsurat',
            'penerimaan_h.tglsurat as tglsurat',
            'penerimaan_h.batasbayar as batasbayar',
            'penerimaan_h.kunci as kunci',
            'penerimaan_h.total_faktur_pbf as total',
        )
            // ->leftJoin('siasik.pihak_ketiga', 'siasik.pihak_ketiga.kode', 'penerimaan_h.kdpbf')
            // ->where('penerimaan_h.nopemesanan', 'Like', '%' . request('cari') . '%')
            ->when($kodegudang['kdruang'] !== '', function ($e) use ($kodegudang) {
                $e->where('penerimaan_h.gudang', $kodegudang['kdruang']);
            })
            ->when(count($supl) > 0, function ($e) use ($supl) {
                $e->orWhereIn('penerimaan_h.kdpbf', $supl);
            })
            ->where('penerimaan_h.nopenerimaan', request('nomorpenerimaan'))
            // ->orWhere('penerimaan_h.tglpenerimaan', 'Like', '%' . request('cari') . '%')
            // ->orWhere('siasik.pihak_ketiga.nama', 'Like', '%' . request('cari') . '%')
            // ->orWhere('penerimaan_h.pengirim', 'Like', '%' . request('cari') . '%')
            // ->orWhere('penerimaan_h.jenissurat', 'Like', '%' . request('cari') . '%')
            // ->orWhere('penerimaan_h.nomorsurat', 'Like', '%' . request('cari') . '%')
            ->with([
                'penerimaanrinci',
                'penerimaanrinci.masterobat',
                'pihakketiga:kode,nama',
                'faktur'
            ])->get();
        return new JsonResponse($listpenerimaan);
    }
}
