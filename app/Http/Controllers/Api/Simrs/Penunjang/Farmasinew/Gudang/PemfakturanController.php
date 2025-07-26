<?php

namespace App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Gudang;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\Harga\DaftarHarga;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\Faktur;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanRinci;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokrel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PemfakturanController extends Controller
{
    //
    public function getPenerimaanBelumAdaFaktur()
    {
        $data = PenerimaanHeder::whereDoesntHave('faktur')
            ->where('jenis_penerimaan', 'Pesanan')
            ->where('jenissurat', '!=', 'Faktur')
            ->with([
                'penerimaanrinci.masterobat',
                'pihakketiga:kode,nama,alamat,telepon,npwp,cp',
                'gudang:kode,nama',
            ])
            ->orderBy('tglpenerimaan', 'DESC')
            ->paginate(request('per_page'));
        return new JsonResponse($data);
    }
    public function simpan(Request $request)
    {
        try {
            DB::connection('farmasi')->beginTransaction();
            $user = FormatingHelper::session_user();
            $simpanrinci = PenerimaanRinci::where('nopenerimaan', $request->nopenerimaan)
                ->where('kdobat', $request->kdobat)
                ->first();
            if (!$simpanrinci) {
                return new JsonResponse(['message' => 'Gagal Update,Data Tidak Ditemukan'], 410);
            }
            $simpanrinci->update([
                'harga' => $request->harga,
                'harga_kcl' => $request->harga_kcl,
                'no_batch' => $request->no_batch,
                'tgl_exp' => $request->tgl_exp,
                'diskon' => $request->diskon,
                'diskon_rp' => $request->diskon_rp,
                'diskon_rp_kecil' => $request->diskon_rp_kecil,
                'ppn' => $request->ppn,
                'ppn_rp' => $request->ppn_rp,
                'ppn_rp_kecil' => $request->ppn_rp_kecil,
                'harga_netto' => $request->harga_netto,
                'harga_netto_kecil' => $request->harga_netto_kecil,
                'subtotal' => $request->subtotal,
            ]);
            $updateHargaStok = Stokrel::where('nopenerimaan', $request->nopenerimaan)
                ->where('kdobat', $request->kdobat)
                ->where('kdruang', $request->kdruang)
                ->where('flag', '1')
                ->first();
            if ($updateHargaStok) {
                $updateHargaStok->harga = $request->harga_netto_kecil;
                $updateHargaStok->save();
            }

            $total = PenerimaanRinci::selectRaw('sum(subtotal) as total')->where('nopenerimaan', $request->nopenerimaan)->first();
            $simpanFaktur = Faktur::updateOrCreate(
                [
                    'nopenerimaan' => $request->nopenerimaan,
                ],
                [
                    'no_faktur' => $request->nomorsurat,
                    'tgl_faktur' => $request->tglsurat,
                    'total_faktur' => $total->total,
                ]
            );
            DB::connection('farmasi')->commit();
            return new JsonResponse([
                'message' => 'Berhasi Update Rinci, Stok belum masuk',
                'rinci' => $simpanrinci,
                'total' => $total,
                'faktur' => $simpanFaktur,
            ]);
        } catch (\Exception $e) {
            DB::connection('farmasi')->rollBack();
            return response()->json(['message' => 'ada kesalahan', 'error' => $e], 410);
        }
    }
    public function simpanHeader(Request $request)
    {
        try {
            DB::connection('farmasi')->beginTransaction();

            $total = PenerimaanRinci::selectRaw('sum(subtotal) as total')->where('nopenerimaan', $request->nopenerimaan)->first();
            // total nilai faktur harus dikurangi dengan barang yang di retur
            $simpanFaktur = Faktur::updateOrCreate(
                [
                    'nopenerimaan' => $request->nopenerimaan,
                ],
                [
                    'no_faktur' => $request->nomorsurat,
                    'tgl_faktur' => $request->tglsurat,
                    'total_faktur' => $total->total,
                ]
            );

            $masukstok = Stokrel::where('nopenerimaan', $request->nopenerimaan)
                ->update(['flag' => '']);

            if (!$masukstok) {
                return new JsonResponse(['message' => 'Stok Tidak Terupdate,mohon segera cek Data Stok Anda...!!!'], 410);
            }
            $head = PenerimaanHeder::where('nopenerimaan', $request->nopenerimaan)->first();
            if ($head) {
                $head->batasbayar = $request->batasbayar;
                $head->save();
            }
            $rinci = PenerimaanRinci::where('nopenerimaan', $request->nopenerimaan)->get();
            $harga = [];
            foreach ($rinci as $key) {
                $tHarga['nopenerimaan'] = $key['nopenerimaan'];
                $tHarga['kd_obat'] = $key['kdobat'];
                $tHarga['harga'] = $key['harga_netto_kecil'];
                $tHarga['tgl_mulai_berlaku'] = date('Y-m-d H:i:s');
                $tHarga['created_at'] = date('Y-m-d H:i:s');
                $tHarga['updated_at'] = date('Y-m-d H:i:s');
                $harga[] = $tHarga;
            }
            if (count($harga) > 0) {
                foreach (array_chunk($harga, 1000) as $t) {
                    DaftarHarga::insert($t);
                }
            }
            DB::connection('farmasi')->commit();
            return new JsonResponse([
                'message' => 'Faktur Sudah disimpan, dan Stok Sudah Diterima',
                'total' => $total,
                'faktur' => $simpanFaktur,
            ]);
        } catch (\Exception $e) {
            DB::connection('farmasi')->rollBack();
            return response()->json(['message' => 'ada kesalahan', 'error' => $e], 410);
        }
    }
}
