<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\BarangRS;
use App\Models\Sigarang\RecentStokUpdate;
use App\Models\Sigarang\Transaksi\Gudang\TransaksiGudang;
use App\Models\Sigarang\Transaksi\Penerimaan\Penerimaan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransaksiGudangController extends Controller
{
    //

    public static function fromPenerimaan($data)
    {
        $user = auth()->user();
        // $terima = Penerimaan::where('id', $data)->with('details')->get();
        $terima = Penerimaan::with('details')->find($data);
        $first = $terima->reff;
        $second = $terima;
        $second['tanggal'] = date('Y-m-d H:i:s');
        // $second->asal = null;
        $second->tujuan = 'Gd-02010100';
        $second->nama = 'PENERIMAAN GUDANG';
        $second->nama_penerima = $user->nama;
        $second->status = 2;
        $detail = $second->details;

        // unset($second['reff']);
        try {
            DB::beginTransaction();
            $gudang = TransaksiGudang::updateOrCreate(['reff' => $first],  $second->only('nama', 'nomor', 'no_penerimaan', 'tanggal', 'tujuan', 'nama_penerima', 'total', 'status'));
            // $header = (object) array('no_penerimaan' => $second->no_penerimaan);
            // $header->kode_ruang = $second->tujuan;
            $anu = [];
            $barangnya = [];
            foreach ($detail as $value) {
                $satu = $value->kode_rs;
                // unset($value['kode_rs']);
                $gudang->details()->updateOrCreate(['kode_rs' => $satu],  $value->only('kode_108', 'qty', 'harga', 'kode_satuan', 'sub_total'));
                // $header->kode_rs = $satu;
                // $header->harga = $value->harga;
                // $header->sisa_stok = $value->sub_total;
                // $this->terimaStokGudang($header);
                // $barang = BarangRS::where('kode', $satu)->first();
                $rec = RecentStokUpdate::updateOrCreate([
                    'no_penerimaan' => $second->no_penerimaan,
                    // 'kode_ruang' => $barang->kode_depo,
                    'kode_ruang' => $second->tujuan,
                    'kode_rs' => $satu,

                ], [
                    'harga' => $value->harga,
                    'sisa_stok' => $value->qty,
                ]);
                array_push($anu, $rec);
                array_push($barangnya, $value);
            }
            DB::commit();
            // return new JsonResponse(['message' => 'success', 'data' => $gudang], 201);
            return ['data' => $gudang, 'recent' => $anu];
        } catch (\Exception $e) {
            DB::rollBack();
            return new JsonResponse([
                'message' => 'ada kesalahan',
                'error' => $e,
                'gudang' => $gudang,
                'anu' => $anu,
                'barang' => $barangnya,
                'detail' => $detail,
            ], 500);
        }
    }

    public function terimaStokGudang($header)
    {
        $data = RecentStokUpdate::create($header->all());
        return 'ok';
    }
}
