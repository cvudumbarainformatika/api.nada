<?php

namespace App\Http\Controllers\Api\Satusehat;

use App\Helpers\AuthSatsetHelper;
use App\Helpers\BridgingSatsetHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Farmasinew\MapingKfa;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MapingKfaController extends Controller
{
    public function getMasterObat()
    {
        $obat = Mobatnew::select('kd_obat', 'nama_obat', 'kode_kfa', 'kode_kfa_93')
            ->where('nama_obat', 'LIKE', '%' . request('q') . '%')
            ->with(['kfa'])
            ->where('flag', '')
            ->paginate(request('per_page'));
        $data = collect($obat)['data'];
        $meta = collect($obat)->except('data');

        return new JsonResponse([
            'data' => $data,
            'meta' => $meta,
            'obat' => $obat,
            'req' => request()->all(),
        ]);
    }
    public function getKfa(Request $request)
    {
        $extend = '/kfa-v2/products';
        $token = AuthSatsetHelper::accessToken();
        // $param = '?page=' . request('page') . '&size=' . request('per_page') . '&product_type=farmasi' . '&keyword=' . request('q');
        // $param = '?page=' . request('page') . '&size=' . request('per_page') . '&product_type=farmasi' . '&template_code=' . request('q');
        $param = '?identifier=kfa' . '&code=' . $request->kode_kfa_93;

        $obat = BridgingSatsetHelper::get_data_kfa($extend, $token, $param);
        $data = $obat['result'] ?? null;
        // $adaur = (int)$obat['page'] ?? 0 < (int)$obat['total'] ?? 0 ? 'ada' : null;
        // $meta = [
        //     'obat' => $obat,
        //     'last_page' => $obat['total'] ?? 1,
        //     'total' => $obat['total'] ?? 1,
        //     'total' => $obat['total'] ?? 1,
        //     // 'next_page_url' => $adaur,
        // ];
        if (!$data) {
            return new JsonResponse([
                'message' => 'Data KFA tidak ditemukan',
                'data' => $data,
                'obat' => $obat,
                'req' => $request->all(),
            ], 410);
        }
        $simpan = MapingKfa::updateOrCreate(
            ['kd_obat' => $request->kd_obat],
            [
                'kode_kfa' => $request->kode_kfa,
                'kode_kfa_93' => $request->kode_kfa_93,
                'response' => $obat,
                'dosage_form' => $obat['result']['dosage_form'] ?? null,
                'active_ingredients' => $obat['result']['active_ingredients'] ?? null,
            ]
        );
        return new JsonResponse([
            'message' => 'Response Detail KFA disimpan',
            'data' => $data,
            'obat' => $obat,
            'simpan' => $simpan,
            'req' => $request->all(),
        ]);
    }
    public function simpanMapingKfa(Request $request)
    {

        $data = Mobatnew::where('kd_obat', $request->kd_obat)->first();
        if (!$data) {
            return new JsonResponse([
                'message' => 'Data Obat Tidak Ditemukan'
            ], 410);
        }
        $data->update([
            'kode_kfa' => $request->kode_kfa,
            'kode_kfa_93' => $request->kode_kfa_93
        ]);
        return new JsonResponse([
            'message' => 'data berhasil disimpan',
            'data' => $data
        ]);
    }
}
