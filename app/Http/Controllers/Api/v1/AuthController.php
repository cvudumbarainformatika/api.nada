<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\aplikasi\Aplikasi;
use App\Models\Github;
use App\Models\Pegawai\Akses\Access;
use App\Models\Pegawai\Akses\AksesUser;
use App\Models\Pegawai\Akses\Menu;
use App\Models\Sigarang\Pegawai;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Konsultasi\Konsultasi;
use App\Models\Simrs\Master\Msistembayar;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use PhpParser\Node\Stmt\TryCatch;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        $temp = User::where('email', '=', $request->email)
            ->first();

        if (!$temp) {
            return new JsonResponse(['message' => 'Harap Periksa Kembali username dan password Anda'], 409);
        }
        if ($temp) {

            $pass = Hash::check($request->password, $temp->password);

            if (!$pass) {
                return new JsonResponse(['message' => 'Harap Periksa Kembali username dan password Anda'], 409);
            }
        }
        if (!$token = auth()->attempt($validator->validated())) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        return self::createNewToken($token);
    }

    public function userProfile()
    {
        return response()->json(auth()->user());
    }
    public function authuser()
    {
        try {
            $me = auth()->user();
            $pegawaiId = $me->pegawai_id;
            $cacheKey = 'account_' . $me->id;

            // Hapus cache untuk testing
            Cache::forget($cacheKey);

            // Gunakan remember biasa dengan waktu yang lama (1 minggu)
            $user = Cache::remember($cacheKey, 60 * 24 * 7, function () use ($me) {
                Log::info('Cache miss - fetching fresh user data');
                return User::with([
                    'pegawai.role',
                    'pegawai.ruang',
                    'pegawai.ruangsim',
                    'pegawai.ruangan:koderuangan,kdmapping'
                ])->find($me->id);
            });

            $loadGudang = array(3, 4, 7);

            if (in_array($user->pegawai->role_id, $loadGudang)) {
                $user->load([
                    'pegawai.depo:kode,nama',
                    'pegawai.role',
                    'pegawai.depoSim:kode,nama',
                    'pegawai.ruangan:koderuangan,kdmapping'
                ]);
            }

            // Gunakan remember biasa untuk apps juga
            $apps = Cache::remember('menu-sso-xenter', 60 * 24 * 7, function () {
                Log::info('Cache miss - fetching fresh apps data');
                return Aplikasi::with(['menus', 'menus.submenus'])->get();
            });

            $akses = 'all';
            $allAccess = array('sa', 'coba', 'wan');

            if (!in_array(auth()->user()->username, $allAccess)) {
                $akses = AksesUser::where('user_id', $me->id)->get();
            }

            // Gunakan remember untuk masterSistemBayar
            $masterSistemBayar = Cache::remember('master-sistembayar', 60 * 24 * 7, function () {
                Log::info('Cache miss - fetching fresh sistembayar data');
                return Msistembayar::query()
                    ->select('rs1 as kode', 'rs2 as nama', 'rs9 as jenis', 'groups')
                    ->where('hidden', '!=', '')
                    ->where('rs1', '!=', '')
                    ->get();
            });

            $pegawai = Petugas::select('id', 'kdpegsimrs', 'kdgroupnakes', 'aktif', 'statusspesialis')
                ->find($pegawaiId);

            $notifRkd = [
                'notif' => 0,
                'kddokterkonsul' => $pegawai->kdpegsimrs
            ];

            if ($pegawai) {
                if ($pegawai->kdgroupnakes === '1' && strtoupper($pegawai->aktif) === 'AKTIF') {
                    $cari = Konsultasi::select(DB::raw('count(kddokterkonsul) as notif'), 'kddokterkonsul')
                        ->where('kddokterkonsul', '=', $pegawai->kdpegsimrs)
                        ->where(function ($q) {
                            $q->whereNull('flag');
                                // ->orWhereNull('jawaban')
                            ;
                        })
                        ->where(function ($q) {
                            $q->whereNull('jawaban');
                        })
                        ->groupBy('kddokterkonsul')
                        ->orderBy('kddokterkonsul')
                        ->get();

                    if (count($cari) > 0) {
                        $notifRkd = $cari[0];
                    }
                }
            }

            // $git = Github::first();

            $result = [
                'apps' => $apps,
                'akses' => $akses,
                'user' => $user,
                'mSistemBayar' => $masterSistemBayar,
                'notifRkd' => $notifRkd,
                // 'git' => $git,
            ];

            return new JsonResponse($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function me()
    {
        $me = auth()->user();
        $akses = User::with('akses.aplikasi', 'akses.menu', 'akses.submenu')->find(auth()->user()->id);
        $pegawai = Pegawai::with('ruang', 'depo', 'role')->find($akses->pegawai_id);
        $submenu = Access::where('role_id', $pegawai->role_id)->with('role', 'aplikasi', 'submenu.menu')->get();

        $col = collect($submenu);
        $role = $col->map(function ($item, $key) {
            return $item->role;
        })->unique();
        $apli = $col->map(function ($item, $key) {
            return $item->aplikasi;
        })->unique('id');
        $subm = $col->map(function ($item, $key) {
            return $item->submenu;
        });

        $menu = $col->map(function ($item, $key) {
            return $item->submenu->menu;
        })->unique('id');

        $into = $menu->map(function ($item, $key) use ($subm) {
            // $mbuh = [];
            $temp = $subm->where('menu_id', $item->id);
            $map = $temp->map(function ($ki, $ke) {
                // $map = $temp->each(function ($ki, $ke) {
                return [
                    'nama' => $ki->nama,
                    'name' => $ki->name,
                    'icon' => $ki->icon,
                    'link' => $ki->link,

                ];
            });
            $apem = [
                'aplikasi_id' => $item->aplikasi_id,
                'nama' => $item->nama,
                'name' => $item->name,
                'icon' => $item->icon,
                'link' => $item->link,
                'submenus' => $map,
            ];
            return $apem;
        });
        // akses 2 start
        $aks = collect($akses->akses);
        // $apli2 = $aks;
        $apli2 = $aks->map(function ($item, $key) {
            return $item->aplikasi;
        })->unique('id');
        $subm2 = $aks->map(function ($item, $key) {
            return $item->submenu;
        });
        $menu2 = $aks->map(function ($item, $key) {
            return $item->menu;
        })->unique('id');

        $into2 = $menu2->map(function ($item, $key) use ($subm2) {
            // $mbuh = [];
            $temp = $subm2->where('menu_id', $item->id);
            $map = $temp->map(function ($ki, $ke) {
                // $map = $temp->each(function ($ki, $ke) {
                return [
                    'nama' => $ki->nama,
                    'name' => $ki->name,
                    'icon' => $ki->icon,
                    'link' => $ki->link,

                ];
            });
            $apem = [
                'aplikasi_id' => $item->aplikasi_id,
                'nama' => $item->nama,
                'name' => $item->name,
                'icon' => $item->icon,
                'link' => $item->link,
                'submenus' => $map,
            ];
            return $apem;
        });
        // akses 2 end
        $foto = $pegawai->nip . '/' . $pegawai->foto;
        $raw = collect($pegawai);
        $apem = $raw['ruang'];
        $gud = $raw['depo'];
        return new JsonResponse([
            'result' => $me,
            'aplikasi' => $apli,
            'aplikasi2' => $apli2,
            'menus' => $into,
            'menus2' => $into2,
            'role' => $role,
            'foto' => $foto,
            'ruang' => $apem,
            'kode_ruang' => $pegawai->kode_ruang,
            'depo' => $gud,
            'akses' => $akses
        ]);
    }

    public function user()
    {
        $data = User::filter(request(['q']))->get();

        return new JsonResponse($data);
    }

    public function logout(Request $request)
    {
        auth()->logout();
        return response()->json(['message' => 'User sukses logout dari aplikasi']);
    }

    // public function refresh() {
    //     return $this->createNewToken(auth()->refresh());
    // }

    public static function createNewToken($token)
    {

        // $akses = User::with('akses.aplikasi', 'akses.menu', 'akses.submenu')->find(auth()->user()->id);
        $user = User::with(['pegawai.role', 'pegawai.ruang'])->find(auth()->user()->id);
        // $loadGudang = array(3, 4);
        // if (in_array($user->pegawai->role_id, $loadGudang)) {
        //     $user->load('pegawai.depo:kode,nama');
        // }

        // $pegawai = Pegawai::with('ruang', 'depo', 'role')->find($akses->pegawai_id);
        // $submenu = Access::where('role_id', $pegawai->role_id)->with(['role', 'aplikasi', 'submenu.menu'])->get();

        // $col = collect($submenu);
        // $role = $col->map(function ($item, $key) {
        //     return $item->role;
        // })->unique();
        // $apli = $col->map(function ($item, $key) {
        //     return $item->aplikasi;
        // })->unique('id');
        // $subm = $col->map(function ($item, $key) {

        //     return $item->submenu;
        // });

        // $menu = $col->map(function ($item, $key) {
        //     return $item->submenu->menu;
        // })->unique('id');

        // $into = $menu->map(function ($item, $key) use ($subm) {
        //     // $mbuh=[];
        //     $temp = $subm->where('menu_id', $item->id);
        //     $map = $temp->map(function ($ki, $ke) {
        //         return [
        //             'nama' => $ki->nama,
        //             'name' => $ki->name,
        //             'icon' => $ki->icon,
        //             'link' => $ki->link,

        //         ];
        //     });
        //     $apem = [
        //         'aplikasi_id' => $item->aplikasi_id,
        //         'nama' => $item->nama,
        //         'name' => $item->name,
        //         'icon' => $item->icon,
        //         'link' => $item->link,
        //         'submenus' => $map,
        //     ];
        //     return $apem;
        // });
        // // akses 2 start
        // $aks = collect($akses->akses);
        // // $apli2 = $aks;
        // $apli2 = $aks->map(function ($item, $key) {
        //     return $item->aplikasi;
        // })->unique('id');
        // $subm2 = $aks->map(function ($item, $key) {
        //     return $item->submenu;
        // });
        // $menu2 = $aks->map(function ($item, $key) {
        //     return $item->menu;
        // })->unique('id');

        // $into2 = $menu2->map(function ($item, $key) use ($subm2) {
        //     // $mbuh = [];
        //     $temp = $subm2->where('menu_id', $item->id);
        //     $map = $temp->map(function ($ki, $ke) {
        //         // $map = $temp->each(function ($ki, $ke) {
        //         return [
        //             'nama' => $ki->nama,
        //             'name' => $ki->name,
        //             'icon' => $ki->icon,
        //             'link' => $ki->link,

        //         ];
        //     });
        //     $apem = [
        //         'aplikasi_id' => $item->aplikasi_id,
        //         'nama' => $item->nama,
        //         'name' => $item->name,
        //         'icon' => $item->icon,
        //         'link' => $item->link,
        //         'submenus' => $map,
        //     ];
        //     return $apem;
        // });
        // // akses 2 end
        // $foto = $pegawai->nip . '/' . $pegawai->foto;
        // $raw = collect($pegawai);
        // $apem = $raw['ruang'];
        // $gud = $raw['depo'];
        // return response()->json([
        //     'token' => $token,
        //     'user' => $user,
        //     'aplikasi' => $apli,
        //     'menus' => $into,
        //     'aplikasi2' => $apli2,
        //     'menus2' => $into2,
        //     'role' => $role,
        //     'foto' => $foto,
        //     'ruang' => $apem,
        //     'kode_ruang' => $pegawai->kode_ruang,
        //     'depo' => $gud,
        //     'akses' => $akses
        // ]);

        return response()->json([
            'token' => $token,
            'user' => $user
        ]);
    }


    public function test()
    {
        $data = User::all();
        return new JsonResponse($data);
    }

    public function new_reg(Request $request)
    {
        $request->validate([
            'nama' => 'required',
            'username' => 'required',
            'email' => 'required',
            'password' => 'required',
        ]);


        $data = new User();
        $data->nama = $request->nama;
        $data->username = $request->username;
        $data->email = $request->email . '@app.com';
        $data->password = bcrypt($request->password);

        $saved = $data->save();

        if (!$saved) {
            return new JsonResponse(['status' => 'failed', 'message' => 'Ada Kesalahan'], 500);
        }
        return new JsonResponse(['status' => 'success', 'message' => 'Data tersimpan'], 201);
    }
}
