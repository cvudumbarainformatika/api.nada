<?php

namespace App\Http\Controllers\Api\Mjkn;

use App\Http\Controllers\Controller;
use App\Models\Sigarang\Pegawai;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthenticateController extends Controller
{
    public function getToken(Request $request)
    {
        $username = $request->header('x-username');
        $password = $request->header('x-password');

        $request = new Request([
            'username'   => $username,
            'password' =>  $password,
        ]);

        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required',
        ]);

        $metadata = array('code' => 200, 'message' => 'ok');
        $data['metadata'] = $metadata;
        if ($validator->fails()) {
            $metadata = array('code' => 201, 'message' => 'Maaf Username dan password harus diisi');
            $data['metadata'] = $metadata;
            return response()->json($data, 200);
        }



        $temp = User::where('username', '=', $username)->first();
        if (!$temp) {

            $metadata = array('code' => 201, 'message' => 'Maaf username dan password tidak sesuai.');
            $data['metadata'] = $metadata;
            return new JsonResponse($data);
        }

        $pass = Hash::check($password, $temp->password);

        if (!$pass) {
            return new JsonResponse($data);
        }

        // return new JsonResponse($data);

        JWTAuth::factory()->setTTL(518400);
        $data = $request->only('username', 'password');
        $token = JWTAuth::attempt($data);
        if (!$token) {
            $metadata['message'] = 'Maaf Ada kesalahan Authorize';
            return response()->json($data);
        }

        $response['token'] = $token;
        $data['response'] = $response;
        return new JsonResponse($data);
    }
    //


    // ganti status
    // null, '' = bisa loagin, 8=tidak bisa scan barcode, 9= tidak bisa scan barcode dan wajah
}
