<?php

namespace App\Http\Controllers\Api\Simrs\Hemodialisa\Pelayanan;

use App\Http\Controllers\Controller;
use App\Models\Simrs\Penunjang\Hemodialisa\PengkajianHemodialisa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PengkajianController extends Controller
{
    public function simpan(Request $request)
    {
        if ($request->has('id')) {
            $data = PengkajianHemodialisa::find($request->id);
            $data->update([

                // 'rs4' => $request->alasan,
                // 'rs5' => $request->riwayat,
                // 'rs6' => $request->hubungan,
                // 'rs7' => $request->psikologis,
                // 'rs8' => $request->lain,
                // 'rs9' => $request->td,
                // 'rs10' => $request->nadi,
                // 'rs11' => $request->suhu,
                // 'rs12' => $request->tb,
                // 'rs13' => $request->bb,
                // 'rs14' => $request->penurunanBB,
                // 'rs15' => $request->asupanNafsu,
                // 'rs16' => $request->diagKhus,
                'rs17' => $request->fungsional,
                'rs18' => $request->lainx,
                'jam_lapor_fs' => $request->jam_lapor_fs,
                'resiko_jatuh' => $request->resiko_jatuh,
            ]);
        } else {
            $data = PengkajianHemodialisa::updateOrCreate(
                [
                    'id' => $request->id,
                    'rs1' => $request->noreg,
                    'rs2' => $request->norm,
                ],
                [
                    'rs3' => $request->tgl,
                    // 'rs4' => $request->alasan,
                    // 'rs5' => $request->riwayat,
                    // 'rs6' => $request->hubungan,
                    // 'rs7' => $request->psikologis,
                    // 'rs8' => $request->lain,
                    // 'rs9' => $request->td,
                    // 'rs10' => $request->nadi,
                    // 'rs11' => $request->suhu,
                    // 'rs12' => $request->tb,
                    // 'rs13' => $request->bb,
                    // 'rs14' => $request->penurunanBB,
                    // 'rs15' => $request->asupanNafsu,
                    // 'rs16' => $request->diagKhus,
                    'rs17' => $request->fungsional,
                    'rs18' => $request->lainx,
                    'jam_lapor_fs' => $request->jam_lapor_fs,
                    'resiko_jatuh' => $request->resiko_jatuh,
                ]
            );
        }
        return new JsonResponse([
            'message' => 'Pengkajian berhasil disimpan',
            'data' => $data,
            'req' => $request->all()
        ]);
    }
    public function hapus(Request $request)
    {
        $data = PengkajianHemodialisa::find($request->id);
        if (!$data) {
            return new JsonResponse([
                'message' => 'Data tidak ditemukan',
            ], 410);
        }
        $data->delete();
        return new JsonResponse([
            'message' => 'Pengkajian berhasil dihapus'
        ]);
    }
}
