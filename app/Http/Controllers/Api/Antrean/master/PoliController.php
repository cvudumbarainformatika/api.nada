<?php

namespace App\Http\Controllers\Api\Antrean\master;

use App\Helpers\BridgingbpjsHelper;
use App\Http\Controllers\Controller;
use App\Models\Antrean\MasterPoli;
use App\Models\Antrean\PoliBpjs;
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

class PoliController extends Controller
{
    public function index()
    {
        // return new JsonResponse(['message' => 'ok']);
        $data = MasterPoli::when(request('q'), function ($search, $q) {
            $search->where('nama', 'LIKE', '%' . $q . '%')
                ->orWhere('kode_simrs', 'LIKE', '%' . $q . '%');
        })
            ->with(['poli_bpjs', 'referensi_poli_bpjs'])->paginate(request('per_page'));

        return new JsonResponse($data);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kode_simrs' => 'required|unique:antrean.masterpoli,kode_simrs, ' . $request->id
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = MasterPoli::updateOrCreate(
            [
                'id' => $request->id,
                'kode_bpjs' => $request->kode_bpjs,
            ],
            [
                'kode_simrs' => $request->kode_simrs,
                'nama' => $request->nama,
                'max_of' => $request->max,
                'max_ol' => $request->max_ol,
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
        $data = MasterPoli::where('id', $id);
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

    public function synch()
    {
        $polirs = Poli::where('rs5', '1')->get();

        $collection = collect($polirs);
        $data = $collection->map(function ($item, $key) {
            $text = $item['rs2'];
            $pecah = explode("Poli ", $text);
            $jadi = null;
            if ($pecah[0] === '') {
                $jadi = $pecah[1];
            } else {
                $jadi = $pecah[0];
            }

            $pecah2 = explode("Peny. ", $jadi);

            $jadi2 = null;
            if ($pecah2[0] === '') {
                $jadi2 = $pecah2[1];
            } else {
                $jadi2 = $pecah2[0];
            }


            $bpjs = $this->caribpjs($jadi2);

            if ($bpjs === null) {
                $bpjs = $this->caribpjs($item['rs6']);
            }



            $dat['nama'] = $jadi2;
            $dat['bpjs'] = $bpjs ? $bpjs->poli : null;
            // return $dat;
            // $coba = [];

            MasterPoli::updateOrCreate(
                ['kode_simrs' => $item['rs1']],
                [
                    'kode_bpjs' => $item['rs6'],
                    'nama' => $item['rs2'],
                ]
            );

            if ($dat['bpjs'] !== null) {
                foreach ($dat['bpjs'] as $key) {
                    // array_push($coba, $key->nama);

                    PoliBpjs::firstOrCreate(
                        ['koders' => $item['rs1'], 'kode' => $key->kode],
                        ['nama' => $key->nama]
                    );
                }
            }
            return 'ok';
        });

        if (!$data) {
            return new JsonResponse(['message' => 'error'], 500);
        }

        return new JsonResponse(['message' => 'success'], 200);
    }

    public function caribpjs($param)
    {
        $sign = BridgingbpjsHelper::getSignature();
        $url = BridgingbpjsHelper::get_url('vclaim') . 'referensi/poli/' . $param;
        $response = Http::withHeaders(BridgingbpjsHelper::getHeader())->get($url);
        $res = json_decode($response, true);

        $kunci = $sign['xconsid'] . $sign['secret_key'] . $sign['xtimestamp'];
        $nilairespon = $res["response"];
        $hasilakhir = BridgingbpjsHelper::decompress(BridgingbpjsHelper::stringDecrypt($kunci, $nilairespon));

        $bpjs = json_decode($hasilakhir);
        return $bpjs;
    }
}
