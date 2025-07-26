<?php

namespace App\Http\Controllers\Api\Logistik\Sigarang;

use App\Http\Controllers\Controller;
use App\Http\Resources\v1\sigarang\UserResource;
use App\Models\Sigarang\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{

    public function index()
    {
        $data = User::filter(request(['q']))->latest()->paginate(request('per_page'));

        $ketemu = false;
        foreach ($data as $key => $value) {
            if ($value->name === 'root') {
                $ketemu = true;
                break;
            }
        }
        if ($ketemu) unset($data[$key]);
        return UserResource::collection($data);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userProfile()
    {
        return response()->json(auth()->user());
    }

    public function update(Request $request)
    {
        try {

            DB::beginTransaction();
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'nip' => 'required',
                'role' => 'required',
                'username' => 'required',
                // 'email' => 'required|string|email|max:100|unique:users',
            ]);
            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }
            $user = User::find($request->id);
            $user->update(array_merge($validator->validated(), ['email' => $request->email]));

            DB::commit();
            return response()->json(['message' => 'success', 'data' => $user], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'ada kesalahan',
                'error' => $e,
            ], 500);
        }
    }
    public function destroy(Request $request)
    {
        // $auth = auth()->user()->id;
        $id = $request->id;

        $data = User::find($id);
        $del = $data->delete();

        if (!$del) {
            return response()->json([
                'message' => 'Error on Delete'
            ], 500);
        }

        // $user->log("Menghapus Data User {$data->nama}");
        return response()->json([
            'message' => 'Data sukses terhapus'
        ], 200);
    }
}
