<?php

namespace App\Http\Controllers\Api\Simrs\Master\Ranap;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\GroupRuangRanap;
use App\Models\Simrs\Master\Mkamar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Group;

class RuanganRanapController extends Controller
{
    public function list(): JsonResponse
    {
        $req = [
            'order_by' => request('order_by', 'rs1'),
            'sort' => request('sort', 'asc'),
            'page' => request('page', 1),
            'per_page' => request('per_page', 10),
        ];
        $query = Mkamar::query()
            ->where(function ($list) {
                $list->where('rs1', 'Like', '%' . request('q') . '%')
                    ->orWhere('rs2', 'Like', '%' . request('q') . '%')
                    ->orWhere('rs4', 'Like', '%' . request('q') . '%');
            })
            ->orderBy($req['order_by'], $req['sort'])
            ->whereNull('hiddens');

        $totalCount = (clone $query)->count();
        $data = $query->simplePaginate($req['per_page']);

        $resp = ResponseHelper::responseGetSimplePaginate($data, $req, $totalCount);


        return new JsonResponse($resp);
    }
    public function simpan(Request $request)
    {
        // 1. buat juga tabel group ruang ranap
        // 2. rs4 dan groups di isi sama group ini di isi untuk jaga2 di kodingan lama biar tidak error.
        // 3. acuan di ambil dari rs4
        $kode = $request->kode;
        $validated = $request->validate([
            'nama' => 'required',
            'rs3' => 'required', // kelas sama dengan jenis
            'groups' => 'required', // group atau rs4
            'rs5' => 'required', // nama group
            'rs6' => 'nullable', // ga tau apa
            'rs7' => 'nullable', // ga tau apa juga
            'bpjskdruang' => 'nullable', // kode ruang bpjs
            'bpjskdkelas' => 'nullable', // kelas bpjs
            'kode_ruang' => 'nullable', // maping dengan kepegx.ruangs
        ], [
            'nama.required' => 'Nama Ruangan Wajib di isi',
            'groups.required' => 'Kode Group Ruangan Wajib di isi',
            'rs3.required' => 'Kelas Ruangan Wajib di isi',
            'rs5.required' => 'Nama Group Ruangan Wajib di isi',
        ]);
        if (!$kode) {
            $generatedKode = self::generateKodeRuangan($validated['groups'], $validated['rs3']);
        } else {
            $generatedKode = $kode;
        }
        $data = Mkamar::updateOrCreate(
            [
                'rs1' => $generatedKode,
            ],
            [
                'rs2' => $validated['nama'],
                'rs3' => $validated['rs3'],
                'jenis' => $validated['rs3'],
                'rs4' => $validated['groups'],
                'groups' => $validated['groups'],
                'rs5' => $validated['rs5'],
                'groups_nama' => $validated['rs5'],
                'rs6' => $validated['rs6'] ?? null,
                'rs7' => $validated['rs7'] ?? null,
                'bpjskdruang' => $validated['bpjskdruang'] ?? null,
                'bpjskdkelas' => $validated['bpjskdkelas'] ?? null,
                'kode_ruang' => $validated['kode_ruang'] ?? null,
            ]
        );
        if (!$data) {
            return new JsonResponse(['message' => 'Data gagal disimpan'], 400);
        }
        return new JsonResponse(['data' => $data]);
    }
    public static function generateKodeRuangan($groupKode, $rs3)
    {
        $baseKode = strtoupper($groupKode . $rs3);
        $kode = $baseKode;
        $counter = 1;

        while (Mkamar::query()->where('rs1', $kode)->exists()) {
            $kode = $baseKode . $counter;
            $counter++;
        }

        return $kode;
    }
    public function hapus(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required',
        ], [
            'id.required' => 'id Wajib di isi',
        ]);
        $data = Mkamar::find($validated['id']);
        if (!$data) {
            return new JsonResponse(['message' => 'data tidak ditemukan'], 500);
        }
        $del = $data->update(['hiddens' => '1']);
        if (!$del) {
            return new JsonResponse(['message' => 'data gagal dihapus'], 500);
        }
        return new JsonResponse(['message' => 'data berhasil dihapus'], 200);
    }
    public function listGroup()
    {
        $req = [
            'order_by' => request('order_by', 'id'),
            'sort' => request('sort', 'asc'),
            'page' => request('page', 1),
            'per_page' => request('per_page', 10),
        ];
        $query = GroupRuangRanap::query()
            ->where(function ($list) {
                $list->where('kode', 'Like', '%' . request('q') . '%')
                    ->orWhere('nama', 'Like', '%' . request('q') . '%');
            })
            ->orderBy($req['order_by'], $req['sort'])
            ->whereNull('hidden');

        $totalCount = (clone $query)->count();
        $data = $query->simplePaginate($req['per_page']);

        $resp = ResponseHelper::responseGetSimplePaginate($data, $req, $totalCount);


        return new JsonResponse($resp);
    }
    public function simpanGroup(Request $request)
    {
        $kode = $request->kode;
        $validated = $request->validate([
            'nama' => 'required',
        ], [
            'nama.required' => 'Nama Group Ruangan Wajib di isi',
        ]);
        if (!$kode) {
            $generatedKode = self::generateKodeUnik($validated['nama']);
        } else {
            $generatedKode = $kode;
        }
        $group = GroupRuangRanap::updateOrCreate(
            [
                'kode' => $generatedKode,
            ],
            [
                'nama' => $validated['nama'],
            ]
        );
        if (!$group) {
            return new JsonResponse(['message' => 'Data gagal disimpan'], 400);
        }
        return new JsonResponse(['data' => $group]);
    }
    public static function generateKodeUnik($nama)
    {
        // Pisahkan nama jadi array kata
        $kata = preg_split('/\s+/', trim($nama));

        // Bersihkan karakter non-huruf
        $kata = array_map(fn($k) => preg_replace('/[^a-z]/i', '', strtolower($k)), $kata);

        if (count($kata) > 1) {
            // Nama lebih dari 1 kata → mulai huruf pertama kata1 + huruf pertama kata2
            $baseKode = strtoupper($kata[0][0] . $kata[1][0]);
        } else {
            // Nama 1 kata → minimal 2 huruf
            $baseKode = strtoupper(substr($kata[0], 0, 2));
        }

        $kode = $baseKode;
        $i = 1;

        // Loop cek unik
        while (GroupRuangRanap::query()->where('kode', $kode)->exists()) {
            if (count($kata) > 1) {
                // Tambah huruf dari kata pertama lalu kata kedua
                $kode = strtoupper(substr($kata[0], 0, $i + 1) . $kata[1][0]);
            } else {
                // Tambah huruf untuk nama 1 kata
                $kode = strtoupper(substr($kata[0], 0, $i + 2)); // +2 supaya tetap minimal 2 huruf
            }
            $i++;
            if ($i > strlen($kata[0])) break; // mencegah loop tak terbatas
        }

        return $kode;
    }
    public function hapusGroup(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required',
        ], [
            'id.required' => 'id Wajib di isi',
        ]);
        $data = GroupRuangRanap::find($validated['id']);
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
