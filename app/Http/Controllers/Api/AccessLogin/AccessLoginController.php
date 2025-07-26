<?php

namespace App\Http\Controllers\Api\AccessLogin;

use App\Http\Controllers\Controller;
use App\Models\Pegawai\Akses\Access;
use App\Models\Pegawai\Akses\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AccessLoginController extends Controller
{
    //
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
        // return new JsonResponse($temp);
        if (!$temp) {
            return new JsonResponse(['message' => 'Harap Periksa Kembali username dan password Anda'], 409);
        }
        if ($temp) {
            $pass = Hash::check($request->password, $temp->password);
            if (!$pass) {
                return new JsonResponse(['message' => 'Harap Periksa Kembali username dan password Anda'], 409);
            }
        }

        // $token = auth()->attempt($validator->validated());
        // $token = auth()->login($validator->validated());
        // $token = auth()->attempt($request->only('email', 'password'));
        // $token = $validator->validated();
        $token = JWTAuth::attempt($validator->validated());

        // $data = $request->only('email', 'password');
        // $token = JWTAuth::attempt($data);
        if (!$token) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        return $this->createNewToken($token);
    }

    public function userProfile()
    {
        return response()->json(auth()->user());
    }

    public function me()
    {
        $me = auth()->user();

        return new JsonResponse(['result' => $me]);
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

    protected function createNewToken($token)
    {
        $temp = auth()->user();
        $user = User::with('role')->find($temp->id);
        $submenu = Access::where('role_id', $user->role_id)->with('aplikasi', 'submenu.menu')->get();

        // maping submenu
        $col = collect($submenu);
        $aplikasi = $col->map(function ($item, $key) {
            if ($item->aplikasi !== null) {
                return $item->aplikasi;
            }
        })->unique('id');
        $subm = $col->map(function ($item, $key) {

            return $item->submenu;
        });

        $menu = $col->map(function ($item, $key) {
            return $item->submenu->menu;
        })->unique('id');
        $into = $menu->map(function ($item, $key) use ($subm) {

            $temp = $subm->where('menu_id', $item->id);
            $map = $temp->map(function ($ki, $ke) {
                return
                    [
                        'nama' => $ki->nama,
                        'name' => $ki->name,
                        'icon' => $ki->icon,
                        'link' => $ki->link,

                    ];
            });
            // $item->submenus = $temp;
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
        return response()->json([
            'token' => $token,
            'user' => $user,
            'aplikasi' => $aplikasi,
            'menus' => $into
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
