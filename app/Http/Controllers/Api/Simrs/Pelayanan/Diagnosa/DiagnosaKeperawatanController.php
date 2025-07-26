<?php

namespace App\Http\Controllers\Api\Simrs\Pelayanan\Diagnosa;

use App\Http\Controllers\Api\Simrs\Bridgingeklaim\EwseklaimController;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\Mdiagnosakeperawatan;
use App\Models\Simrs\Pelayanan\Diagnosa\Diagnosa;
use App\Models\Simrs\Pelayanan\Diagnosa\Diagnosakeperawatan;
use App\Models\Simrs\Pelayanan\Intervensikeperawatan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DiagnosaKeperawatanController extends Controller
{
    public function diagnosakeperawatan()
    {
        $listdiagnosa = Mdiagnosakeperawatan::select('kode', 'nama')
        ->with(['intervensis:id,nama,group,mdiagnosakeperawatan_kode,kdSnwmed,display'])
            ->get();


        return new JsonResponse($listdiagnosa);
    }


    public function listdiagnosakeperawatan()
    {
       $data = Diagnosakeperawatan::where('noreg', request('noreg'))
       ->with('intervensi', 'intervensi.masterintervensi')->get();

       return new JsonResponse($data);
    }

    public function simpandiagnosakeperawatan(Request $request)
    {

        try {
            DB::beginTransaction();

            $thumb = [];
            $user = auth()->user()->pegawai_id;
            foreach ($request->diagnosa as $key => $value) {
                $diagnosakeperawatan = Diagnosakeperawatan::create(
                    [
                        'noreg' => $value['noreg'],
                        'norm' => $value['norm'],
                        'kode' => $value['kode'],
                        'nama' => $value['nama'],
                        'user_input'=> $user,
                        'kdruang'=>  $value['kdruang'] ?? '',
                    ]
                );

                foreach ($value['details'] as $key => $det) {
                    Intervensikeperawatan::create([
                        'diagnosakeperawatan_kode' => $diagnosakeperawatan->id,
                        'intervensi_id' => $det['intervensi_id']
                    ]);
                }
                array_push($thumb, $diagnosakeperawatan->id);
            }

            DB::commit();

            $success = Diagnosakeperawatan::whereIn('id', $thumb)->get();

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

    public function deletediagnosakeperawatan(Request $request)
    {
        try {
            DB::beginTransaction();

            $id = $request->id;

            $target = Diagnosakeperawatan::find($id);

            if (!$target) {
                return new JsonResponse(['message' => 'Data tidak ditemukan'], 500);
            }

            Intervensikeperawatan::where('diagnosakeperawatan_kode', $target->id)->delete();

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
