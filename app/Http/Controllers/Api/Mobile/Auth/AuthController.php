<?php

namespace App\Http\Controllers\Api\Mobile\Auth;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    //

    // ganti password
    public function newPassword(Request $request)
    {
        try {
            DB::beginTransaction();

            $auth = JWTAuth::user();
            $user = User::find($auth->id);
            $pegawai = Pegawai::find($auth->pegawai_id);
            $user->update([
                'password' => bcrypt($request->password)
            ]);
            $pegawai->update([
                'account_pass' => $request->password
            ]);

            DB::commit();

            return new JsonResponse(['message' => 'berhasil ganti password'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal update password', 'error' => $e], 500);
        }
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
            'device' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // $allAccess = array('sa@app.com', 'coba@app.com', '3574041305820002@app.com');

        if ($request->email === 'sa@app.com' || 
            $request->email === '3574041305820002@app.com' || 
            $request->email === '3574011504680001@app.com' || 
            $request->device === 'ios'
            ) {
            JWTAuth::factory()->setTTL(43200);
            $data = $request->only('email', 'password');
            $token = JWTAuth::attempt($data);
            if (!$token) {
                return response()->json(['error' => 'Unauthorized', 'validator' => $data], 401);
            }
            return $this->createNewToken($token);
        }
        // $found = User::where(['email' => $request->email, 'password' => $request->password])->first();
        $temp = User::where('email', '=', $request->email)
            ->first();
        if (!$temp) {
            return new JsonResponse(['message' => 'Harap Periksa Kembali username dan password Anda'], 409);
        }
        if ($temp) {
            if ($temp->status === '2') {
                return new JsonResponse(['message' => 'Device Reset Approved', 'id' => $temp->id], 410);
            }

            $pass = Hash::check($request->password, $temp->password);
            if (!$pass) {
                return new JsonResponse(['message' => 'Harap Periksa Kembali username dan password Anda'], 409);
            }
        }
        $user = User::where('email', '=', $request->email)
            ->where('device', '=', $request->device)
            ->first();

        // return new JsonResponse(['message' => $user], 205);
        if (!$user) {
            return new JsonResponse(['message' => 'Maaf User ini belum terdaftar atau user ini sudah didaftarkan pada device yang lain'], 406);
        }
        // }
        // JWTAuth::factory()->setTTL(1);
        // JWTAuth::factory()->setTTL(43200);
        JWTAuth::factory()->setTTL(518400);
        $data = $request->only('email', 'password');
        $token = JWTAuth::attempt($data);
        if (!$token) {
            return response()->json(['error' => 'Unauthorized', 'validator' => $data], 401);
        }
        return $this->createNewToken($token);
    }

    protected function createNewToken($token)
    {
        $user = User::with(['pegawai', 'pegawai.user', 'pegawai.jadwal'])->find(auth()->user()->id);
        // $pegawai = Pegawai::with('user', 'jadwal')->find($user->pegawai_id);
        return response()->json([
            'token' => $token,
            'user' => $user
        ]);
    }

    public function resetDevice(Request $request)
    {
        $user = User::find($request->id);
        $user->update([
            'device' => $request->device,
            'status' => '',
        ]);

        return new JsonResponse(['message' => 'Update Device Berhasil'], 200);
    }
    public function me()
    {
        // $me = auth()->user();
        $user = JWTAuth::user();
        $pegawai = Pegawai::with('user', 'jadwal')->find($user->pegawai_id);

        return new JsonResponse(['result' => $pegawai]);
    }

    public function register(Request $request)
    {
        //username -> $req->nip
        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }


        // $cek = User::find('pegawai_id', $request->pegawai_id)->first();
        // if ($cek) {
        //     return new JsonResponse(['status' => 'failed', 'message' => 'Maaf, Anda sudah Register Sebelumnya'], 500);
        // }

        $data = new User();
        $data->username = $request->username;
        $data->email = $request->username . '@app.com';
        $data->password = bcrypt($request->password);
        $data->pegawai_id = $request->pegawai_id;
        $data->device = $request->device;
        $data->nama = $request->nama;

        $saved = $data->save();

        if (!$saved) {
            return new JsonResponse(['status' => 'failed', 'message' => 'Ada Kesalahan'], 500);
        }
        $pegawai = Pegawai::find($request->pegawai_id);
        $pegawai->update([
            'account_pass' => $request->password
        ]);
        $data->load('pegawai');
        return new JsonResponse(['status' => 'success', 'message' => 'Data tersimpan', 'user' => $data], 201);
    }

    public function logout()
    {
        auth()->logout();
        // JWTAuth::logout();
        return response()->json(['message' => 'User sukses logout dari aplikasi']);
    }

    // ganti status
    // null, '' = bisa loagin, 8=tidak bisa scan barcode, 9= tidak bisa scan barcode dan wajah
}
