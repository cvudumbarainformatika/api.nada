<?php

namespace App\Http\Controllers\Api\Antrean\master;

use App\Helpers\BridgingbpjsHelper;
use App\Http\Controllers\Controller;
use App\Models\Antrean\Display;
use App\Models\Antrean\Layanan;
use App\Models\Antrean\PoliBpjs;
use App\Models\Antrean\Unit;
use App\Models\Poli;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Mockery\Undefined;

class UnitController extends Controller
{
    public function index()
    {
        // return new JsonResponse(['message' => 'ok']);
        $data = Unit::when(request('q'), function ($search, $q) {
            $search->where('loket', 'LIKE', '%' . $q . '%');
        })
            ->with(['layanan'])
            ->orderBy('layanan_id', 'ASC')
            ->orderBy('loket_no', 'ASC')
            ->paginate(request('per_page'));

        return new JsonResponse($data);
    }

    public function getLayanans()
    {
        $data = Layanan::all();
        return new JsonResponse($data);
    }
    public function getDisplays()
    {
        $data = Display::all();
        return new JsonResponse($data);
    }

    public function store(Request $request)
    {

        $kode_layanan = null;
        if ($request->layanan_id !== null) {
            $layanan = Layanan::where('id_layanan', '=', $request->layanan_id)->first();
            $kode_layanan = $layanan->kode;
        }

        $data = Unit::updateOrCreate(
            [
                'id' => $request->id,
            ],
            [
                'loket' => $request->loket,
                'loket_no' => $request->loket_no,
                'layanan_id' => $request->layanan_id,
                'kuotajkn' => $request->kuotajkn,
                'kuotanonjkn' => $request->kuotanonjkn,
                'kode_layanan' => $kode_layanan,
                'display_id' => $request->display_id
            ]
        );

        if (!$data) {
            return new JsonResponse(['message' => "Gagal Menyimpan"], 500);
        }

        return new JsonResponse(['message' => "success"], 200);
    }

    public function destroy(Request $request)
    {
        $id = $request->id;
        $data = Unit::where('id', $id);
        $del = $data->delete();

        if (!$del) {
            return response()->json([
                'message' => 'Error on Delete'
            ], 500);
        }

        // $user->log("Menghapus Data Jabatan {$data->nama}");
        return response()->json([
            'message' => 'Data sukses terhapus'
        ], 200);
    }

    // public function synch()
    // {
    //     $polirs = Poli::where('rs5', '1')->get();

    //     $collection = collect($polirs);
    //     $data = $collection->map(function ($item, $key) {
    //         $text = $item['rs2'];
    //         $pecah = explode("Poli ", $text);
    //         $jadi = null;
    //         if ($pecah[0] === '') {
    //             $jadi = $pecah[1];
    //         } else {
    //             $jadi = $pecah[0];
    //         }

    //         $pecah2 = explode("Peny. ", $jadi);

    //         $jadi2 = null;
    //         if ($pecah2[0] === '') {
    //             $jadi2 = $pecah2[1];
    //         } else {
    //             $jadi2 = $pecah2[0];
    //         }


    //         $bpjs = $this->caribpjs($jadi2);

    //         if ($bpjs === null) {
    //             $bpjs = $this->caribpjs($item['rs6']);
    //         }



    //         $dat['nama'] = $jadi2;
    //         $dat['bpjs'] = $bpjs ? $bpjs->poli : null;
    //         // return $dat;
    //         // $coba = [];

    //         MasterPoli::updateOrCreate(
    //             ['kode_simrs' => $item['rs1']],
    //             [
    //                 'kode_bpjs' => $item['rs6'],
    //                 'nama' => $item['rs2'],
    //             ]
    //         );

    //         if ($dat['bpjs'] !== null) {
    //             foreach ($dat['bpjs'] as $key) {
    //                 // array_push($coba, $key->nama);

    //                 PoliBpjs::firstOrCreate(
    //                     ['koders' => $item['rs1'], 'kode' => $key->kode],
    //                     ['nama' => $key->nama]
    //                 );
    //             }
    //         }
    //         return 'ok';
    //     });

    //     if (!$data) {
    //         return new JsonResponse(['message' => 'error'], 500);
    //     }

    //     return new JsonResponse(['message' => 'success'], 200);
    // }

    // public function caribpjs($param)
    // {
    //     $sign = BridgingbpjsHelper::getSignature();
    //     $url = BridgingbpjsHelper::get_url('vclaim') . 'referensi/poli/' . $param;
    //     $response = Http::withHeaders(BridgingbpjsHelper::getHeader())->get($url);
    //     $res = json_decode($response, true);

    //     $kunci = $sign['xconsid'] . $sign['secret_key'] . $sign['xtimestamp'];
    //     $nilairespon = $res["response"];
    //     $hasilakhir = BridgingbpjsHelper::decompress(BridgingbpjsHelper::stringDecrypt($kunci, $nilairespon));

    //     $bpjs = json_decode($hasilakhir);
    //     return $bpjs;
    // }
}
