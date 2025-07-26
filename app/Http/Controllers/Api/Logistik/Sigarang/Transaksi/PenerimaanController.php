<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\Sigarang\RecentStokUpdate;
use App\Models\Sigarang\Transaksi\DistribusiDepo\DetailDistribusiDepo;
use App\Models\Sigarang\Transaksi\Pemesanan\DetailPemesanan;
use App\Models\Sigarang\Transaksi\Pemesanan\Pemesanan;
use App\Models\Sigarang\Transaksi\Penerimaan\DetailPenerimaan;
use App\Models\Sigarang\Transaksi\Penerimaan\Penerimaan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PenerimaanController extends Controller
{
    public function cariPemesanan()
    {
        $user = auth()->user();
        // $pegawai = Pegawai::find($user->pegawai_id);
        $data = Pemesanan::filter(request(['q']))
            // ->where('status', 2)
            // ->orWhere('status', 3)
            ->whereIn('status', [2, 3])
            ->latest('id')
            ->with(['details.barang108', 'details.barangrs', 'details.satuan', 'perusahaan', 'details_kontrak'])
            ->get();

        return new JsonResponse($data);
    }
    public function cariDetailPesanan()
    {
        // $data = Pemesanan::where('nomor', request('nomor'))
        //     ->with(
        //         'details'
        //     )
        //     ->get();
        $raw = DetailPenerimaan::where('penerimaan_id', request('id'))->get();
        $detail = collect($raw)->map(function ($anu) {
            return $anu->kode_rs;
        });
        $data['pesanan'] = DetailPemesanan::with('barangrs', 'satuan')
            ->select('detail_pemesanans.*', 'pemesanans.nomor', 'pemesanans.status as statuspesanan')
            ->join('pemesanans', 'detail_pemesanans.pemesanan_id', '=', 'pemesanans.id')
            ->where('pemesanans.nomor', request('nomor'))
            ->whereIn('detail_pemesanans.kode_rs', $detail)
            // ->whereIn('detail_pemesanans.kode_rs', [request('detail')])
            ->get();
        $data['distribusi'] = DetailDistribusiDepo::where('no_penerimaan', request('no_penerimaan'))->get();
        // $data['detail'] = $detail;
        return new JsonResponse($data);
        // return new JsonResponse($detail);
    }

    public function cariDetailPenerimaan()
    {
        // return new JsonResponse(request()->all());
        $temp = DetailPenerimaan::query();
        $temp->join('penerimaans', function ($jo) {
            $jo->on('detail_penerimaans.penerimaan_id', '=', 'penerimaans.id')
                ->where('status', 2)
                ->where('nomor', request('nomor'))
                ->whereIn('kode_rs', request('kodeBarang'));
        });
        $data = $temp->get();
        // $data['request'] = request()->all();

        return new JsonResponse($data);
    }

    public function jumlahPenerimaan()
    {
        // $data = penerimaan::where('nomor', request('nomor'))->get();
        $data = Penerimaan::selectRaw('nomor')->where('nomor', request('nomor'))->count();

        return new JsonResponse(['jumlah' => $data]);
    }

    public function penerimaan()
    {

        $pemesanan = Pemesanan::where('reff', '=', request()->reff)
            ->where('status', '>=', 2)
            ->latest('id')->with(['details', 'details.barangrs', 'details.satuan', 'perusahaan', 'details_kontrak'])->get();
        $penerimaanLama = Penerimaan::where('nomor', '=', request()->nomor)
            ->where('status', '>=', 2)->with('details')->get();

        $penerimaanSkr = Penerimaan::where('nomor', '=', request()->nomor)
            ->where('status', '=', 1)->with('details')->get();

        $detailLama = DetailPenerimaan::selectRaw('kode_rs, sum(qty) as jml, harga')->groupBy('kode_rs')
            ->whereHas('penerimaan', function ($p) {
                // ->where('nama', '=', 'PENERIMAAN')
                $p->where('status', '>=', 2)
                    ->where('nomor', '=', request()->nomor);
            })->get();




        $draft = (object) array(
            'pemesanan' => $pemesanan,
            'trmSblm' => $penerimaanLama,
            'trmSkr' => $penerimaanSkr,
            'detailLama' => $detailLama,
        );
        return new JsonResponse($draft);
    }
    public function suratBelumLengkap()
    {
        $data = Penerimaan::where('faktur', '=', null)
            ->orWhere('surat_jalan', '=', null)
            ->latest('id')
            ->get();
        return new JsonResponse($data);
    }

    public function simpanPenerimaan(Request $request)
    {
        $user = auth()->user();
        // $pegawai = Pegawai::find($user->pegawai_id);
        $second = $request->all();
        $second['tanggal'] = $request->tanggal !== null ? $request->tanggal : date('Y-m-d H:i:s');
        $second['created_by'] = $user->pegawai_id;

        $valid = Validator::make($request->all(), ['reff' => 'required']);
        if ($valid->fails()) {
            return new JsonResponse($valid->errors(), 422);
        }

        try {
            DB::beginTransaction();

            $data = Penerimaan::updateOrCreate(['reff' => $request->reff], $second);

            if ($request->has('kode_rs') && $request->has('kode_108') && $request->kode_rs !== null) {
                $data->details()->updateOrCreate(['kode_rs' => $request->kode_rs], $second);
            }

            if ($request->status === 2 && $data) {
                TransaksiGudangController::fromPenerimaan($data->id);
            }
            // $pesan=Pemesanan::where()
            PemesananController::updateStatus($request->id, $request->statuspemesanan);


            DB::commit();

            return new JsonResponse([
                'message' => 'Data Tersimpan',
                'data' => $data,
                // 'gudang' => $gudang,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return new JsonResponse([
                'message' => 'ada kesalahan',
                'error' => $e
            ], 500);
        }
    }

    public function editHeaderPenerimaan(Request $request)
    {
        // $lama = Penerimaan::find($request->id);
        // if ($lama->no_penerimaan !== $request->no_penerimaan) {
        //     $data['stok'] = RecentStokUpdate::where('no_penerimaan', $lama->no_penerimaan)
        //         ->whereIn('kode_rs', $request->detail)->update(['no_penerimaan', $request->no_penerimaan]);
        // }
        $data = Penerimaan::updateOrCreate([
            'id' => $request->id,
            'reff' => $request->reff,
        ], $request->all());
        $balik = Penerimaan::with('perusahaan',  'details.barangrs.barang108', 'details.satuan')->find($request->id);
        if ($data->wasChanged()) {
            return new JsonResponse(['data' => $balik, 'message' => 'header Transaksi sudah di update'], 200);
        } else {
            return new JsonResponse(['data' => $balik, 'message' => 'Tidak ada perubahan data'], 200);
        }
    }

    public function lengkapiSurat(Request $request)
    {
        $second = $request->all();

        try {
            DB::beginTransaction();

            $valid = Validator::make($request->all(), [
                'reff' => 'required',
                'faktur' => 'required',
                'surat_jalan' => 'required',
                'tanggal_surat' => 'required',
                'tanggal_faktur' => 'required',
                'tempo' => 'required',
            ]);
            if ($valid->fails()) {
                return new JsonResponse($valid->errors(), 422);
            }

            $data = Penerimaan::updateOrCreate(['reff' => $request->reff], $second);


            DB::commit();

            return new JsonResponse([
                'message' => 'success',
                'data' => $data,
                // 'gudang' => $gudang,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return new JsonResponse([
                'message' => 'ada kesalahan',
                'error' => $e
            ], 500);
        }
    }



    public function destroy()
    {
        return new JsonResponse(['msg' => 'Masih kosong bos']);
    }

    public function editDetailPenerimaan(Request $request)
    {
        $detailPesanan = DetailPemesanan::select('detail_pemesanans.*', 'pemesanans.nomor', 'pemesanans.status as statuspesanan')
            ->join('pemesanans', 'detail_pemesanans.pemesanan_id', '=', 'pemesanans.id')
            ->where('pemesanans.nomor', $request->nomor)
            ->where('detail_pemesanans.kode_rs', $request->kode_rs)
            ->first();

        $pesanan = Pemesanan::find($detailPesanan->pemesanan_id);

        $detailTerima = DetailPenerimaan::find($request->id);

        $detailTerima->update(['qty' => $request->qty]);

        if ($request->has('harga')) {
            $detailTerima->update(['harga' => $request->harga]);
        }

        if ($request->has('totalHarga')) {
            $penerimaan = Penerimaan::find($request->penerimaan_id);
            if ($penerimaan) {
                $penerimaan->update(['total' => $request->totalHarga]);
            }
        }
        $detailTerima->update(['sub_total' => $detailTerima->qty * $detailTerima->harga]);
        if ($detailTerima->wasChanged()) {
            if ($request->has('statuspesanan')) {
                if ($request->statuspesanan === 3) {
                    $pesanan->update(['status' => 3]);
                }
            }
        }
        // else {
        // cari antara pesanan dan penerimaan sudah klop atau belum jumlahnya
        // $detail = DetailPemesanan::where('pemesanan_id', $detailPesanan->pemesanan_id)
        //     ->get();
        // $terima = Penerimaan::where('nomor', $request->nomor)
        //     ->with('details')
        //     ->get();
        // }
        // $data['detail'] = $detailPesanan;
        $data['pesanan'] = $pesanan;
        $data['terima'] = $detailTerima;
        return new JsonResponse($data);
    }

    public function hapusDetailPenerimaan(Request $request)
    {
        $terima = Penerimaan::find($request->penerimaan);
        $detail = DetailPenerimaan::find($request->id);

        $recent = RecentStokUpdate::where('no_penerimaan', $terima->no_penerimaan)
            ->where('kode_rs', $detail->kode_rs)
            ->where('kode_ruang', 'Gd-02010100')->first();
        if ($recent) {
            $recent->delete();
        }

        if ($request->jmlDet <= 1) {
            $terima->delete();
            if (!$terima) {
                return new JsonResponse(['message' => 'Data tidak bisa dihapus'], 410);
            }
            return new JsonResponse(['message' => 'Data berhasil dihapus'], 200);
        }

        $detail->delete();
        if (!$detail) {
            return new JsonResponse(['message' => 'Data tidak bisa dihapus'], 410);
        }
        return new JsonResponse(['message' => 'Data berhasil dihapus'], 200);
    }
}
