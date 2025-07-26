<?php

namespace App\Http\Controllers\Api\Antrean\master;

use App\Helpers\BridgingbpjsHelper;
use App\Http\Controllers\Controller;
use App\Models\Antrean\Layanan;
use App\Models\Antrean\PoliBpjs;
// use App\Models\Antrean\Unit;
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

class LayananController extends Controller
{
    public function index()
    {
        // return new JsonResponse(['message' => 'ok']);
        $data = Layanan::when(request('q'), function ($search, $q) {
            $search->where('nama', 'LIKE', '%' . $q . '%');
        })
            // ->with(['layanan'])
            ->orderBy('id_layanan', 'ASC')
            // ->orderBy('loket_no', 'ASC')
            ->paginate(request('per_page'));

        return new JsonResponse($data);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_layanan' => 'required|unique:antrean.layanans,id_layanan, ' . $request->id,
            'kode' => 'required|unique:antrean.layanans,kode, ' . $request->id
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }



        $data = Layanan::updateOrCreate(
            [
                'id' => $request->id,
                'id_layanan' => $request->id_layanan,
            ],
            [
                'nama' => $request->nama,
                'kode' => $request->kode,
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
        $data = Layanan::where('id', $id);
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
        $polirs = Poli::where('rs4', 'Poliklinik')->get();

        $collection = collect($polirs);
        $data = $collection->map(function ($item, $key) {
            $text = $item['rs2'];
            // $pecah = explode("Poli ", $text);
            // $jadi = null;
            // if ($pecah[0] === '') {
            //     $jadi = $pecah[1];
            // } else {
            //     $jadi = $pecah[0];
            // }

            // $pecah2 = explode("Peny. ", $jadi);

            // $jadi2 = null;
            // if ($pecah2[0] === '') {
            //     $jadi2 = $pecah2[1];
            // } else {
            //     $jadi2 = $pecah2[0];
            // }

            // // return $jadi;
            // $bpjs = $this->caribpjs($jadi2);

            // if ($bpjs['metadata']['code'] === 201) {
            //     $bpjs = $this->caribpjs($item['rs6']);
            // }
            // // return $bpjs['result'];



            // $dat['nama'] = $jadi2;
            // $dat['bpjs'] = $bpjs['result'] !== 'Tidak ditemukan' ? $bpjs['result']->poli : null;
            // // // return $dat;
            // // // $coba = [];

            Layanan::updateOrCreate(
                ['id_layanan' => $item['rs1']],
                [
                    'kode_bpjs' => $item['rs6'],
                    'nama' => $item['rs2'],
                ]
            );

            // if ($dat['bpjs'] !== null) {
            //     foreach ($dat['bpjs'] as $key) {
            //         // array_push($coba, $key->nama);

            //         Layanan::updateOrCreate(
            //             ['id_layanan' => $item['rs1']],
            //             ['nama' => $key->nama, 'kode_bpjs' => $key->kode]
            //         );
            //     }
            // }
            return 'ok';
        });

        // if (!$data) {
        //     return new JsonResponse(['message' => 'error'], 500);
        // }

        // return new JsonResponse(['message' => 'success'], 200);
        return response()->json($data);
    }

    public function caribpjs($param)
    {
        // $sign = BridgingbpjsHelper::getSignature();
        return BridgingbpjsHelper::get_url('vclaim', 'referensi/poli/' . $param);
    }
}
