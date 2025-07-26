<?php

namespace App\Http\Controllers\Api\Pegawai\User;

use App\Http\Controllers\Controller;
use App\Models\Pegawai\Alpha;
use App\Models\Pegawai\Hari;
use App\Models\Pegawai\JadwalAbsen;
use App\Models\Pegawai\Kategory;
use App\Models\Pegawai\Libur;
use App\Models\Pegawai\TransaksiAbsen;
use App\Models\Sigarang\Pegawai;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LiburController extends Controller
{
    //
    public function index()
    {
        $userIds = User::query()
            ->where('nama', 'LIKE', '%' . request('q') . '%')
            ->pluck('id');

        $data = Libur::with(['user'])
            // ->whereHas('user', function ($q) {
            //     $q->where('nama', 'LIKE', '%' . request('q') . '%');
            // })
            ->whereIn('user_id', $userIds)
            ->orderBy(request('order_by'), request('sort'))
            ->when(request('tanggal'), function ($tg) {
                $tg->where('tanggal', request('tanggal'));
            })
            ->when(request('bulan'), function ($tg) {
                $tg->whereMonth('tanggal',  request('bulan'));
            })
            ->paginate(request('per_page'));
        return new JsonResponse($data);
    }

    public function month()
    {
        $tahun = request('tahun') ? request('tahun') : date('Y');
        $bulan = request('bulan') ? request('bulan') : date('m');
        $from = $tahun . '-' . $bulan . '-01';
        $to = $tahun . '-' . $bulan . '-31';
        $data = Libur::where('tanggal', '>=', $from)
            ->where('tanggal', '<=', $to)
            ->with('user')
            ->get();

        foreach ($data as $key) {
            $temp = explode('-', $key['tanggal']);
            $day = $temp[2];
            $key['day'] = $day;
        }
        return new JsonResponse($data);
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'tanggal' => 'required',
            'flag' => 'required',
        ]);
        if ($validator->fails()) {
            return new JsonResponse(['message' => 'isi data yang belum terisi'], 422);
        }
        $path = '';
        // $data = Libur::create($request->all());
        $data = Libur::updateOrCreate(
            [
                'user_id' => $request->user_id,
                'tanggal' => $request->tanggal
            ],
            [
                'flag' => $request->flag,
                'alasan' => $request->alasan,
            ]
        );
        if (!$data) {
            return new JsonResponse(['message' => 'Gagal menyimpan data', 'request' => $request->all()], 500);
        }
        if ($request->has('gambar')) {
            $path = $request->file('gambar')->store('image', 'public');
            // array_merge($request, ['image' => $path]);
            $data->update(['image' => $path]);
        }

        return new JsonResponse(['message' => 'Berhasil menyimpan data', 'request' => $request->all()], 201);
    }
    public function storeMultiDate(Request $request)
    {
        // return new JsonResponse(['request' => $request->all()], 410);
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'tanggal' => 'required',
            'flag' => 'required',
        ]);
        if ($validator->fails()) {
            return new JsonResponse(['message' => 'isi data yang belum terisi'], 422);
        }
        $path = '';
        // $data = Libur::create($request->all());
        $coll = $request->tanggal;
        $tanggals = explode(',', $coll);
        foreach ($tanggals as $tang) {
            $data = Libur::updateOrCreate(
                [
                    'user_id' => $request->user_id,
                    'tanggal' => $tang
                ],
                [
                    'flag' => $request->flag,
                    'alasan' => $request->alasan,
                ]
            );
            if (!$data) {
                return new JsonResponse(['message' => 'Gagal menyimpan data', 'request' => $request->all()], 500);
            }
            if ($request->has('gambar')) {
                $path = $request->file('gambar')->store('image', 'public');
                // array_merge($request, ['image' => $path]);
                $data->update(['image' => $path]);
            }
        }
        return new JsonResponse(['message' => 'Berhasil menyimpan data', 'request' => $request->all()], 201);
    }
    public function getPegawai()
    {
        $data = Pegawai::select('pegawai.id', 'pegawai.nama')
            ->leftJoin('jadwal_absens', 'jadwal_absens.pegawai_id', '=', 'pegawai.id')
            ->where('pegawai.aktif', 'AKTIF')
            ->where(function ($q) {
                $q->where('pegawai.nama', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('pegawai.nik', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('pegawai.nip', 'LIKE', '%' . request('q') . '%')
                    ->orWhere('pegawai.nip_baru', 'LIKE', '%' . request('q') . '%');
            })

            ->where('jadwal_absens.kategory_id', request('id_kategory'))
            ->groupBy('jadwal_absens.pegawai_id')
            ->limit(50)
            ->get();
        return new JsonResponse($data);
    }
    public function getKategori()
    {
        $data = Kategory::whereIn('id', [1, 2])
            ->get();
        return new JsonResponse($data);
    }
    public function getHari()
    {
        $data = Hari::get();
        return new JsonResponse($data);
    }
    public function getJadwal()
    {
        $data = JadwalAbsen::where('kategory_id', request('id_kategory'))
            ->when(request('id_peg'), function ($q) {
                $q->where('pegawai_id', request('id_peg'));
            })
            ->when(request('hari'), function ($q) {
                $q->whereIn('hari', request('hari'));
            })
            ->with('pegawai:id,nama')
            ->limit(20)
            ->get();
        return new JsonResponse($data);
    }
    public function simpanPerubahanJadwal(Request $request)
    {
        $kategory = $request->id_kategory;
        $id_peg = $request->id_peg;
        $hari = $request->hari;
        $masuk = $request->masuk;
        $pulang = $request->pulang;
        if (!$hari) {
            return new JsonResponse([
                'message' => 'harus dipilih hari apa jadwal akan diganti'
            ], 410);
        }
        if (!$masuk && !$pulang) {
            return new JsonResponse([
                'message' => 'Tidak ada jam yang akan diganti'
            ], 410);
        }


        $data = JadwalAbsen::where('kategory_id', $kategory)
            ->whereIn('hari', $hari)
            ->when($id_peg, function ($q) use ($id_peg) {
                $q->where('pegawai_id', $id_peg);
            })
            ->when($masuk, function ($q) use ($masuk) {
                $q->update(['masuk' => $masuk]);
            })
            ->when($pulang, function ($q) use ($pulang) {
                $q->update(['pulang' => $pulang]);
            });

        return new JsonResponse([
            'message' => 'Data sudah disimpan',
            'data' => $data,
        ]);
    }
    public function ramadhan(Request $request)
    {
        $anu = [];
        foreach ($request->all() as $key) {

            if ($key['kategory'] === 1) {
                $temp = JadwalAbsen::where('kategory_id', $key['kategory'])
                    ->whereIn('hari', ['Senin', 'Selasa', 'Rabu', 'Kamis'])
                    ->update(['pulang' => $key['pulang']]);
                $temp1 = JadwalAbsen::where('kategory_id', $key['kategory'])
                    ->whereIn('hari', ['Jumat'])
                    ->update(['pulang' => $key['Jumat']]);
                if ($temp) {
                    array_push($anu, $temp);
                }
                if ($temp1) {
                    array_push($anu, $temp1);
                }
            }
            // if ($key['kategory'] === 2) {
            //     $temp2 = JadwalAbsen::where('kategory_id', $key['kategory'])
            //         ->whereIn('hari', ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'])
            //         ->update(['masuk' => $key['masuk']]);
            //     array_push($anu, $temp2);
            // }
        }
        return new JsonResponse(['message' => 'Jadwal diganti ke Jadwal Ramdhan']);
    }
    public function lebaran()
    {
        $temp = JadwalAbsen::where('kategory_id', 1)
            ->whereIn('hari', ['Senin', 'Selasa', 'Rabu', 'Kamis'])
            ->update(['pulang' => '16:00:00']);
        $temp1 = JadwalAbsen::where('kategory_id', 1)
            ->whereIn('hari', ['Jumat'])
            ->update(['pulang' => '13:00:00']);
        $temp2 = JadwalAbsen::where('kategory_id', 2)
            ->whereIn('hari', ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'])
            ->update(['masuk' => '07:00:00']);

        // $temp2 = JadwalAbsen::where('kategory_id', 2)
        //     ->whereIn('hari', ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'])
        //     ->update(['masuk' => '07:00:00']);

        return new JsonResponse(['messaga' => 'Jadwal kembali Normal']);
    }

    public function tulisTidakMasuk()
    {
        // return 'ok';

        $today = date('l');
        $date = date('Y-m-d');
        $jadwal = JadwalAbsen::where('day', $today)
            ->where('status', 2)
            ->get();
        $absen = TransaksiAbsen::where('tanggal', $date)->get();
        $peg = collect($absen)->map(function ($x) {
            return $x->pegawai_id;
        });
        $not = collect($jadwal)->whereNotIn('pegawai_id', $peg);
        foreach ($not as $tidak) {
            Alpha::updateOrCreate(
                [
                    'pegawai_id' => $tidak->pegawai_id,
                    'tanggal' => $date
                ],
                ['flag' => 'ABSEN']
            );
        }
        $tidakDaftar = Pegawai::where('account_pass', null)->where('aktif', 'AKTIF')->get();
        foreach ($tidakDaftar as $tidak) {
            Alpha::updateOrCreate(
                [
                    'pegawai_id' => $tidak->id,
                    'tanggal' => $date
                ],
                ['flag' => 'ABSEN']
            );
        }

        // $data['tidak masuk'] = Alpha::where('tanggal', $date)->get();

        return new JsonResponse(['message' => 'sudah di tulis']);
    }

    public function delete(Request $request)
    {
        $data = Libur::find($request->id);
        $data->delete();
        if (!$data) {
            return new JsonResponse(['message' => 'Data gagal dihapus'], 410);
        }
        return new JsonResponse(['message' => 'Data sudah dihapus'], 200);
    }
}
