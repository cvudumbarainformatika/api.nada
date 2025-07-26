<?php

namespace App\Http\Controllers\Api\Simrs\Pelayanan\Diagnosa;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mdiagnosagizi;
use App\Models\Simrs\Pelayanan\Diagnosa\Diagnosagizi;
use App\Models\Simrs\Pelayanan\Intervensigizi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DiagnosaGiziController extends Controller
{
    public function diagnosagizi()
    {
        $listdiagnosa = Mdiagnosagizi::with(['intervensis'])
            ->get();
        return new JsonResponse($listdiagnosa);
    }

    public function simpandiagnosagizi(Request $request)
    {

        try {
            DB::beginTransaction();

            $user = auth()->user()->pegawai_id;

            $thumb = [];
            foreach ($request->diagnosa as $key => $value) {
                $diagnosagizi = Diagnosagizi::create(
                    [
                        'noreg' => $value['noreg'],
                        'norm' => $value['norm'],
                        'kode' => $value['kode'],
                        'nama' => $value['nama'],
                        'user_input'=> $user,
                        'kdruang' => $value['kdruang'] ?? '',
                    ]
                );

                foreach ($value['details'] as $key => $det) {
                    Intervensigizi::create([
                        'diagnosagizi_kode' => $diagnosagizi->id,
                        'intervensi_id' => $det['intervensi_id']
                    ]);
                }
                array_push($thumb, $diagnosagizi->id);
            }

            DB::commit();

            $success = Diagnosagizi::whereIn('id', $thumb)->get();

            return new JsonResponse(
                [
                    'message' => 'Data Berhasil disimpan',
                    'result' => $success->load(['intervensi.masterintervensi'])
                ],
                200
            );
        } catch (\Exception $e) {
            DB::rollback();
            return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!', 'result' => $e], 500);
        }
    }

    public function deletediagnosagizi(Request $request)
    {
        try {
            DB::beginTransaction();

            $id = $request->id;

            $target = Diagnosagizi::find($id);

            if (!$target) {
                return new JsonResponse(['message' => 'Data tidak ditemukan'], 500);
            }

            Intervensigizi::where('diagnosagizi_kode', $target->id)->delete();

            $target->delete();
            DB::commit();
            return new JsonResponse(
                [
                    'message' => 'Data Berhasil dihapus'
                ],
                200
            );
        } catch (\Exception $e) {
            DB::rollback();
            return new JsonResponse(['message' => 'Data Gagal Disimpan...!!!', 'result' => $e], 500);
        }
    }
}
