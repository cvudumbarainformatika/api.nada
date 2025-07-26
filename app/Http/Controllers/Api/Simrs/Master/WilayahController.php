<?php

namespace App\Http\Controllers\Api\Simrs\Master;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mwilayah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WilayahController extends Controller
{
    // public function simpannegara(Request $request)
    // {
    //     $simpannegara = Mwilayah::updateOrcreate(['kd_negara' => $request->kd_negara],
    //     [
    //         'nm_negara' => $request->nm_negara,
    //         // 'kd_propinsi' => $request->kd_propinsi,
    //         // 'nm_propinsi' => $request->nm_propinsi,
    //         // 'kd_kota' => $request->kd_kota,
    //         // 'nm_kota' => $request->nm_kota,
    //         // 'kd_kecamatan' => $request->kd_kecamatan,
    //         // 'nm_kecamatan' => $request->nm_kecamatan,
    //         // 'kd_kelurahan' => $request->kdkeluarahan,
    //         // 'nama_kelurahan' => $request->nm_kelurahan,
    //         // 'kodepost' => $request->kodepos,
    //         // 'kd_rw' => $request->kd_rw,
    //         // 'rw' => $request->rw,
    //         // 'kd_rt' => $request->rt,
    //         // 'rt' => $request->rt
    //     ]);
    //     if(!$simpannegara)
    //     {
    //         return new JsonResponse(['message' => 'DATA TERSIMPAN...!!!'], 200);
    //     }
    //         return new JsonResponse(['message' => 'DATA TIDAK TERSIMPAN...!!!'], 500);
    // }

    public function getnegara()
    {
        $negara = Mwilayah::select('kode1 as kd_negara','wilayah as wilayah')
        ->where('kode2' , '=' , '')->where('kode3' , '=' , '')->where('kode4' , '=' , '')->where('kode5' , '=' , '')
        ->get();

        return new JsonResponse(['message' => 'OK', $negara ], 200);
    }

    public function getpropinsi()
    {
        $kd_negara = request('kd_negara');
        $propinsi = Mwilayah::select('kode2 as propinsi','wilayah as wilayah')
        ->where('kode1', '=' , $kd_negara)->where('kode3', '=' , '')->where('kode4' , '=' , '')
        ->where('kode5' , '=' , '')->where('kode2', '!=', '')
        ->get();

        return new JsonResponse(['message' => 'OK', $propinsi ], 200);
    }

    public function getkotakabupaten()
    {
        $kd_negara = request('kd_negara');
        $kd_propinsi = request('kd_propinsi');
        $kotakabupaten = Mwilayah::select('kode3 as kotakabupaten','wilayah as wilayah')
        ->where('kode1', '=' , $kd_negara)->where('kode2', '=' , $kd_propinsi)
        ->where('kode3', '!=' , '')->where('kode4' , '=' , '') ->where('kode5' , '=' , '')
        ->get();

        return new JsonResponse(['message' => 'OK', $kotakabupaten ], 200);
    }

    public function getkecamatan()
    {
        $kd_negara = request('kd_negara');
        $kd_propinsi = request('kd_propinsi');
        $kd_kotakabupaten = request('kd_kotakabupaten');
        $kotakabupaten = Mwilayah::select('kode4 as kotakabupaten','wilayah as wilayah')
        ->where('kode1', '=' , $kd_negara)->where('kode2', '=' , $kd_propinsi)->where('kode3', '=' , $kd_kotakabupaten)
        ->where('kode4' , '!=' , '') ->where('kode5' , '=' , '')
        ->get();

        return new JsonResponse(['message' => 'OK', $kotakabupaten ], 200);
    }

    public function getkelurahan()
    {
        $kd_negara = request('kd_negara');
        $kd_propinsi = request('kd_propinsi');
        $kd_kotakabupaten = request('kd_kotakabupaten');
        $kd_kecamatan = request('kd_kecamatan');
        $kelurahan = Mwilayah::select('kode5 as kotakabupaten','wilayah as wilayah')
        ->where('kode1', '=' , $kd_negara)->where('kode2', '=' , $kd_propinsi)->where('kode3', '=' , $kd_kotakabupaten)
        ->where('kode4' , '=' , $kd_kecamatan) ->where('kode5' , '!=' , '')
        ->get();

        return new JsonResponse(['message' => 'OK', $kelurahan ], 200);
    }

    public function getwilayah()
    {

        $getwilayah = Mwilayah::select(
            'kode1 as kd_negara',
            'kode2 as propinsi',
            'kode3 as kotakabupaten',
            'kode5 as kotakabupaten',
            'kode4 as kotakabupaten',
            'wilayah as wilayah'
            )
            ->paginate(request('per_page'));

        return new JsonResponse(['message' => 'OK', $getwilayah ], 200);
    }


}
