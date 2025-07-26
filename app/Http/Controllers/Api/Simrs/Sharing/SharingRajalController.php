<?php

namespace App\Http\Controllers\Api\Simrs\Sharing;

use App\Helpers\FormatingHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\MsharingRajal;
use App\Models\Simrs\Sharing\SharingTrans;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SharingRajalController extends Controller
{
    public function dialogmaster()
    {
        $dialog = MsharingRajal::where('hidden', '')->get();
        return new JsonResponse($dialog);
    }

    public function simpansharing(Request $request)
    {
        $user = FormatingHelper::session_user();
        $simpanpoli = SharingTrans::firstOrCreate(
            [
                'noreg' => $request->noreg,
                'norm' => $request->norm,
                'kdRuang' => $request->kodepoli,
                'kodeAkun' => $request->kodesistembayar,
                'kode' => $request->kodesharing,
                'nominal' => $request->nominal,
                'jumlah' => $request->jumlah,
                'subtotal' => $request->subtotal,
                'tglEntry' => date('Y-m-d H:i:s'),
                'userEntry' => $user['kodesimrs'],
            ]
        );
        if (!$simpanpoli) {
            return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!'], 500);
        }
        return new JsonResponse(
            [
                'message' => 'Data Berhasil Disimpan...!!!',
                'result' => $simpanpoli
            ],
            200
        );
    }

    public function listpermintaansharing()
    {
        $list = SharingTrans::select(
            'noreg',
            'norm',
            'tglEntry',
            'kdRuang',
            'rs15.rs2 as nama_panggil',
            DB::raw('concat(rs15.rs3," ",rs15.gelardepan," ",rs15.rs2," ",rs15.gelarbelakang) as nama'),
            DB::raw('concat(rs15.rs4," KEL ",rs15.rs5," RT ",rs15.rs7," RW ",rs15.rs8," ",rs15.rs6," ",rs15.rs11," ",rs15.rs10) as alamat'),
            DB::raw('concat(TIMESTAMPDIFF(YEAR, rs15.rs16, CURDATE())," Tahun ",
                TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()) % 12," Bulan ",
                TIMESTAMPDIFF(DAY, TIMESTAMPADD(MONTH, TIMESTAMPDIFF(MONTH, rs15.rs16, CURDATE()), rs15.rs16), CURDATE()), " Hari") AS usia'),
            'rs15.rs16 as tgllahir',
            'rs15.rs17 as kelamin',
            'rs15.rs19 as pendidikan',
            'rs15.rs22 as agama',
            'rs15.rs37 as templahir',
            'rs15.rs39 as suku',
            'rs15.rs40 as jenispasien',
            'rs15.rs46 as noka',
            'rs15.rs49 as nktp',
            'rs15.rs55 as nohp',
        )->leftjoin('rs15', 'rs15.rs1', '=', 'sharingRajal.norm') //pasien
            ->where('rs15.rs2', 'like', '%' . request('cari') . '%')
            ->where('flag', '')
            ->paginate(request('per_page'));
        return new JsonResponse($list);
    }

    public function updatesimpansharing(Request $request)
    {
        $cek = FormatingHelper::session_user();
        $update = SharingTrans::where('noreg', $request->noreg)->where('kdRuang', $request->kodepoli)->first();
        $update->flag = '1';
        $update->klaimBpjs = $request->klaimbpjs;
        $update->tglterima = date('Y-m-d h:i:s');
        $update->user = $cek['kodesimrs'];
        $update->save();

        return new JsonResponse(['message' => 'Data Berhasil Disimpan'], 200);
    }
}
