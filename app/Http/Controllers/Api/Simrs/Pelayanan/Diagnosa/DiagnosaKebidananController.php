<?php

namespace App\Http\Controllers\Api\Simrs\Pelayanan\Diagnosa;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mdiagnosakebidanan;
use App\Models\Simrs\Pelayanan\Diagnosa\Diagnosakebidanan;
use App\Models\Simrs\Pelayanan\Intervensikebidanan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DiagnosaKebidananController extends Controller
{
    public function diagnosakebidanan()
    {
        $listdiagnosa = Mdiagnosakebidanan::with(['intervensis'])
            ->get();
        return new JsonResponse($listdiagnosa);
    }

    public function simpandiagnosakebidanan(Request $request)
    {

        try {
            DB::beginTransaction();

            $user = auth()->user()->pegawai_id;

            $thumb = [];
            foreach ($request->diagnosa as $key => $value) {
                $diagnosakebidanan = Diagnosakebidanan::create(
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
                    Intervensikebidanan::create([
                        'diagnosakebidanan_kode' => $diagnosakebidanan->id,
                        'intervensi_id' => $det['intervensi_id']
                    ]);
                }
                array_push($thumb, $diagnosakebidanan->id);
            }

            DB::commit();

            $success = Diagnosakebidanan::whereIn('id', $thumb)->get();

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

    public function deletediagnosakebidanan(Request $request)
    {
        try {
            DB::beginTransaction();

            $id = $request->id;

            $target = Diagnosakebidanan::find($id);

            if (!$target) {
                return new JsonResponse(['message' => 'Data tidak ditemukan'], 500);
            }

            Intervensikebidanan::where('diagnosakebidanan_kode', $target->id)->delete();

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
