<?php

namespace App\Http\Controllers\Api\Simrs\Master\Poliklinik;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Poli;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RuanganPoliController extends Controller
{
    // model : Poli
    public function list(): JsonResponse
    {

        $req = [
            'order_by' => request('order_by', 'rs1'),
            'sort' => request('sort', 'asc'),
            'page' => request('page', 1),
            'per_page' => request('per_page', 10),
        ];
        // return new JsonResponse(request()->all());
        $query = Poli::query()
            ->where(function ($list) {
                $list->where('rs1', 'Like', '%' . request('q') . '%')
                    ->orWhere('rs2', 'Like', '%' . request('q') . '%')
                    ->orWhere('rs3', 'Like', '%' . request('q') . '%')
                    ->orWhere('rs4', 'Like', '%' . request('q') . '%')
                    ->orWhere('rs6', 'Like', '%' . request('q') . '%')
                    ->orWhere('rs7', 'Like', '%' . request('q') . '%')
                    ->orWhere('panggil_antrian', 'Like', '%' . request('q') . '%');
            })
            ->where('rs1', '!=', 'POL014')
            ->orderBy($req['order_by'], $req['sort'])
            ->whereNull('hidden');

        $totalCount = (clone $query)->count();
        $data = $query->simplePaginate($req['per_page']);

        $resp = ResponseHelper::responseGetSimplePaginate($data, $req, $totalCount);
        // ->paginate(request('per_page'));

        return new JsonResponse($resp);
    }
    public function simpan(Request $request)
    {

        /* rules rs 4 =>
        * Penunjang
        * Poliklinik
        */
        $pri = $request->rs1 ?? '';

        $statt = $request->rs4 ?? null;
        if ($pri == 'POL014') {
            return new JsonResponse(['message' => 'Data tidak boleh di edit'], 410);
        }
        $validated = $request->validate([
            'rs2' => 'required',
            'rs3' => 'nullable',
            'rs4' => 'nullable',
            'rs5' => 'nullable',
            'rs6' => 'nullable',
            'rs7' => 'nullable',
            'panggil_antrian' => 'nullable',
        ], [
            'rs1.required' => 'Kode Poliklinik Wajib Diisi',
            'rs1.unique' => 'Kode Poliklinik Sudah Ada',
            'rs2.required' => 'Nama Poliklinik Wajib Diisi',
        ]);
        if (empty($pri)) {

            if ($statt == 'Penunjang') {
                $rw1 = Poli::where('rs1', 'like', 'PEN%')->get()->max('rs1');
                $max = (int)substr($rw1, 3) + 1;
                $hasil = str_pad($max, 3, '0', STR_PAD_LEFT);
                $pri = 'PEN' . $hasil;
            } else if ($statt == 'Poliklinik') {
                $rw1 = Poli::where('rs1', 'like', 'POL%')->get()->max('rs1');
                $max = (int)substr($rw1, 3) + 1;
                $hasil = str_pad($max, 3, '0', STR_PAD_LEFT);
                $pri = 'POL' . $hasil;
            } else {
                $rw1 = Poli::where('rs1', 'Not like', 'PEN%')->where('rs1', 'Not like', 'POL%')->get()->max('rs1');
                $max = (int)substr($rw1, 3) + 1;
                $hasil = str_pad($max, 3, '0', STR_PAD_LEFT);
                $pri = 'UNG' . $hasil;
            }

            if (!empty($pri)) {
                $simpan = Poli::create(
                    [
                        'rs1' => $pri,
                        'rs2' => $validated['rs2'],
                        'rs3' => $validated['rs3'] ?? '',
                        'rs4' => $validated['rs4'] ?? '',
                        'rs5' => $validated['rs5'] ?? '',
                        'rs6' => $validated['rs6'] ?? '',
                        'rs7' => $validated['rs7'] ?? '',
                        'panggil_antrian' => $validated['panggil_antrian'] ?? '',
                    ]
                );
            } else {
                return new JsonResponse(['message' => 'data gagal disimpan, kode ' . $statt . ' tidak dapat di generate'], 500);
            }
        } else {

            $simpan = Poli::updateOrCreate(
                [
                    'rs1' => $pri
                ],
                [
                    'rs2' => $validated['rs2'],
                    'rs3' => $validated['rs3'] ?? '',
                    'rs4' => $validated['rs4'] ?? '',
                    'rs5' => $validated['rs5'] ?? '',
                    'rs6' => $validated['rs6'] ?? '',
                    'rs7' => $validated['rs7'] ?? '',
                    'panggil_antrian' => $validated['panggil_antrian'] ?? '',
                ]
            );
        }

        if (!$simpan) {
            return new JsonResponse(['message' => 'data gagal disimpan'], 500);
        }

        return new JsonResponse([
            'message' => 'data berhasil disimpan',

            'data' => $simpan,
        ], 200);
    }
    public function hapus(Request $request)
    {
        $validated = $request->validate([
            'rs1' => 'required',
        ], [
            'rs1.required' => 'Kode Poliklinik Wajib Diisi',
        ]);
        $data = Poli::find($validated['rs1']);
        if (!$data) {
            return new JsonResponse(['message' => 'data tidak ditemukan'], 500);
        }
        $del = $data->update(['hidden' => '1']);
        if (!$del) {
            return new JsonResponse(['message' => 'data gagal dihapus'], 500);
        }
        return new JsonResponse(['message' => 'data berhasil dihapus'], 200);
    }
}
