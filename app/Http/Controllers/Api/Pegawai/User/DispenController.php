<?php

namespace App\Http\Controllers\Api\Pegawai\User;

use App\Http\Controllers\Controller;
use App\Models\Pegawai\Akses\User;
use App\Models\Pegawai\Alpha;
use App\Models\Pegawai\JadwalAbsen;
use App\Models\Pegawai\Libur;
use App\Models\Pegawai\TransaksiAbsen;
use App\Models\Sigarang\Pegawai;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DispenController extends Controller
{
    //
    public function index()
    {
        $data = Pegawai::where('aktif', '=', 'AKTIF')
            ->where(function ($query) {
                $query->when(request('flag') ?? false, function ($search, $q) {
                    return $search->where('flag', '=', $q);
                });
                $query->when(request('ruang') ?? false, function ($search, $q) {
                    return $search->where('ruang', '=', $q);
                });
            })
            ->filter(request(['q']))
            ->with(['ruangan', 'user'])
            ->paginate(request('per_page'));

        return new JsonResponse($data);
    }

    public function store(Request $request)
    {
        // $data = $request->all();
        $coll = $request->user_ids;
        $ids = explode(',', $coll);

        foreach ($ids as $user_id) {
            Libur::create([
                'user_id' => $user_id,
                'flag' => $request->flag,
                'alasan' => $request->alasan,
                'tanggal' => $request->tanggal
            ]);
        }
        return new JsonResponse($ids);
    }
}
