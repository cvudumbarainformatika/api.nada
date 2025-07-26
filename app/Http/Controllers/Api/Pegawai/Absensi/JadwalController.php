<?php

namespace App\Http\Controllers\Api\Pegawai\Absensi;

use App\Http\Controllers\Controller;
use App\Models\Pegawai\Hari;
use App\Models\Pegawai\Jadwal;
use App\Models\Pegawai\JadwalAbsen;
use App\Models\Pegawai\Kategory;
use App\Models\Pegawai\TransaksiAbsen;
use App\Models\Sigarang\Pegawai;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class JadwalController extends Controller
{

    public function index()
    {
        // return new JsonResponse(['to' => $to, 'from' => $from]);
        $data = JadwalAbsen::orderBy(request('order_by'), request('sort'))
            ->filter(request(['q']))
            ->with(
                'pegawai',
                'ruang',
                'kategory',
                'pertama',
                'kedua',
                'ketiga',
                'keempat',
                'kelima',
                'keenam',
                'ketujuh',
                'jam01',
                'jam02',
                'jam03',
                'jam04',
                'jam05',
                'jam06',
                'jam07',
            )
            ->paginate(request('per_page'));

        return new JsonResponse($data);
    }

    public function getKategories()
    {
        $data = Kategory::get();
        return new JsonResponse($data);
    }

    public function getDays()
    {
        $data = Hari::get();
        return new JsonResponse($data);
    }
    // mobile auth jwt
    public function getByUser()
    {
        // return new JsonResponse(['to' => $to, 'from' => $from]);
        $user = JWTAuth::user();
        $data = JadwalAbsen::where('user_id', $user->id)
            ->with('kategory')
            ->get();

        return new JsonResponse($data);
    }
    // desktop auth api
    public function getByUserDesk()
    {
        // return new JsonResponse(['to' => $to, 'from' => $from]);
        // $user = auth()->user();
        $data = JadwalAbsen::where('user_id', request('id'))->with('kategory')->get();
        // ->orderBy(request('order_by'), request('sort'))
        // ->filter(request(['q']))
        // ->paginate(request('per_page'));

        return new JsonResponse($data);
    }

    public static function toMatch($id, $request)
    {
        // isinya match jadwal dengan user ybs
        // tanngal, jam, qrcode, masuk, pulang
        // dasar update, user id, tanggal, kategory_id
        $user = User::find($id);
        $day = date('l');
        // $yesterday = date('l', strtotime('-1 days'));
        $now = date('Y-m-d');
        $time = date('H:i:s');

        // $jadwal = JadwalAbsen::updateOrCreate(
        //     [
        //         'user_id' => $id,
        //         'tanggal' => $request->tanggal,
        //         'kategory_id' => $request->kategory_id
        //     ],
        //     $request->all()
        // );

        // if ($jadwal->wasRecentlyCreated) {
        //     $result = ['absen' => 'masuk', 'data' => $jadwal];
        // } else if ($jadwal->wasChanged()) {
        //     $result = ['absen' => 'pulang', 'data' => $jadwal];
        // } else {
        //     $result = false;
        // }
        $jadwal = JadwalAbsen::where('user_id', $id)->where('day', $day)->with('kategory')->first();


        if ($jadwal->status === '2') {
            if ($request->id > 0) {
                $data = TransaksiAbsen::with('kategory')->find($request->id);
                $data->update([
                    'pulang' => $time,
                ]);
                $apem = ['id' => 0, 'jam' => 0];
                $result = ['absen' => 'pulang', 'data' => $apem];
                return $result;
            }
            $data = TransaksiAbsen::create([
                'user_id' => $user->id,
                'kategory_id' => $jadwal->kategory_id,
                'tanggal' => $now,
                'pegawai_id' => $user->pegawai_id,
                'masuk' => $time,
            ]);
            $data->load('kategory');
            $result = ['absen' => 'masuk', 'data' => $data];
            return $result;
        } else {
            if ($request->has('id') && $request->id > 0) {
                $data = TransaksiAbsen::find($request->id);
                $data->update([
                    'pulang' => $time,
                ]);
                $result = ['absen' => 'pulang', 'data' => $data];
                return $result;
            } else {
                $result = false;
            }
        }
        return $result;
    }

    public static function toMatch2($id, $request)
    {
        // isinya match jadwal dengan user ybs
        // tanngal, jam, qrcode, masuk, pulang
        // dasar update, user id, tanggal, kategory_id
        $user = User::find($id);

        if ($request->status === 'masuk') {
            $data = TransaksiAbsen::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'tanggal' => $request->tanggal,
                    'kategory_id' => $request->kategory_id
                ],

                [
                    'pegawai_id' => $user->pegawai_id,
                    'masuk' => $request->jam,
                ]
            );
            $data->load('kategory');
            $result = ['absen' => 'masuk', 'data' => $data];
            return $result;
        } else {
            $data = TransaksiAbsen::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'tanggal' => $request->tanggal,
                    'kategory_id' => $request->kategory_id
                ],

                [
                    'pegawai_id' => $user->pegawai_id,
                    'pulang' => $request->jam,
                ]
            );

            if ($data->wasRecentlyCreated) {
                $result = ['absen' => 'pulang', 'data' => $data];
            } else if ($data->wasChanged()) {
                $result = ['absen' => 'pulang', 'data' => $data];
            } else {
                $result = false;
            }
        }
        return $result;
    }

    public function store(Request $request)
    {


        // try {
        //     DB::beginTransaction();
        //validate data
        $valid = Validator::make($request[0], ['user_id' => 'required']);
        if ($valid->fails()) {
            return new JsonResponse([$valid->errors(), 422]);
        }
        $jadwal = JadwalAbsen::where('user_id', $request[0]['user_id'])->first();
        if (!$jadwal) {
            $jumlah = count($request->all());
            if ($jumlah < 7) {
                return new JsonResponse(['message' => 'jumlah data yang di kirim kurang'], 411);
            }
            foreach ($request->all() as $key) {
                // return new JsonResponse($key);
                // update atau buat baru jika tidak ada masalah
                $data = JadwalAbsen::create(
                    [
                        'user_id' => $key['user_id'],
                        'pegawai_id' => $key['pegawai_id'],
                        'ruang_id' => $key['ruang_id'],
                        'day' => $key['day'],
                        'hari' => $key['hari']
                    ]
                );
                if ($key['status'] === '2') {
                    $data->update([
                        'status' => $key['status'],
                        'masuk' => $key['masuk'],
                        'pulang' => $key['pulang'],
                        'jam' => $key['jam'],
                        'menit' => $key['menit'],
                    ]);
                } else {
                    $data->update([
                        'status' => $key['status'],
                        'masuk' => null,
                        'pulang' => null,
                        'jam' => null,
                        'menit' => null,
                    ]);
                }
            }
            return new JsonResponse(['message' => 'Jadwal telah dibuat'], 406);
        }
        // $data = User::with('jadwal')->find($request[0]['user_id']);
        // return new JsonResponse($data->jadwal);
        foreach ($request->all() as $key) {

            $data = JadwalAbsen::where('day', '=', $key['day'])->first();
            if ($key['status'] === '2') {
                $data->update([
                    'status' => $key['status'],
                    'masuk' => $key['masuk'],
                    'pulang' => $key['pulang'],
                    'jam' => $key['jam'],
                    'menit' => $key['menit'],
                ]);
            } else {
                $data->update([
                    'status' => $key['status'],
                    'masuk' => null,
                    'pulang' => null,
                    'jam' => null,
                    'menit' => null,
                ]);
            }
        }

        // DB::commit();
        // if (!$data->wasRecentlyCreated) {
        $status = 200;
        $pesan = 'Data telah di perbarui';
        // } else {
        // $status = 201;
        // $pesan = 'Data telah di tambakan';
        // }
        return new JsonResponse(['message' => $pesan], $status);
        // } catch (\Exception $e) {
        //     DB::rollBack();
        //     return new JsonResponse([
        //         'message' => 'ada kesalahan',
        //         'error' => $e
        //     ], 500);
        // }
    }


    public function create(Request $request)
    {
        // return new JsonResponse($request->all());
        $user = JWTAuth::user();
        $jadwal = JadwalAbsen::where('user_id', $user->id)->first();
        if ($jadwal) {
            return new JsonResponse(['message' => 'Jadwal Absen Sudah ada'], 406);
        }
        $pegawai = Pegawai::find($user->pegawai_id);
        $hari = Hari::get();
        // return new JsonResponse([$request->all()]);
        foreach ($hari as $key) {
            // return new JsonResponse([$key]);
            if ($request->kategory_id === 1 || $request->kategory_id === '1') {

                if ($key->nama === 'Minggu' || $key->nama === 'Sabtu') {
                    $data = JadwalAbsen::create(
                        [
                            'day' => $key->name,
                            'hari' => $key->nama,
                            'user_id' => $user->id,
                            'pegawai_id' => $user->pegawai_id,
                            'ruang_id' => $pegawai->ruang,
                            'status' => '1',
                            'kategory_id' => null,
                            'masuk' => null,
                            'pulang' => null,
                            'jam' => 0,
                            'menit' => 0,
                        ]
                    );
                } else if ($key->nama === 'Jumat') {
                    $data = JadwalAbsen::create(
                        [
                            'day' => $key->name,
                            'hari' => $key->nama,
                            'user_id' => $user->id,
                            'pegawai_id' => $user->pegawai_id,
                            'ruang_id' => $pegawai->ruang,
                            'status' => 2,
                            'masuk' => '07:30',
                            'pulang' => '13:00',
                            'jam' => 5,
                            'menit' => 30,
                            'kategory_id' => 1,
                        ]
                    );
                } else {
                    $data = JadwalAbsen::create(
                        [
                            'day' => $key->name,
                            'hari' => $key->nama,
                            'user_id' => $user->id,
                            'pegawai_id' => $user->pegawai_id,
                            'ruang_id' => $pegawai->ruang,
                            'status' => 2,
                            'masuk' => '07:30',
                            'pulang' => '16:00',
                            'jam' => 8,
                            'menit' => 30,
                            'kategory_id' => 1,
                        ]
                    );
                }
            } else if ($request->kategory_id === 2 || $request->kategory_id === '2') {
                $data = JadwalAbsen::create(
                    [
                        'kategory_id' => 2,
                        'day' => $key->name,
                        'hari' => $key->nama,
                        'user_id' => $user->id,
                        'pegawai_id' => $user->pegawai_id,
                        'ruang_id' => $pegawai->ruang,
                        'status' => 2,
                        'masuk' => '07:00',
                        'pulang' => '14:00',
                        'jam' => 7,
                        'menit' => 0,
                    ]
                );
                if ($key->nama === 'Minggu') {
                    $data->update(
                        [
                            'kategory_id' => 2,
                            'status' => '1',
                            'masuk' => null,
                            'pulang' => null,
                            'jam' => 0,
                            'menit' => 0,
                        ]
                    );
                }
                if ($key->nama === 'Jumat') {
                    $data->update(
                        [
                            'kategory_id' => 2,
                            'status' => '2',
                            'masuk' => '07:00',
                            'pulang' => '11:30',
                            'jam' => 4,
                            'menit' => 30,
                        ]
                    );
                }
                if ($key->nama === 'Sabtu') {
                    $data->update(
                        [
                            'kategory_id' => 2,
                            'status' => '2',
                            'masuk' => '07:00',
                            'pulang' => '12:00',
                            'jam' => 5,
                            'menit' => 0,
                        ]
                    );
                }
            } else {
                $data = JadwalAbsen::create(
                    [
                        'day' => $key->name,
                        'hari' => $key->nama,
                        'user_id' => $user->id,
                        'pegawai_id' => $user->pegawai_id,
                        'ruang_id' => $pegawai->ruang,
                        'status' => 1,
                    ]
                );
            }
            // return new JsonResponse($key);
        }
        if ($data->wasRecentlyCreated) {
            $status = 201;
            $pesan = 'Jadwal telah dibuat';
        } else {
            $status = 500;
            $pesan = 'Jadwal gagal dibuat';
        }
        return new JsonResponse(['message' => $pesan], $status);
    }
    public function update(Request $request)
    {
        $jadwal = JadwalAbsen::find($request->id);
        $kategori = Kategory::find($request->kategory_id);

        if ($request->status === '2') {

            $toIn = explode(':', $kategori->masuk);
            $act = explode(':', $kategori->pulang);
            // $jam = (int)$act[0] > (int)$toIn[0] ? (int)$act[0] - (int)$toIn[0] : (int)$toIn[0] - (int)$act[0];
            $jam = (int)$act[0] > (int)$toIn[0] ? (int)$act[0] - (int)$toIn[0] : (24 - (int)$toIn[0]) + (int)$act[0];
            $menit = (int)$act[1] > (int)$toIn[1] ? (int)$act[1] - (int)$toIn[1] : (int)$toIn[1] - (int)$act[1];

            $jadwal->update([
                'kategory_id' => $request->kategory_id,
                'masuk' => $kategori->masuk,
                'pulang' => $kategori->pulang,
                'jam' => $jam,
                'menit' => $menit,
                'status' => 2,
            ]);

            $jadwal->kategory = $kategori;
        } else if ($request->status === '1') {
            $jadwal->update([
                'masuk' => null,
                'pulang' => null,
                'jam' => 0,
                'menit' => 0,
                'status' => 1,
                'kategory_id' => null
            ]);
        } else {
            return new JsonResponse(['message' => 'Tidak ada data status'], 406);
        }
        if ($jadwal->wasChanged()) {
            $status = 200;
            $pesan = 'Jadwal telah diupdate';
        } else {
            $status = 500;
            $pesan = 'Tidak ada perubahan data';
        }
        return new JsonResponse(['message' => $pesan, 'data' => $jadwal], $status);
    }

    public function destroy(Request $request)
    {
        // $auth = auth()->user()->id;
        $data = JadwalAbsen::where('user_id', $request->id)->get();
        if (!count($data)) {
            return new JsonResponse(['message' => 'Jadwal tidak ditemukan', $data], 410);
        }

        foreach ($data as $key) {
            $del = $key->delete();
        }

        if (!$del) {
            return response()->json([
                'message' => 'Error on Delete'
            ], 500);
        }

        // $user->log("Menghapus Data Jadwal Absen {$data->nama}");
        return new JsonResponse([
            'message' => 'Data sukses terhapus',
            'data' => $data
        ], 200);
    }

    public function sycncroneJadwal()
    {
        $data = JadwalAbsen::selectRaw('user_id, kategory_id')->where('kategory_id', 2)->groupBy('user_id')->get();
        foreach ($data as $key) {
            $jadwal = JadwalAbsen::where('user_id', $key->user_id)->get();
            // return new JsonResponse(count($jadwal));
            if (count($jadwal) > 7) {
                foreach ($jadwal as $val) {

                    if ($val->hari === 'Minggu' && $val->kategory_id === 2 && $val->kategory_id !== null) {
                        // delete disini
                        $val->update(
                            [
                                'kategory_id' => 2,
                                'status' => '1',
                                'masuk' => null,
                                'pulang' => null,
                                'jam' => 0,
                                'menit' => 0,
                            ]
                        );
                        // return new JsonResponse(['update', $val]);
                    }

                    if ($val->hari === 'Minggu' && $val->kategory_id === null && $val->kategory_id !== 2) {
                        // delete disini
                        $val->delete();

                        // return new JsonResponse(['delete', $val]);
                        // return new JsonResponse($val);
                    }
                    if ($val->hari === 'Jumat') {
                        // ganti jam disini
                        $val->update(
                            [
                                'kategory_id' => 2,
                                'status' => '2',
                                'masuk' => '07:00',
                                'pulang' => '11:30',
                                'jam' => 4,
                                'menit' => 30,
                            ]
                        );
                        // return new JsonResponse($val);
                    }
                    if ($val->hari === 'Sabtu') {
                        // ganti jam disini
                        $val->update(
                            [
                                'kategory_id' => 2,
                                'status' => '2',
                                'masuk' => '07:00',
                                'pulang' => '12:00',
                                'jam' => 5,
                                'menit' => 0,
                            ]
                        );
                        // return new JsonResponse($val);
                    }
                }
            }
        }

        return new JsonResponse($data);
    }
}
