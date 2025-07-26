<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\sigarang\TransactionResource;
use App\Models\Sigarang\Transaksi\DetailTransaction;
use App\Models\Sigarang\Transaksi\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    public function index()
    {

        $data = Transaction::orderBy(request()->order_by, request()->sort)
            ->filter(request(['q']))->get();

        return TransactionResource::collection($data);
    }

    public function draft()
    {
        $complete = Transaction::where('reff', '=', request()->reff)
            ->where('status', '=', 1)->get();
        $draft = Transaction::where('reff', '=', request()->reff)
            ->where('status', '=', 0)
            ->latest('id')->with(['details.barang108', 'details.barangrs', 'details.satuan'])->get();
        if (count($complete)) {
            return new JsonResponse(['message' => 'completed']);
        }
        return TransactionResource::collection($draft);
    }

    public function cariPemesanan()
    {
        $data = Transaction::filter(request(['q']))
            ->where('nama', '=', 'PEMESANAN')
            ->where('status', '=', 1)
            ->latest('id')->with(['details.barang108', 'details.barangrs', 'details.satuan', 'perusahaan', 'details_kontrak'])->get();
        return new JsonResponse($data);
    }

    public function ambilPenerimaan()
    {
        $data = Transaction::where('nomor', '=', request('nomor'))
            ->where('status', '=', 1)
            ->latest('id')->with(['details.barang108', 'details.barangrs', 'details.satuan', 'perusahaan', 'details_kontrak'])->get();
        return new JsonResponse($data);
    }


    public function withDetail()
    {

        $data = Transaction::where('reff', '=', request()->reff)
            ->where('status', '=', 1)
            ->latest('id')->with(['details.barang108', 'details.barangrs', 'details.satuan'])->get();

        return TransactionResource::collection($data);
    }

    public function penerimaan()
    {

        $pemesanan = Transaction::where('nomor', '=', request()->nomor)
            ->where('nama', '=', 'PEMESANAN')
            ->where('status', '=', 1)
            ->latest('id')->with(['details.barang108', 'details.barangrs', 'details.satuan', 'perusahaan', 'details_kontrak'])->get();
        $penerimaanLama = Transaction::where('nomor', '=', request()->nomor)
            ->where('nama', '=', 'PENERIMAAN')
            ->where('status', '=', 1)->with('details')->get();

        $penerimaanSkr = Transaction::where('nomor', '=', request()->nomor)
            ->where('nama', '=', 'PENERIMAAN')
            ->where('status', '=', 0)->with('details')->get();

        $detailLama = DetailTransaction::selectRaw('kode_rs, sum(qty) as jml, harga')->groupBy('kode_rs')
            ->whereHas('transaction', function ($p) {
                $p->where('nama', '=', 'PENERIMAAN')
                    ->where('status', '=', 1)
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

    public function store(Request $request)
    {
        $second = $request->all();
        unset($second['reff']);
        try {
            DB::beginTransaction();

            $valid = Validator::make($request->all(), ['reff' => 'required']);
            if ($valid->fails()) {
                return new JsonResponse($valid->errors(), 422);
            }

            $data = Transaction::updateOrCreate(['reff' => $request->reff], $second);
            if ($request->has('kode_rs') && $request->has('kode_108') && $request->kode_rs !== null) {
                $data->details()->updateOrCreate(['kode_rs' => $request->kode_rs], [
                    'kode_108' => $request->kode_108,
                    'kode_satuan' => $request->kode_satuan,
                    'harga' => $request->harga,
                    'qty' => $request->qty,
                    'sub_total' => $request->sub_total,
                ]);
            }

            DB::commit();
            return new JsonResponse(['message' => 'success'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return new JsonResponse([
                'message' => 'ada kesalahan',
                'error' => $e
            ], 500);
        }
    }

    public function simpanPenerimaan(Request $request)
    {
        $second = $request->all();
        unset($second['faktur']);
        unset($second['surat_jalan']);

        $rule = [
            'faktur' => 'required',
            'surat_jalan' => 'required',
        ];
        if ($request->has('faktur')) {
            $rule['surat_jalan'] = 'exclude_if:faktur,true';
            $first = array('faktur' => $request->faktur);
        }
        if ($request->has('surat_jalan')) {
            $rule['faktur'] = 'exclude_if:surat_jalan,true';
            $first = array('surat_jalan' => $request->faktur);
        }
        try {
            DB::beginTransaction();

            $valid = Validator::make($request->all(), $rule);
            if ($valid->fails()) {
                return new JsonResponse($valid->errors(), 422);
            }

            $data = Transaction::updateOrCreate($first, $second);

            if ($request->has('kode_rs') && $request->has('kode_108') && $request->kode_rs !== null) {
                $data->details()->updateOrCreate(['kode_rs' => $request->kode_rs], $second);
            }

            DB::commit();
            return new JsonResponse(['message' => 'success'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return new JsonResponse([
                'message' => 'ada kesalahan',
                'error' => $e
            ], 500);
        }
    }
}
