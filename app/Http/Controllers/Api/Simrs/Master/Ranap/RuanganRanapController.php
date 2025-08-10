<?php

namespace App\Http\Controllers\Api\Simrs\Master\Ranap;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Simrs\Master\GroupRuangRanap;
use App\Models\Simrs\Master\Mkamar;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'tampil' => request('tampil', 'semua'), // semua. aktif. tidak aktif
        ];
        $query = Mkamar::query()
            ->where(function ($list) {
                $list->where('rs1', 'Like', '%' . request('q') . '%')
                    ->orWhere('rs2', 'Like', '%' . request('q') . '%')
                    ->orWhere('rs4', 'Like', '%' . request('q') . '%');
            })
            ->orderBy($req['order_by'], $req['sort'])
            ->when($req['tampil'], function ($q) use ($req) {
                if ($req['tampil'] == 'aktif') {
                    $q->whereNull('hiddens');
                } else if ($req['tampil'] == 'tidak aktif') {
                    $q->whereNotNull('hiddens');
                }
            });

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
        try {
            DB::beginTransaction();
            if (!$kode) {
                $generatedKode = self::generateKodeRuangan($validated['groups'], $validated['rs3']);
                if ($generatedKode['status'] == '0') {
                    return new JsonResponse($generatedKode);
                }
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
                    'rs7' => $validated['rs7'] ?? 0,
                    'bpjskdruang' => $validated['bpjskdruang'] ?? null,
                    'bpjskdkelas' => $validated['bpjskdkelas'] ?? null,
                    'kode_ruang' => $validated['kode_ruang'] ?? null,
                ]
            );
            if (!$data) {
                return new JsonResponse(['message' => 'Data gagal disimpan'], 400);
            }
            DB::commit();
            return new JsonResponse(['data' => $data]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return new JsonResponse([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 410);
        }
    }
    public static function generateKodeRuangan($groupKode, $rs3)
    {
        $baseKode = strtoupper($groupKode . $rs3);
        $kode = $baseKode;
        $counter = 1;
        // kalo exist dan ke hidden, maka promp untuk buka
        $exist = Mkamar::query()->where('rs1', $kode)->exists();
        $ada = Mkamar::where('rs1', $kode)->first();
        if ($exist && $ada) {
            if ($ada->hiddens == '1') {
                $msg = 'Ruangan di hidden, apakah kan dibuka?';
                $groupKode = GroupRuangRanap::where('kode', $ada->rs4)->where('hiddens', '1')->first();
                if ($groupKode) {
                    $msg = 'Ruangan di hidden, apakah kan dibuka?. dan Group Ruangan juga di kunci, silahkan buka dari group ruangan untuk membuka semua ruang ranap group ini';
                }
                return [
                    'kode' => $ada,
                    'message' => $msg,
                    'status' => '0',
                ];
            }
            if ($ada->hiddens == null) {
                return [
                    'kode' => $ada,
                    'message' => 'Ruangan sudah ada, silahkan cek kembali group dan kelas ruangan',
                    'status' => '0',
                ];
            }
        } else {
            while ($exist) {
                $kode = $baseKode . $counter;
                $counter++;
            }
            return [
                'kode' => $kode,
                'status' => '1',
            ];
        }
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
            return new JsonResponse(['message' => 'data tidak ditemukan'], 410);
        }
        $del = $data->update(['hiddens' => '1']);
        if (!$del) {
            return new JsonResponse(['message' => 'data gagal dihapus'], 410);
        }
        return new JsonResponse(['message' => 'data berhasil dihapus'], 200);
    }
    public function buka(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required',
        ], [
            'id.required' => 'id Wajib di isi',
        ]);
        $data = Mkamar::find($validated['id']);
        if (!$data) {
            return new JsonResponse(['message' => 'data tidak ditemukan'], 410);
        }
        $del = $data->update(['hiddens' => null]);
        if (!$del) {
            return new JsonResponse(['message' => 'data gagal dibuka'], 410);
        }
        return new JsonResponse(['message' => 'data berhasil dibuka'], 200);
    }

    public function listGroup()
    {
        $req = [
            'order_by' => request('order_by', 'id'),
            'sort' => request('sort', 'asc'),
            'page' => request('page', 1),
            'per_page' => request('per_page', 10),
            'tampil' => request('tampil', 'semua'), // semua. aktif. tidak aktif
        ];
        $query = GroupRuangRanap::query()
            ->where(function ($list) {
                $list->where('kode', 'Like', '%' . request('q') . '%')
                    ->orWhere('nama', 'Like', '%' . request('q') . '%');
            })
            ->orderBy($req['order_by'], $req['sort'])
            ->when($req['tampil'], function ($q) use ($req) {
                if ($req['tampil'] == 'aktif') {
                    $q->whereNull('hidden');
                } else if ($req['tampil'] == 'tidak aktif') {
                    $q->whereNotNull('hidden');
                }
            });
        // ->whereNull('hidden');

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
            return new JsonResponse(['message' => 'data tidak ditemukan'], 410);
        }
        $ruanganGroup = Mkamar::where('rs4', $data->kode)->get();
        if (count($ruanganGroup) > 0) {
            foreach ($ruanganGroup as $key) {
                $key->update(['hiddens' => '1']);
            }
        }
        $del = $data->update(['hidden' => '1']);
        if (!$del) {
            return new JsonResponse(['message' => 'data gagal dihapus'], 410);
        }
        return new JsonResponse(['message' => 'data berhasil dihapus'], 200);
    }
    public function bukaGroup(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required',
        ], [
            'id.required' => 'id Wajib di isi',
        ]);
        $data = GroupRuangRanap::find($validated['id']);
        if (!$data) {
            return new JsonResponse(['message' => 'data tidak ditemukan'], 410);
        }
        $ruanganGroup = Mkamar::where('rs4', $data->kode)->get();
        if (count($ruanganGroup) > 0) {
            foreach ($ruanganGroup as $key) {
                $key->update(['hiddens' => null]);
            }
        }
        $del = $data->update(['hidden' => null]);
        if (!$del) {
            return new JsonResponse(['message' => 'data gagal dibuka'], 410);
        }
        return new JsonResponse(['message' => 'data berhasil dibuka'], 200);
    }
}
